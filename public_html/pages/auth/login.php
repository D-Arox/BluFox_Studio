<?php
require_once __DIR__ . '/../../classes/MainClass.php';
require_once __DIR__ . '/../../classes/RobloxOAuth.php';

$mainClass = new MainClass();

if ($mainClass->isAuthenticated()) {
    header('Location: /dashboard');
    exit;
}

$robloxOAuth = new RobloxOAuth();
$authUrl = $robloxOAuth->getAuthorizationUrl(['openid', 'profile'], 'consent+select_account');

if (isset($_GET['code']) && isset($_GET['state'])) {
    try {
        $userId = $robloxOAuth->processCallback($_GET['code'], $_GET['state']);
        $redirectUrl = $_SESSION['intended_url'] ?? '/dashboard';
        unset($_SESSION['intended_url']);
        
        header('Location: ' . $redirectUrl);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        logMessage('error', 'Login failed: ' . $error);
    }
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
                        <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn btn-roblox btn-large">
                            <i class="icon-roblox"></i>
                            Login with Roblox
                        </a>
                        
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
                // Load Google Fonts
                const link = document.createElement('link');
                link.href = 'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Nunito:wght@400;500;600;700&display=swap';
                link.rel = 'stylesheet';
                document.head.appendChild(link);
            } else {
                // Use fallback system fonts
                document.body.classList.add('system-fonts');
            }
        }
    </script>
</body>
</html>