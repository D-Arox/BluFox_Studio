<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// If user is already logged in, redirect to dashboard
if (is_authenticated()) {
    redirect('/dashboard');
}

// Store the intended redirect URL
if (isset($_GET['redirect'])) {
    $_SESSION['auth_redirect'] = $_GET['redirect'];
}

$page_title = "Login - BluFox Studio";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape_html($page_title); ?></title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/components.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="/assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio" class="auth-logo">
                <h1>Welcome Back</h1>
                <p>Sign in with your Roblox account to continue</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    switch($_GET['error']) {
                        case 'access_denied':
                            echo 'You cancelled the authorization process.';
                            break;
                        case 'invalid_request':
                            echo 'Invalid request. Please try again.';
                            break;
                        case 'authentication_failed':
                            echo 'Authentication failed. Please try again.';
                            break;
                        case 'missing_credentials':
                            echo 'Roblox OAuth is not properly configured.';
                            break;
                        default:
                            echo 'An error occurred during login: ' . escape_html($_GET['error']);
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logged_out'])): ?>
                <div class="alert" style="background: #f0fff4; border-color: #9ae6b4; color: #2f855a;">
                    You have been successfully logged out.
                </div>
            <?php endif; ?>
            
            <div class="auth-buttons">
                <?php 
                try {
                    $auth = new RobloxAuth();
                    $auth_url = $auth->getAuthorizationUrl();
                ?>
                    <a href="<?php echo escape_html($auth_url); ?>" class="btn-roblox">
                        <img src="/assets/images/icons/roblox_icon.png" alt="" srcset="" class="icon roblox-login-icon">
                        Continue with Roblox
                    </a>
                <?php 
                } catch (Exception $e) {
                    if (DEBUG_MODE) {
                        echo '<div class="alert alert-error">OAuth Configuration Error: ' . escape_html($e->getMessage()) . '</div>';
                    } else {
                        echo '<div class="alert alert-error">Login is temporarily unavailable. Please try again later.</div>';
                    }
                    
                    echo '<a href="/?error=missing_credentials" class="btn-roblox" style="opacity: 0.5; cursor: not-allowed;">Continue with Roblox (Unavailable)</a>';
                }
                ?>
            </div>
            
            <?php if (DEBUG_MODE): ?>
                <div class="debug-info">
                    <strong>Debug Information:</strong><br>
                    Client ID: <?php echo !empty(ROBLOX_CLIENT_ID) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Client Secret: <?php echo !empty(ROBLOX_CLIENT_SECRET) ? 'Configured ✓' : 'Missing ✗'; ?><br>
                    Redirect URI: <?php echo escape_html(ROBLOX_REDIRECT_URI); ?><br>
                    Site URL: <?php echo escape_html(SITE_URL); ?>
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