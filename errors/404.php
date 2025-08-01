<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!preg_match('/\.php$/', $_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: text/html; charset=utf-8');
}

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/../assets/css/err_page.css">
    <link rel="shortcut icon" href="/../assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
</head>
<body class="search-page">
    <div class="error-container">
        <div class="search-scan"></div>
        <div class="search-indicators">
            <div class="search-dot"></div>
            <div class="search-dot"></div>
            <div class="search-dot"></div>
        </div>
        
        <div class="search-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
        </div>
        
        <div class="error-code">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <div class="error-subtitle">// ERROR: RESOURCE_NOT_FOUND</div>
        
        <div class="search-notice">
            🔍 The requested page could not be located in our system
        </div>
        
        <p class="error-message">
            The page you're looking for doesn't exist or has been moved to a different location. 
            Let's help you find what you're looking for.
        </p>
        
        <div class="error-links">
            <a href="/" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                Return Home
            </a>
            <a href="/portfolio" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                View Portfolio
            </a>
        </div>
    </div>
</body>
</html>