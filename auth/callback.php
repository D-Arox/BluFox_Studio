<?php
require_once '../includes/config.php';

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    $errorDescription = $_GET['error_description'] ?? $error;
    redirect('/auth/login', 'Authentication failed: ' . $errorDescription, 'error');
}

if (!$code || !$state) {
    redirect('/auth/login', 'Invalid authentication response', 'error');
}

try {
    $codeVerifier = $_SESSION['oauth_code_verifier'] ?? null;
    $isPKCE = !empty($codeVerifier);
    
    if ($isPKCE) {
        echo "<!-- PKCE Flow Detected -->\n";
        logActivity('oauth_callback_pkce', [
            'code_length' => strlen($code),
            'has_verifier' => true
        ]);
        
        $tokenData = exchangeCodeForTokenPKCE($code, $codeVerifier);
        
        unset($_SESSION['oauth_code_verifier']);
    } else {
        echo "<!-- Legacy Flow -->\n";
        logActivity('oauth_callback_legacy', [
            'code_length' => strlen($code),
            'has_verifier' => false
        ]);
        
        $tokenData = exchangeCodeForTokenLegacy($code);
    }
    
    if (!$tokenData) {
        throw new Exception('Failed to exchange code for token');
    }
    
    $userInfo = getRobloxUserInfo($tokenData['access_token']);
    
    if (!$userInfo) {
        throw new Exception('Failed to get user information');
    }
    
    $user = Auth::handleRobloxUser($userInfo);
    
    if (!$user) {
        throw new Exception('Failed to create or update user');
    }
    
    Auth::login($user);
    
    logActivity('roblox_login_success', [
        'user_id' => $user['id'],
        'roblox_id' => $user['roblox_id'],
        'flow_type' => $isPKCE ? 'pkce' : 'legacy',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    $redirectTo = $_SESSION['login_redirect'] ?? '/';
    unset($_SESSION['login_redirect']);
    
    redirect($redirectTo, 'Welcome back, ' . $user['display_name'] . '!', 'success');
    
} catch (Exception $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    logActivity('roblox_login_error', [
        'error' => $e->getMessage(),
        'flow_type' => $isPKCE ? 'pkce' : 'legacy',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    redirect('/auth/login', 'Authentication failed. Please try again.', 'error');
}

function exchangeCodeForTokenPKCE($code, $codeVerifier) {
    $postData = [
        'client_id' => ROBLOX_CLIENT_ID,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => ROBLOX_REDIRECT_URI,
        'code_verifier' => $codeVerifier
    ];
    
    return makeTokenRequest($postData, 'PKCE');
}

function exchangeCodeForTokenLegacy($code) {
    $postData = [
        'client_id' => ROBLOX_CLIENT_ID,
        'client_secret' => ROBLOX_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => ROBLOX_REDIRECT_URI
    ];
    
    return makeTokenRequest($postData, 'Legacy');
}

function makeTokenRequest($postData, $flowType) {
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
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    logActivity('token_exchange_attempt', [
        'flow_type' => $flowType,
        'http_code' => $httpCode,
        'has_error' => !empty($error),
        'response_length' => strlen($response),
        'client_id' => ROBLOX_CLIENT_ID
    ]);
    
    if ($error) {
        error_log("cURL error in {$flowType} token exchange: " . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("HTTP error in {$flowType} token exchange: " . $httpCode . ' - ' . $response);
        
        $errorData = json_decode($response, true);
        logActivity('token_exchange_error', [
            'flow_type' => $flowType,
            'http_code' => $httpCode,
            'error_response' => $errorData,
            'raw_response' => substr($response, 0, 500)
        ]);
        
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['access_token'])) {
        error_log("Invalid {$flowType} token response: " . $response);
        return false;
    }
    
    logActivity('token_exchange_success', [
        'flow_type' => $flowType,
        'has_access_token' => isset($data['access_token']),
        'has_refresh_token' => isset($data['refresh_token']),
        'expires_in' => $data['expires_in'] ?? null
    ]);
    
    return $data;
}

function getRobloxUserInfo($accessToken) {
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
    
    if ($error) {
        error_log('cURL error in user info: ' . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log('HTTP error in user info: ' . $httpCode . ' - ' . $response);
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['sub'])) {
        error_log('Invalid user info response: ' . $response);
        return false;
    }
    
    return $data;
}
?>