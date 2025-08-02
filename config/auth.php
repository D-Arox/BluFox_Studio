<?php
if (!defined('BLUFOX_CONFIG')) {
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/database.php';

class Auth {
    private static $instance = null;
    private $db;
    private $currentUser = null;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function generateJWT($payload, $expiry = null) {
        if ($expiry === null) {
            $expiry = time() + SESSION_EXPIRE;
        }

        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = $expiry;
        $payload['iat'] = time();
        $payload = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    public function verifyJWT($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }
            
            [$header, $payload, $signature] = $parts;
            
            $validSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
            $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
            
            if (!hash_equals($signature, $validSignature)) {
                return false;
            }
            
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            
            if ($payload['exp'] < time()) {
                return false;
            }
            
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }

    public function generateCSRFToken() {
        $token = generate_random_string(32);
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }

    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function login($robloxId, $userData, $rememberMe = false) {
        try {
            $this->db->beginTransaction();
            
            $user = $this->db->select('users', ['roblox_id' => $robloxId]);
            
            if (empty($user)) {
                $userId = $this->db->insert('users', [
                    'roblox_id' => $robloxId,
                    'username' => $userData['username'],
                    'display_name' => $userData['display_name'] ?? $userData['username'],
                    'avatar_url' => $userData['avatar_url'] ?? null,
                    'role' => 'user',
                    'status' => 'active',
                    'last_login' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $user = $this->db->select('users', ['id' => $userId])[0];
            } else {
                $user = $user[0];
                
                $this->db->update('users', [
                    'username' => $userData['username'],
                    'display_name' => $userData['display_name'] ?? $userData['username'],
                    'avatar_url' => $userData['avatar_url'] ?? $user['avatar_url'],
                    'last_login' => date('Y-m-d H:i:s')
                ], ['id' => $user['id']]);
            }
            
            $sessionToken = generate_random_string(64);
            $expiry = $rememberMe ? time() + REMEMBER_ME_EXPIRE : time() + SESSION_EXPIRE;
            
            $this->db->insert('user_sessions', [
                'user_id' => $user['id'],
                'session_token' => hash('sha256', $sessionToken),
                'expires_at' => date('Y-m-d H:i:s', $expiry),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'is_remember_me' => $rememberMe ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            if ($rememberMe) {
                setcookie('remember_token', $sessionToken, $expiry, '/', '', ENVIRONMENT === 'production', true);
            }
            
            $this->currentUser = $user;
            $this->db->commit();
            
            return $user;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Login failed: " . $e->getMessage());
            return false;
        }
    }

    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                $this->db->delete('user_sessions', ['user_id' => $_SESSION['user_id']]);
            }
            
            session_unset();
            session_destroy();
            
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/', '', false, true);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Logout failed: " . $e->getMessage());
            return false;
        }
    }

    public function isAuthenticated() {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }
        
        if (isset($_COOKIE['remember_token'])) {
            return $this->validateRememberToken($_COOKIE['remember_token']);
        }
        
        return false;
    }

    private function validateRememberToken($token) {
        try {
            $hashedToken = hash('sha256', $token);
            
            $session = $this->db->select('user_sessions', [
                'session_token' => $hashedToken,
                'is_remember_me' => 1
            ]);
            
            if (empty($session)) {
                return false;
            }
            
            $session = $session[0];
            
            if (strtotime($session['expires_at']) < time()) {
                $this->db->delete('user_sessions', ['id' => $session['id']]);
                return false;
            }
            
            $user = $this->db->select('users', ['id' => $session['user_id']])[0];
            
            if (!$user || $user['status'] !== 'active') {
                return false;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            $this->currentUser = $user;
            
            $this->db->update('user_sessions', [
                'last_activity' => date('Y-m-d H:i:s')
            ], ['id' => $session['id']]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Remember token validation failed: " . $e->getMessage());
            return false;
        }
    }

    public function getCurrentUser() {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }
        
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        if (isset($_SESSION['user_id'])) {
            $user = $this->db->select('users', ['id' => $_SESSION['user_id']]);
            if (!empty($user)) {
                $this->currentUser = $user[0];
                return $this->currentUser;
            }
        }
        
        return null;
    }

    public function hasRole($role) {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }
        
        $roles = ['user', 'moderator', 'admin', 'superadmin'];
        $userRoleIndex = array_search($user['role'], $roles);
        $requiredRoleIndex = array_search($role, $roles);
        
        return $userRoleIndex !== false && $userRoleIndex >= $requiredRoleIndex;
    }

    public function canAccessAdmin() {
        return $this->hasRole('moderator');
    }

    public function getUserPermissions() {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }
        
        $permissions = [
            'user' => ['view_projects', 'contact_form'],
            'moderator' => ['view_projects', 'contact_form', 'manage_inquiries', 'view_analytics'],
            'admin' => ['view_projects', 'contact_form', 'manage_inquiries', 'view_analytics', 'manage_projects', 'manage_services', 'manage_users'],
            'superadmin' => ['*'] // All permissions
        ];
        
        return $permissions[$user['role']] ?? [];
    }

    public function hasPermission($permission) {
        $permissions = $this->getUserPermissions();
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }

    public function checkRateLimit($action, $limit = 10, $window = 3600) {
        $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $cacheFile = CACHE_PATH . '/rate_limit_' . md5($key) . '.json';
        
        $data = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        $now = time();
        $windowStart = $now - $window;
        
        $data = array_filter($data, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        if (count($data) >= $limit) {
            return false;
        }
        
        $data[] = $now;
        
        file_put_contents($cacheFile, json_encode($data));
        
        return true;
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function generateApiKey($userId) {
        $apiKey = generate_random_string(64);
        
        $this->db->insert('api_keys', [
            'user_id' => $userId,
            'api_key' => hash('sha256', $apiKey),
            'name' => 'Default API Key',
            'permissions' => json_encode(['read']),
            'expires_at' => date('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60)), // 1 year
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $apiKey;
    }

    public function validateApiKey($apiKey) {
        $hashedKey = hash('sha256', $apiKey);
        
        $key = $this->db->select('api_keys', [
            'api_key' => $hashedKey,
            'is_active' => 1
        ]);
        
        if (empty($key)) {
            return false;
        }
        
        $key = $key[0];
        
        if (strtotime($key['expires_at']) < time()) {
            return false;
        }
        
        $this->db->update('api_keys', [
            'last_used' => date('Y-m-d H:i:s'),
            'usage_count' => $key['usage_count'] + 1
        ], ['id' => $key['id']]);
        
        return $key;
    }

    public function cleanupSessions() {
        try {
            $this->db->delete('user_sessions', ['expires_at < NOW()']);
            $this->db->delete('api_keys', ['expires_at < NOW()']);
            return true;
        } catch (Exception $e) {
            error_log("Session cleanup failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUserSessions($userId) {
        return $this->db->select('user_sessions', [
            'user_id' => $userId,
            'expires_at > NOW()'
        ], '*', 'created_at DESC');
    }
    
    public function revokeSession($sessionId, $userId) {
        return $this->db->delete('user_sessions', [
            'id' => $sessionId,
            'user_id' => $userId
        ]);
    }
    
    public function generateTOTPSecret() {
        return generate_random_string(32);
    }
    
    public function logActivity($action, $details = null) {
        $user = $this->getCurrentUser();
        
        $this->db->insert('audit_logs', [
            'user_id' => $user['id'] ?? null,
            'action' => $action,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}

function auth() {
    return Auth::getInstance();
}

function is_logged_in() {
    return auth()->isAuthenticated();
}

function current_user() {
    return auth()->getCurrentUser();
}

function has_role($role) {
    return auth()->hasRole($role);
}

function has_permission($permission) {
    return auth()->hasPermission($permission);
}

function can_access_admin() {
    return auth()->canAccessAdmin();
}

function require_auth() {
    if (!is_logged_in()) {
        safe_redirect('/auth/login');
    }
}

function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        http_response_code(403);
        include PUBLIC_PATH . '/errors/403.php';
        exit;
    }
}

function require_permission($permission) {
    require_auth();
    if (!has_permission($permission)) {
        http_response_code(403);
        include PUBLIC_PATH . '/errors/403.php';
        exit;
    }
}

function csrf_token() {
    return auth()->generateCSRFToken();
}

function csrf_field() {
    $token = csrf_token();
    return "<input type='hidden' name='csrf_token' value='$token'>";
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!auth()->verifyCSRFToken($token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
}

$auth = Auth::getInstance();

if (rand(1, 100) === 1) {
    $auth->cleanupSessions();
}