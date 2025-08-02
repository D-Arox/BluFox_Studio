<?php
function handleRobloxOAuthRoutes($method, $segments, $data) {
    $action = $segments[0] ?? '';
    
    switch ($action) {
        case 'authorize':
            return getRobloxOAuthUrl($data);
        case 'callback':
            return handleRobloxOAuthCallback($data);
        case 'refresh':
            return refreshRobloxToken($data);
        case 'revoke':
            return revokeRobloxToken($data);
        default:
            return ApiResponse::notFound('Roblox OAuth endpoint not found');
    }
}

function getRobloxOAuthUrl($data) {
    try {
        $robloxOAuth = new RobloxOAuth();
        
        $state = generate_random_string(32);
        $_SESSION['roblox_oauth_state'] = $state;
        
        $authUrl = $robloxOAuth->getAuthorizationUrlWithPKCE($state);
        
        return ApiResponse::success([
            'authorization_url' => $authUrl,
            'state' => $state
        ], 'Roblox authorization URL generated');
        
    } catch (Exception $e) {
        error_log("Get Roblox OAuth URL error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to generate authorization URL');
    }
}

function handleRobloxOAuthCallback($data) {
    $validator = new ApiValidator($data);
    $validator->required(['code', 'state']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    if (!isset($_SESSION['roblox_oauth_state']) || $_SESSION['roblox_oauth_state'] !== $data['state']) {
        return ApiResponse::error('Invalid state parameter', 400);
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        
        $tokenData = $robloxOAuth->getAccessTokenWithPKCE($data['code'], $data['state']);
        $userInfo = $robloxOAuth->getUserInfo($tokenData['access_token']);
        
        $_SESSION['roblox_access_token'] = $tokenData['access_token'];
        $_SESSION['roblox_refresh_token'] = $tokenData['refresh_token'] ?? null;
        $_SESSION['roblox_user_info'] = $userInfo;
        
        unset($_SESSION['roblox_oauth_state']);
        
        return ApiResponse::success([
            'user_info' => $userInfo,
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'] ?? 3600
        ], 'Roblox OAuth callback processed successfully');
        
    } catch (Exception $e) {
        error_log("Roblox OAuth callback error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to process OAuth callback');
    }
}

function refreshRobloxToken($data) {
    $validator = new ApiValidator($data);
    $validator->required(['refresh_token']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        $tokenData = $robloxOAuth->refreshToken($data['refresh_token']);
        
        return ApiResponse::success([
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'] ?? 3600,
            'refresh_token' => $tokenData['refresh_token'] ?? $data['refresh_token']
        ], 'Roblox token refreshed successfully');
        
    } catch (Exception $e) {
        error_log("Refresh Roblox token error: " . $e->getMessage());
        return ApiResponse::unauthorized('Failed to refresh token');
    }
}

function revokeRobloxToken($data) {
    $validator = new ApiValidator($data);
    $validator->required(['token']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        $success = $robloxOAuth->revokeToken($data['token'], $data['token_type'] ?? 'access_token');
        
        if ($success) {
            unset($_SESSION['roblox_access_token'], $_SESSION['roblox_refresh_token'], $_SESSION['roblox_user_info']);
            
            return ApiResponse::success(null, 'Roblox token revoked successfully');
        } else {
            return ApiResponse::error('Failed to revoke token', 400);
        }
        
    } catch (Exception $e) {
        error_log("Revoke Roblox token error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to revoke token');
    }
}

function getRobloxUserInfo($data) {
    $validator = new ApiValidator($data);
    
    if (isset($data['user_id'])) {
        $validator->required(['user_id'])->integer('user_id');
    } elseif (isset($data['username'])) {
        $validator->required(['username']);
    } else {
        return ApiResponse::error('Either user_id or username is required', 400);
    }
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        
        if (isset($data['user_id'])) {
            $userId = $data['user_id'];
            $userDetails = $robloxOAuth->getRobloxUserDetails($userId);
        } else {
            $response = $robloxOAuth->makeRequest('POST', 'https://users.roblox.com/v1/usernames/users', [
                'usernames' => [$data['username']]
            ]);
            
            if (empty($response['data'])) {
                return ApiResponse::notFound('Roblox user not found');
            }
            
            $userId = $response['data'][0]['id'];
            $userDetails = $robloxOAuth->getRobloxUserDetails($userId);
        }
        
        return ApiResponse::success($userDetails, 'Roblox user info retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get Roblox user info error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve Roblox user info');
    }
}

function getRobloxUserGames($data) {
    $validator = new ApiValidator($data);
    $validator->required(['user_id'])->integer('user_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        $accessToken = $_SESSION['roblox_access_token'] ?? $data['access_token'] ?? null;
        
        $games = $robloxOAuth->getUserGames($data['user_id'], $accessToken);
        
        foreach ($games as &$game) {
            $game['thumbnail'] = $robloxOAuth->getGameThumbnail($game['id']);
        }
        
        return ApiResponse::success([
            'games' => $games,
            'count' => count($games)
        ], 'Roblox user games retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get Roblox user games error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve Roblox user games');
    }
}

function verifyRobloxOwnership($data) {
    require_auth();
    
    $validator = new ApiValidator($data);
    $validator->required(['game_id'])->integer('game_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $user = current_user();
        
        if (!$user['roblox_id']) {
            return ApiResponse::error('No Roblox account linked to this user', 400);
        }
        
        $robloxOAuth = new RobloxOAuth();
        $accessToken = $_SESSION['roblox_access_token'] ?? $data['access_token'] ?? null;
        
        if (!$accessToken) {
            return ApiResponse::error('Roblox access token required', 400);
        }
        
        $isOwner = $robloxOAuth->verifyGameOwnership($user['roblox_id'], $data['game_id'], $accessToken);
        
        return ApiResponse::success([
            'is_owner' => $isOwner,
            'user_id' => $user['roblox_id'],
            'game_id' => $data['game_id']
        ], $isOwner ? 'Game ownership verified' : 'Game ownership not verified');
        
    } catch (Exception $e) {
        error_log("Verify Roblox ownership error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to verify game ownership');
    }
}

function getRobloxGameDetails($data) {
    $validator = new ApiValidator($data);
    $validator->required(['game_id'])->integer('game_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        
        $gameDetails = $robloxOAuth->getGameDetails($data['game_id']);
        
        if (!$gameDetails) {
            return ApiResponse::notFound('Roblox game not found');
        }
        
        $gameDetails['thumbnail'] = $robloxOAuth->getGameThumbnail($data['game_id']);
        
        return ApiResponse::success($gameDetails, 'Roblox game details retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get Roblox game details error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve Roblox game details');
    }
}

function searchRobloxGames($data) {
    $validator = new ApiValidator($data);
    $validator->required(['query']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $query = urlencode($data['query']);
        $limit = min(50, max(1, (int)($data['limit'] ?? 10)));
        
        $robloxOAuth = new RobloxOAuth();
        $response = $robloxOAuth->makeRequest('GET', "https://catalog.roblox.com/v1/search/items?category=UniverseId&keyword=$query&limit=$limit");
        
        $games = [];
        if ($response && isset($response['data'])) {
            foreach ($response['data'] as $item) {
                $gameDetails = $robloxOAuth->getGameDetails($item['id']);
                if ($gameDetails) {
                    $gameDetails['thumbnail'] = $robloxOAuth->getGameThumbnail($item['id']);
                    $games[] = $gameDetails;
                }
            }
        }
        
        return ApiResponse::success([
            'games' => $games,
            'query' => $data['query'],
            'count' => count($games)
        ], 'Roblox games search completed');
        
    } catch (Exception $e) {
        error_log("Search Roblox games error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to search Roblox games');
    }
}

function getRobloxAssetInfo($data) {
    $validator = new ApiValidator($data);
    $validator->required(['asset_id'])->integer('asset_id');
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        
        $response = $robloxOAuth->makeRequest('GET', "https://economy.roblox.com/v2/assets/{$data['asset_id']}/details");
        
        if (!$response) {
            return ApiResponse::notFound('Roblox asset not found');
        }
        
        $thumbnailResponse = $robloxOAuth->makeRequest('GET', "https://thumbnails.roblox.com/v1/assets?assetIds={$data['asset_id']}&size=420x420&format=Png");
        
        if ($thumbnailResponse && isset($thumbnailResponse['data'][0])) {
            $response['thumbnail'] = $thumbnailResponse['data'][0]['imageUrl'];
        }
        
        return ApiResponse::success($response, 'Roblox asset info retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get Roblox asset info error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve Roblox asset info');
    }
}

function validateRobloxAccessToken($data) {
    $validator = new ApiValidator($data);
    $validator->required(['access_token']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $robloxOAuth = new RobloxOAuth();
        $isValid = $robloxOAuth->validateToken($data['access_token']);
        
        $response = [
            'valid' => $isValid
        ];
        
        if ($isValid) {
            $tokenInfo = $robloxOAuth->getTokenInfo($data['access_token']);
            $response['token_info'] = $tokenInfo;
        }
        
        return ApiResponse::success($response, $isValid ? 'Token is valid' : 'Token is invalid');
        
    } catch (Exception $e) {
        error_log("Validate Roblox access token error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to validate access token');
    }
}