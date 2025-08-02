<?php
/**
 * Simple test to debug the auth endpoint
 * Place this in your root directory and access it to test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Auth Endpoint Debug</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .good{color:green;} .bad{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";

// Test 1: Direct API call
echo "<h2>📡 Direct API Test</h2>";

$testUrl = 'https://blufox-studio.com/api/v1/auth/roblox/url'; // Replace with your actual domain
echo "<strong>Testing URL:</strong> $testUrl<br><br>";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "<div class='good'>✅ HTTP Code: $httpCode</div>";

if ($error) {
    echo "<div class='bad'>❌ cURL Error: $error</div>";
} else {
    echo "<div class='good'>✅ No cURL errors</div>";
}

// Separate headers and body
$headerSize = $info['header_size'];
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "<h3>📋 Response Headers:</h3>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h3>📄 Response Body:</h3>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Test if it's valid JSON
$json = json_decode($body, true);
if ($json !== null) {
    echo "<div class='good'>✅ Valid JSON response</div>";
    echo "<h3>🔍 Parsed JSON:</h3>";
    echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
    
    // Check for expected structure
    if (isset($json['success'])) {
        echo "<div class='good'>✅ Has 'success' field: " . ($json['success'] ? 'true' : 'false') . "</div>";
    } else {
        echo "<div class='bad'>❌ Missing 'success' field</div>";
    }
    
    if (isset($json['data'])) {
        echo "<div class='good'>✅ Has 'data' field</div>";
    } else {
        echo "<div class='bad'>❌ Missing 'data' field</div>";
    }
    
    if (isset($json['message'])) {
        echo "<div class='good'>✅ Message: " . htmlspecialchars($json['message']) . "</div>";
    }
    
} else {
    echo "<div class='bad'>❌ Invalid JSON response</div>";
    echo "<strong>JSON Error:</strong> " . json_last_error_msg() . "<br>";
}

// Test 2: Test the API info endpoint
echo "<hr><h2>📊 API Info Endpoint Test</h2>";

$infoUrl = str_replace('/auth/roblox/url', '/info', $testUrl);
echo "<strong>Testing URL:</strong> $infoUrl<br><br>";

$ch2 = curl_init();
curl_setopt_array($ch2, [
    CURLOPT_URL => $infoUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$infoResponse = curl_exec($ch2);
$infoHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$infoError = curl_error($ch2);
curl_close($ch2);

echo "<div class='good'>✅ HTTP Code: $infoHttpCode</div>";

if ($infoError) {
    echo "<div class='bad'>❌ cURL Error: $infoError</div>";
} else {
    echo "<div class='good'>✅ No cURL errors</div>";
    echo "<h3>📄 API Info Response:</h3>";
    echo "<pre>" . htmlspecialchars($infoResponse) . "</pre>";
}

// Test 3: Check what your login.php is actually receiving
echo "<hr><h2>🔍 Login.php Response Analysis</h2>";
echo "<p>This is what your login.php is likely seeing...</p>";

if ($json !== null) {
    // Simulate what login.php checks
    if (isset($json['success']) && $json['success'] && isset($json['data']['authorization_url'])) {
        echo "<div class='good'>✅ Response has correct structure for login.php</div>";
        echo "<div class='good'>✅ Authorization URL found: " . htmlspecialchars($json['data']['authorization_url']) . "</div>";
    } else {
        echo "<div class='bad'>❌ Response structure doesn't match what login.php expects</div>";
        echo "<strong>Expected:</strong> {'success': true, 'data': {'authorization_url': 'https://...'}}<br>";
        echo "<strong>Actual structure:</strong><br>";
        if (isset($json['success'])) {
            echo "- success: " . ($json['success'] ? 'true' : 'false') . "<br>";
        }
        if (isset($json['data'])) {
            echo "- data keys: " . implode(', ', array_keys($json['data'])) . "<br>";
        }
    }
}

echo "<hr>";
echo "<p><strong>🚨 IMPORTANT:</strong> Delete this file after debugging!</p>";
?>