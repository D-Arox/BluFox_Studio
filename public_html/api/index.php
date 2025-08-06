<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../classes/MainClass.php';
require_once __DIR__ . '/../models/BaseModel.php';

class ApiRouter {
    private $mainClass;
    private $method;
    private $path;
    private $apiKey;
    private $authenticatedUser;
    
    public function __construct() {
        $this->mainClass = new MainClass();
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->path = $this->parsePath();
        $this->authenticateRequest();
    }
    
    private function parsePath() {
        $requestUri = $_SERVER['REQUEST_URI'];
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        $path = preg_replace('/^\/api/', '', $path);
        
        return trim($path, '/');
    }
    
    private function authenticateRequest() {
        $apiKey = null;
        
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            $apiKey = $_SERVER['HTTP_X_API_KEY'];
        }
        
        if (!$apiKey && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                $apiKey = $matches[1];
            }
        }
        
        if (!$apiKey && isset($_GET['api_key'])) {
            $apiKey = $_GET['api_key'];
        }
        
        if ($apiKey) {
            $this->apiKey = $this->mainClass->validateApiKey($apiKey);
            
            if ($this->apiKey) {
                if (!$this->mainClass->checkRateLimit($this->apiKey['id'])) {
                    $this->jsonError(429, 'Rate limit exceeded');
                }
                
                $this->authenticatedUser = $this->apiKey;
            }
        }
    }
    
    public function route() {
        $startTime = microtime(true);
        
        try {
            $response = $this->handleRequest();
            $statusCode = 200;
            
        } catch (Exception $e) {
            $response = ['error' => $e->getMessage()];
            $statusCode = $e->getCode() ?: 500;
            
            logMessage('error', 'API request failed: ' . $e->getMessage(), [
                'path' => $this->path,
                'method' => $this->method,
                'user_id' => $this->authenticatedUser['user_id'] ?? null
            ]);
        }
        
        $responseTime = round((microtime(true) - $startTime) * 1000);
        
        if ($this->apiKey) {
            $this->mainClass->logApiRequest(
                $this->authenticatedUser['user_id'],
                $this->apiKey['id'],
                $this->path,
                $this->method,
                $statusCode,
                $responseTime,
                $_POST ?: $_GET
            );
        }
        
        $this->jsonResponse($response, $statusCode);
    }
    
    private function handleRequest() {
        $pathParts = explode('/', $this->path);
        $version = $pathParts[0] ?? 'v1';
        $endpoint = $pathParts[1] ?? '';
        
        if ($version !== 'v1') {
            throw new Exception('API version not supported', 400);
        }
        
        switch ($endpoint) {
            case 'vault':
                return $this->handleVaultEndpoint($pathParts);
            case 'auth':
                return $this->handleAuthEndpoint($pathParts);
            case 'user':
                return $this->handleUserEndpoint($pathParts);
            case 'games':
                return $this->handleGamesEndpoint($pathParts);
            case 'products':
                return $this->handleProductsEndpoint($pathParts);
            case 'status':
                return $this->handleStatusEndpoint();
            default:
                throw new Exception('Endpoint not found', 404);
        }
    }
    
    private function handleVaultEndpoint($pathParts) {
        $this->requireAuthentication();
        
        $action = $pathParts[2] ?? '';
        
        switch ($this->method) {
            case 'POST':
                switch ($action) {
                    case 'stats':
                        return $this->submitVaultStats();
                    case 'heartbeat':
                        return $this->vaultHeartbeat();
                    default:
                        throw new Exception('Vault action not found', 404);
                }
                
            case 'GET':
                switch ($action) {
                    case 'config':
                        return $this->getVaultConfig($pathParts[3] ?? null);
                    case 'license':
                        return $this->validateVaultLicense($pathParts[3] ?? null);
                    default:
                        throw new Exception('Vault action not found', 404);
                }
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function submitVaultStats() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['game_id'])) {
            throw new Exception('Invalid request data', 400);
        }
        
        $userGame = new UserGame();
        $game = $userGame->findWhere([
            'user_id' => $this->authenticatedUser['user_id'],
            'game_id' => $input['game_id'],
            'vault_enabled' => true
        ]);
        
        if (!$game) {
            throw new Exception('Game not found or Vault not enabled', 403);
        }
        
        $this->mainClass->processVaultStats($game['id'], $input);
        
        return ['status' => 'success', 'message' => 'Statistics recorded'];
    }
    
    private function vaultHeartbeat() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['game_id'])) {
            throw new Exception('Invalid request data', 400);
        }
        
        return [
            'status' => 'alive',
            'server_time' => time(),
            'config' => [
                'update_interval' => 120,
                'max_retries' => 3,
                'timeout' => 30
            ]
        ];
    }
    
    private function getVaultConfig($licenseKey = null) {
        if (!$licenseKey) {
            throw new Exception('License key required', 400);
        }
        
        $userGame = new UserGame();
        $game = $userGame->findWhere([
            'vault_license_key' => $licenseKey,
            'vault_enabled' => true
        ]);
        
        if (!$game) {
            throw new Exception('Invalid license key', 403);
        }
        
        return [
            'game_id' => $game['game_id'],
            'api_endpoint' => SITE_URL . '/api/v1/vault/stats',
            'heartbeat_endpoint' => SITE_URL . '/api/v1/vault/heartbeat',
            'update_interval' => 120,
            'version' => '2.0.0'
        ];
    }
    
    private function validateVaultLicense($licenseKey = null) {
        if (!$licenseKey) {
            throw new Exception('License key required', 400);
        }
        
        $userGame = new UserGame();
        $game = $userGame->findWhere([
            'vault_license_key' => $licenseKey,
            'vault_enabled' => true
        ]);
        
        return [
            'valid' => $game !== null,
            'game_id' => $game['game_id'] ?? null,
            'expires_at' => null
        ];
    }
    
    private function handleAuthEndpoint($pathParts) {
        $action = $pathParts[2] ?? '';
        
        switch ($this->method) {
            case 'POST':
                switch ($action) {
                    case 'generate-key':
                        return $this->generateApiKey();
                    case 'revoke-key':
                        return $this->revokeApiKey();
                    default:
                        throw new Exception('Auth action not found', 404);
                }
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function generateApiKey() {
        if (!$this->mainClass->isAuthenticated()) {
            throw new Exception('Authentication required', 401);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $keyName = $input['name'] ?? 'Default Key';
        $permissions = $input['permissions'] ?? ['vault:read', 'vault:write'];
        
        $apiKeyModel = new ApiKey();
        $result = $apiKeyModel->createApiKey(
            $this->mainClass->getCurrentUser()['id'],
            $keyName,
            $permissions
        );
        
        return [
            'id' => $result['id'],
            'key' => $result['key'],
            'name' => $keyName,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function revokeApiKey() {
        if (!$this->mainClass->isAuthenticated()) {
            throw new Exception('Authentication required', 401);
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $keyId = $input['key_id'] ?? null;
        
        if (!$keyId) {
            throw new Exception('Key ID required', 400);
        }
        
        $apiKeyModel = new ApiKey();
        $result = $apiKeyModel->revokeKey($keyId, $this->mainClass->getCurrentUser()['id']);
        
        if ($result === 0) {
            throw new Exception('API key not found or not owned by user', 404);
        }
        
        return ['status' => 'success', 'message' => 'API key revoked'];
    }
    
    private function handleUserEndpoint($pathParts) {
        $this->requireAuthentication();
        
        $action = $pathParts[2] ?? '';
        
        switch ($this->method) {
            case 'GET':
                switch ($action) {
                    case 'profile':
                        return $this->getUserProfile();
                    case 'games':
                        return $this->getUserGames();
                    case 'api-keys':
                        return $this->getUserApiKeys();
                    case 'purchases':
                        return $this->getUserPurchases();
                    case 'stats':
                        return $this->getUserStats($pathParts[3] ?? null);
                    default:
                        throw new Exception('User action not found', 404);
                }
                
            case 'PUT':
                switch ($action) {
                    case 'profile':
                        return $this->updateUserProfile();
                    default:
                        throw new Exception('User action not found', 404);
                }
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function getUserProfile() {
        $user = new User();
        $userData = $user->find($this->authenticatedUser['user_id']);
        
        unset($userData['unique_id']);
        
        return $userData;
    }
    
    private function getUserGames() {
        $user = new User();
        return $user->getGames($this->authenticatedUser['user_id']);
    }
    
    private function getUserApiKeys() {
        $apiKey = new ApiKey();
        $keys = $apiKey->getUserKeys($this->authenticatedUser['user_id']);
        
        foreach ($keys as &$key) {
            unset($key['key_hash']);
        }
        
        return $keys;
    }
    
    private function getUserPurchases() {
        $user = new User();
        return $user->getPurchases($this->authenticatedUser['user_id']);
    }
    
    private function getUserStats($gameId = null) {
        if (!$gameId) {
            throw new Exception('Game ID required', 400);
        }
        
        $userGame = new UserGame();
        $game = $userGame->findWhere([
            'user_id' => $this->authenticatedUser['user_id'],
            'id' => $gameId
        ]);
        
        if (!$game) {
            throw new Exception('Game not found', 404);
        }
        
        $vaultStats = new VaultStats();
        $recentStats = $vaultStats->getRecentStats($gameId, 24);
        $aggregatedStats = $vaultStats->getAggregatedStats($gameId, 7);
        
        return [
            'game' => $game,
            'recent_stats' => $recentStats,
            'aggregated_stats' => $aggregatedStats
        ];
    }
    
    private function updateUserProfile() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $allowedFields = ['email', 'marketing_emails', 'gdpr_consent', 'cookie_consent'];
        $updateData = array_intersect_key($input, array_flip($allowedFields));
        
        if (empty($updateData)) {
            throw new Exception('No valid fields to update', 400);
        }
        
        $user = new User();
        $user->update($this->authenticatedUser['user_id'], $updateData);
        
        return ['status' => 'success', 'message' => 'Profile updated'];
    }
    
    private function handleGamesEndpoint($pathParts) {
        $this->requireAuthentication();
        
        $action = $pathParts[2] ?? '';
        
        switch ($this->method) {
            case 'POST':
                switch ($action) {
                    case 'add':
                        return $this->addUserGame();
                    case 'verify':
                        return $this->verifyGameOwnership();
                    case 'enable-vault':
                        return $this->enableVault();
                    default:
                        throw new Exception('Games action not found', 404);
                }
                
            case 'DELETE':
                $gameId = $pathParts[2] ?? null;
                if ($gameId) {
                    return $this->removeUserGame($gameId);
                }
                throw new Exception('Game ID required', 400);
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function addUserGame() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['game_id'])) {
            throw new Exception('Game ID required', 400);
        }
        
        $robloxOAuth = new RobloxOAuth();
        $verification = $robloxOAuth->verifyGameOwnership(
            $this->authenticatedUser['user_id'],
            $input['game_id']
        );
        
        return $verification;
    }
    
    private function verifyGameOwnership() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['game_id'])) {
            throw new Exception('Game ID required', 400);
        }
        
        $robloxOAuth = new RobloxOAuth();
        $verified = $robloxOAuth->completeGameVerification(
            $this->authenticatedUser['user_id'],
            $input['game_id']
        );
        
        if ($verified) {
            return ['status' => 'success', 'message' => 'Game ownership verified'];
        } else {
            throw new Exception('Game ownership verification failed', 400);
        }
    }
    
    private function enableVault() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['game_id'])) {
            throw new Exception('Game ID required', 400);
        }
        
        $userGame = new UserGame();
        $game = $userGame->findWhere([
            'user_id' => $this->authenticatedUser['user_id'],
            'id' => $input['game_id'],
            'is_verified' => true
        ]);
        
        if (!$game) {
            throw new Exception('Game not found or not verified', 404);
        }
        
        $purchase = new Purchase();
        $vaultPurchase = $purchase->findUserPurchase(
            $this->authenticatedUser['user_id'],
            1 // Vault product ID
        );
        
        if (!$vaultPurchase) {
            throw new Exception('Vault license required. Please purchase Vault first.', 403);
        }
        
        $licenseKey = $userGame->generateLicenseKey($game['id']);
        $userGame->update($game['id'], ['vault_enabled' => true]);
        
        return [
            'status' => 'success',
            'message' => 'Vault enabled for game',
            'license_key' => $licenseKey
        ];
    }
    
    private function removeUserGame($gameId) {
        $userGame = new UserGame();
        $result = $userGame->delete($gameId);
        
        if ($result === 0) {
            throw new Exception('Game not found', 404);
        }
        
        return ['status' => 'success', 'message' => 'Game removed'];
    }
    
    private function handleProductsEndpoint($pathParts) {
        $action = $pathParts[2] ?? '';
        
        switch ($this->method) {
            case 'GET':
                switch ($action) {
                    case '':
                    case 'list':
                        return $this->getProducts();
                    default:
                        return $this->getProduct($action);
                }
                
            default:
                throw new Exception('Method not allowed', 405);
        }
    }
    
    private function getProducts() {
        $product = new Product();
        $products = $product->findAll(['is_active' => true], 'created_at DESC');
        
        return $products;
    }
    
    private function getProduct($slug) {
        $product = new Product();
        $productData = $product->findBySlug($slug);
        
        if (!$productData) {
            throw new Exception('Product not found', 404);
        }
        
        return $productData;
    }
    
    private function handleStatusEndpoint() {
        $db = Database::getInstance();
        
        try {
            $db->fetchOne("SELECT 1");
            $dbStatus = 'connected';
        } catch (Exception $e) {
            $dbStatus = 'error';
        }
        
        return [
            'status' => 'operational',
            'version' => '1.0.0',
            'timestamp' => time(),
            'database' => $dbStatus,
            'vault_api_version' => $this->mainClass->getSetting('vault_api_version', '1.0')
        ];
    }
    
    private function requireAuthentication() {
        if (!$this->apiKey) {
            throw new Exception('Authentication required', 401);
        }
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    private function jsonError($statusCode, $message, $details = null) {
        $response = ['error' => $message];
        if ($details) {
            $response['details'] = $details;
        }
        $this->jsonResponse($response, $statusCode);
    }
}

try {
    $router = new ApiRouter();
    $router->route();
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>