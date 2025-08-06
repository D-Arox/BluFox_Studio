<?php
require_once __DIR__ . '/../../classes/MainClass.php';
require_once __DIR__ . '/../../classes/RobloxOAuth.php';

header('Content-Type: application/json; charset=utf-8');

$mainClass = new MainClass();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

try {
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            switch ($path) {
                case '/check':
                    $mainClass->jsonResponse([
                        'authenticated' => $mainClass->isAuthenticated(),
                        'user' => $mainClass->getCurrentUser()
                    ]);
                    break;
                    
                case '/logout':
                    $robloxOAuth = new RobloxOAuth();
                    $robloxOAuth->logout();
                    
                    $mainClass->jsonResponse([
                        'status' => 'success',
                        'message' => 'Logged out successfully'
                    ]);
                    break;
                    
                case '/update-email':
                    if (!$mainClass->isAuthenticated()) {
                        throw new Exception('Authentication required', 401);
                    }
                    
                    if (!isset($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('Valid email address required', 400);
                    }
                    
                    $user = new User();
                    $user->update($mainClass->getCurrentUser()['id'], [
                        'email' => $input['email'],
                        'email_verified' => false
                    ]);
                    
                    $verificationToken = bin2hex(random_bytes(32));
                    $verificationUrl = SITE_URL . "/auth/verify-email?token={$verificationToken}";
                    
                    $emailBody = "
                        <h2>Verify your email address</h2>
                        <p>Hi {$mainClass->getCurrentUser()['username']},</p>
                        <p>Please click the link below to verify your email address:</p>
                        <p><a href=\"{$verificationUrl}\">Verify Email Address</a></p>
                        <p>If you didn't request this verification, you can safely ignore this email.</p>
                        <p>Best regards,<br>The BluFox Studio Team</p>
                    ";
                    
                    $mainClass->sendEmail(
                        $input['email'],
                        'Verify your BluFox Studio email',
                        $emailBody
                    );
                    
                    $mainClass->jsonResponse([
                        'status' => 'success',
                        'message' => 'Verification email sent'
                    ]);
                    break;
                    
                case '/update-preferences':
                    if (!$mainClass->isAuthenticated()) {
                        throw new Exception('Authentication required', 401);
                    }
                    
                    $allowedFields = ['marketing_emails', 'gdpr_consent', 'cookie_consent'];
                    $updateData = array_intersect_key($input, array_flip($allowedFields));
                    
                    if (empty($updateData)) {
                        throw new Exception('No valid preferences to update', 400);
                    }
                    
                    $user = new User();
                    $user->update($mainClass->getCurrentUser()['id'], $updateData);
                    
                    $mainClass->jsonResponse([
                        'status' => 'success',
                        'message' => 'Preferences updated'
                    ]);
                    break;
                    
                default:
                    throw new Exception('Endpoint not found', 404);
            }
            break;
            
        case 'GET':
            switch ($path) {
                case '/user':
                    if (!$mainClass->isAuthenticated()) {
                        throw new Exception('Authentication required', 401);
                    }
                    
                    $user = $mainClass->getCurrentUser();
                    unset($user['unique_id']);
                    
                    $mainClass->jsonResponse($user);
                    break;
                    
                default:
                    throw new Exception('Endpoint not found', 404);
            }
            break;
            
        default:
            throw new Exception('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    $mainClass->jsonResponse([
        'error' => $e->getMessage()
    ], $e->getCode() ?: 500);
}
?>