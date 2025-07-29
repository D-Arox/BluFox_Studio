<?php
// BluFox Studio - Debug OAuth Callback Handler
// Replace your auth/callback.php with this debug version temporarily

require_once '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log everything for debugging
$debug_log = [];
$debug_log[] = "=== OAUTH CALLBACK DEBUG ===";
$debug_log[] = "Timestamp: " . date('Y-m-d H:i:s');
$debug_log[] = "Request Method: " . $_SERVER['REQUEST_METHOD'];
$debug_log[] = "Request URI: " . $_SERVER['REQUEST_URI'];

// Get OAuth parameters
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;
$errorDescription = $_GET['error_description'] ?? null;

$debug_log[] = "=== OAUTH PARAMETERS ===";
$debug_log[] = "Code: " . ($code ? substr($code, 0, 20) . '...' : 'NULL');
$debug_log[] = "State: " . ($state ? substr($state, 0, 20) . '...' : 'NULL');
$debug_log[] = "Error: " . ($error ?: 'NULL');
$debug_log[] = "Error Description: " . ($errorDescription ?: 'NULL');

// Check session state
$sessionState = $_SESSION['oauth_state'] ?? null;
$debug_log[] = "Session State: " . ($sessionState ? substr($sessionState, 0, 20) . '...' : 'NULL');

// Check configuration
$debug_log[] = "=== CONFIGURATION ===";
$debug_log[] = "ROBLOX_CLIENT_ID: " . (ROBLOX_CLIENT_ID ?: 'EMPTY');
$debug_log[] = "ROBLOX_CLIENT_SECRET: " . (ROBLOX_CLIENT_SECRET ? 'SET' : 'EMPTY');
$debug_log[] = "ROBLOX_REDIRECT_URI: " . ROBLOX_REDIRECT_URI;

// Handle OAuth error
if ($error) {
    $debug_log[] = "=== OAUTH ERROR DETECTED ===";
    $debug_log[] = "Error: " . $error;
    $debug_log[] = "Description: " . $errorDescription;
    
    // Log the debug info
    error_log("OAuth Error Debug:\n" . implode("\n", $debug_log));
    
    redirect('/auth/login', 'Authentication failed: ' . ($errorDescription ?: $error), 'error');
    exit;
}

// Validate required parameters
if (!$code) {
    $debug_log[] = "=== MISSING CODE PARAMETER ===";
    error_log("Missing Code Debug:\n" . implode("\n", $debug_log));
    redirect('/auth/login', 'Invalid authentication response: Missing authorization code', 'error');
    exit;
}

if (!$state) {
    $debug_log[] = "=== MISSING STATE PARAMETER ===";
    error_log("Missing State Debug:\n" . implode("\n", $debug_log));
    redirect('/auth/login', 'Invalid authentication response: Missing state parameter', 'error');
    exit;
}

// Verify state parameter
if ($state !== $sessionState) {
    $debug_log[] = "=== STATE MISMATCH ===";
    $debug_log[] = "URL State: " . $state;
    $debug_log[] = "Session State: " . $sessionState;
    error_log("State Mismatch Debug:\n" . implode("\n", $debug_log));
    redirect('/auth/login', 'Invalid authentication response: State mismatch (possible CSRF attack)', 'error');
    exit;
}

$debug_log[] = "=== VALIDATION PASSED ===";
$debug_log[] = "Starting token exchange...";

