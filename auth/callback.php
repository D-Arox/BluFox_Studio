<?php
require_once '../includes/config.php';

// Get all parameters
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;
$errorDescription = $_GET['error_description'] ?? null;
$allParams = $_GET;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Callback Debug - BluFox Studio</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .success { color: #10B981; }
        .error { color: #EF4444; }
        .warning { color: #F59E0B; }
        .info { color: #3B82F6; }
        
        .param-box {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        
        .btn {
            background: linear-gradient(135deg, #3B82F6, #8B5CF6);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }
        
        .timer {
            font-size: 24px;
            color: #F59E0B;
            text-align: center;
            margin: 20px 0;
        }
        
        .section {
            margin: 30px 0;
            padding: 20px;
            border-left: 4px solid #3B82F6;
            background: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 OAuth Callback Debug</h1>
        
        <div class="timer" id="timer">
            Auto-continuing in <span id="countdown">30</span> seconds...
        </div>
        
        <div class="section">
            <h2>📋 Callback Information</h2>
            <p><strong>Current URL:</strong> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></p>
            <p><strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
        </div>
        
        <div class="section">
            <h2>🔗 OAuth Parameters</h2>
            <?php if (empty($allParams)): ?>
                <p class="error">❌ No parameters received! This means:</p>
                <ul>
                    <li>Roblox is not redirecting to this page</li>
                    <li>The redirect URL in your Roblox app might be wrong</li>
                    <li>Or there's a server configuration issue</li>
                </ul>
            <?php else: ?>
                <div class="param-box">
                    <?php foreach ($allParams as $key => $value): ?>
                        <p><strong><?php echo htmlspecialchars($key); ?>:</strong> 
                        <?php 
                        if ($key === 'code' && $value) {
                            echo htmlspecialchars(substr($value, 0, 20)) . '... (truncated)';
                        } else {
                            echo htmlspecialchars($value);
                        }
                        ?>
                        </p>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($error): ?>
                    <p class="error">❌ OAuth Error: <?php echo htmlspecialchars($error); ?></p>
                    <?php if ($errorDescription): ?>
                        <p class="error">Description: <?php echo htmlspecialchars($errorDescription); ?></p>
                    <?php endif; ?>
                <?php elseif ($code): ?>
                    <p class="success">✅ Authorization code received!</p>
                    <p class="info">Code length: <?php echo strlen($code); ?> characters</p>
                    <?php if ($state): ?>
                        <p class="success">✅ State parameter received!</p>
                    <?php else: ?>
                        <p class="warning">⚠️ State parameter missing!</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="warning">⚠️ No authorization code received!</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>🔧 Configuration Check</h2>
            <p><strong>Expected Redirect URI:</strong> <?php echo ROBLOX_REDIRECT_URI; ?></p>
            <p><strong>Current URL:</strong> <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>Client ID:</strong> <?php echo ROBLOX_CLIENT_ID ? 'Set (' . ROBLOX_CLIENT_ID . ')' : 'Missing'; ?></p>
            <p><strong>Client Secret:</strong> <?php echo ROBLOX_CLIENT_SECRET ? 'Set' : 'Missing'; ?></p>
        </div>
        
        <div class="section">
            <h2>🛠️ Actions</h2>
            <button onclick="stopTimer()" class="btn">⏸️ Stop Timer</button>
            <button onclick="continueNow()" class="btn">▶️ Continue Now</button>
            <a href="/auth/login" class="btn">🔄 Try Login Again</a>
            <button onclick="copyDebugInfo()" class="btn">📋 Copy Debug Info</button>
        </div>
        
        <div class="section">
            <h2>📊 Session Information</h2>
            <div class="param-box">
                <p><strong>Session Active:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No'; ?></p>
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                <p><strong>Stored OAuth State:</strong> <?php echo isset($_SESSION['oauth_state']) ? substr($_SESSION['oauth_state'], 0, 20) . '...' : 'Not found'; ?></p>
                <p><strong>Login Redirect:</strong> <?php echo $_SESSION['login_redirect'] ?? 'Not set'; ?></p>
            </div>
        </div>
        
        <div class="section">
            <h2>🔍 Next Steps</h2>
            <?php if (empty($allParams)): ?>
                <ol>
                    <li>Check your Roblox app Redirect URLs</li>
                    <li>Make sure it's exactly: <code><?php echo ROBLOX_REDIRECT_URI; ?></code></li>
                    <li>Verify your website is accessible via HTTPS</li>
                    <li>Check your server's error logs</li>
                </ol>
            <?php elseif ($error): ?>
                <ol>
                    <li>OAuth error occurred: <?php echo htmlspecialchars($error); ?></li>
                    <li>Check your Roblox app configuration</li>
                    <li>Verify client ID and redirect URI are correct</li>
                </ol>
            <?php elseif ($code && $state): ?>
                <ol>
                    <li>✅ OAuth callback successful!</li>
                    <li>Ready to exchange code for access token</li>
                    <li>Will continue automatically or click "Continue Now"</li>
                </ol>
            <?php else: ?>
                <ol>
                    <li>Incomplete OAuth response</li>
                    <li>Missing required parameters</li>
                    <li>Check Roblox app configuration</li>
                </ol>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let countdown = 30;
        let timer = setInterval(function() {
            countdown--;
            document.getElementById('countdown').textContent = countdown;
            
            if (countdown <= 0) {
                continueNow();
            }
        }, 1000);
        
        function stopTimer() {
            clearInterval(timer);
            document.getElementById('timer').innerHTML = '⏸️ Timer stopped - callback paused for debugging';
        }
        
        function continueNow() {
            clearInterval(timer);
            document.getElementById('timer').innerHTML = '▶️ Continuing with OAuth process...';
            
            // Check if we have the required parameters
            <?php if ($code && $state && !$error): ?>
                // Redirect to the actual callback processing
                window.location.href = '/auth/process-callback?<?php echo http_build_query($allParams); ?>';
            <?php elseif ($error): ?>
                // Redirect back to login with error
                window.location.href = '/auth/login?error=<?php echo urlencode($error . ': ' . $errorDescription); ?>';
            <?php else: ?>
                // Redirect back to login - something went wrong
                window.location.href = '/auth/login?error=Invalid OAuth response';
            <?php endif; ?>
        }
        
        function copyDebugInfo() {
            const debugInfo = `
OAuth Callback Debug Information
================================
Timestamp: <?php echo date('Y-m-d H:i:s'); ?>
URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
Method: <?php echo $_SERVER['REQUEST_METHOD']; ?>

Parameters:
<?php foreach ($allParams as $key => $value): ?>
<?php echo $key; ?>: <?php echo $key === 'code' && $value ? substr($value, 0, 50) . '...' : $value; ?>
<?php endforeach; ?>

Configuration:
Client ID: <?php echo ROBLOX_CLIENT_ID; ?>
Redirect URI: <?php echo ROBLOX_REDIRECT_URI; ?>
Current URL: <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>

Session:
OAuth State: <?php echo isset($_SESSION['oauth_state']) ? substr($_SESSION['oauth_state'], 0, 20) . '...' : 'Not found'; ?>
Login Redirect: <?php echo $_SESSION['login_redirect'] ?? 'Not set'; ?>
            `;
            
            navigator.clipboard.writeText(debugInfo).then(function() {
                alert('Debug info copied to clipboard!');
            });
        }
        
        // Show what parameters we received in console
        console.log('OAuth Callback Parameters:', <?php echo json_encode($allParams); ?>);
    </script>
</body>
</html>