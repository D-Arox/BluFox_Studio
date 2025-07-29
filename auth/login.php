<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

if (is_authenticated()) {
    redirect('/dashboard');
}

if (isset($_GET['redirect'])) {
    $_SESSION['auth_redirect'] = $_GET['redirect'];
}

$page_title = "Login - BluFox Studio";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/components/head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/components/header.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your BluFox Studio account</p>
            </div>
            
            <!-- Error Messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    switch ($_GET['error']) {
                        case 'access_denied':
                            echo 'Login was cancelled. Please try again.';
                            break;
                        case 'authentication_failed':
                            echo 'Authentication failed. Please try again.';
                            break;
                        case 'missing_credentials':
                            echo 'OAuth configuration is incomplete. Please contact support.';
                            break;
                        case 'session_expired':
                            echo 'Your session has expired. Please log in again.';
                            break;
                        default:
                            echo 'An error occurred during login: ' . htmlspecialchars($_GET['error']);
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="alert alert-success">
                    You have been successfully logged out.
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" class="auth-form" action="/auth/login" method="POST">
                <div class="auth-options">
                    <div class="remember-me-container">
                        <div class="remember-me-checkbox">
                            <input type="checkbox" id="remember_me" name="remember_me" value="1">
                            <label for="remember_me">Keep me signed in</label>
                        </div>
                        <div class="security-note">
                            Only check this on your personal device. You'll stay logged in for 30 days.
                        </div>
                    </div>
                </div>
                
                <div class="auth-buttons">
                    <?php 
                    try {
                        require_once __DIR__ . '/../includes/auth.php';
                        $auth = new RobloxAuth();
                        $auth_url = $auth->getAuthorizationUrl();
                    ?>
                        <button type="submit" class="btn-roblox" id="robloxLoginBtn">
                            <img src="/assets/images/icons/roblox_icon.png" alt="" class="roblox-login-icon">
                            <span class="btn-text">Continue with Roblox</span>
                            <div class="loading-spinner"></div>
                        </button>
                        <input type="hidden" name="auth_url" value="<?php echo htmlspecialchars($auth_url); ?>">
                    <?php 
                    } catch (Exception $e) {
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            echo '<div class="alert alert-error">OAuth Configuration Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        } else {
                            echo '<div class="alert alert-error">Login is temporarily unavailable. Please try again later.</div>';
                        }
                        
                        echo '<button type="button" class="btn-roblox" style="opacity: 0.5; cursor: not-allowed;" disabled>Continue with Roblox (Unavailable)</button>';
                    }
                    ?>
                </div>
            </form>
            
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div class="debug-info" style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 8px; font-size: 12px;">
                    <strong>Debug Information:</strong><br>
                    Client ID: <?php echo !empty(ROBLOX_CLIENT_ID) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Client Secret: <?php echo !empty(ROBLOX_CLIENT_SECRET) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Redirect URI: <?php echo htmlspecialchars(ROBLOX_REDIRECT_URI); ?><br>
                    Site URL: <?php echo htmlspecialchars(SITE_URL); ?>
                </div>
            <?php endif; ?>
            
            <div class="auth-footer">
                <p>Don't have a Roblox account? <a href="https://www.roblox.com/signup" target="_blank">Create one here</a></p>
                <p><a href="/">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>