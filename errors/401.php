<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!preg_match('/\.php$/', $_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: text/html; charset=utf-8');
}

http_response_code(401);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/../assets/css/err_page.css">
    <link rel="shortcut icon" href="/../assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
</head>
<body class="auth-page">
    <div class="error-container">
        <div class="auth-scan"></div>
        <div class="auth-indicators">
            <div class="auth-dot"></div>
            <div class="auth-dot"></div>
            <div class="auth-dot"></div>
        </div>
        
        <div class="auth-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <circle cx="12" cy="16" r="1"></circle>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        
        <div class="error-code">401</div>
        <h1 class="error-title">Unauthorized</h1>
        <div class="error-subtitle">// ERROR: AUTHENTICATION_REQUIRED</div>
        
        <div class="auth-notice">
            🔐 Valid authentication credentials are required to access this resource
        </div>
        
        <p class="error-message">
            You need to be authenticated to access this page. Please sign in with your account 
            or contact support if you believe you should have access.
        </p>
        
        <div class="error-links">
            <a href="/auth/login" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10,17 15,12 10,7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Sign In
            </a>
            <a href="/" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                Return Home
            </a>
        </div>
    </div>
</body>
</html>