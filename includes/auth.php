<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class RobloxAuth {
    private const AUTH_URL = 'https://apis.roblox.com/oauth/v1/authorize';
    private const TOKEN_URL = 'https://apis.roblox.com/oauth/v1/token';
    private const USERINFO_URL = 'https://apis.roblox.com/oauth/v1/userinfo';

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
        $state = $state ?: bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_nonce'] = bin2hex(random_bytes(16));

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
            throw new Exception('Invalid state parameter');
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

        $response = $this->makeHttpRequest(self::USERINFO_URL, 'GET', null, $headers);

        if (!isset($response['sub'])) {
            throw new Exception('Failed to retrieve user info.');
        }

        return $response;
    }

    public function createOrUpdateUser($userInfo, $tokenData) {
        $db = db();
        
        $roblox_id = $userInfo['sub'];
        $username = $userInfo['preferred_username'] ?? 'Unknown';
        $display_name = $userInfo['name'] ?? $username;
        $avatar_url = $userInfo['picture'] ?? null;
        
        try {
            $db->beginTransaction();
            
            $existing_user = $db->fetch(
                "SELECT * FROM users WHERE roblox_id = ?",
                [$roblox_id]
            );
            
            if ($existing_user) {
                $db->query(
                    "UPDATE users SET 
                        username = ?, 
                        display_name = ?, 
                        avatar_url = ?, 
                        last_login_at = NOW() 
                    WHERE roblox_id = ?",
                    [$username, $display_name, $avatar_url, $roblox_id]
                );
                $user_id = $existing_user['id'];
            } else {
                $db->query(
                    "INSERT INTO users (roblox_id, username, display_name, avatar_url, last_login_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    [$roblox_id, $username, $display_name, $avatar_url]
                );
                $user_id = $db->lastInsertId();
            }
            
            $session_token = $this->generateSessionToken();
            $expires_at = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days
            
            $db->query(
                "INSERT INTO oauth_sessions 
                (user_id, session_token, access_token, refresh_token, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)",
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
                'session_token' => $session_token
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
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
    
    public function logout($session_token = null) {
        $session_token = $session_token ?: ($_SESSION['session_token'] ?? null);
        
        if ($session_token) {
            db()->query(
                "UPDATE oauth_sessions SET is_active = 0 WHERE session_token = ?",
                [$session_token]
            );
        }
        
        session_unset();
        session_destroy();
        
        return true;
    }
    
    private function generateSessionToken() {
        return bin2hex(random_bytes(32));
    }
    
    private function makeHttpRequest($url, $method = 'GET', $data = null, $headers = []) {
        $curl = curl_init();
        
        $default_headers = [
            'User-Agent: BluFoxStudio/1.0 (https://blufox-studio.com)',
            'Accept: application/json'
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        if ($method === 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data) {
                $content_type_header = null;
                foreach ($headers as $header) {
                    if (stripos($header, 'content-type:') === 0) {
                        $content_type_header = $header;
                        break;
                    }
                }
                
                if ($content_type_header && stripos($content_type_header, 'application/json') !== false) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                } else {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            }
        }
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        if (DEBUG_MODE) {
            error_log("HTTP Request: $method $url");
            error_log("HTTP Response Code: $http_code");
            if ($error) {
                error_log("cURL Error: $error");
            }
        }
    
        curl_close($curl);
        
        if ($error) {
            if (DEBUG_MODE) {
                error_log("cURL Error: " . $error);
            }
            throw new Exception("HTTP request failed: " . $error);
        }
        
        if ($http_code >= 400) {
            if (DEBUG_MODE) {
                error_log("HTTP Error {$http_code}: " . $response);
            }
            throw new Exception("HTTP request failed with status {$http_code}: " . $response);
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            if (DEBUG_MODE) {
                error_log("JSON decode error: " . json_last_error_msg());
                error_log("Raw response: " . $response);
            }
            throw new Exception("Invalid JSON response from server");
        }
        
        return $decoded_response;
    }
}

function require_auth() {
    if (!is_authenticated()) {
        redirect('/auth/login');
    }
}

function require_admin() {
    require_auth();
    if (!is_admin()) {
        redirect('/', 403);
    }
}

function is_authenticated() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    $auth = new RobloxAuth();
    $session = $auth->verifySession($_SESSION['session_token']);
    
    if (!$session) {
        logout();
        return false;
    }
    
    return true;
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_moderator() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'moderator']);
}

function get_user() {
    if (!is_authenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'roblox_id' => $_SESSION['roblox_id'],
        'username' => $_SESSION['username'],
        'display_name' => $_SESSION['display_name'],
        'avatar_url' => $_SESSION['user_avatar'],
        'role' => $_SESSION['user_role']
    ];
}

function logout() {
    $auth = new RobloxAuth();
    $auth->logout();
}

// Global auth helper functions
function auth_user() {
    return get_user();
}

function auth_check() {
    return is_authenticated();
}

function auth_admin() {
    return is_admin();
}
?>