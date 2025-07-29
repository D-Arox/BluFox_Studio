<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
http_response_code(402);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Required - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/../assets/css/err_page.css">
    <link rel="shortcut icon" href="/../assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
</head>
<body class="payment-page">
    <div class="error-container">
        <div class="payment-scan"></div>
        <div class="payment-indicators">
            <div class="payment-dot"></div>
            <div class="payment-dot"></div>
            <div class="payment-dot"></div>
        </div>
        
        <div class="payment-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
        </div>
        
        <div class="error-code">402</div>
        <h1 class="error-title">Payment Required</h1>
        <div class="error-subtitle">// ERROR: SUBSCRIPTION_REQUIRED</div>
        
        <div class="payment-notice">
            💳 A valid subscription or payment is required to access this premium feature
        </div>
        
        <p class="error-message">
            This content is part of our premium services. Subscribe to BluFox Pro to unlock 
            advanced features, priority support, and exclusive content.
        </p>
        
        <div class="error-links">
            <a href="/pricing" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M16 8l-6 6"></path>
                    <path d="M8 8l6 6"></path>
                </svg>
                View Pricing
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