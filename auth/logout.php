<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    if (isset($_GET['token']) && verify_csrf_token($_GET['token'])) {
        
    } else {
        redirect('/', 403);
    }
}

try {
    logout();
    redirect('/?logged_out=1');
} catch (Exception $e) {
    if (DEBUG_MODE) {
        error_log("Logout Error: " . $e->getMessage());
    }
    redirect('/');
}
?>