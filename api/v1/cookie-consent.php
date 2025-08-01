<?php
/**
 * Cookie Consent API Endpoint v1 for BluFox Studio - DEBUG VERSION
 * Added extensive error handling and debugging
 */

// Turn off HTML error display for JSON API
ini_set('display_errors', E_ALL);
ini_set('log_errors', 1);

// Capture any output that shouldn't be there
ob_start();

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Set JSON headers immediately
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');

    // CORS headers
    if (defined('SITE_URL')) {
        header('Access-Control-Allow-Origin: ' . SITE_URL);
    } else {
        header('Access-Control-Allow-Origin: *');
    }
    header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        ob_end_clean();
        exit('{}');
    }

    // Try to include config files with error handling
    $config_loaded = false;
    $db_loaded = false;

    // Try to load config
    $config_paths = [
        __DIR__ . '/../../../config/config.php'
    ];

    foreach ($config_paths as $path) {
        if (file_exists($path)) {
            try {
                require_once $path;
                $config_loaded = true;
                break;
            } catch (Exception $e) {
                error_log("Failed to load config from $path: " . $e->getMessage());
            }
        }
    }

    if (!$config_loaded) {
        throw new Exception('Could not load configuration file');
    }

    // Try to load database
    $db_paths = [
        __DIR__ . '/../../../config/database.php',
    ];

    foreach ($db_paths as $path) {
        if (file_exists($path)) {
            try {
                require_once $path;
                $db_loaded = true;
                break;
            } catch (Exception $e) {
                error_log("Failed to load database from $path: " . $e->getMessage());
            }
        }
    }

    if (!$db_loaded) {
        throw new Exception('Could not load database configuration');
    }

    // Check if rate limiting function exists
    if (function_exists('check_rate_limit')) {
        check_rate_limit(50);
    }

    // Try to get database connection
    $db = null;
    if (class_exists('Database')) {
        try {
            $db = Database::getInstance();
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Continue without database - we'll handle this gracefully
        }
    }

    // Ensure consent table exists if we have database
    if ($db) {
        createConsentTableIfNotExists($db);
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            handleConsentSubmission($db);
            break;
            
        case 'GET':
            handleConsentRetrieval($db);
            break;
            
        case 'DELETE':
            handleConsentWithdrawal($db);
            break;
            
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    // Clean any unwanted output
    ob_end_clean();
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500,
        'timestamp' => time(),
        'debug' => [
            'config_loaded' => $config_loaded ?? false,
            'db_loaded' => $db_loaded ?? false,
            'db_available' => isset($db) && $db !== null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]
    ]);
    exit;
} catch (Error $e) {
    // Handle fatal errors
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage(),
        'code' => 500,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Create consent table if it doesn't exist
 */
function createConsentTableIfNotExists($db) {
    if (!$db) return;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `privacy_consents` (
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
            KEY `idx_created_at` (`created_at`),
            KEY `idx_ip_address` (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $db->query($sql);
        
    } catch (Exception $e) {
        error_log("Failed to create privacy consents table: " . $e->getMessage());
        // Don't throw - continue without database logging
    }
}

/**
 * Handle consent submission (POST)
 */
function handleConsentSubmission($db) {
    // Validate request
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        throw new Exception('Invalid request method', 400);
    }

    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg(), 400);
    }

    // Validate required fields
    if (!isset($data['consent']) || !is_array($data['consent'])) {
        throw new Exception('Missing or invalid consent data', 400);
    }

    $consent = $data['consent'];

    // Add timestamp if not present
    if (!isset($consent['timestamp'])) {
        $consent['timestamp'] = time() * 1000; // JavaScript timestamp
    }

    // Add version if not present
    if (!isset($consent['version'])) {
        $consent['version'] = '1.0';
    }

    // Validate categories
    if (!isset($consent['categories']) || !is_array($consent['categories'])) {
        throw new Exception('Invalid categories data', 400);
    }

    // Ensure necessary cookies are always enabled
    $consent['categories']['necessary'] = true;

    // Get user info
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $consent_id = null;

    // Try to save to database if available
    if ($db) {
        try {
            $sql = "INSERT INTO privacy_consents 
                    (session_id, user_id, ip_address, user_agent, consent_data, consent_version) 
                    VALUES (:session_id, :user_id, :ip_address, :user_agent, :consent_data, :version)";

            $params = [
                ':session_id' => $session_id,
                ':user_id' => $user_id,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent,
                ':consent_data' => json_encode($consent),
                ':version' => $consent['version']
            ];

            $stmt = $db->query($sql, $params);
            $consent_id = $db->lastInsertId();

        } catch (Exception $e) {
            error_log("Failed to save consent to database: " . $e->getMessage());
            // Continue without database - cookie will still work
        }
    }

    // Set consent cookie (this always works)
    $cookie_value = json_encode($consent);
    $expires = time() + (365 * 24 * 60 * 60); // 1 year
    
    $cookie_set = setcookie(
        'user_prefs',
        $cookie_value,
        $expires,
        '/',
        $_SERVER['HTTP_HOST'] ?? '',
        isset($_SERVER['HTTPS']),
        true // HttpOnly
    );

    // Clean any output and send response
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent preferences saved successfully',
        'consent_id' => $consent_id,
        'consent' => $consent,
        'timestamp' => time(),
        'debug' => [
            'cookie_set' => $cookie_set,
            'database_saved' => $consent_id !== null,
            'session_id' => $session_id
        ]
    ]);
    exit;
}

