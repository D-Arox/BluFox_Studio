<?php
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
}

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Environment file not found at: $path");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, '"\''); 
            
            if (!empty($name)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

$env_path = __DIR__ . '/../../.env';
if (!file_exists($env_path)) {
    $env_path = __DIR__ . '/../../.env';
}

try {
    loadEnv($env_path);
} catch (Exception $e) {
    if ($_ENV['DEBUG_MODE'] ?? true) {
        error_log("Environment Error: " . $e->getMessage());
    }
}

define('DB_CONNECTION', $_ENV['DB_CONNECTION'] ?? 'mysql');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

define('ROBLOX_CLIENT_ID', $_ENV['ROBLOX_CLIENT_ID'] ?? '');
define('ROBLOX_CLIENT_SECRET', $_ENV['ROBLOX_CLIENT_SECRET'] ?? '');
define('ROBLOX_REDIRECT_URI', $_ENV['ROBLOX_REDIRECT_URI'] ?? '');

define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? bin2hex(random_bytes(32)));

define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://blufox-studio.com');
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'BluFox Studio');
define('CONTACT_EMAIL', $_ENV['CONTACT_EMAIL'] ?? 'support@blufox-studio.com');

define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('ERROR_REPORTING', filter_var($_ENV['ERROR_REPORTING'] ?? false, FILTER_VALIDATE_BOOLEAN));

if (ERROR_REPORTING) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

date_default_timezone_set('Europe/Berlin');

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['rate_limit'])) {
    $_SESSION['rate_limit'] = [
        'requests' => 0,
        'reset_time' => time() + 3600
    ];
}

function check_rate_limit($limit = 100) {
    if (time() > $_SESSION['rate_limit']['reset_time']) {
        $_SESSION['rate_limit'] = [
            'requests' => 0,
            'reset_time' => time() + 3600
        ];
    }
    
    $_SESSION['rate_limit']['requests']++;
    
    if ($_SESSION['rate_limit']['requests'] > $limit) {
        http_response_code(429);
        die('Too Many Requests');
    }
}

function escape_html($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function escape_url($string) {
    return urlencode($string ?? '');
}

function sanitize_string($string) {
    return filter_var(trim($string), FILTER_SANITIZE_STRING);
}

function generate_csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url, $status_code = 302) {
    if (!headers_sent()) {
        header("Location: $url", true, $status_code);
    }
    exit;
}

function get_base_url() {
    return SITE_URL;
}

function asset_url($path) {
    return get_base_url() . '/assets/' . ltrim($path, '/');
}

function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function is_ajax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function log_security_event($event, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => get_client_ip(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'details' => $details
    ];
    
    error_log("SECURITY: " . json_encode($log_entry));
}

function generate_nonce() {
    return bin2hex(random_bytes(16));
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function format_bytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function init_database() {
    require_once __DIR__ . '/database.php';
    return Database::getInstance();
}

if (basename($_SERVER['PHP_SELF']) !== 'config.php') {
    require_once __DIR__ . '/auth.php';
}

function global_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error_msg = "Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
    
    if (DEBUG_MODE) {
        error_log($error_msg);
    }
    
    if ($errno === E_ERROR || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        log_security_event('critical_error', ['message' => $errstr, 'file' => $errfile, 'line' => $errline]);
    }
    
    return true;
}

set_error_handler('global_error_handler');

function global_exception_handler($exception) {
    $error_msg = "Uncaught exception: " . $exception->getMessage() . 
                 " in " . $exception->getFile() . 
                 " on line " . $exception->getLine();
    
    error_log($error_msg);
    log_security_event('uncaught_exception', ['message' => $exception->getMessage()]);
    
    if (!DEBUG_MODE) {
        if (!headers_sent()) {
            http_response_code(500);
            include __DIR__ . '/../errors/500.php';
        }
    }
}

set_exception_handler('global_exception_handler');
?>