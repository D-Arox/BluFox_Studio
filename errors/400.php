<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
http_response_code(400);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bad Request - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/err_page.css">
    <link rel="shortcut icon" href="/assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
</head>
<body class="forbidden-page">
    <div class="error-container">
        <div class="forbidden-scan"></div>
        <div class="forbidden-indicators">
            <div class="forbidden-dot"></div>
            <div class="forbidden-dot"></div>
            <div class="forbidden-dot"></div>
        </div>
        
        <div class="forbidden-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        
        <div class="error-code">400</div>
        <h1 class="error-title">Bad Request</h1>
        <div class="error-subtitle">// ERROR: MALFORMED_REQUEST</div>
        
        <div class="forbidden-notice">
            ⚠️ The request could not be understood by the server
        </div>
        
        <p class="error-message">
            The server could not understand the request due to invalid syntax. 
            Please check your request and try again.
        </p>
        
        <div class="error-links">
            <a href="/" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                Return Home
            </a>
            <a href="/contact" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                    <polyline points="22,6 12,13 2,6"></polyline>
                </svg>
                Contact Support
            </a>
        </div>
    </div>
</body>
</html>