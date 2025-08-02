<?php
// api/v1/auth.php - Authentication API Endpoints

function handleLogin($data) {
    $validator = new ApiValidator($data);
    $validator->required(['username', 'password'])
             ->min('username', 3)
             ->min('password', 6);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    // Rate limit login attempts
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $loginLimiter = new RateLimiter("login_$clientIp", 5, 900); // 5 attempts per 15 minutes
    
    if (!$loginLimiter->attempt()) {
        return ApiResponse::error('Too many login attempts. Please try again later.', 429);
    }
    
    try {
        $userModel = new User();
        $user = $userModel->findByUsername($data['username']);
        
        if (!$user || !auth()->verifyPassword($data['password'], $user['password'] ?? '')) {
            return ApiResponse::unauthorized('Invalid credentials');
        }
        
        if ($user['status'] !== 'active') {
            return ApiResponse::forbidden('Account is not active');
        }
        
        // Generate JWT token
        $tokenPayload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        $accessToken = auth()->generateJWT($tokenPayload, time() + 3600); // 1 hour
        $refreshToken = auth()->generateJWT(['user_id' => $user['id']], time() + (30 * 24 * 3600)); // 30 days
        
        // Update last login
        $userModel->updateLastLogin($user['id']);
        
        // Log activity
        auth()->logActivity('login', ['ip' => $clientIp]);
        
        return ApiResponse::success([
            'user' => array_intersect_key($user, array_flip(['id', 'username', 'display_name', 'email', 'avatar_url', 'role'])),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ], 'Login successful');
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ApiResponse::serverError('Login failed');
    }
}

function handleLogout() {
    try {
        auth()->logout();
        return ApiResponse::success(null, 'Logout successful');
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
        return ApiResponse::serverError('Logout failed');
    }
}

function handleGetCurrentUser() {
    if (!is_logged_in()) {
        return ApiResponse::unauthorized();
    }
    
    try {
        $user = current_user();
        $userModel = new User();
        
        // Get extended profile
        $profile = $userModel->getProfile($user['id']);
        
        // Get notifications count
        $unreadNotifications = count($userModel->getNotifications($user['id'], true));
        
        return ApiResponse::success([
            'user' => $profile,
            'unread_notifications' => $unreadNotifications,
            'permissions' => auth()->getUserPermissions()
        ]);
        
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to get user data');
    }
}

function handleRefreshToken($data) {
    $validator = new ApiValidator($data);
    $validator->required(['refresh_token']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $payload = auth()->verifyJWT($data['refresh_token']);
        
        if (!$payload || !isset($payload['user_id'])) {
            return ApiResponse::unauthorized('Invalid refresh token');
        }
        
        $userModel = new User();
        $user = $userModel->find($payload['user_id']);
        
        if (!$user || $user['status'] !== 'active') {
            return ApiResponse::unauthorized('User not found or inactive');
        }
        
        // Generate new access token
        $tokenPayload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        $accessToken = auth()->generateJWT($tokenPayload, time() + 3600);
        
        return ApiResponse::success([
            'access_token' => $accessToken,
            'expires_in' => 3600
        ], 'Token refreshed successfully');
        
    } catch (Exception $e) {
        error_log("Refresh token error: " . $e->getMessage());
        return ApiResponse::unauthorized('Invalid refresh token');
    }
}

function handleRobloxAuth($method, $segments, $data) {
    $action = $segments[0] ?? '';
    
    switch ($action) {
        case 'url':
            return getRobloxAuthUrl();
        case 'callback':
            return handleRobloxCallback($data);
        case 'link':
            return linkRobloxAccount($data);
        case 'unlink':
            return unlinkRobloxAccount();
        default:
            return ApiResponse::notFound('Roblox auth endpoint not found');
    }
}

