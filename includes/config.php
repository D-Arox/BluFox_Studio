<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception("Environment file not found at: $path");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; 
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, '"\''); 
        
        if (!empty($name)) {
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

$env_path = __DIR__ . '/../../../.env';
try {
    loadEnv($env_path);
} catch (Exception $e) {
    if ($_ENV['DEBUG_MODE'] ?? true) {
        error_log("Environment Error: " . $e->getMessage());
    }
}

// Database Configuration
define('DB_CONNECTION', $_ENV['DB_CONNECTION'] ?? 'mysql');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// Roblox OAuth Configuration
define('ROBLOX_CLIENT_ID', $_ENV['ROBLOX_CLIENT_ID'] ?? '');
define('ROBLOX_CLIENT_SECRET', $_ENV['ROBLOX_CLIENT_SECRET'] ?? '');
define('ROBLOX_REDIRECT_URI', $_ENV['ROBLOX_REDIRECT_URI'] ?? '');

// JWT Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '');

// Site Configuration
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://blufox-studio.com');
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'BluFox Studio');
define('CONTACT_EMAIL', $_ENV['CONTACT_EMAIL'] ?? 'support@blufox-studio.com');

// Debug Configuration
define('DEBUG_MODE', filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('ERROR_REPORTING', filter_var($_ENV['ERROR_REPORTING'] ?? false, FILTER_VALIDATE_BOOLEAN));

if (ERROR_REPORTING) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Europe/Berlin');

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Global functions
function escape_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url, $status_code = 302) {
    header("Location: $url", true, $status_code);
    exit;
}

function get_base_url() {
    return SITE_URL;
}

function asset_url($path) {
    return get_base_url() . '/assets/' . ltrim($path, '/');
}

// Include database connection
require_once __DIR__ . '/database.php';

?>