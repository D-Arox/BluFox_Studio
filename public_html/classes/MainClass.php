<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/RememberMe.php';

class MainClass {
    private $db;
    private $currentUser;
    private $rememberMe;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->rememberMe = new RememberMe();
        $this->loadCurrentUser();
    }

    private function loadCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->db->fetchOne(
                "SELECT * FROM users WHERE id = ? AND is_active = 1",
                [$_SESSION['user_id']]
            );
        }

        if (!$this->currentUser) {
            $rememberMeData = $this->rememberMe->validateToken();
            if ($rememberMeData) {
                $this->currentUser = $this->db->fetchOne(
                    "SELECT * FROM users WHERE id = ? AND is_active = 1",
                    [$rememberMeData['user_id']]
                );
                
                if ($this->currentUser) {
                    $_SESSION['user_id'] = $this->currentUser['id'];
                    $_SESSION['roblox_id'] = $this->currentUser['roblox_id'];
                    $_SESSION['username'] = $this->currentUser['username'];
                    
                    $this->db->update(
                        "UPDATE users SET last_login = NOW() WHERE id = ?",
                        [$this->currentUser['id']]
                    );
                    
                    logMessage('info', 'User session restored from remember me token', [
                        'user_id' => $this->currentUser['id'],
                        'username' => $this->currentUser['username']
                    ]);
                }
            }
        }
    }

    public function getCurrentUser() {
        return $this->currentUser;
    }

    public function isAuthenticated() {
        return $this->currentUser !== null;
    }

    public function isAdmin() {
        return $this->isAuthenticated() &&
              ($this->currentUser['roblox_id'] == '250751329' || 
               $this->currentUser['email'] == 'social@blufox-studio.com');
    }

    public function getUserRememberMeSessions() {
        if (!$this->isAuthenticated()) {
            return [];
        }

        return $this->rememberMe->getUserTokens($this->currentUser['id']);
    }

    public function revokeRememberMeSession($tokenId) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $token = $this->db->fetchOne(
            "SELECT id FROM remember_me_tokens WHERE id = ? AND user_id = ?",
            [$tokenId, $this->currentUser['id']]
        );
        
        if ($token) {
            return $this->rememberMe->revokeToken($tokenId);
        }
        
        return false;
    }

     public function revokeAllOtherRememberMeSessions() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $currentSelector = null;
        if (isset($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me'], 2);
            if (count($parts) === 2) {
                $currentSelector = $parts[0];
            }
        }
        
        if ($currentSelector) {
            $this->db->update(
                "UPDATE remember_me_tokens SET is_active = 0 
                 WHERE user_id = ? AND token_selector != ?",
                [$this->currentUser['id'], $currentSelector]
            );
        } else {
            $this->rememberMe->revokeAllUserTokens($this->currentUser['id']);
        }
        
        logMessage('info', 'Revoked all other remember me sessions', [
            'user_id' => $this->currentUser['id'],
            'kept_selector' => $currentSelector ? substr($currentSelector, 0, 8) . '...' : null
        ]);
        
        return true;
    }

    public function generateUniqueId() {
        return bin2hex(random_bytes(16));
    }

    public function hashApiKey($key) {
        return hash('sha256', $key . API_SECRET_KEY);
    }

    public function generateApiKey() {
        return 'bf_' . bin2hex(random_bytes(32));
    }

    public function validateApiKey($key) {
        $hashedKey = $this->hashApiKey($key);
        
        return $this->db->fetchOne(
            "SELECT ak.*, u.id as user_id, u.username, u.is_active as user_active 
             FROM api_keys ak 
             JOIN users u ON ak.user_id = u.id 
             WHERE ak.key_hash = ? AND ak.is_active = 1 AND u.is_active = 1",
            [$hashedKey]
        );
    }

    public function checkRateLimit($apiKeyId, $limit = null) {
        $limit = $limit ?? API_RATE_LIMIT_REQUESTS;
        $window = API_RATE_LIMIT_WINDOW;

        $apiKey = $this->db->fetchOne(
            "SELECT * FROM api_keys WHERE id = ?",
            [$apiKeyId]
        );

        if (!$apiKey) return false;

        $now = time();
        $resetTime = strtotime($apiKey['rate_limit_reset']);

        if ($now > $resetTime) {
            $this->db->update(
                "UPDATE api_keys SET rate_limit_request = 1, rate_limit_reset = ? WHERE id = ?",
                [date('Y-m-d H:i:s', $now + $window), $apiKeyId]
            );
        }

        if ($apiKey['rate_limit_request'] >= $limit) {
            return false;
        }

        $this->db->update(
            "UPDATE api_keys SET rate_limit_requests = rate_limit_requests + 1, last_used = NOW() WHERE id = ?",
            [$apiKeyId]
        );

        return true;
    }

    public function logApiRequest($userId, $apiKeyId, $endpoint, $method, $responseCode, $responseTime, $requestData = null) {
        $this->db->insert(
            "INSERT INTO api_logs (user_id, api_key_id, endpoint, method, ip_address, user_agent, request_data, response_code, response_time_ms) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $apiKeyId,
                $endpoint,
                $method,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $requestData ? json_encode($requestData) : null,
                $responseCode,
                $responseTime
            ]
        );
    }

    public function jsonResponse($data, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        foreach ($headers as $header => $value) {
            header("{$header}: {$value}");
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function validateGameOwnership($userId, $gameId) {
        $verificationToken = bin2hex(random_bytes(16));
        
        $this->db->insert(
            "INSERT INTO user_games (user_id, game_id, game_name, verification_token, is_verified) 
             VALUES (?, ?, ?, ?, 0) 
             ON DUPLICATE KEY UPDATE verification_token = VALUES(verification_token)",
            [$userId, $gameId, 'Pending Verification', $verificationToken]
        );
        
        return $verificationToken;
    }

    public function processVaultStats($gameId, $statsData) {
        try {
            $this->db->beginTransaction();
            $game = $this->db->fetchOne(
                "SELECT ug.*, u.id as user_id FROM user_games ug 
                 JOIN users u ON ug.user_id = u.id 
                 WHERE ug.id = ? AND ug.vault_enabled = 1",
                [$gameId]
            );
            
            if (!$game) {
                throw new Exception('Game not found or Vault not enabled');
            }
            
            $this->db->insert(
                "INSERT INTO vault_stats (game_id, player_count, active_vaults, total_operations, 
                                        data_stored_mb, performance_ms, error_count, uptime_seconds, 
                                        memory_usage_mb, raw_data) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $gameId,
                    $statsData['player_count'] ?? 0,
                    $statsData['active_vaults'] ?? 0,
                    $statsData['total_operations'] ?? 0,
                    $statsData['data_stored_mb'] ?? 0,
                    $statsData['performance_ms'] ?? 0,
                    $statsData['error_count'] ?? 0,
                    $statsData['uptime_seconds'] ?? 0,
                    $statsData['memory_usage_mb'] ?? 0,
                    json_encode($statsData)
                ]
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            logMessage('error', 'Failed to process vault stats: ' . $e->getMessage(), $statsData);
            throw $e;
        }
    }

     public function aggregateVaultStats() {
        $currentHour = date('H');
        $currentDate = date('Y-m-d');
        
        $games = $this->db->fetchAll(
            "SELECT id FROM user_games WHERE vault_enabled = 1"
        );
        
        foreach ($games as $game) {
            $stats = $this->db->fetchAll(
                "SELECT * FROM vault_stats 
                 WHERE game_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
                [$game['id']]
            );
            
            if (empty($stats)) continue;
            
            $totalRecords = count($stats);
            $avgPlayers = array_sum(array_column($stats, 'player_count')) / $totalRecords;
            $maxPlayers = max(array_column($stats, 'player_count'));
            $avgVaults = array_sum(array_column($stats, 'active_vaults')) / $totalRecords;
            $totalOperations = array_sum(array_column($stats, 'total_operations'));
            $avgPerformance = array_sum(array_column($stats, 'performance_ms')) / $totalRecords;
            $totalErrors = array_sum(array_column($stats, 'error_count'));
            
            $totalUptime = array_sum(array_column($stats, 'uptime_seconds'));
            $maxPossibleUptime = $totalRecords * 300; // 5 minutes per record
            $uptimePercentage = ($totalUptime / $maxPossibleUptime) * 100;
            
            $this->db->execute(
                "INSERT INTO vault_analytics 
                 (game_id, date, hour, avg_players, max_players, avg_vaults, total_operations, 
                  avg_performance_ms, total_errors, uptime_percentage, data_points_count) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 avg_players = VALUES(avg_players),
                 max_players = GREATEST(max_players, VALUES(max_players)),
                 avg_vaults = VALUES(avg_vaults),
                 total_operations = total_operations + VALUES(total_operations),
                 avg_performance_ms = VALUES(avg_performance_ms),
                 total_errors = total_errors + VALUES(total_errors),
                 uptime_percentage = VALUES(uptime_percentage),
                 data_points_count = data_points_count + VALUES(data_points_count)",
                [
                    $game['id'], $currentDate, $currentHour,
                    round($avgPlayers, 2), $maxPlayers, round($avgVaults, 2),
                    $totalOperations, round($avgPerformance, 2), $totalErrors,
                    round($uptimePercentage, 2), $totalRecords
                ]
            );
        }
        
        logMessage('info', 'Vault statistics aggregated for ' . count($games) . ' games');
    }

    public function cleanOldData() {
        $this->db->delete(
            "DELETE FROM vault_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        $this->db->delete(
            "DELETE FROM api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        $this->db->delete(
            "DELETE FROM oauth_states WHERE expires_at < NOW()"
        );
        
        $this->db->delete(
            "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL " . SESSION_LIFETIME . " SECOND))"
        );
        
        logMessage('info', 'Old data cleaned up');
    }
    
    public function getSetting($key, $default = null) {
        static $cache = [];
        
        if (!isset($cache[$key])) {
            $setting = $this->db->fetchOne(
                "SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?",
                [$key]
            );
            
            if ($setting) {
                switch ($setting['setting_type']) {
                    case 'boolean':
                        $cache[$key] = $setting['setting_value'] === 'true';
                        break;
                    case 'integer':
                        $cache[$key] = (int) $setting['setting_value'];
                        break;
                    case 'json':
                        $cache[$key] = json_decode($setting['setting_value'], true);
                        break;
                    default:
                        $cache[$key] = $setting['setting_value'];
                }
            } else {
                $cache[$key] = $default;
            }
        }
        
        return $cache[$key];
    }
    
    public function updateSetting($key, $value, $type = 'string') {
        $stringValue = $value;
        
        if ($type === 'boolean') {
            $stringValue = $value ? 'true' : 'false';
        } elseif ($type === 'json') {
            $stringValue = json_encode($value);
        } else {
            $stringValue = (string) $value;
        }
        
        $this->db->execute(
            "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
             VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)",
            [$key, $stringValue, $type]
        );
        
        logMessage('info', "System setting updated: {$key} = {$stringValue}");
    }
    
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to);
            
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            $mail->send();
            logMessage('info', "Email sent successfully to {$to}");
            return true;
            
        } catch (Exception $e) {
            logMessage('error', "Failed to send email to {$to}: " . $mail->ErrorInfo);
            return false;
        }
    }
}
?>