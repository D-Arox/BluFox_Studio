<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class RobloxAuth {
    private const AUTH_URL = 'https://apis.roblox.com/oauth/v1/authorize';
    private const TOKEN_URL = 'https://apis.roblox.com/oauth/v1/token';
    private const USERINFO_URL = 'https://apis.roblox.com/oauth/v1/userinfo';
    
    private const REMEMBER_TOKEN_LIFETIME = 2592000; 
    private const REMEMBER_COOKIE_NAME = 'blufox_remember_token';

    private $client_id;
    private $client_secret;
    private $redirect_uri;

    public function __construct() {
        $this->client_id = ROBLOX_CLIENT_ID;
        $this->client_secret = ROBLOX_CLIENT_SECRET;
        $this->redirect_uri = ROBLOX_REDIRECT_URI;

        if (!$this->client_id || !$this->client_secret || !$this->redirect_uri) {
            throw new Exception("OAuth credentials or redirect URI are not set.");
        }
    }

    public function getAuthorizationUrl($state = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $state = $state ?: bin2hex(random_bytes(16));

        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_nonce'] = bin2hex(random_bytes(16));

        session_write_close();

        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => 'openid profile',
            'state' => $state,
            'nonce' => $_SESSION['oauth_nonce']
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public function getAccessToken($code, $state) {
        if (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
            throw new Exception("Invalid state parameter - possible CSRF attack.");
        }

        unset($_SESSION['oauth_state']);
        unset($_SESSION['oauth_nonce']);

        $postData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];

        $response = $this->makeHttpRequest(self::TOKEN_URL, 'POST', $postData, $headers);

        if (!isset($response['access_token'])) {
            throw new Exception('Access token not returned: ' . json_encode($response));
        }

        return $response;
    }

    public function getUserInfo($access_token) {
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Accept: application/json'
        ];

        return $this->makeHttpRequest(self::USERINFO_URL, 'GET', null, $headers);
    }

    private function makeHttpRequest($url, $method, $data = null, $headers = []) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'BluFoxStudio/1.0'
        ]);

        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        if ($httpCode >= 400) {
            throw new Exception("HTTP error $httpCode: $response");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON decode error: " . json_last_error_msg());
        }

        return $decoded;
    }

    public function createOrUpdateUser($userInfo, $tokenData, $rememberMe = false) {
        $db = db();
        
        try {
            $db->beginTransaction();
            
            $roblox_id = $userInfo['sub'];
            $username = $userInfo['preferred_username'];
            $display_name = $userInfo['name'] ?? $username;
            $avatar_url = $userInfo['picture'] ?? null;
            
            $existing_user = $db->fetch(
                "SELECT * FROM users WHERE roblox_id = ?",
                [$roblox_id]
            );
            
            if ($existing_user) {
                $db->query(
                    "UPDATE users SET username = ?, display_name = ?, avatar_url = ?, last_login = NOW(), updated_at = NOW() WHERE roblox_id = ?",
                    [$username, $display_name, $avatar_url, $roblox_id]
                );
                $user_id = $existing_user['id'];
            } else {
                $user_id = $db->insert(
                    "INSERT INTO users (roblox_id, username, display_name, avatar_url, role, created_at, updated_at, last_login) VALUES (?, ?, ?, ?, 'user', NOW(), NOW(), NOW())",
                    [$roblox_id, $username, $display_name, $avatar_url]
                );
            }
            
            $session_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600);
            
            $db->query(
                "INSERT INTO oauth_sessions (user_id, session_token, access_token, refresh_token, expires_at, ip_address, user_agent, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $user_id,
                    $session_token,
                    $tokenData['access_token'],
                    $tokenData['refresh_token'] ?? null,
                    $expires_at,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
            
            $remember_token = null;
            if ($rememberMe) {
                $remember_token = $this->createRememberToken($user_id);
            }
            
            $db->commit();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['session_token'] = $session_token;
            $_SESSION['roblox_id'] = $roblox_id;
            $_SESSION['username'] = $username;
            $_SESSION['display_name'] = $display_name;
            $_SESSION['user_avatar'] = $avatar_url;
            $_SESSION['user_role'] = $existing_user['role'] ?? 'user';
            
            return [
                'success' => true,
                'user_id' => $user_id,
                'session_token' => $session_token,
                'remember_token' => $remember_token
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
    
    private function createRememberToken($user_id) {
        $remember_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + self::REMEMBER_TOKEN_LIFETIME);
        
        try {
            db()->query(
                "INSERT INTO remember_tokens (user_id, token, expires_at, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                [
                    $user_id,
                    hash('sha256', $remember_token),
                    $expires_at,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
            
            $this->setRememberCookie($remember_token);
            
            return $remember_token;
            
        } catch (Exception $e) {
            error_log("Failed to create remember token: " . $e->getMessage());
            return null;
        }
    }
    
    private function setRememberCookie($token) {
        $cookie_options = [
            'expires' => time() + self::REMEMBER_TOKEN_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        setcookie(self::REMEMBER_COOKIE_NAME, $token, $cookie_options);
    }
    
    public function checkRememberToken() {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            return false;
        }
        
        $remember_token = $_COOKIE[self::REMEMBER_COOKIE_NAME];
        $hashed_token = hash('sha256', $remember_token);
        
        try {
            $token_data = db()->fetch(
                "SELECT rt.*, u.* FROM remember_tokens rt 
                 JOIN users u ON rt.user_id = u.id 
                 WHERE rt.token = ? AND rt.expires_at > NOW() AND rt.is_valid = 1",
                [$hashed_token]
            );
            
            if (!$token_data) {
                $this->clearRememberCookie();
                return false;
            }
            
            db()->query(
                "UPDATE remember_tokens SET last_used = NOW() WHERE id = ?",
                [$token_data['id']]
            );
            
            $session_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
            
            db()->query(
                "INSERT INTO oauth_sessions (user_id, session_token, expires_at, ip_address, user_agent, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $token_data['user_id'],
                    $session_token,
                    $expires_at,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
            
            $_SESSION['user_id'] = $token_data['user_id'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['roblox_id'] = $token_data['roblox_id'];
            $_SESSION['username'] = $token_data['username'];
            $_SESSION['display_name'] = $token_data['display_name'];
            $_SESSION['user_avatar'] = $token_data['avatar_url'];
            $_SESSION['user_role'] = $token_data['role'];
            
            return true;
            
        } catch (Exception $e) {
            error_log("Remember token check failed: " . $e->getMessage());
            $this->clearRememberCookie();
            return false;
        }
    }
    
    private function clearRememberCookie() {
        if (isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            $cookie_options = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict'
            ];
            
            setcookie(self::REMEMBER_COOKIE_NAME, '', $cookie_options);
        }
    }

    public function verifySession($session_token) {
        $session = db()->fetch(
            "SELECT s.*, u.* FROM oauth_sessions s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.session_token = ? AND s.is_active = 1 AND s.expires_at > NOW()",
            [$session_token]
        );
        
        if (!$session) {
            return false;
        }
        
        db()->query(
            "UPDATE oauth_sessions SET updated_at = NOW() WHERE session_token = ?",
            [$session_token]
        );
        
        return $session;
    }
    
    public function logout($session_token = null, $clearRememberToken = true) {
        $session_token = $session_token ?: ($_SESSION['session_token'] ?? null);
        
        if ($session_token) {
            db()->query(
                "UPDATE oauth_sessions SET is_active = 0 WHERE session_token = ?",
                [$session_token]
            );
        }
        
        if ($clearRememberToken && isset($_COOKIE[self::REMEMBER_COOKIE_NAME])) {
            $remember_token = $_COOKIE[self::REMEMBER_COOKIE_NAME];
            $hashed_token = hash('sha256', $remember_token);
            
            db()->query(
                "UPDATE remember_tokens SET is_valid = 0 WHERE token = ?",
                [$hashed_token]
            );
            
            $this->clearRememberCookie();
        }
        
        session_unset();
        session_destroy();
        
        return true;
    }
    
    public function cleanupExpiredTokens() {
        try {
            db()->query("DELETE FROM remember_tokens WHERE expires_at < NOW()");
            db()->query("DELETE FROM oauth_sessions WHERE expires_at < NOW() AND is_active = 0");
        } catch (Exception $e) {
            error_log("Token cleanup failed: " . $e->getMessage());
        }
    }
}

function check_auto_login() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    try {
        $auth = new RobloxAuth();
        return $auth->checkRememberToken();
    } catch (Exception $e) {
        error_log("Auto-login check failed: " . $e->getMessage());
        return false;
    }
}

// Auto-initialize check on include
if (!isset($_SESSION['user_id'])) {
    check_auto_login();
}
?>