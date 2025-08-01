<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/CookieManager.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

check_rate_limit(50);

try {
    $db = null;
    if (defined('DB_HOST') && DB_HOST) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $db = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed in cookie consent API: " . $e->getMessage());
        }
    }

    $cookieManager = new CookieManager($db);
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            handleConsentSubmission($cookieManager);
            break;
            
        case 'GET':
            handleConsentRetrieval($cookieManager);
            break;
            
        default:
            throw new Exception('Method not allowed', 405);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]);
}

function handleConsentSubmission($cookieManager) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        throw new Exception('Invalid request', 400);
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    if (!isset($data['consent']) || !is_array($data['consent'])) {
        throw new Exception('Missing or invalid consent data', 400);
    }

    $consent = $data['consent'];

    if (!isset($consent['timestamp'])) {
        $consent['timestamp'] = time() * 1000; 
    }

    if (!isset($consent['version'])) {
        $consent['version'] = '1.0';
    }

    if (!isset($consent['categories']) || !is_array($consent['categories'])) {
        throw new Exception('Invalid categories data', 400);
    }

    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $result = $cookieManager->saveConsent($consent, $session_id, $user_id);

        if ($result) {
            error_log("Cookie consent saved for session: $session_id, categories: " . json_encode($consent['categories']));

            echo json_encode([
                'success' => true,
                'message' => 'Consent preferences saved successfully',
                'consent' => $consent,
                'timestamp' => time()
            ]);
        } else {
            throw new Exception('Failed to save consent', 500);
        }

    } catch (InvalidArgumentException $e) {
        throw new Exception('Invalid consent data: ' . $e->getMessage(), 400);
    }
}

function handleConsentRetrieval($cookieManager) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $consent = $cookieManager->getConsent($session_id, $user_id);
        $categories = $cookieManager->getCategories();

        echo json_encode([
            'success' => true,
            'consent' => $consent,
            'categories' => $categories,
            'requires_consent' => $consent === null,
            'timestamp' => time()
        ]);

    } catch (Exception $e) {
        throw new Exception('Failed to retrieve consent: ' . $e->getMessage(), 500);
    }
}

function handleConsentWithdrawal($cookieManager) {
    $session_id = session_id();
    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $result = $cookieManager->removeConsent($session_id, $user_id);

        echo json_encode([
            'success' => true,
            'message' => 'Consent withdrawn successfully',
            'timestamp' => time()
        ]);

    } catch (Exception $e) {
        throw new Exception('Failed to withdraw consent: ' . $e->getMessage(), 500);
    }
}
?>