<?php
// Fixed public_html/pages/auth/login.php
require_once __DIR__ . '/../../classes/MainClass.php';
require_once __DIR__ . '/../../classes/RobloxOAuth.php';

$mainClass = new MainClass();

if ($mainClass->isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

$robloxOAuth = new RobloxOAuth();

// Handle OAuth callback
if (isset($_GET['code']) && isset($_GET['state'])) {
    try {
        // Check if remember me was requested from session storage
        $rememberMe = isset($_SESSION['remember_me_requested']) && $_SESSION['remember_me_requested'] === true;
        
        $userId = $robloxOAuth->processCallback($_GET['code'], $_GET['state'], $rememberMe);
        
        // Clean up the remember me session flag
        unset($_SESSION['remember_me_requested']);
        
        $redirectUrl = $_SESSION['intended_url'] ?? '/dashboard';
        unset($_SESSION['intended_url']);
        
        header('Location: ' . $redirectUrl);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logMessage('error', 'Login failed: ' . $error);
    }
}

// Handle remember me preference storage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_remember_me') {
    $_SESSION['remember_me_requested'] = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';
    
    // Generate OAuth URL and redirect
    $authUrl = $robloxOAuth->getAuthorizationUrl(['openid', 'profile'], 'consent+select_account');
    header('Location: ' . $authUrl);
    exit;
}

$pageTitle = 'Login';
$pageDescription = 'Login to BluFox Studio with your Roblox account to access premium tools and features.';
$pageKeywords = 'BluFox Studio login, Roblox OAuth, developer tools, Vault DataStore';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../components/global/head.php'; ?>
    <?php echo generateSEOTags($pageTitle, $pageDescription, $pageKeywords); ?>
    
    <?php echo generateJSONLD('WebPage', [
        'name' => $pageTitle . ' | ' . SITE_NAME,
        'description' => $pageDescription,
        'url' => SITE_URL . '/auth/login'
    ]); ?>
