<?php
if (!defined('BLUFOX_LOADED')) {
    define('BLUFOX_LOADED', true);
}

define('SITE_NAME', 'BluFox Studio');
define('SITE_URL', 'https://blufox-studio.com');
define('SITE_DESCRIPTION', 'Professional Roblox Development Studio - Games, Frameworks, and Custom Solutions');
define('NO_EMAIL', 'no-reply@blufox-studio.com');
define('CONTACT_EMAIL', 'support@blufox-studio.com');

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'blufox_studio');

define('ROBLOX_CLIENT_ID', $_ENV['ROBLOX_CLIENT_ID'] ?? '');
define('ROBLOX_CLIENT_SECRET', $_ENV['ROBLOX_CLIENT_SECRET'] ?? '');
define('ROBLOX_REDIRECT_URI', SITE_URL . '/auth/callback');

define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this');
define('JWT_EXPIRE', 86400); // 24 hours

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';
?>