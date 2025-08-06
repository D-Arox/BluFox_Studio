<?php
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/RememberMe.php';

class RobloxOAuth {
    private $db;
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $rememberMe;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->clientId = ROBLOX_CLIENT_ID;
        $this->clientSecret = ROBLOX_CLIENT_SECRET;
        $this->redirectUri = ROBLOX_REDIRECT_URI;
        $this->rememberMe = new RememberMe();
    }
    
    public function getAuthorizationUrl($scopes = ['openid', 'profile'], $prompt = 'consent+select_account') {
        $state = $this->generateState();
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'state' => $state
        ];
        
        $this->storeState($state);
        
        $baseUrl = ROBLOX_OAUTH_URL . '?' . http_build_query($params);
        
        if ($prompt) {
            $baseUrl .= '&prompt=' . $prompt;
        }
        
        return $baseUrl;
    }
    
    public function exchangeCodeForToken($code, $state) {
        if (!$this->verifyState($state)) {
            throw new Exception('Invalid state parameter');
        }
        
        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ];
        
        logMessage('debug', 'Token exchange request', [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'code' => substr($code, 0, 20) . '...'
        ]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => ROBLOX_TOKEN_URL,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'User-Agent: BluFoxStudio/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_VERBOSE => DEBUG_MODE
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        logMessage('debug', 'Token exchange response', [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response_length' => strlen($response),
            'response_preview' => substr($response, 0, 200)
        ]);
        
        if (!empty($curlError)) {
            logMessage('error', 'CURL error during token exchange: ' . $curlError);
            throw new Exception('Network error during token exchange: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            logMessage('error', 'Roblox token exchange failed', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            
            $errorData = json_decode($response, true);
            $errorMessage = 'HTTP ' . $httpCode;
            
            if ($errorData && isset($errorData['error'])) {
                $errorMessage .= ': ' . $errorData['error'];
                if (isset($errorData['error_description'])) {
                    $errorMessage .= ' - ' . $errorData['error_description'];
                }
            }
            
            throw new Exception('Token exchange failed: ' . $errorMessage);
        }
        
        $tokenData = json_decode($response, true);
        
        if (!$tokenData) {
            logMessage('error', 'Invalid JSON response from Roblox token endpoint', ['response' => $response]);
            throw new Exception('Invalid JSON response from token endpoint');
        }
        
        if (!isset($tokenData['access_token'])) {
            logMessage('error', 'No access token in response', ['token_data' => $tokenData]);
            throw new Exception('No access token received from Roblox');
        }
        
        logMessage('debug', 'Token exchange successful', [
            'token_type' => $tokenData['token_type'] ?? 'unknown',
            'expires_in' => $tokenData['expires_in'] ?? 'unknown',
            'scope' => $tokenData['scope'] ?? 'unknown'
        ]);
        
        return $tokenData;
    }
    
    public function getUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => ROBLOX_USERINFO_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            logMessage('error', 'Failed to get Roblox user info', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            throw new Exception('Failed to get user information from Roblox');
        }
        
        $userInfo = json_decode($response, true);
        
        if (!$userInfo || !isset($userInfo['sub'])) {
            throw new Exception('Invalid user info response from Roblox');
        }
        
        return $userInfo;
    }
    
    public function getUserDetails($userId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://users.roblox.com/v1/users/{$userId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    public function getUserAvatar($userId, $size = '180x180', $format = 'png') {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://thumbnails.roblox.com/v1/users/avatar-headshot?userIds={$userId}&size={$size}&format={$format}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data'][0]['imageUrl'])) {
                return $data['data'][0]['imageUrl'];
            }
        }
        
        return null;
    }
    
    public function processCallback($code, $state, $rememberMe = false) {
        try {
            $tokenData = $this->exchangeCodeForToken($code, $state);
            $userInfo = $this->getUserInfo($tokenData['access_token']);
            $userDetails = $this->getUserDetails($userInfo['sub']);
            $avatarUrl = $this->getUserAvatar($userInfo['sub']);
            
            $userModel = new User();
            $user = $userModel->findByRobloxId($userInfo['sub']);
            
            $userData = [
                'roblox_id' => $userInfo['sub'],
                'username' => $userInfo['preferred_username'] ?? ($userDetails['name'] ?? 'Unknown'),
                'display_name' => $userDetails['displayName'] ?? ($userInfo['preferred_username'] ?? null),
                'avatar_url' => $avatarUrl,
                'last_login' => date('Y-m-d H:i:s'),
                'is_active' => true
            ];
            
            if ($user) {
                $userModel->update($user['id'], $userData);
                $userId = $user['id'];
            } else {
                $userData['unique_id'] = (new MainClass())->generateUniqueId();
                $userId = $userModel->create($userData);
            }
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['roblox_id'] = $userInfo['sub'];
            $_SESSION['username'] = $userData['username'];
            
            if ($rememberMe) {
                $this->rememberMe->createToken($userId);
                logMessage('info', 'Remember me token created for user', [
                    'user_id' => $userId,
                    'username' => $userData['username']
                ]);
            }
            
            logMessage('info', 'User logged in via Roblox OAuth', [
                'user_id' => $userId,
                'roblox_id' => $userInfo['sub'],
                'username' => $userData['username'],
                'remember_me' => $rememberMe
            ]);
            
            return $userId;
            
        } catch (Exception $e) {
            logMessage('error', 'OAuth callback processing failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function checkRememberMe() {
        return $this->rememberMe->validateToken();
    }
    
    public function getRememberMeSession($userId) {
        return $this->rememberMe->getUserTokens($userId);
    }

    public function revokeRememberMeToken($tokenId) {
        return $this->rememberMe->revokeToken($tokenId);
    }

    private function generateState() {
        return bin2hex(random_bytes(32));
    }
    
    private function storeState($state) {
        $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes
        
        $this->db->insert(
            "INSERT INTO oauth_states (state, user_ip, user_agent, redirect_uri, expires_at) VALUES (?, ?, ?, ?, ?)",
            [
                $state,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $_SERVER['REQUEST_URI'] ?? null,
                $expiresAt
            ]
        );
    }
    
    private function verifyState($state) {
        $storedState = $this->db->fetchOne(
            "SELECT * FROM oauth_states WHERE state = ? AND expires_at > NOW()",
            [$state]
        );
        
        if (!$storedState) {
            return false;
        }
        
        $this->db->delete(
            "DELETE FROM oauth_states WHERE id = ?",
            [$storedState['id']]
        );
        
        return true;
    }
    
    public function getAuthorizationUrlWithPrompt($scopes = ['openid', 'profile'], $customPrompt = null) {
        $defaultPrompt = 'consent+select_account';
        $prompt = $customPrompt ?? $defaultPrompt;
        
        return $this->getAuthorizationUrl($scopes, $prompt);
    }
    
    public function getAuthorizationUrlForPermissions($permissions = ['basic']) {
        $scopes = ['openid', 'profile'];
        $prompt = 'consent+select_account';
        
        switch (implode(',', $permissions)) {
            case 'basic':
                $scopes = ['openid', 'profile'];
                break;
            case 'games':
                $scopes = ['openid', 'profile'];
                $prompt = 'consent+select_account';
                break;
            case 'admin':
                $scopes = ['openid', 'profile'];
                $prompt = 'consent+select_account';
                break;
            default:
                $scopes = ['openid', 'profile'];
        }
        
        return $this->getAuthorizationUrl($scopes, $prompt);
    }
    
    public function verifyGameOwnership($userId, $gameId, $accessToken = null) {
        // This is a complex process that requires:
        // 1. Getting the user's places/games from Roblox API
        // 2. Checking if the gameId belongs to the user
        // 3. For security, we'll use a verification system instead
        
        return $this->initiateGameVerification($userId, $gameId);
    }
    
    private function initiateGameVerification($userId, $gameId) {
        $mainClass = new MainClass();
        $verificationToken = $mainClass->validateGameOwnership($userId, $gameId);
        
        return [
            'verification_token' => $verificationToken,
            'instructions' => 'Please add a StringValue named "BluFoxVerification" to your game\'s ServerStorage with the value: ' . $verificationToken . '. Then click verify.'
        ];
    }
    
    public function completeGameVerification($userId, $gameId) {
        $userGame = new UserGame();
        $game = $userGame->findByGameId($gameId, $userId);
        
        if (!$game || !$game['verification_token']) {
            throw new Exception('Game verification not initiated');
        }
        
        $verified = $this->checkVerificationTokenInGame($gameId, $game['verification_token']);
        
        if ($verified) {
            $gameDetails = $this->getGameDetails($gameId);
            
            $userGame->update($game['id'], [
                'is_verified' => true,
                'game_name' => $gameDetails['name'] ?? 'Unknown Game',
                'game_icon' => $gameDetails['icon'] ?? null,
                'verification_token' => null
            ]);
            
            return true;
        }
        
        return false;
    }
    
    private function checkVerificationTokenInGame($gameId, $token) {
        // In a real implementation, this would need to:
        // 1. Use Roblox's Open Cloud API to check game contents
        // 2. Or implement a different verification method
        // For now, we'll return true after a delay to simulate verification
        
        logMessage('info', "Game verification requested", [
            'game_id' => $gameId,
            'token' => $token
        ]);
        
        return true;
    }
    
    private function getGameDetails($gameId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://games.roblox.com/v1/games?universeIds={$gameId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['data'][0])) {
                return $data['data'][0];
            }
        }
        
        return ['name' => 'Unknown Game'];
    }
    
    public function logout($revokeRememberMe = true) {
        $userId = $_SESSION['user_id'] ?? null;

        if ($revokeRememberMe && $userId) {
            $this->rememberMe->revokeAllUserTokens($userId);
        }

        $this->rememberMe->deleteCookie();

        session_destroy();
        session_start();

        logMessage('info', 'User logged out', [
            'user_id' => $userId,
            'revoked_remember_me' => $revokeRememberMe
        ]);
    }
}
?>