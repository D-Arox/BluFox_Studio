<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../config/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
    header('Location: /dashboard');
    exit;
}

// Handle error messages from callback
$error_message = $_SESSION['auth_error'] ?? null;
$success_message = $_SESSION['auth_success'] ?? null;
unset($_SESSION['auth_error'], $_SESSION['auth_success']);

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get Roblox OAuth URL from API
$auth_url = '';
try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => SITE_URL . '/api/v1/roblox/authorize',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => ENVIRONMENT === 'production'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response && $httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && $data['success'] && isset($data['data']['authorization_url'])) {
            $auth_url = $data['data']['authorization_url'];
        }
    }
} catch (Exception $e) {
    error_log('Failed to get auth URL: ' . $e->getMessage());
}

$page_title = "Login - BluFox Studio";
$page_description = "Login to BluFox Studio with your Roblox account to access exclusive features and services.";
?>

<!DOCTYPE html>
<html lang="en">
<?php include '../includes/components/head.php'; ?>
<body>
    <!-- Navigation -->
    <?php include '../includes/components/header.php'; ?>

    <!-- Main Content -->
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Login to your BluFox Studio account with Roblox</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Login Failed:</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Remember Me Checkbox -->
            <div class="remember-me-checkbox">
                <input type="checkbox" id="remember_me" name="remember_me" value="1">
                <label for="remember_me">Remember me for 30 days</label>
            </div>

            <!-- Roblox Login Button -->
            <button 
                id="robloxLoginBtn" 
                class="btn btn-roblox" 
                data-auth-url="<?php echo htmlspecialchars($auth_url); ?>"
                <?php echo empty($auth_url) ? 'disabled' : ''; ?>
            >
                <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="roblox-login-icon">
                <?php echo empty($auth_url) ? 'Login Unavailable' : 'Login with Roblox'; ?>
            </button>

            <?php if (empty($auth_url)): ?>
                <div class="debug-info">
                    <strong>Debug Info:</strong> Unable to connect to authentication service. Please try again later or contact support if this persists.
                </div>
            <?php endif; ?>

            <div class="security-note">
                <strong>Security Notice:</strong> Only check "Remember me" on your personal device. 
                We use secure authentication through Roblox OAuth 2.0.
            </div>

            <div class="auth-footer">
                <p>
                    New to BluFox Studio? 
                    <a href="/about">Learn more about our services</a>
                </p>
                <p style="margin-top: 10px;">
                    <a href="/">← Back to Homepage</a>
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="/assets/js/auth.js"></script>
</body>
</html>