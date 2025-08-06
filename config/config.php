<?php
if (file_exists(__DIR__ . '/.env')) {
    $envFile = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envFile);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Site Configuration
define('SITE_URL', env('SITE_URL', 'https://blufox-studio.com'));
define('SITE_NAME', env('SITE_NAME', 'BluFox Studio'));
define('ENVIRONMENT', env('ENVIRONMENT', 'production'));
define('DEBUG_MODE', env('DEBUG_MODE', 'false') === 'true');

// Security Configuration
define('JWT_SECRET', env('JWT_SECRET', 'default_jwt_secret_change_this'));
define('API_SECRET_KEY', env('API_SECRET_KEY', 'default_api_secret_change_this'));
define('ENCRYPTION_KEY', env('ENCRYPTION_KEY', 'default_encryption_key_change_this'));
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 7200));

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'blufox_studio'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Roblox OAuth Configuration
define('ROBLOX_CLIENT_ID', env('ROBLOX_CLIENT_ID'));
define('ROBLOX_CLIENT_SECRET', env('ROBLOX_CLIENT_SECRET'));
define('ROBLOX_REDIRECT_URI', env('ROBLOX_REDIRECT_URI', SITE_URL . '/auth/roblox/callback'));
define('ROBLOX_OAUTH_URL', 'https://apis.roblox.com/oauth/v1/authorize');
define('ROBLOX_TOKEN_URL', 'https://apis.roblox.com/oauth/v1/token');
define('ROBLOX_USERINFO_URL', 'https://apis.roblox.com/oauth/v1/userinfo');

// Email Configuration
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) env('MAIL_PORT', 587));
define('MAIL_USERNAME', env('MAIL_USERNAME'));
define('MAIL_PASSWORD', env('MAIL_PASSWORD'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@blufox-studio.com'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'BluFox Studio'));

// API Configuration
define('API_RATE_LIMIT_REQUESTS', (int) env('API_RATE_LIMIT_REQUESTS', 100));
define('API_RATE_LIMIT_WINDOW', (int) env('API_RATE_LIMIT_WINDOW', 3600));

// File Upload Configuration
define('MAX_FILE_SIZE', (int) env('MAX_FILE_SIZE', 10485760)); // 10MB
define('ALLOWED_FILE_TYPES', explode(',', env('ALLOWED_FILE_TYPES', 'rbxm,rbxl,lua,txt,png,jpeg,webp')));

// Social Media Links
define('YOUTUBE_URL', env('YOUTUBE_URL', 'https://youtube.com/@BluFox-Studio'));
define('INSTAGRAM_URL', env('INSTAGRAM_URL', 'https://www.instagram.com/blufox_studio/'));
define('TWITTER_URL', env('TWITTER_URL', 'https://x.com/blufox_studio'));
define('ROBLOX_GROUP_URL', env('ROBLOX_GROUP_URL', 'https://www.roblox.com/communities/16787120/BluFox#!/about'));
define('DISCORD_URL', env('DISCORD_URL', 'https://discord.com/invite/gYSNjEG6g7'));

// GDPR & EU Compliance
define('COOKIE_DOMAIN', env('COOKIE_DOMAIN', '.blufox-studio.com'));
define('GDPR_COMPLIANCE', env('GDPR_COMPLIANCE', 'true') === 'true');

// Timezone
define('APP_TIMEZONE', 'Europe/Berlin');
date_default_timezone_set(APP_TIMEZONE);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_domain', COOKIE_DOMAIN);
ini_set('session.cookie_secure', ENVIRONMENT === 'production');
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (ENVIRONMENT === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function getCSPPolicy() {
    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data: https:",
        "connect-src 'self' https://apis.roblox.com https://www.roblox.com",
        "frame-ancestors 'none'",
        "base-uri 'self'"
    ];
    
    return implode('; ', $csp);
}

function logMessage($level, $message, $context = []) {
    $logFile = __DIR__ . '/../' . env('LOG_FILE', 'logs/app.log');
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = empty($context) ? '' : ' ' . json_encode($context);
    $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}\n";
    
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

spl_autoload_register(function ($className) {
    $paths = [
        __DIR__ . '/../public_html/classes/',
        __DIR__ . '/../public_html/models/',
        __DIR__ . '/../PHPMailer/src/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setSecurityHeaders();

function generateSEOTags($title, $description, $keywords = '', $image = '', $type = 'website') {
    $siteUrl = SITE_URL;
    $siteName = SITE_NAME;
    $currentUrl = $siteUrl . $_SERVER['REQUEST_URI'];
    
    $tags = [
        // Basic Meta Tags
        '<title>' . htmlspecialchars($title) . ' | ' . htmlspecialchars($siteName) . '</title>',
        '<meta name="description" content="' . htmlspecialchars($description) . '">',
        '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">',
        
        // Open Graph
        '<meta property="og:title" content="' . htmlspecialchars($title) . '">',
        '<meta property="og:description" content="' . htmlspecialchars($description) . '">',
        '<meta property="og:type" content="' . $type . '">',
        '<meta property="og:url" content="' . htmlspecialchars($currentUrl) . '">',
        '<meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">',
        
        // Twitter Card
        '<meta name="twitter:card" content="summary_large_image">',
        '<meta name="twitter:site" content="@blufox_studio">',
        '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">',
        '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">',
    ];
    
    if ($image) {
        $tags[] = '<meta property="og:image" content="' . htmlspecialchars($image) . '">';
        $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">';
    }
    
    return implode("\n", $tags);
}

function generateJSONLD($type, $data) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $type
    ];
    
    $schema = array_merge($schema, $data);
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
}

?>