try {
    // Exchange code for token
    $debug_log[] = "=== TOKEN EXCHANGE ===";
    $tokenData = exchangeCodeForToken($code);
    
    if (!$tokenData) {
        throw new Exception('Failed to exchange code for token');
    }
    
    $debug_log[] = "Token exchange successful";
    $debug_log[] = "Access token length: " . strlen($tokenData['access_token'] ?? '');
    
    // Get user info from Roblox
    $debug_log[] = "=== USER INFO FETCH ===";
    $userInfo = getRobloxUserInfo($tokenData['access_token']);
    
    if (!$userInfo) {
        throw new Exception('Failed to get user information');
    }
    
    $debug_log[] = "User info retrieved for: " . ($userInfo['name'] ?? 'Unknown');
    $debug_log[] = "Roblox ID: " . ($userInfo['sub'] ?? 'Unknown');
    
    // Handle user in database
    $debug_log[] = "=== DATABASE OPERATIONS ===";
    $user = Auth::handleRobloxUser($userInfo);
    
    if (!$user) {
        throw new Exception('Failed to create or update user');
    }
    
    $debug_log[] = "User handled in database: ID " . $user['id'];
    
    // Log the user in
    $debug_log[] = "=== LOGIN USER ===";
    Auth::login($user);
    
    $debug_log[] = "User logged in successfully";
    
    // Log successful login
    logActivity('roblox_login_success', [
        'user_id' => $user['id'],
        'roblox_id' => $user['roblox_id'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Redirect to intended destination or home
    $redirectTo = $_SESSION['login_redirect'] ?? '/';
    unset($_SESSION['login_redirect']);
    
    $debug_log[] = "=== SUCCESS ===";
    $debug_log[] = "Redirecting to: " . $redirectTo;
    
    // Log success
    error_log("OAuth Success Debug:\n" . implode("\n", $debug_log));
    
    redirect($redirectTo, 'Welcome back, ' . $user['display_name'] . '!', 'success');
    
} catch (Exception $e) {
    $debug_log[] = "=== EXCEPTION CAUGHT ===";
    $debug_log[] = "Error: " . $e->getMessage();
    $debug_log[] = "File: " . $e->getFile() . ":" . $e->getLine();
    $debug_log[] = "Stack trace: " . $e->getTraceAsString();
    
    error_log("OAuth Exception Debug:\n" . implode("\n", $debug_log));
    
    logActivity('roblox_login_error', [
        'error' => $e->getMessage(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'debug_info' => $debug_log
    ]);
    
    redirect('/auth/login', 'Authentication failed: ' . $e->getMessage(), 'error');
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken($code) {
    global $debug_log;
    
    $postData = [
        'client_id' => ROBLOX_CLIENT_ID,
        'client_secret' => ROBLOX_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => ROBLOX_REDIRECT_URI
    ];
    
    $debug_log[] = "POST data prepared (client_secret hidden)";
    $debug_log[] = "URL: https://apis.roblox.com/oauth/v1/token";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://apis.roblox.com/oauth/v1/token',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_VERBOSE => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $debug_log[] = "cURL response received";
    $debug_log[] = "HTTP Code: " . $httpCode;
    $debug_log[] = "cURL Error: " . ($error ?: 'None');
    $debug_log[] = "Response length: " . strlen($response);
    $debug_log[] = "Response preview: " . substr($response, 0, 200) . '...';
    
    if ($error) {
        $debug_log[] = "cURL error occurred: " . $error;
        return false;
    }
    
    if ($httpCode !== 200) {
        $debug_log[] = "HTTP error: " . $httpCode;
        $debug_log[] = "Full response: " . $response;
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['access_token'])) {
        $debug_log[] = "Invalid token response structure";
        $debug_log[] = "JSON decode error: " . json_last_error_msg();
        return false;
    }
    
    $debug_log[] = "Token exchange successful";
    return $data;
}

/**
 * Get user information from Roblox using access token
 */
function getRobloxUserInfo($accessToken) {
    global $debug_log;
    
    $debug_log[] = "Fetching user info with access token";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://apis.roblox.com/oauth/v1/userinfo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $debug_log[] = "User info response received";
    $debug_log[] = "HTTP Code: " . $httpCode;
    $debug_log[] = "cURL Error: " . ($error ?: 'None');
    $debug_log[] = "Response: " . $response;
    
    if ($error) {
        $debug_log[] = "cURL error in user info: " . $error;
        return false;
    }
    
    if ($httpCode !== 200) {
        $debug_log[] = "HTTP error in user info: " . $httpCode;
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['sub'])) {
        $debug_log[] = "Invalid user info response";
        return false;
    }
    
    return $data;
}

// Test connection at the end
echo "<!-- Debug callback loaded successfully -->";
?>