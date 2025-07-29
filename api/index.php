<?php

if ($endpoint === 'auth') {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $segments[2] ?? '';
    
    switch ($action) {
        case 'roblox':
            if ($method === 'POST') {
                handleRobloxAuth();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'status':
            if ($method === 'GET') {
                handleAuthStatus();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        case 'logout':
            if ($method === 'POST') {
                handleApiLogout();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Auth endpoint not found']);
    }
    exit;
}

function handleRobloxAuth() {
    try {
        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['code'])) {
            throw new Exception('Missing authorization code');
        }
        
        $code = $input['code'];
        $redirectUri = $input['redirect_uri'] ?? SITE_URL . '/auth/callback';
        
        $tokenData = exchangeRobloxCode($code, $redirectUri);
        
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
        
        $jwt = Auth::generateJWT($user['id']);
        
        logActivity('api_roblox_login_success', [
            'user_id' => $user['id'],
            'roblox_id' => $user['roblox_id'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_url' => $user['avatar_url'],
                'role' => $user['role'],
                'subscription_tier' => $user['subscription_tier']
            ],
            'token' => $jwt
        ]);
        
    } catch (Exception $e) {
        error_log('API Roblox auth error: ' . $e->getMessage());
        
        logActivity('api_roblox_login_error', [
            'error' => $e->getMessage(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handleAuthStatus() {
    if (Auth::check()) {
        $user = Auth::user();
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'avatar_url' => $user['avatar_url'],
                'role' => $user['role'],
                'subscription_tier' => $user['subscription_tier']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'authenticated' => false,
            'user' => null
        ]);
    }
}

function handleApiLogout() {
    try {
        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }
        
        if (Auth::check()) {
            $user = Auth::user();
            logActivity('api_user_logout', [
                'user_id' => $user['id'] ?? null,
                'username' => $user['username'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
        
        Auth::logout();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        
    } catch (Exception $e) {
        error_log('API logout error: ' . $e->getMessage());
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function exchangeRobloxCode($code, $redirectUri) {
    $postData = [
        'client_id' => ROBLOX_CLIENT_ID,
        'client_secret' => ROBLOX_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];
    
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
    
    if ($error) {
        error_log('cURL error in token exchange: ' . $error);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log('HTTP error in token exchange: ' . $httpCode . ' - ' . $response);
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['access_token'])) {
        error_log('Invalid token response: ' . $response);
        return false;
    }
    
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