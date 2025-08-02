<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mailer;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->fromEmail = SITE_EMAIL;
        $this->fromName = SITE_NAME;
        
        $this->configureSMTP();
    }

    private function configureSMTP() {
        try {
            if (!empty(SMTP_HOST)) {
                $this->mailer->isSMTP();
                $this->mailer->Host = SMTP_HOST;
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = SMTP_USER;
                $this->mailer->Password = SMTP_PASS;
                $this->mailer->Port = SMTP_PORT;
                
                if (SMTP_ENCRYPTION === 'tls') {
                    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif (SMTP_ENCRYPTION === 'ssl') {
                    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
                
                if (DEBUG_MODE) {
                    $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                    $this->mailer->Debugoutput = function($str, $level) {
                        error_log("SMTP Debug ($level): $str");
                    };
                }
            } else {
                $this->mailer->isMail();
            }
            
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';
            $this->mailer->setFrom($this->fromEmail, $this->fromName);
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }

    public function send($to, $subject, $body, $isHtml = true, $from = null, $replyTo = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearReplyTos();
            
            if ($from) {
                $this->mailer->setFrom($from, $this->fromName);
            } else {
                $this->mailer->setFrom($this->fromEmail, $this->fromName);
            }
            
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo);
            }
            
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        $this->mailer->addAddress($name);
                    } else {
                        $this->mailer->addAddress($email, $name);
                    }
                }
            } else {
                $this->mailer->addAddress($to);
            }
            
            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            
            if ($isHtml) {
                $this->mailer->Body = $body;
                $this->mailer->AltBody = $this->htmlToPlainText($body);
            } else {
                $this->mailer->Body = $body;
            }
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Email sent successfully to: " . (is_array($to) ? implode(', ', array_keys($to)) : $to));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendBulk($recipients, $subject, $body, $isHtml = true) {
        $results = [];
        
        foreach ($recipients as $email => $name) {
            if (is_numeric($email)) {
                $email = $name;
                $name = '';
            }
            
            $result = $this->send($email, $subject, $body, $isHtml);
            $results[$email] = $result;
            
            usleep(100000);
        }
        
        return $results;
    }

    public function sendWithAttachments($to, $subject, $body, $attachments = [], $isHtml = true) {
        try {
            foreach ($attachments as $attachment) {
                if (is_array($attachment)) {
                    $this->mailer->addAttachment(
                        $attachment['path'],
                        $attachment['name'] ?? '',
                        $attachment['encoding'] ?? 'base64',
                        $attachment['type'] ?? ''
                    );
                } else {
                    $this->mailer->addAttachment($attachment);
                }
            }
            
            return $this->send($to, $subject, $body, $isHtml);
            
        } catch (Exception $e) {
            error_log("Email with attachments failed: " . $e->getMessage());
            return false;
        }
    }

    public function sendTemplate($template, $to, $data = []) {
        $templatePath = ROOT_PATH . "/includes/email_templates/$template.php";
        
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: $template");
        }
        
        extract(array_merge([
            'site_name' => SITE_NAME,
            'site_url' => SITE_URL,
            'site_email' => SITE_EMAIL
        ], $data));
        
        ob_start();
        include $templatePath;
        $body = ob_get_clean();
        
        $subject = $data['subject'] ?? 'Message from ' . SITE_NAME;
        
        return $this->send($to, $subject, $body, true);
    }

    public function sendWelcomeEmail($userEmail, $userName) {
        return $this->sendTemplate('welcome', $userEmail, [
            'subject' => 'Welcome to ' . SITE_NAME,
            'user_name' => $userName,
            'login_url' => SITE_URL . '/auth/login'
        ]);
    }

    public function sendContactNotification($inquiry) {
        return $this->sendTemplate('contact_notification', ADMIN_EMAIL, [
            'subject' => 'New Contact Inquiry - ' . $inquiry['subject'],
            'inquiry' => $inquiry,
            'admin_url' => SITE_URL . '/admin/inquiries/' . $inquiry['id']
        ]);
    }

    public function sendContactResponse($inquiry, $response) {
        return $this->sendTemplate('contact_response', $inquiry['email'], [
            'subject' => 'Re: ' . $inquiry['subject'],
            'inquiry' => $inquiry,
            'response' => $response,
            'contact_url' => SITE_URL . '/contact'
        ]);
    }

    public function sendCommentNotification($project, $comment, $projectOwner) {
        if ($comment['user_id'] == $projectOwner['id']) {
            return true;
        }
        
        return $this->sendTemplate('comment_notification', $projectOwner['email'], [
            'subject' => 'New comment on your project: ' . $project['title'],
            'project' => $project,
            'comment' => $comment,
            'project_url' => SITE_URL . '/projects/' . $project['slug']
        ]);
    }

    public function sendPasswordReset($userEmail, $resetToken) {
        return $this->sendTemplate('password_reset', $userEmail, [
            'subject' => 'Password Reset - ' . SITE_NAME,
            'reset_url' => SITE_URL . '/auth/reset-password?token=' . $resetToken,
            'expires_in' => '1 hour'
        ]);
    }

    public function sendEmailVerification($userEmail, $verificationToken) {
        return $this->sendTemplate('email_verification', $userEmail, [
            'subject' => 'Verify your email - ' . SITE_NAME,
            'verification_url' => SITE_URL . '/auth/verify-email?token=' . $verificationToken
        ]);
    }

    public function sendProjectApproval($project, $user) {
        return $this->sendTemplate('project_approval', $user['email'], [
            'subject' => 'Your project has been approved!',
            'project' => $project,
            'project_url' => SITE_URL . '/projects/' . $project['slug']
        ]);
    }

    public function sendNewsletter($recipients, $subject, $content) {
        $htmlContent = $this->wrapInNewsletterTemplate($content, $subject);
        return $this->sendBulk($recipients, $subject, $htmlContent, true);
    }

    private function htmlToPlainText($html) {
        // Remove script and style elements
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Convert common HTML elements
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);
        $html = str_replace(['<p>', '</p>'], ["\n", "\n\n"], $html);
        $html = str_replace(['<div>', '</div>'], ["\n", "\n"], $html);
        
        // Remove all remaining HTML tags
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        $text = trim($text);
        
        return $text;
    }

    private function wrapInNewsletterTemplate($content, $title) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>$title</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    $content
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                    <p><a href='" . SITE_URL . "'>Visit our website</a></p>
                </div>
            </div>
        </body>
        </html>";
    }

    public function testConnection() {
        try {
            if ($this->mailer->isSMTP()) {
                $this->mailer->smtpConnect();
                $this->mailer->smtpClose();
                return true;
            }
            return true; 
        } catch (Exception $e) {
            error_log("SMTP connection test failed: " . $e->getMessage());
            return false;
        }
    }

    public function getStats() {
        return [
            'smtp_enabled' => !empty(SMTP_HOST),
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'encryption' => SMTP_ENCRYPTION,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName
        ];
    }
}

function email() {
    static $mailer = null;
    if ($mailer === null) {
        $mailer = new EmailSender();
    }
    return $mailer;
}