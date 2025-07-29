<?php
// BluFox Studio - Quick Client Secret Fix
// Add this to the TOP of your auth/process-callback.php (or any OAuth file)

// Force reload environment variables for this specific request
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    if ($env && isset($env['ROBLOX_CLIENT_SECRET'])) {
        // Force define the constant with the .env value
        if (!defined('ROBLOX_CLIENT_SECRET') || !ROBLOX_CLIENT_SECRET) {
            if (defined('ROBLOX_CLIENT_SECRET')) {
                // Can't redefine, so we'll use a variable instead
                $ROBLOX_CLIENT_SECRET_OVERRIDE = $env['ROBLOX_CLIENT_SECRET'];
            } else {
                define('ROBLOX_CLIENT_SECRET', $env['ROBLOX_CLIENT_SECRET']);
            }
        }
    }
}

// Debug: Show what we actually have
echo "<h2>🔍 Client Secret Debug:</h2>";
echo "<ul>";
echo "<li>From .env file directly: " . ($env['ROBLOX_CLIENT_SECRET'] ?? 'NOT FOUND') . " (" . strlen($env['ROBLOX_CLIENT_SECRET'] ?? '') . " chars)</li>";
echo "<li>ROBLOX_CLIENT_SECRET constant: " . (defined('ROBLOX_CLIENT_SECRET') ? ROBLOX_CLIENT_SECRET : 'NOT DEFINED') . " (" . strlen(defined('ROBLOX_CLIENT_SECRET') ? ROBLOX_CLIENT_SECRET : '') . " chars)</li>";
echo "<li>Override variable: " . (isset($ROBLOX_CLIENT_SECRET_OVERRIDE) ? 'SET (' . strlen($ROBLOX_CLIENT_SECRET_OVERRIDE) . ' chars)' : 'NOT SET') . "</li>";
echo "</ul>";

// Continue with your existing process-callback.php code...
require_once '../includes/config.php';

// Rest of your existing code, but modify the exchangeCodeForToken function:

function debugExchangeCodeForToken($code) {
    global $ROBLOX_CLIENT_SECRET_OVERRIDE;
    
    echo "<strong>Preparing token exchange request...</strong><br>";
    
    // Use override if available, otherwise use constant
    $clientSecret = $ROBLOX_CLIENT_SECRET_OVERRIDE ?? ROBLOX_CLIENT_SECRET;
    
    echo "<strong>Client Secret Debug:</strong><br>";
    echo "<ul>";
    echo "<li>Using override: " . (isset($ROBLOX_CLIENT_SECRET_OVERRIDE) ? 'YES' : 'NO') . "</li>";
    echo "<li>Final secret length: " . strlen($clientSecret) . " characters</li>";
    echo "<li>Secret starts with: " . substr($clientSecret, 0, 10) . "...</li>";
    echo "</ul>";
    
    $postData = [
        'client_id' => ROBLOX_CLIENT_ID,
        'client_secret' => $clientSecret, // Use the working secret
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => ROBLOX_REDIRECT_URI
    ];
    
    echo "POST data prepared:<br>";
    echo "<ul>";
    foreach ($postData as $key => $value) {
        if ($key === 'client_secret') {
            echo "<li>{$key}: " . ($value ? 'SET (' . strlen($value) . ' chars)' : 'EMPTY') . "</li>";
        } elseif ($key === 'code') {
            echo "<li>{$key}: " . htmlspecialchars(substr($value, 0, 30)) . "...</li>";
        } else {
            echo "<li>{$key}: " . htmlspecialchars($value) . "</li>";
        }
    }
    echo "</ul>";
    
    $url = 'https://apis.roblox.com/oauth/v1/token';
    echo "Making request to: {$url}<br>";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<strong>Response received:</strong><br>";
    echo "<ul>";
    echo "<li>HTTP Code: {$httpCode}</li>";
    echo "<li>cURL Error: " . ($error ?: 'None') . "</li>";
    echo "<li>Response Length: " . strlen($response) . " characters</li>";
    echo "</ul>";
    
    echo "<strong>Raw Response:</strong><br>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
    echo htmlspecialchars($response);
    echo "</pre>";
    
    if ($error) {
        echo "<span style='color: red;'>❌ cURL error: {$error}</span><br>";
        return false;
    }
    
    if ($httpCode !== 200) {
        echo "<span style='color: red;'>❌ HTTP error code: {$httpCode}</span><br>";
        
        // Try to decode error response
        $errorData = json_decode($response, true);
        if ($errorData) {
            echo "<strong>Error details:</strong><br>";
            echo "<pre>" . json_encode($errorData, JSON_PRETTY_PRINT) . "</pre>";
        }
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "<span style='color: red;'>❌ Failed to decode JSON response</span><br>";
        echo "JSON Error: " . json_last_error_msg() . "<br>";
        return false;
    }
    
    if (!isset($data['access_token'])) {
        echo "<span style='color: red;'>❌ No access token in response</span><br>";
        echo "<strong>Decoded response:</strong><br>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        return false;
    }
    
    echo "<span style='color: green;'>✅ Token exchange successful!</span><br>";
    return $data;
}

// Also create a simple test function
function testClientSecretLoading() {
    echo "<h2>🧪 Client Secret Loading Test</h2>";
    
    // Method 1: Direct from .env
    $envFile = __DIR__ . '/../.env';
    $env = parse_ini_file($envFile);
    $fromEnv = $env['ROBLOX_CLIENT_SECRET'] ?? 'NOT FOUND';
    
    // Method 2: From constant
    $fromConstant = defined('ROBLOX_CLIENT_SECRET') ? ROBLOX_CLIENT_SECRET : 'NOT DEFINED';
    
    // Method 3: From $_ENV
    $fromEnvVar = $_ENV['ROBLOX_CLIENT_SECRET'] ?? 'NOT SET';
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Source</th><th>Value</th><th>Length</th></tr>";
    echo "<tr><td>Direct from .env file</td><td>" . ($fromEnv ? 'SET' : 'EMPTY') . "</td><td>" . strlen($fromEnv) . "</td></tr>";
    echo "<tr><td>ROBLOX_CLIENT_SECRET constant</td><td>" . ($fromConstant ? 'SET' : 'EMPTY') . "</td><td>" . strlen($fromConstant) . "</td></tr>";
    echo "<tr><td>\$_ENV variable</td><td>" . ($fromEnvVar ? 'SET' : 'EMPTY') . "</td><td>" . strlen($fromEnvVar) . "</td></tr>";
    echo "</table>";
    
    if ($fromEnv && !$fromConstant) {
        echo "<p style='color: red;'>❌ The secret exists in .env but not in the constant!</p>";
        echo "<p>💡 The config.php file isn't loading it properly.</p>";
    } elseif ($fromEnv && $fromConstant) {
        echo "<p style='color: green;'>✅ Secret is loaded correctly!</p>";
    } else {
        echo "<p style='color: red;'>❌ Secret not found in .env file!</p>";
    }
}

// Call the test function
testClientSecretLoading();
?>