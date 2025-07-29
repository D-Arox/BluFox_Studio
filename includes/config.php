<?php
// BluFox Studio - Fixed includes/config.php
// Replace your config.php with this version that handles special characters properly

if (!defined('BLUFOX_LOADED')) {
    define('BLUFOX_LOADED', true);
}

// Enhanced .env file parsing that handles special characters
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $variables = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Find the first = sign
        $equalPos = strpos($line, '=');
        if ($equalPos === false) {
            continue;
        }
        
        $key = trim(substr($line, 0, $equalPos));
        $value = trim(substr($line, $equalPos + 1));
        
        // Remove surrounding quotes if present
        if (strlen($value) >= 2) {
            if (($value[0] === '"' && $value[-1] === '"') || 
                ($value[0] === "'" && $value[-1] === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        
        $variables[$key] = $value;
    }
    
    return $variables;
}

// Load environment variables with the enhanced parser
$envFile = __DIR__ . '/../../.env';
$env = loadEnvFile($envFile);

// Set environment variables
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Debug: Log what we loaded
if (isset($env['ROBLOX_CLIENT_SECRET'])) {
    error_log("ROBLOX_CLIENT_SECRET loaded from .env: " . strlen($env['ROBLOX_CLIENT_SECRET']) . " characters, starts with: " . substr($env['ROBLOX_CLIENT_SECRET'], 0, 10));
}

// Site configuration
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'BluFox Studio');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'https://blufox-studio.com');
define('SITE_DESCRIPTION', 'Professional Roblox Development Studio - Games, Frameworks, and Custom Solutions');
define('NO_EMAIL', 'no-reply@blufox-studio.com');
define('CONTACT_EMAIL', $_ENV['CONTACT_EMAIL'] ?? 'support@blufox-studio.com');

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASSWORD'] ?? ''); // Note: DB_PASSWORD in .env, DB_PASS in PHP
define('DB_NAME', $_ENV['DB_NAME'] ?? 'blufox_studio');

// Roblox OAuth configuration with enhanced loading
define('ROBLOX_CLIENT_ID', $_ENV['ROBLOX_CLIENT_ID'] ?? '6692844983306448575');
define('ROBLOX_CLIENT_SECRET', $_ENV['ROBLOX_CLIENT_SECRET'] ?? '');
define('ROBLOX_REDIRECT_URI', (isset($_ENV['ROBLOX_REDIRECT_URI']) ? $_ENV['ROBLOX_REDIRECT_URI'] : SITE_URL . '/auth/callback'));

// Debug the loaded secret
error_log("After constants - ROBLOX_CLIENT_SECRET: " . (ROBLOX_CLIENT_SECRET ? 'SET (' . strlen(ROBLOX_CLIENT_SECRET) . ' chars)' : 'EMPTY'));

// Security configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'blufox-change-this-secret-key-in-production');
define('JWT_EXPIRE', 86400); // 24 hours

// Upload configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Security settings
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('LOGIN_ATTEMPTS_MAX', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Debug function to check configuration
function debugConfig() {
    if (isset($_GET['debug_config']) && $_GET['debug_config'] === 'blufox') {
        global $env;
        
        echo "<h2>Enhanced Configuration Debug</h2>";
        
        echo "<h3>Raw .env File Content:</h3>";
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px;'>";
            echo htmlspecialchars($content);
            echo "</pre>";
        }
        
        echo "<h3>Parsed Environment Variables:</h3>";
        echo "<ul>";
        foreach ($env as $key => $value) {
            if (strpos($key, 'SECRET') !== false || strpos($key, 'PASSWORD') !== false) {
                echo "<li>{$key}: " . ($value ? 'SET (' . strlen($value) . ' chars) - starts with: ' . substr($value, 0, 10) . '...' : 'EMPTY') . "</li>";
            } else {
                echo "<li>{$key}: " . htmlspecialchars($value) . "</li>";
            }
        }
        echo "</ul>";
        

        echo "<h3>Defined Constants:</h3>";
        echo "<ul>";
        echo "<li>ROBLOX_CLIENT_ID: " . ROBLOX_CLIENT_ID . "</li>";
        echo "<li>ROBLOX_CLIENT_SECRET: " . (ROBLOX_CLIENT_SECRET ? 'SET (' . strlen(ROBLOX_CLIENT_SECRET) . ' chars) - starts with: ' . substr(ROBLOX_CLIENT_SECRET, 0, 10) . '...' : 'EMPTY') . "</li>";
        echo "<li>ROBLOX_REDIRECT_URI: " . ROBLOX_REDIRECT_URI . "</li>";
        echo "</ul>";
        
        echo "<h3>Manual Secret Test:</h3>";
        $envFile = __DIR__ . '/../.env';
        $manualEnv = loadEnvFile($envFile);
        echo "<p>Manual parsing result for ROBLOX_CLIENT_SECRET: " . (isset($manualEnv['ROBLOX_CLIENT_SECRET']) ? 'FOUND (' . strlen($manualEnv['ROBLOX_CLIENT_SECRET']) . ' chars)' : 'NOT FOUND') . "</p>";
        
        if (isset($manualEnv['ROBLOX_CLIENT_SECRET'])) {
            echo "<p>Manual secret starts with: " . substr($manualEnv['ROBLOX_CLIENT_SECRET'], 0, 15) . "...</p>";
            echo "<p>Manual secret ends with: ..." . substr($manualEnv['ROBLOX_CLIENT_SECRET'], -10) . "</p>";
        }
        
        // Test database connection
        echo "<h3>Database Connection Test:</h3>";
        try {
            $db = db();
            $result = $db->fetch("SELECT 1 as test");
            if ($result && $result['test'] == 1) {
                echo "<p style='color: green;'>✅ Database connection successful!</p>";
            } else {
                echo "<p style='color: red;'>❌ Database query failed</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        exit;
    }
}

// Call debug function
debugConfig();
?>