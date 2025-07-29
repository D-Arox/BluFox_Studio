<?php

require_once '../includes/config.php';

if (Auth::check()) {
    redirect('/', 'You are already logged in', 'info');
}

$pageTitle = 'Login - BluFox Studio';
$metaDescription = 'Login to BluFox Studio using your Roblox account to access exclusive content and services.';
$currentPage = 'login';

$redirectTo = $_GET['redirect'] ?? '/';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle); ?></title>
    <meta name="description" content="<?php echo escape($metaDescription); ?>">
    
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/BluFox_Studio_Logo.svg">
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/pages/auth.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-background">
            <div class="auth-glow"></div>
            <div class="auth-particles"></div>
        </div>
        
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="auth-logo">
                    <img src="/assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio" class="logo-image">
                    <span class="logo-text">BluFox Studio</span>
                </a>
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to access exclusive content and services</p>
            </div>
            
            <div class="auth-content">
                <button onclick="loginWithRoblox()" class="roblox-login-btn" id="roblox-login">
                    <div class="btn-icon">
                        <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="roblox-icon">
                    </div>
                    <div class="btn-content">
                        <span class="btn-title">Continue with Roblox</span>
                        <span class="btn-subtitle">Secure OAuth 2.0 authentication</span>
                    </div>
                    <div class="btn-arrow">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 17L17 7M17 7H7M17 7V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>
                
                <div class="auth-features">
                    <div class="feature-item">
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none">
                            <path d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Secure & Private</span>
                    </div>
                    <div class="feature-item">
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span>Instant Access</span>
                    </div>
                    <div class="feature-item">
                        <svg class="feature-icon" viewBox="0 0 24 24" fill="none">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Community Access</span>
                    </div>
                </div>
                
                <div class="auth-info">
                    <p>By signing in, you agree to our <a href="/terms">Terms of Service</a> and <a href="/privacy">Privacy Policy</a>.</p>
                    <p>We only access your public Roblox profile information.</p>
                </div>
            </div>
            
            <div class="auth-footer">
                <a href="/" class="back-link">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M19 12H5M12 19L5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
    <script>
        if ('<?php echo escape($_GET['redirect'] ?? ''); ?>') {
            sessionStorage.setItem('login_redirect', '<?php echo escape($_GET['redirect'] ?? ''); ?>');
        }

        function loginWithRoblox() {
            const state = generateRandomString(32);
            sessionStorage.setItem('oauth_state', state);

            const params = new URLSearchParams({
                client_id: '<?php echo ROBLOX_CLIENT_ID; ?>',
                redirect_uri: '<?php echo SITE_URL; ?>/auth/callback',
                scope: 'openid profile',
                response_type: 'code',
                state: state
            });

            const authUrl = `https://apis.roblox.com/oauth/v1/authorize?${params.toString()}`;

            if (typeof trackEvent === 'function') {
                trackEvent('login_attempt', { method: 'roblox' });
            }

            window.location.href = authUrl;
        }
    </script>
</body>
</html>