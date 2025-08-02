<?php
/**
 * API Debug Script
 * Place this in your root directory and access via yoursite.com/debug-api.php
 * This will help us find the exact issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 BluFox Studio API Debug</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .good{color:green;} .bad{color:red;} .warn{color:orange;} pre{background:#f5f5f5;padding:10px;}</style>";

// Test 1: File Structure
echo "<h2>📁 File Structure Check</h2>";
$requiredFiles = [
    'config/config.php',
    'api/v1/index.php',
    'api/v1/auth.php',
    'api/v1/roblox.php',
    'includes/classes/ApiResponse.php',
    'includes/classes/RobloxOAuth.php',
    '.htaccess'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    $class = $exists ? 'good' : 'bad';
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    echo "<div class='$class'>$file: $status</div>";
}

// Test 2: API Direct Access
echo "<h2>🌐 Direct API Access Test</h2>";
echo "<p>Testing if API v1 index.php loads directly...</p>";

$apiIndexPath = __DIR__ . '/api/v1/index.php';
if (file_exists($apiIndexPath)) {
    echo "<div class='good'>✅ api/v1/index.php exists</div>";
    
    // Try to capture output from the API file
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/v1/info';
    
    try {
        include $apiIndexPath;
        $output = ob_get_contents();
        echo "<div class='good'>✅ API index.php loads without fatal errors</div>";
        echo "<strong>API Output:</strong><pre>" . htmlspecialchars($output) . "</pre>";
    } catch (Exception $e) {
        $output = ob_get_contents();
        echo "<div class='bad'>❌ API index.php has errors: " . $e->getMessage() . "</div>";
        echo "<strong>Error output:</strong><pre>" . htmlspecialchars($output) . "</pre>";
    } finally {
        ob_end_clean();
    }
} else {
    echo "<div class='bad'>❌ api/v1/index.php not found</div>";
}

// Test 3: Include Config
echo "<h2>⚙️ Configuration Test</h2>";
try {
    if (file_exists(__DIR__ . '/config/config.php')) {
        require_once __DIR__ . '/config/config.php';
        echo "<div class='good'>✅ Config loaded successfully</div>";
        
        // Check important constants
        $constants = ['SITE_URL', 'API_RATE_LIMIT', 'DEBUG_MODE'];
        foreach ($constants as $const) {
            if (defined($const)) {
                echo "<div class='good'>✅ $const: " . constant($const) . "</div>";
            } else {
                echo "<div class='bad'>❌ $const: NOT DEFINED</div>";
            }
        }
    } else {
        echo "<div class='bad'>❌ config/config.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='bad'>❌ Config error: " . $e->getMessage() . "</div>";
}

// Test 4: Class Loading
echo "<h2>🏗️ Class Loading Test</h2>";
$classes = ['ApiResponse', 'RobloxOAuth', 'RateLimiter'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<div class='good'>✅ Class $class: LOADED</div>";
    } else {
        echo "<div class='bad'>❌ Class $class: NOT FOUND</div>";
    }
}

// Test 5: cURL Test
echo "<h2>🌍 cURL Test to API</h2>";
if (defined('SITE_URL')) {
    $testUrls = [
        SITE_URL . '/api/v1/info',
        SITE_URL . '/api/v1/auth/roblox/url'
    ];
    
    foreach ($testUrls as $url) {
        echo "<strong>Testing: $url</strong><br>";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "<div class='bad'>❌ cURL Error: $error</div>";
        } else {
            $statusClass = ($httpCode == 200) ? 'good' : (($httpCode == 302) ? 'bad' : 'warn');
            echo "<div class='$statusClass'>📡 HTTP Code: $httpCode</div>";
            
            // Extract just the response body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseBody = substr($response, $headerSize);
            
            echo "<details><summary>Response (click to expand)</summary><pre>" . htmlspecialchars($response) . "</pre></details>";
        }
        echo "<br>";
    }
} else {
    echo "<div class='bad'>❌ SITE_URL not defined, can't test URLs</div>";
}

// Test 6: .htaccess Rules
echo "<h2>📜 .htaccess Analysis</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    $htaccess = file_get_contents(__DIR__ . '/.htaccess');
    echo "<div class='good'>✅ .htaccess exists</div>";
    
    // Look for API rules
    if (strpos($htaccess, 'api/v1') !== false) {
        echo "<div class='good'>✅ Contains API v1 rules</div>";
    } else {
        echo "<div class='bad'>❌ Missing API v1 rules</div>";
    }
    
    // Show API-related rules
    $lines = explode("\n", $htaccess);
    $apiRules = [];
    foreach ($lines as $line) {
        if (stripos($line, 'api') !== false) {
            $apiRules[] = trim($line);
        }
    }
    
    if (!empty($apiRules)) {
        echo "<strong>API-related rules found:</strong><pre>" . htmlspecialchars(implode("\n", $apiRules)) . "</pre>";
    }
} else {
    echo "<div class='bad'>❌ .htaccess not found</div>";
}

// Test 7: Manual URL Parsing
echo "<h2>🔍 URL Parsing Test</h2>";
$testPath = '/api/v1/auth/roblox/url';
echo "<strong>Testing path: $testPath</strong><br>";

$basePath = '/api/v1';
if (strpos($testPath, $basePath) === 0) {
    $remainingPath = substr($testPath, strlen($basePath));
    $segments = array_filter(explode('/', trim($remainingPath, '/')));
    
    echo "<div class='good'>✅ Base path matches</div>";
    echo "<div class='good'>✅ Remaining path: '$remainingPath'</div>";
    echo "<div class='good'>✅ Segments: " . json_encode($segments) . "</div>";
    
    // Test routing logic
    $endpoint = $segments[0] ?? '';
    echo "<div class='good'>✅ Endpoint: '$endpoint'</div>";
    
    if ($endpoint === 'auth') {
        echo "<div class='good'>✅ Would route to auth handler</div>";
        $action = $segments[1] ?? '';
        echo "<div class='good'>✅ Auth action: '$action'</div>";
    }
} else {
    echo "<div class='bad'>❌ Base path doesn't match</div>";
}

echo "<hr>";
echo "<p><strong>🚨 IMPORTANT:</strong> Delete this file after debugging!</p>";
echo "<p>📧 If you need help, share the output above.</p>";
?>