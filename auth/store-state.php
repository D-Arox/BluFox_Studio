<?php
// BluFox Studio - Store OAuth State
// Create this as auth/store-state.php

require_once '../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$state = $_POST['state'] ?? null;
$csrfToken = $_POST['csrf_token'] ?? null;

if (!$state) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'State parameter required']);
    exit;
}

// Store state in session
$_SESSION['oauth_state'] = $state;

// Optional: Also store in database for extra reliability
try {
    if (function_exists('logActivity')) {
        logActivity('oauth_state_stored', [
            'state_preview' => substr($state, 0, 10) . '...',
            'session_id' => session_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
} catch (Exception $e) {
    // Don't fail if logging fails
    error_log('Failed to log state storage: ' . $e->getMessage());
}

echo json_encode([
    'success' => true, 
    'message' => 'State stored successfully',
    'session_id' => session_id()
]);
?>