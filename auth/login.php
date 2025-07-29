<?php
/**
 * auth/login.php - Login page with Roblox OAuth
 */

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
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .auth-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .auth-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }
        .auth-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .auth-header p {
            color: #666;
            margin-bottom: 30px;
        }
        .btn-roblox {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, #00A2FF 0%, #0066CC 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            padding: 15px 30px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 16px rgba(0, 162, 255, 0.3);
        }
        .btn-roblox:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(0, 162, 255, 0.5);
            color: white;
        }
        .btn-roblox svg {
            width: 24px;
            height: 24px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            color: #c53030;
        }
        .auth-footer {
            margin-top: 30px;
            font-size: 14px;
        }
        .auth-footer a {
            color: #667eea;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .debug-info {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
            font-size: 12px;
            color: #4a5568;
        }
    </style>
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
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2L2 7V17L12 22L22 17V7L12 2Z"/>
                        </svg>
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