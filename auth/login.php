<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BluFox Studio</title>
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
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
                        default:
                            echo 'An error occurred during login. Please try again.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="auth-buttons">
                <a href="<?php echo escape_html((new RobloxAuth())->getAuthorizationUrl()); ?>" class="btn btn-primary btn-roblox">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L2 7V17L12 22L22 17V7L12 2Z"/>
                    </svg>
                    Continue with Roblox
                </a>
            </div>
            
            <div class="auth-footer">
                <p>Don't have a Roblox account? <a href="https://www.roblox.com/signup" target="_blank">Create one here</a></p>
                <p><a href="/">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>