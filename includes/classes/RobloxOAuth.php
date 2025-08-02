<?php
class RobloxOAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scopes;
    
    public function __construct() {
        $this->clientId = ROBLOX_CLIENT_ID;
        $this->clientSecret = ROBLOX_CLIENT_SECRET;
        $this->redirectUri = ROBLOX_REDIRECT_URI;
        $this->scopes = ['openid', 'profile'];
    }
    
    public function getAuthorizationUrl($state = null) {
        if (!$state) {
            $state = generate_random_string(32);
            $_SESSION['oauth_state'] = $state;
        }
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'response_type' => 'code',
            'state' => $state,
            'prompt' => 'consent'
        ];
        
        return 'https://apis.roblox.com/oauth/v1/authorize?' . http_build_query($params);
    }
    
    public function getAccessToken($code, $state = null) {
        if ($state && (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state)) {
            throw new Exception('Invalid state parameter');
        }
        
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];
        
        $response = $this->makeRequest('POST', 'https://apis.roblox.com/oauth/v1/token', $data);
        
        if (!$response || !isset($response['access_token'])) {
            throw new Exception('Failed to obtain access token');
        }
        
        return $response;
    }
    
    public function getUserInfo($accessToken) {
        $headers = ['Authorization: Bearer ' . $accessToken];
        $response = $this->makeRequest('GET', 'https://apis.roblox.com/oauth/v1/userinfo', null, $headers);
        
        if (!$response || !isset($response['sub'])) {
            throw new Exception('Failed to get user information');
        }
        
        $userDetails = $this->getRobloxUserDetails($response['sub']);
        
        return array_merge($response, $userDetails);
    }
    
    public function getRobloxUserDetails($userId) {
        try {
            $profile = $this->makeRequest('GET', "https://users.roblox.com/v1/users/$userId");
            $avatar = $this->makeRequest('GET', "https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds=$userId&size=420x420&format=Png&isCircular=false");
            
            $avatarUrl = null;
            if ($avatar && isset($avatar['data'][0]['imageUrl'])) {
                $avatarUrl = $avatar['data'][0]['imageUrl'];
            }
            
            return [
                'roblox_id' => $userId,
                'username' => $profile['name'] ?? '',
                'display_name' => $profile['displayName'] ?? $profile['name'] ?? '',
                'description' => $profile['description'] ?? '',
                'avatar_url' => $avatarUrl,
                'created' => $profile['created'] ?? null,
                'is_banned' => $profile['isBanned'] ?? false
            ];
        } catch (Exception $e) {
            error_log("Failed to get Roblox user details: " . $e->getMessage());
            return [
                'roblox_id' => $userId,
                'username' => '',
                'display_name' => '',
                'avatar_url' => null
            ];
        }
    }
    
    public function makeRequest($method, $url, $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'BluFoxStudio/1.0'
        ]);
        
        $defaultHeaders = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
        if ($method === 'GET') {
            $defaultHeaders = ['Accept: application/json'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("cURL error: $error");
            return false;
        }
        
        if ($httpCode >= 400) {
            error_log("HTTP error: $httpCode - Response: $response");
            return false;
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            return false;
        }
        
        return $decoded;
    }
    
    public function refreshToken($refreshToken) {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];
        
        $response = $this->makeRequest('POST', 'https://apis.roblox.com/oauth/v1/token', $data);
        
        if (!$response || !isset($response['access_token'])) {
            throw new Exception('Failed to refresh access token');
        }
        
        return $response;
    }
    
    public function revokeToken($token, $tokenType = 'access_token') {
        $data = [
            'token' => $token,
            'token_type_hint' => $tokenType,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];
        
        $response = $this->makeRequest('POST', 'https://apis.roblox.com/oauth/v1/token/revoke', $data);
        return $response !== false;
    }
    
    public function getUserGames($userId, $accessToken = null) {
        try {
            $url = "https://games.roblox.com/v2/users/$userId/games?accessFilter=Public&sortOrder=Asc&limit=50";
            $headers = $accessToken ? ['Authorization: Bearer ' . $accessToken] : [];
            
            $response = $this->makeRequest('GET', $url, null, $headers);
            return $response['data'] ?? [];
        } catch (Exception $e) {
            error_log("Failed to get user games: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUserBadges($userId, $limit = 10) {
        try {
            $url = "https://badges.roblox.com/v1/users/$userId/badges?limit=$limit&sortOrder=Asc";
            $response = $this->makeRequest('GET', $url);
            return $response['data'] ?? [];
        } catch (Exception $e) {
            error_log("Failed to get user badges: " . $e->getMessage());
            return [];
        }
    }
    
    public function verifyGameOwnership($userId, $gameId, $accessToken) {
        try {
            $games = $this->getUserGames($userId, $accessToken);
            foreach ($games as $game) {
                if ($game['id'] == $gameId) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            error_log("Failed to verify game ownership: " . $e->getMessage());
            return false;
        }
    }
    
    public function getGameDetails($gameId) {
        try {
            $response = $this->makeRequest('GET', "https://games.roblox.com/v1/games?universeIds=$gameId");
            return $response['data'][0] ?? null;
        } catch (Exception $e) {
            error_log("Failed to get game details: " . $e->getMessage());
            return null;
        }
    }
    
    public function getGameThumbnail($universeId, $size = '768x432') {
        try {
            $url = "https://thumbnails.roblox.com/v1/games/icons?universeIds=$universeId&returnPolicy=PlaceHolder&size={$size}&format=Png&isCircular=false";
            $response = $this->makeRequest('GET', $url);
            
            if ($response && isset($response['data'][0]['imageUrl'])) {
                return $response['data'][0]['imageUrl'];
            }
            return null;
        } catch (Exception $e) {
            error_log("Failed to get game thumbnail: " . $e->getMessage());
            return null;
        }
    }
    
    private function makeRequestPrivate($method, $url, $data = null, $headers = []) {
        return $this->makeRequest($method, $url, $data, $headers);
    }
    
    public function validateToken($accessToken) {
        try {
            $response = $this->makeRequest('GET', 'https://apis.roblox.com/oauth/v1/userinfo', null, [
                'Authorization: Bearer ' . $accessToken
            ]);
            
            return $response !== false && isset($response['sub']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getTokenInfo($accessToken) {
        try {
            $headers = ['Authorization: Bearer ' . $accessToken];
            return $this->makeRequest('GET', 'https://apis.roblox.com/oauth/v1/userinfo', null, $headers);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function generatePKCE() {
        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        
        $_SESSION['pkce_verifier'] = $codeVerifier;
        
        return [
            'code_verifier' => $codeVerifier,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ];
    }
    
    public function getAuthorizationUrlWithPKCE($state = null) {
        if (!$state) {
            $state = generate_random_string(32);
            $_SESSION['oauth_state'] = $state;
        }
        
        $pkce = $this->generatePKCE();
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $this->scopes),
            'response_type' => 'code',
            'state' => $state,
            'code_challenge' => $pkce['code_challenge'],
            'code_challenge_method' => $pkce['code_challenge_method'],
            'prompt' => 'consent'
        ];
        
        return 'https://apis.roblox.com/oauth/v1/authorize?' . http_build_query($params);
    }
    
    public function getAccessTokenWithPKCE($code, $state = null) {
        // Verify state parameter
        if ($state && (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state)) {
            throw new Exception('Invalid state parameter');
        }
        
        if (!isset($_SESSION['pkce_verifier'])) {
            throw new Exception('PKCE verifier not found');
        }
        
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'client_id' => $this->clientId,
            'code_verifier' => $_SESSION['pkce_verifier']
        ];
        
        unset($_SESSION['oauth_state'], $_SESSION['pkce_verifier']);
        
        $response = $this->makeRequest('POST', 'https://apis.roblox.com/oauth/v1/token', $data);
        
        if (!$response || !isset($response['access_token'])) {
            throw new Exception('Failed to obtain access token');
        }
        
        return $response;
    }
}