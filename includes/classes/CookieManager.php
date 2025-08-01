<?php
class CookieManager {
    private $db;
    private $consent_table = 'cookie_consents';
    private $categories = [
        'necessary' => [
            'name' => 'Necessary',
            'description' => 'Essential cookies required for website functionality and security.',
            'required' => true,
            'cookies' => ['PHPSESSID', 'csrf_token', 'cookie_consent']
        ],
        'functional' => [
            'name' => 'Functional',
            'description' => 'Cookies that enhance your experience and remember your preferences.',
            'required' => false,
            'cookies' => ['theme_preference', 'language_preference', 'nav_state']
        ],
        'analytics' => [
            'name' => 'Analytics',
            'description' => 'Cookies that help us understand how visitors interact with our website.',
            'required' => false,
            'cookies' => ['_ga', '_gid', '_gat', 'page_views', 'user_analytics']
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Cookies used to deliver personalized content and track marketing effectiveness.',
            'required' => false,
            'cookies' => ['_fbp', '_fbc', 'marketing_id', 'ad_preferences']
        ]
    ];

    public function __construct($database = null) {
        $this->db = $database;
        $this->createConsentTable();
    }

    private function createConsentTable() {
        if (!$this->db) return;

        $sql = "CREATE TABLE IF NOT EXISTS `{$this->consent_table}` (
            `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `session_id` varchar(255) NOT NULL,
            `user_id` bigint(20) unsigned DEFAULT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` text DEFAULT NULL,
            `consent_data` json NOT NULL,
            `consent_version` varchar(10) DEFAULT '1.0',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_session_id` (`session_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create consent table: " . $e->getMessage());
        }
    }

    public function getConsent($session_id = null, $user_id = null) {
        $session_id = $session_id ?: session_id();
        
        if (isset($_COOKIE['cookie_consent'])) {
            $consent = json_decode($_COOKIE['cookie_consent'], true);
            if ($consent && is_array($consent)) {
                return $consent;
            }
        }

        if ($this->db) {
            return $this->getConsentFromDatabase($session_id, $user_id);
        }

        return null;
    }

    private function getConsentFromDatabase($session_id, $user_id = null) {
        try {
            $sql = "SELECT consent_data FROM {$this->consent_table} 
                    WHERE session_id = :session_id";
            $params = ['session_id' => $session_id];

            if ($user_id) {
                $sql .= " OR user_id = :user_id";
                $params['user_id'] = $user_id;
            }

            $sql .= " ORDER BY created_at DESC LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return json_decode($result['consent_data'], true);
            }
        } catch (PDOException $e) {
            error_log("Failed to get consent from database: " . $e->getMessage());
        }

        return null;
    }

    public function saveConsent($consent_data, $session_id = null, $user_id = null) {
        $session_id = $session_id ?: session_id();
        
        if (!$this->validateConsent($consent_data)) {
            throw new InvalidArgumentException('Invalid consent data');
        }

        $consent_data['categories']['necessary'] = true;

        $this->setConsentCookie($consent_data);

        if ($this->db) {
            $this->saveConsentToDatabase($consent_data, $session_id, $user_id);
        }

        return true;
    }

    private function setConsentCookie($consent_data) {
        $cookie_value = json_encode($consent_data);
        $expires = time() + (365 * 24 * 60 * 60); // 1 year
        
        setcookie(
            'cookie_consent',
            $cookie_value,
            $expires,
            '/',
            $_SERVER['HTTP_HOST'],
            isset($_SERVER['HTTPS']),
            true // HttpOnly
        );
    }

    private function saveConsentToDatabase($consent_data, $session_id, $user_id = null) {
        try {
            $sql = "INSERT INTO {$this->consent_table} 
                    (session_id, user_id, ip_address, user_agent, consent_data, consent_version) 
                    VALUES (:session_id, :user_id, :ip_address, :user_agent, :consent_data, :version)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'session_id' => $session_id,
                'user_id' => $user_id,
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'consent_data' => json_encode($consent_data),
                'version' => $consent_data['version'] ?? '1.0'
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Failed to save consent to database: " . $e->getMessage());
            return false;
        }
    }

    private function validateConsent($consent_data) {
        if (!is_array($consent_data)) return false;
        if (!isset($consent_data['categories']) || !is_array($consent_data['categories'])) return false;
        if (!isset($consent_data['timestamp']) || !is_numeric($consent_data['timestamp'])) return false;
        
        foreach ($consent_data['categories'] as $category => $enabled) {
            if (!is_bool($enabled)) return false;
        }

        return true;
    }

    public function hasConsent($category, $session_id = null, $user_id = null) {
        $consent = $this->getConsent($session_id, $user_id);
        
        if (!$consent || !isset($consent['categories'])) {
            return false;
        }

        return isset($consent['categories'][$category]) && $consent['categories'][$category] === true;
    }

    public function requiresConsent($session_id = null, $user_id = null) {
        $consent = $this->getConsent($session_id, $user_id);
        return $consent === null;
    }

    public function getCategories() {
        return $this->categories;
    }

    public function updateCategory($category_id, $data) {
        if (isset($this->categories[$category_id])) {
            $this->categories[$category_id] = array_merge($this->categories[$category_id], $data);
            return true;
        }
        return false;
    }

    public function removeConsent($session_id = null, $user_id = null) {
        $session_id = $session_id ?: session_id();

        setcookie('cookie_consent', '', time() - 3600, '/', $_SERVER['HTTP_HOST']);

        if ($this->db) {
            try {
                $sql = "UPDATE {$this->consent_table} 
                        SET consent_data = JSON_SET(consent_data, '$.withdrawn', true),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE session_id = :session_id";
                
                $params = ['session_id' => $session_id];
                
                if ($user_id) {
                    $sql .= " OR user_id = :user_id";
                    $params['user_id'] = $user_id;
                }

                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } catch (PDOException $e) {
                error_log("Failed to update consent withdrawal: " . $e->getMessage());
            }
        }

        return true;
    }

    public function getConsentStats($days = 30) {
        if (!$this->db) return null;

        try {
            $sql = "SELECT 
                        JSON_EXTRACT(consent_data, '$.categories.necessary') as necessary,
                        JSON_EXTRACT(consent_data, '$.categories.functional') as functional,
                        JSON_EXTRACT(consent_data, '$.categories.analytics') as analytics,
                        JSON_EXTRACT(consent_data, '$.categories.marketing') as marketing,
                        COUNT(*) as count,
                        DATE(created_at) as date
                    FROM {$this->consent_table} 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    AND JSON_EXTRACT(consent_data, '$.withdrawn') IS NULL
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get consent stats: " . $e->getMessage());
            return null;
        }
    }

    public function cleanupOldRecords($days = 1095) { // 3 years default
        if (!$this->db) return false;

        try {
            $sql = "DELETE FROM {$this->consent_table} 
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['days' => $days]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to cleanup old consent records: " . $e->getMessage());
            return false;
        }
    }

    public function exportUserData($user_id) {
        if (!$this->db || !$user_id) return null;

        try {
            $sql = "SELECT session_id, consent_data, consent_version, created_at, updated_at
                    FROM {$this->consent_table} 
                    WHERE user_id = :user_id
                    ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to export user consent data: " . $e->getMessage());
            return null;
        }
    }

    public function deleteUserData($user_id) {
        if (!$this->db || !$user_id) return false;

        try {
            $sql = "DELETE FROM {$this->consent_table} WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Failed to delete user consent data: " . $e->getMessage());
            return false;
        }
    }

    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function shouldEnableAnalytics($session_id = null, $user_id = null) {
        return $this->hasConsent('analytics', $session_id, $user_id);
    }

    public function shouldEnableMarketing($session_id = null, $user_id = null) {
        return $this->hasConsent('marketing', $session_id, $user_id);
    }

    public function shouldEnableFunctional($session_id = null, $user_id = null) {
        return $this->hasConsent('functional', $session_id, $user_id);
    }

    public function generateComplianceReport($start_date = null, $end_date = null) {
        if (!$this->db) return null;

        $start_date = $start_date ?: date('Y-m-d', strtotime('-30 days'));
        $end_date = $end_date ?: date('Y-m-d');

        try {
            $sql = "SELECT 
                        COUNT(*) as total_consents,
                        COUNT(CASE WHEN JSON_EXTRACT(consent_data, '$.categories.analytics') = true THEN 1 END) as analytics_consents,
                        COUNT(CASE WHEN JSON_EXTRACT(consent_data, '$.categories.marketing') = true THEN 1 END) as marketing_consents,
                        COUNT(CASE WHEN JSON_EXTRACT(consent_data, '$.categories.functional') = true THEN 1 END) as functional_consents,
                        COUNT(CASE WHEN JSON_EXTRACT(consent_data, '$.withdrawn') = true THEN 1 END) as withdrawn_consents,
                        MIN(created_at) as first_consent,
                        MAX(created_at) as last_consent
                    FROM {$this->consent_table} 
                    WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            
            $report = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($report['total_consents'] > 0) {
                $report['analytics_percentage'] = round(($report['analytics_consents'] / $report['total_consents']) * 100, 2);
                $report['marketing_percentage'] = round(($report['marketing_consents'] / $report['total_consents']) * 100, 2);
                $report['functional_percentage'] = round(($report['functional_consents'] / $report['total_consents']) * 100, 2);
                $report['withdrawn_percentage'] = round(($report['withdrawn_consents'] / $report['total_consents']) * 100, 2);
            }
            
            $report['period'] = ['start' => $start_date, 'end' => $end_date];
            
            return $report;
        } catch (PDOException $e) {
            error_log("Failed to generate compliance report: " . $e->getMessage());
            return null;
        }
    }
}
?>