<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!preg_match('/\.php$/', $_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: text/html; charset=utf-8');
}

http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/../assets/css/err_page.css">
    <link rel="shortcut icon" href="/../assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
</head>
<body class="error-page">
    <div class="error-container">
        <div class="malfunction-lines">
            <div class="malfunction-line"></div>
            <div class="malfunction-line"></div>
            <div class="malfunction-line"></div>
        </div>
        
        <div class="error-indicators">
            <div class="error-dot"></div>
            <div class="error-dot"></div>
            <div class="error-dot"></div>
            <div class="error-dot"></div>
        </div>
        
        <div class="server-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
                <path d="M6 7h.01"></path>
                <path d="M10 7h.01"></path>
            </svg>
        </div>
        
        <div class="error-code">500</div>
        <h1 class="error-title">Internal Server Error</h1>
        <div class="error-subtitle">// CRITICAL: SYSTEM_MALFUNCTION</div>
        
        <div class="status-panel">
            <div class="status-line">
                <span>Server Status:</span>
                <span class="status-value">CRITICAL ERROR</span>
            </div>
            <div class="status-line">
                <span>Service Health:</span>
                <span class="status-value">DEGRADED</span>
            </div>
            <div class="status-line">
                <span>Recovery ETA:</span>
                <span class="status-value">ESTIMATING...</span>
            </div>
        </div>
        
        <p class="error-message">
            Our servers are experiencing technical difficulties. Our engineering team 
            has been automatically notified and is working to resolve the issue.
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