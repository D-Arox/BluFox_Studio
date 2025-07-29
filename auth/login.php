<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Helper function to check authentication (only if not already defined)
if (!function_exists('is_authenticated')) {
    function is_authenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// If user is already logged in, redirect to dashboard
if (is_authenticated()) {
    redirect('/dashboard');
}

// Store the intended redirect URL
if (isset($_GET['redirect'])) {
    $_SESSION['auth_redirect'] = $_GET['redirect'];
}

$page_title = "Login - BluFox Studio";
$current_page = 'login';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../includes/components/head.php'; ?>
    
    <!-- Fallback Auth Styles -->
    <style>
    </style>
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
            
            <!-- Remember Me Option -->
            <div class="remember-me-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Keep me signed in for 30 days</label>
            </div>
            
            <!-- Login Button -->
            <div class="auth-buttons">
                <?php 
                try {
                    $auth = new RobloxAuth();
                    $auth_url = $auth->getAuthorizationUrl();
                ?>
                    <a href="<?php echo htmlspecialchars($auth_url); ?>" class="btn-roblox" id="robloxLoginBtn">
                        <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" width="24" height="24" 
                             onerror="this.style.display='none'">
                        Continue with Roblox
                    </a>
                <?php 
                } catch (Exception $e) {
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        echo '<div class="alert alert-error">OAuth Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    } else {
                        echo '<div class="alert alert-error">Login is temporarily unavailable.</div>';
                    }
                    echo '<button type="button" class="btn-roblox" disabled>Login Unavailable</button>';
                }
                ?>
            </div>
            
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div class="debug-info">
                    <strong>Debug Information:</strong><br>
                    Client ID: <?php echo !empty(ROBLOX_CLIENT_ID) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Client Secret: <?php echo !empty(ROBLOX_CLIENT_SECRET) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Redirect URI: <?php echo htmlspecialchars(ROBLOX_REDIRECT_URI ?? 'Not set'); ?><br>
                    Site URL: <?php echo htmlspecialchars(SITE_URL ?? 'Not set'); ?>
                </div>
            <?php endif; ?>
            
            <div class="auth-footer">
                <p>Don't have a Roblox account? <a href="https://www.roblox.com/signup" target="_blank">Create one here</a></p>
                <p><a href="/">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <script>
        // Store remember me preference
        document.getElementById('remember_me').addEventListener('change', function() {
            if (this.checked) {
                sessionStorage.setItem('remember_me', '1');
                localStorage.setItem('remember_me_preference', '1');
            } else {
                sessionStorage.removeItem('remember_me');
                localStorage.removeItem('remember_me_preference');
            }
        });

        // Restore preference
        if (localStorage.getItem('remember_me_preference') === '1') {
            document.getElementById('remember_me').checked = true;
            sessionStorage.setItem('remember_me', '1');
        }
    </script>
</body>
</html>