</head>
<body>
    <?php include __DIR__ . '/../../components/global/header.php'; ?>
    
    <main class="login-page">
        <div class="container">
            <div class="login-card">
                <div class="login-header">
                    <img src="/assets/images/logos/blufox-logo.png" alt="BluFox Studio" class="logo">
                    <h1>Welcome to BluFox Studio</h1>
                    <p>Login with your Roblox account to access premium developer tools</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="icon-warning"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="login-content">
                    <div class="benefits">
                        <h2>What you get with BluFox Studio:</h2>
                        <ul>
                            <li><i class="icon-check"></i> Access to Vault DataStore System</li>
                            <li><i class="icon-check"></i> Real-time analytics dashboard</li>
                            <li><i class="icon-check"></i> Premium Roblox development tools</li>
                            <li><i class="icon-check"></i> Priority customer support</li>
                            <li><i class="icon-check"></i> Early access to new features</li>
                        </ul>
                    </div>
                    
                    <div class="login-form">
                        <form id="login-form" method="POST" action="">
                            <input type="hidden" name="action" value="set_remember_me">
                            
                            <div class="remember-me-section">
                                <div class="checkbox-group">
                                    <label class="remember-me-label">
                                        <input type="checkbox" name="remember_me" id="remember_me" value="1">
                                        <span class="checkmark"></span>
                                        Remember me for 30 days
                                    </label>
                                </div>
                                
                                <div class="security-warning">
                                    <div class="warning-icon">
                                        <i class="icon-shield"></i>
                                    </div>
                                    <div class="warning-content">
                                        <strong>Security Notice:</strong>
                                        <p>Only enable "Remember me" on your personal devices. Never use this option on public or shared computers for your account security.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-roblox btn-large">
                                <i class="icon-roblox"></i>
                                Login with Roblox
                            </button>
                        </form>
                        
                        <div class="login-info">
                            <p><small>
                                By logging in, you agree to our 
                                <a href="/terms">Terms of Service</a> and 
                                <a href="/privacy">Privacy Policy</a>.
                                We only access your public Roblox profile information.
                            </small></p>
                        </div>
                    </div>
                </div>
                
                <div class="security-info">
                    <h3><i class="icon-shield"></i> Your Security Matters</h3>
                    <ul>
                        <li>We use official Roblox OAuth 2.0 authentication</li>
                        <li>Your Roblox password is never shared with us</li>
                        <li>We only access your public profile information</li>
                        <li>EU GDPR compliant data handling</li>
                        <li>Remember me tokens are encrypted and expire automatically</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../components/global/footer.php'; ?>
    
    <div id="cookie-consent" class="cookie-consent" style="display: none;">
        <div class="cookie-content">
            <h4>We use cookies</h4>
            <p>We use essential cookies for authentication and optional cookies for Google Fonts. You can customize your preferences below.</p>
            
            <div class="cookie-options">
                <label>
                    <input type="checkbox" id="essential-cookies" checked disabled>
                    Essential cookies (required for login)
                </label>
                <label>
                    <input type="checkbox" id="font-cookies" checked>
                    Google Fonts (external fonts)
                </label>
            </div>
            
            <div class="cookie-buttons">
                <button class="btn btn-secondary" onclick="rejectCookies()">Reject Optional</button>
                <button class="btn btn-primary" onclick="acceptCookies()">Accept All</button>
            </div>
        </div>
    </div>
    
    <script>
        // Cookie consent handling
        document.addEventListener('DOMContentLoaded', function() {
            const consent = localStorage.getItem('cookie_consent');
            if (!consent) {
                document.getElementById('cookie-consent').style.display = 'block';
            } else {
                loadOptionalResources(JSON.parse(consent));
            }
        });
        
        function acceptCookies() {
            const consent = {
                essential: true,
                fonts: document.getElementById('font-cookies').checked,
                timestamp: Date.now()
            };
            
            localStorage.setItem('cookie_consent', JSON.stringify(consent));
            document.getElementById('cookie-consent').style.display = 'none';
            loadOptionalResources(consent);
        }
        
        function rejectCookies() {
            const consent = {
                essential: true,
                fonts: false,
                timestamp: Date.now()
            };
            
            localStorage.setItem('cookie_consent', JSON.stringify(consent));
            document.getElementById('cookie-consent').style.display = 'none';
            loadOptionalResources(consent);
        }
        
        function loadOptionalResources(consent) {
            if (consent.fonts) {
                const link = document.createElement('link');
                link.href = 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Nunito:wght@400;500;600;700&display=swap';
                link.rel = 'stylesheet';
                document.head.appendChild(link);
            } else {
                document.body.classList.add('system-fonts');
            }
        }
    </script>
    
    <style>
        /* Enhanced Login Form Styles */
        .remember-me-section {
            margin-bottom: var(--space-6);
            padding: var(--space-4);
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-lg);
        }
        
        .remember-me-label {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            font-size: var(--font-size-base);
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
            margin-bottom: var(--space-4);
        }
        
        .security-warning {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            padding: var(--space-3);
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: var(--radius-md);
        }
        
        .warning-icon {
            color: var(--warning-color);
            font-size: var(--font-size-lg);
            flex-shrink: 0;
        }
        
        .warning-content {
            flex: 1;
        }
        
        .warning-content strong {
            display: block;
            color: var(--warning-color);
            font-size: var(--font-size-sm);
            font-weight: 600;
            margin-bottom: var(--space-1);
        }
        
        .warning-content p {
            font-size: var(--font-size-xs);
            color: var(--text-secondary);
            line-height: 1.4;
            margin: 0;
        }
        
        /* Custom checkbox styling */
        .remember-me-label input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 18px;
            background: var(--bg-glass);
            border: 2px solid var(--border-primary);
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }
        
        .remember-me-label input[type="checkbox"]:checked + .checkmark {
            background: var(--primary-cyan);
            border-color: var(--primary-cyan);
            box-shadow: 0 0 10px rgba(6, 182, 212, 0.3);
        }
        
        .remember-me-label input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 480px) {
            .security-warning {
                flex-direction: column;
                gap: var(--space-2);
            }
            
            .warning-icon {
                align-self: flex-start;
            }
            
            .remember-me-section {
                padding: var(--space-3);
            }
        }
    </style>
</body>
</html>