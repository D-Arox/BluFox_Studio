<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        if (isset($_GET['error'])) {
            redirect('/auth/login?error=' . urlencode($_GET['error']));
        }
        throw new Exception("Missing authorization code or state");
    }
    
    $auth = new RobloxAuth();
    
    $tokenData = $auth->getAccessToken($_GET['code'], $_GET['state']);
    $userInfo = $auth->getUserInfo($tokenData['access_token']);
    
    $rememberMe = false;
    
    if (isset($_GET['remember_me']) && $_GET['remember_me'] === '1') {
        $rememberMe = true;
    }
    
    if (isset($_SESSION['remember_me_requested']) && $_SESSION['remember_me_requested']) {
        $rememberMe = true;
        unset($_SESSION['remember_me_requested']); 
    }
    
    $result = $auth->createOrUpdateUser($userInfo, $tokenData, $rememberMe);
    
    if ($result['success']) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $remember_status = $result['remember_token'] ? 'created' : 'not requested';
            error_log("Remember Me Status: $remember_status for user " . $result['user_id']);
        }
        
        $redirect_to = $_SESSION['auth_redirect'] ?? '/dashboard';
        unset($_SESSION['auth_redirect']);
        redirect($redirect_to);
    } else {
        throw new Exception("Failed to create user session");
    }
    
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log("OAuth Callback Error: " . $e->getMessage());
    }
    
    redirect('/auth/login?error=authentication_failed');
}
?>