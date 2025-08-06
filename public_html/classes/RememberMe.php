<?php
require_once __DIR__ . '/../../config/database.php';

class RememberMe {
    private $db;
    private $cookieName = 'remember_me';
    private $maxAge = 30 * 24 * 3600; //30 days
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function createToken($userId) {
        try {
            $selector = $this->generateRandomString(12);
            $authenticator = $this->generateRandomString(32);
            $tokenHash = hash('sha256', $authenticator);

            $deviceFingerprint = $this->generateDeviceFingerprint();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $ipAddress = $this->getClientIP();

            $expiresAt = date('Y-m-d H:i:s', time() + $this->maxAge);

            $this->cleanupUserTokens($userId);
            $this->db->insert(
                "INSERT INTO remember_me_tokens 
                 (user_id, token_hash, token_selector, user_agent, ip_address, device_fingerprint, expires_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, $tokenHash, $selector, $userAgent, $ipAddress, $deviceFingerprint, $expiresAt]
            );

            $cookieValue = $selector . ':' . $authenticator;
            $this->setCookie($cookieValue);

            logMessage('info', 'Remember me token created', [
                'user_id' => $userId,
                'selector' => $selector,
                'expires_at' => $expiresAt,
                'ip_address' => $ipAddress
            ]);

            return true;
        } catch (Exception $e) {
            logMessage('error', 'Failed to create remember me token: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            return false;
        }
    }

    public function validateToken() {
        if (!isset($_COOKIE[$this->cookieName])) {
            return false;
        }

        $cookieValue = $_COOKIE[$this->cookieName];

        $parts = explode(':', $cookieValue, 2);
        if (count($parts) !== 2) {
            $this->deleteCookie();
            return false;
        }

        list($selector, $authenticator) = $parts;

        try {
            $token = $this->db->fetchOne(
                "SELECT rmt.* u.id as user_id, u.username, u.roblox_id, u.is_active as user_active
                 FROM remember_me_tokens rmt
                 JOIN users u ON rmt.user_id = u.id
                 WHERE rmt.token_selector = ? AND rmt.is_active = 1 AND rmt.expires_at > NOW() AND u.is_active = 1",
                [$selector]
            );

            if (!$token) {
                $this->deleteCookie();
                logMessage('warning', 'Invalid or expired remember me token', [
                    'selector' => $selector,
                    'ip_address' => $this->getClientIP()
                ]);
                return false;
            }

            $expectedHash = hash('sha256', $authenticator);
            if (!hash_equals($token['token_hash'], $expectedHash)) {
                $this->revokeAllUserTokens($token['user_id']);
                $this->deleteCookie();

                logMessage('security', 'Remember me token hash mismatch - possible attack', [
                    'user_id' => $token['user_id'],
                    'selector' => $selector,
                    'ip_address' => $this->getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                return false;
            }

            $currentIP = $this->getClientIP();
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $currentFingerprint = $this->generateDeviceFingerprint();

            if ($this->shouldRejectToken($token, $currentIP, $currentUserAgent, $currentFingerprint)) {
                $this->revokeToken($token['id']);
                $this->deleteCookie();

                logMessage('security', 'Remember me token rejected due to device/IP change', [
                    'user_id' => $token['user_id'],
                    'original_ip' => $token['ip_address'],
                    'current_ip' => $currentIP,
                    'selector' => $selector
                ]);
                return false;
            }

            $this->db->update(
                "UPDATE remember_me_tokens SET last_used = NOW() WHERE id = ?",
                [$token['id']]
            );

            $this->rotateToken($token);

            logMessage('info', 'Remember me token validated successfully', [
                'user_id' => $token['user_id'],
                'username' => $token['username'],
                'ip_address' => $currentIP
            ]);

            return [
                'user_id' => $token['user_id'],
                'username' => $token['username'],
                'ip_address' => $currentIP
            ];
        } catch (Exception $e) {
            logMessage('error', 'Error validating remember me token: ' . $e->getMessage());
            $this->deleteCookie();
            return false;
        }
    }

    public function revokeToken($tokenId) {
        try {
            $this->db->update(
                "UPDATE remember_me_tokens SET is_active = 0 WHERE id = ?",
                [$tokenId]
            );
            return true;
        } catch (Exception $e) {
            logMessage('error', 'Failed to revoke remember me token: ' . $e->getMessage());
            return false;
        }
    }

    public function revokeAllUserTokens($userId) {
        try {
            $this->db->update(
                "UPDATE remember_me_tokens SET is_active = 0 WHERE user_id = ?",
                [$userId]
            );
            
            logMessage('info', 'All remember me tokens revoked for user', ['user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            logMessage('error', 'Failed to revoke all user tokens: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteCookie() {
        if (isset($_COOKIE[$this->cookieName])) {
            setcookie(
                $this->cookieName,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => COOKIE_DOMAIN,
                    'secure' => ENVIRONMENT === 'production',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
            unset($_COOKIE[$this->cookieName]);
        }
    }

    public function getUserTokens($userId) {
        return $this->db->fetchAll(
            "SELECT id, user_agent, ip_address, created_at, last_used, expires_at
             FROM remember_me_tokens
             WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
             ORDER BY last_used DESC, created_at DESC",
            [$userId]
        );
    }

    private function cleanupUserTokens($userId) {
        $this->db->execute(
            "UPDATE remember_me_tokens SET is_active = 0 
             WHERE user_id = ? AND id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM remember_me_tokens 
                     WHERE user_id = ? AND is_active = 1 
                     ORDER BY created_at DESC 
                     LIMIT 3
                 ) as keep_tokens
             )",
            [$userId, $userId]
        );
    }

    private function rotateToken($token) {
        $this->revokeToken($token['id']);
        $this->createToken($token['user_id']);
    }

    private function setCookie($value) {
        setcookie(
            $this->cookieName,
            $value,
            [
                'expires' => time() + $this->maxAge,
                'path' => '/',
                'domain' => COOKIE_DOMAIN,
                'secure' => ENVIRONMENT === 'production',
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    private function generateRandomString($len) {
        return bin2hex(random_bytes($len));
    }

    private function generateDeviceFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEnc = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

        return hash('sha256', $userAgent . $acceptLang . $acceptEnc);
    }

    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    private function shouldRejectToken($token, $currentIP, $currentUserAgent, $currentFingerprint) {
        if ($token['user_agent'] && $currentUserAgent) {
            similar_text($token['user_agent'], $currentUserAgent, $similarity);
            if ($similarity < 80) {
                return true;
            }
        }

        if ($token['device_fingerprint'] && $currentFingerprint) {
            if ($token['device_fingerprint'] !== $currentFingerprint) {
                return true;
            }
        }

        if ($token['ip_address'] && $currentIP) {
            $oldParts = explode('.', $token['ip_address']);
            $newParts = explode('.', $currentIP);

            if (count($oldParts) >= 2 && count($newParts) >= 2) {
                if ($oldParts[0] !== $newParts[0] && $oldParts[1] !== $newParts[1]) {
                    return true;
                }
            }
        }

        return false;
    }

    public function cleanupExpiredTokens() {
        try {
            $deleted = $this->db->delete(
                "DELETE FROM remember_me_tokens WHERE expires_at < NOW() OR is_active = 0"
            );

            logMessage('info', 'Cleaned up expired remember me tokens', ['deleted_count' => $deleted]);
            return $deleted;
        } catch (Exception $e) {
            logMessage('error', 'Failed to cleanup expired tokens: ' . $e->getMessage());
            return false;
        }
    }
}