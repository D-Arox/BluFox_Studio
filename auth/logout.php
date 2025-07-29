<?php
require_once '../includes/config.php';

if (isset($_POST['csrf_token']) && !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('/', 'Invalid security token', 'error');
}

if (Auth::check()) {
    $user = Auth::user();
    logActivity('user_logout', [
        'user_id' => $user['id'] ?? null,
        'username' => $user['username'] ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

Auth::logout();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

redirect('/', 'You have been logged out successfully', 'success');
?>