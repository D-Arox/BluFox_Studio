<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $auth = new RobloxAuth();
    $token = $auth->getAccessToken($_GET['code'], $_GET['state']);
    $user = $auth->getUserInfo($token['access_token']);
    $auth->createOrUpdateUser($user, $token);
    header('Location: /dashboard');
    echo "<pre>";
    echo "Roblox returned state: " . htmlspecialchars($_GET['state'] ?? 'null') . "\n";
    echo "Session expected state: " . htmlspecialchars($_SESSION['oauth_state'] ?? 'null') . "\n";
    echo "</pre>";
    exit;
} catch (Exception $e) {
    error_log("OAuth Callback Error: " . $e->getMessage());
    echo "<pre>OAuth Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<pre>";
    echo "Roblox returned state: " . htmlspecialchars($_GET['state'] ?? 'null') . "\n";
    echo "Session expected state: " . htmlspecialchars($_SESSION['oauth_state'] ?? 'null') . "\n";
    echo "</pre>";
    exit;
}

// try {
//     if (!isset($_GET['code']) || !isset($_GET['state'])) {
//         if (isset($_GET['error'])) {
//             redirect('/auth/login?error=' . urlencode($_GET['error']));
//         }
//         throw new Exception("Missing authorization code or state");
//     }
    
//     $auth = new RobloxAuth();
    
//     $tokenData = $auth->getAccessToken($_GET['code'], $_GET['state']);
    
//     $userInfo = $auth->getUserInfo($tokenData['access_token']);
    
//     $result = $auth->createOrUpdateUser($userInfo, $tokenData);
    
//     if ($result['success']) {
//         $redirect_to = $_SESSION['auth_redirect'] ?? '/dashboard';
//         unset($_SESSION['auth_redirect']);
//         redirect($redirect_to);
//     } else {
//         throw new Exception("Failed to create user session");
//     }
    
// } catch (Exception $e) {
//     if (DEBUG_MODE) {
//         error_log("OAuth Callback Error: " . $e->getMessage());
//     }
    
//     redirect('/auth/login?error=authentication_failed');
// }
?>