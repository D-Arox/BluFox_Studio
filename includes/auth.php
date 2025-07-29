<?php
class Auth {
    private static $currentUser = null;
    
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public static function user() {
        if (self::$currentUser === null && self::check()) {
            self::$currentUser = db()->fetch(
                "SELECT * FROM users WHERE id = ?",
                [$_SESSION['user_id']]
            );
        }

        return self::$currentUser;
    }

    public static function login($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['login_time'] = time();

        db()->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userData['id']]
        );

        logActivity('user_login', ['user_id' => $userData['id']]);
        return true;
    }

    public static function logout() {
        $userId = $_SESSION['user_id'] ?? null;
        
        session_destroy();
        session_start();
        
        if ($userId) {
            logActivity('user_logout', ['user_id' => $userId]);
        }
        
        return true;
    }

    public static function hasRole($role) {
        $user = self::user();
        return $user && $user['role'] === $role;
    }

    public static function isAdmin() {
        return self::hasRole('admin') || self::hasRole('developer');
    }

    public static function requireAuth() {
        if (!self::check()) {
            redirect('/auth/login', 'Please log in to continue', 'warning');
        }
    }

    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) {
            redirect('/', 'Access denied', 'error');
        }
    }

    public static function handleRobloxUser($robloxData) {
        try {
            $existingUser = db()->fetch(
                "SELECT * FROM users WHERE roblox_id = ?",
                [$robloxData['sub']]
            );
            
            $userData = [
                'roblox_id' => $robloxData['sub'],
                'username' => $robloxData['preferred_username'] ?? $robloxData['name'],
                'display_name' => $robloxData['name'],
                'avatar_url' => $robloxData['picture'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existingUser) {
                db()->update('users', $userData, 'roblox_id = ?', [$robloxData['sub']]);
                $userId = $existingUser['id'];
            } else {
                $userData['role'] = 'user';
                $userData['subscription_tier'] = 'free';
                $userData['created_at'] = date('Y-m-d H:i:s');
                $userId = db()->insert('users', $userData);
            }
            
            // Get updated user data
            $user = db()->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            return $user;
            
        } catch (Exception $e) {
            error_log("Failed to handle Roblox user: " . $e->getMessage());
            return false;
        }
    }

    public static function generateJWT($userId) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'exp' => time() + JWT_EXPIRE,
            'iat' => time()
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, JWT_SECRET, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public static function verifyJWT($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        [$header, $payload, $signature] = $parts;
        
        $validSignature = hash_hmac('sha256', $header . "." . $payload, JWT_SECRET, true);
        $validSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
        
        if (!hash_equals($signature, $validSignatureEncoded)) {
            return false;
        }
        
        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        
        if ($payloadData['exp'] < time()) {
            return false;
        }
        
        return $payloadData;
    }
}
?>