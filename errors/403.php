<?php
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - BluFox Studio</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #ffa726 0%, #ff7043 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .error-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #ffa726;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .error-title {
            font-size: 2rem;
            margin: 20px 0;
            color: #333;
        }
        .error-message {
            font-size: 1.1rem;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        .error-links {
            margin-top: 40px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #ffa726;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #ff9800;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <h1 class="error-title">Access Forbidden</h1>
        <p class="error-message">
            You don't have permission to access this page.
            Please sign in or contact support if you believe this is an error.
        </p>
        <div class="error-links">
            <a href="/" class="btn">Go Home</a>
            <a href="/auth/login" class="btn btn-secondary">Sign In</a>
        </div>
    </div>
</body>
</html>