function getRobloxAuthUrl() {
    try {
        $robloxOAuth = new RobloxOAuth();
        $authUrl = $robloxOAuth->getAuthorizationUrlWithPKCE();
        
        return ApiResponse::success([
            'auth_url' => $authUrl
        ], 'Authorization URL generated');
        
    } catch (Exception $e) {
        error_log("Roblox auth URL error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to generate auth URL');
    }
}

function handleRobloxCallback($data) {
    $validator = new ApiValidator($data);
    $validator->required(['code']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        
        // Exchange code for access token
        $tokenData = $robloxOAuth->getAccessTokenWithPKCE($data['code'], $data['state'] ?? null);
        
        // Get user info
        $userInfo = $robloxOAuth->getUserInfo($tokenData['access_token']);
        
        // Login or create user
        $rememberMe = isset($data['remember_me']) && $data['remember_me'];
        $user = auth()->login($userInfo['roblox_id'], $userInfo, $rememberMe);
        
        if (!$user) {
            return ApiResponse::serverError('Failed to login user');
        }
        
        // Generate JWT tokens
        $tokenPayload = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ];
        
        $accessToken = auth()->generateJWT($tokenPayload, time() + 3600);
        $refreshToken = auth()->generateJWT(['user_id' => $user['id']], time() + (30 * 24 * 3600));
        
        return ApiResponse::success([
            'user' => array_intersect_key($user, array_flip(['id', 'roblox_id', 'username', 'display_name', 'avatar_url', 'role'])),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ], 'Roblox login successful');
        
    } catch (Exception $e) {
        error_log("Roblox callback error: " . $e->getMessage());
        return ApiResponse::serverError('Roblox authentication failed');
    }
}

function linkRobloxAccount($data) {
    require_auth();
    
    $validator = new ApiValidator($data);
    $validator->required(['code']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $user = current_user();
        $robloxOAuth = new RobloxOAuth();
        
        // Exchange code for access token
        $tokenData = $robloxOAuth->getAccessTokenWithPKCE($data['code'], $data['state'] ?? null);
        
        // Get user info
        $userInfo = $robloxOAuth->getUserInfo($tokenData['access_token']);
        
        // Check if Roblox account is already linked
        $userModel = new User();
        $existingUser = $userModel->findByRobloxId($userInfo['roblox_id']);
        
        if ($existingUser && $existingUser['id'] !== $user['id']) {
            return ApiResponse::error('This Roblox account is already linked to another user');
        }
        
        // Update user with Roblox info
        $updateData = [
            'roblox_id' => $userInfo['roblox_id'],
            'avatar_url' => $userInfo['avatar_url'] ?? $user['avatar_url']
        ];
        
        $updatedUser = $userModel->update($user['id'], $updateData);
        
        // Log activity
        auth()->logActivity('roblox_account_linked', ['roblox_id' => $userInfo['roblox_id']]);
        
        return ApiResponse::success([
            'user' => $updatedUser,
            'roblox_info' => $userInfo
        ], 'Roblox account linked successfully');
        
    } catch (Exception $e) {
        error_log("Link Roblox account error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to link Roblox account');
    }
}

function unlinkRobloxAccount() {
    require_auth();
    
    try {
        $user = current_user();
        $userModel = new User();
        
        // Remove Roblox ID
        $userModel->update($user['id'], ['roblox_id' => null]);
        
        // Log activity
        auth()->logActivity('roblox_account_unlinked');
        
        return ApiResponse::success(null, 'Roblox account unlinked successfully');
        
    } catch (Exception $e) {
        error_log("Unlink Roblox account error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to unlink Roblox account');
    }
}

// Helper function to validate API token
function validateApiToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    $token = $matches[1];
    $payload = auth()->verifyJWT($token);
    
    if (!$payload || !isset($payload['user_id'])) {
        return false;
    }
    
    // Set user session from token
    $userModel = new User();
    $user = $userModel->find($payload['user_id']);
    
    if (!$user || $user['status'] !== 'active') {
        return false;
    }
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    return true;
}

// Enhanced require functions for API
function require_auth() {
    if (!is_logged_in() && !validateApiToken()) {
        ApiResponse::unauthorized('Authentication required')->send();
    }
}

function require_role($role) {
    require_auth();
    if (!has_role($role)) {
        ApiResponse::forbidden('Insufficient permissions')->send();
    }
}

function require_permission($permission) {
    require_auth();
    if (!has_permission($permission)) {
        ApiResponse::forbidden('Insufficient permissions')->send();
    }
}