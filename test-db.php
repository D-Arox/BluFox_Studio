<?php
// BluFox Studio - Database Credentials Test
// Create this as test-db.php in your root directory

require_once 'includes/config.php';

echo "<h1>Database Credentials Test</h1>";

echo "<h2>Constants Defined:</h2>";
echo "<ul>";
echo "<li>DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "</li>";
echo "<li>DB_USER: " . (defined('DB_USER') ? (DB_USER ?: 'EMPTY') : 'NOT DEFINED') . "</li>";
echo "<li>DB_PASS: " . (defined('DB_PASS') ? (DB_PASS ? 'SET' : 'EMPTY') : 'NOT DEFINED') . "</li>";
echo "<li>DB_NAME: " . (defined('DB_NAME') ? (DB_NAME ?: 'EMPTY') : 'NOT DEFINED') . "</li>";
echo "</ul>";

echo "<h2>Environment Variables:</h2>";
echo "<ul>";
echo "<li>\$_ENV['DB_HOST']: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "</li>";
echo "<li>\$_ENV['DB_USER']: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "</li>";
echo "<li>\$_ENV['DB_PASSWORD']: " . (isset($_ENV['DB_PASSWORD']) ? ($_ENV['DB_PASSWORD'] ? 'SET' : 'EMPTY') : 'NOT SET') . "</li>";
echo "<li>\$_ENV['DB_NAME']: " . ($_ENV['DB_NAME'] ?? 'NOT SET') . "</li>";
echo "</ul>";

echo "<h2>Server Variables:</h2>";
echo "<ul>";
echo "<li>\$_SERVER['DB_HOST']: " . ($_SERVER['DB_HOST'] ?? 'NOT SET') . "</li>";
echo "<li>\$_SERVER['DB_USER']: " . ($_SERVER['DB_USER'] ?? 'NOT SET') . "</li>";
echo "<li>\$_SERVER['DB_PASSWORD']: " . (isset($_SERVER['DB_PASSWORD']) ? ($_SERVER['DB_PASSWORD'] ? 'SET' : 'EMPTY') : 'NOT SET') . "</li>";
echo "<li>\$_SERVER['DB_NAME']: " . ($_SERVER['DB_NAME'] ?? 'NOT SET') . "</li>";
echo "</ul>";

echo "<h2>.env File Check:</h2>";
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    echo "<p>✅ .env file exists</p>";
    
    $env = parse_ini_file($envPath);
    if ($env) {
        echo "<p>✅ .env file parsed successfully</p>";
        echo "<ul>";
        foreach (['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'] as $key) {
            if (isset($env[$key])) {
                if ($key === 'DB_PASSWORD') {
                    echo "<li>{$key}: " . ($env[$key] ? 'SET' : 'EMPTY') . "</li>";
                } else {
                    echo "<li>{$key}: " . ($env[$key] ?: 'EMPTY') . "</li>";
                }
            } else {
                echo "<li>{$key}: NOT FOUND</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Failed to parse .env file</p>";
    }
} else {
    echo "<p>❌ .env file not found at: {$envPath}</p>";
}

echo "<h2>Manual Connection Test:</h2>";
try {
    // Try to connect manually with the constants
    $host = defined('DB_HOST') ? DB_HOST : '';
    $dbname = defined('DB_NAME') ? DB_NAME : '';
    $username = defined('DB_USER') ? DB_USER : '';
    $password = defined('DB_PASS') ? DB_PASS : '';
    
    echo "<p>Attempting connection with:</p>";
    echo "<ul>";
    echo "<li>Host: " . ($host ?: 'EMPTY') . "</li>";
    echo "<li>Database: " . ($dbname ?: 'EMPTY') . "</li>";
    echo "<li>Username: " . ($username ?: 'EMPTY') . "</li>";
    echo "<li>Password: " . ($password ? 'SET' : 'EMPTY') . "</li>";
    echo "</ul>";
    
    if (!$host || !$dbname || !$username || !$password) {
        echo "<p class='error'>❌ Missing required database credentials!</p>";
    } else {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<p class='success'>✅ Database connection successful!</p>";
        
        // Test if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Users table exists!</p>";
        } else {
            echo "<p class='warning'>⚠️ Users table not found - you need to run the database migration!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<style>
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    body { font-family: Arial, sans-serif; }
    ul { background: #f5f5f5; padding: 10px; }
</style>";
?>