/**
 * Handle consent retrieval (GET)
 */
function handleConsentRetrieval($db) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    // Check cookie first
    $consent = null;
    if (isset($_COOKIE['user_prefs'])) {
        $consent = json_decode($_COOKIE['user_prefs'], true);
    }

    // Fallback to database if no cookie and database available
    if (!$consent && $session_id && $db) {
        try {
            $sql = "SELECT consent_data, created_at FROM privacy_consents 
                    WHERE session_id = :session_id";
            $params = [':session_id' => $session_id];

            if ($user_id) {
                $sql .= " OR user_id = :user_id";
                $params[':user_id'] = $user_id;
            }

            $sql .= " ORDER BY created_at DESC LIMIT 1";

            $result = $db->fetch($sql, $params);

            if ($result) {
                $consent = json_decode($result['consent_data'], true);
            }
        } catch (Exception $e) {
            error_log("Failed to retrieve consent from database: " . $e->getMessage());
            // Continue without database data
        }
    }

    // Get available categories
    $categories = [
        'necessary' => [
            'name' => 'Necessary',
            'description' => 'Essential for website functionality and security.',
            'required' => true,
            'cookies' => ['PHPSESSID', 'csrf_token', 'user_prefs']
        ],
        'functional' => [
            'name' => 'Functional',
            'description' => 'Enhance your experience and remember your preferences.',
            'required' => false,
            'cookies' => ['theme_preference', 'language_preference', 'nav_state']
        ],
        'analytics' => [
            'name' => 'Analytics',
            'description' => 'Help us understand how visitors interact with our website.',
            'required' => false,
            'cookies' => ['_ga', '_gid', '_gat', 'page_views', 'user_analytics']
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Used to deliver personalized content and track marketing effectiveness.',
            'required' => false,
            'cookies' => ['_fbp', '_fbc', 'marketing_id', 'ad_preferences']
        ]
    ];

    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'consent' => $consent,
        'categories' => $categories,
        'requires_consent' => $consent === null,
        'session_id' => $session_id,
        'timestamp' => time()
    ]);
    exit;
}

/**
 * Handle consent withdrawal (DELETE)
 */
function handleConsentWithdrawal($db) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    // Remove cookie
    $cookie_removed = setcookie('user_prefs', '', time() - 3600, '/', $_SERVER['HTTP_HOST'] ?? '');

    $affected_rows = 0;

    // Update database record to mark as withdrawn if database available
    if ($db) {
        try {
            $sql = "UPDATE privacy_consents 
                    SET consent_data = JSON_SET(consent_data, '$.withdrawn', true),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE session_id = :session_id";
            
            $params = [':session_id' => $session_id];
            
            if ($user_id) {
                $sql .= " OR user_id = :user_id";
                $params[':user_id'] = $user_id;
            }

            $stmt = $db->query($sql, $params);
            $affected_rows = $stmt->rowCount();

        } catch (Exception $e) {
            error_log("Failed to update consent withdrawal: " . $e->getMessage());
            // Continue - cookie removal is still successful
        }
    }

    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent withdrawn successfully',
        'affected_rows' => $affected_rows,
        'timestamp' => time(),
        'debug' => [
            'cookie_removed' => $cookie_removed,
            'database_updated' => $affected_rows > 0
        ]
    ]);
    exit;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle multiple IPs (take the first one)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>