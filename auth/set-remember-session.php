<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['remember_me'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    $rememberMe = (bool) $input['remember_me'];

    $_SESSION['remember_me_requested'] = $rememberMe;
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'remember_me' => $rememberMe]);
    
} catch (Exception $e) {
    error_log("Remember session error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Internal server error']);
}
?>