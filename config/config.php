<?php
if (!defined('BLUFOX_CONFIG')) {
    define('BLUFOX_CONFIG', true);
}

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$name] = $value;
        }
    }
}

define('ENVIRONMENT', $_ENV['ENVIRONMENT'] ?? 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'blufox_studio');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-32-character-encryption-key!!');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('SESSION_EXPIRE', 86400 * 7); // 7 days
define('REMEMBER_ME_EXPIRE', 86400 * 30); // 30 days

// Roblox OAuth Configuration
define('ROBLOX_CLIENT_ID', $_ENV['ROBLOX_CLIENT_ID'] ?? '');
define('ROBLOX_CLIENT_SECRET', $_ENV['ROBLOX_CLIENT_SECRET'] ?? '');
define('ROBLOX_REDIRECT_URI', $_ENV['ROBLOX_REDIRECT_URI'] ?? 'https://blufox-studio.com/auth/callback');

// Site Configuration
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'BluFox Studio');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://blufox-studio.com');
define('SITE_EMAIL', $_ENV['SITE_EMAIL'] ?? 'contact@blufox-studio.com');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@blufox-studio.com');

// File Upload Configuration
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_FILE_TYPES', ['application/pdf', 'text/plain', 'application/zip']);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URLs
define('BASE_URL', rtrim(SITE_URL, '/'));
define('API_URL', BASE_URL . '/api/v1');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// API Configuration
define('API_VERSION', 'v1');
define('API_RATE_LIMIT', 100); // requests per hour per IP
define('API_RATE_LIMIT_WINDOW', 3600); // 1 hour

// Cache Configuration
define('CACHE_ENABLED', $_ENV['CACHE_ENABLED'] ?? 'true');
define('CACHE_DRIVER', $_ENV['CACHE_DRIVER'] ?? 'file'); // file, redis, memcached
define('CACHE_EXPIRE', 3600); // 1 hour default

// Email Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');

// Analytics Configuration
define('GOOGLE_ANALYTICS_ID', $_ENV['GOOGLE_ANALYTICS_ID'] ?? '');
define('ANALYTICS_ENABLED', $_ENV['ANALYTICS_ENABLED'] ?? 'true');

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google-analytics.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.roblox.com;",
]);

// Feature Flags
define('FEATURES', [
    'registration' => $_ENV['FEATURE_REGISTRATION'] ?? 'true',
    'maintenance_mode' => $_ENV['FEATURE_MAINTENANCE'] ?? 'false',
    'analytics' => $_ENV['FEATURE_ANALYTICS'] ?? 'true',
    'contact_form' => $_ENV['FEATURE_CONTACT_FORM'] ?? 'true',
    'project_comments' => $_ENV['FEATURE_PROJECT_COMMENTS'] ?? 'true',
    'user_profiles' => $_ENV['FEATURE_USER_PROFILES'] ?? 'true',
]);

// Timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Europe/Berlin');

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', SESSION_EXPIRE);
    session_start();
}

// Auto-create directories if they don't exist
$directories = [CACHE_PATH, LOGS_PATH, UPLOAD_PATH . '/avatars', UPLOAD_PATH . '/projects', UPLOAD_PATH . '/temp'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Create .htaccess to protect directories
        if (in_array($dir, [CACHE_PATH, LOGS_PATH])) {
            file_put_contents($dir . '/.htaccess', "Order Deny,Allow\nDeny from all");
        }
    }
}

// Autoload classes
spl_autoload_register(function ($class) {
    $directories = [
        ROOT_PATH . '/includes/classes/',
        PUBLIC_PATH . '/includes/classes/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

function feature_enabled($feature) {
    return (FEATURES[$feature] ?? 'false') === 'true';
}

function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function safe_redirect($url, $code = 302) {
    if (filter_var($url, FILTER_VALIDATE_URL) && strpos($url, SITE_URL) === 0) {
        header("Location: $url", true, $code);
        exit;
    } elseif ($url[0] === '/' && strpos($url, '//') !== 0) {
        header("Location: " . SITE_URL . $url, true, $code);
        exit;
    } else {
        header("Location: " . SITE_URL, true, $code);
        exit;
    }
}

foreach (SECURITY_HEADERS as $header => $value) {
    header("$header: $value");
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
?>