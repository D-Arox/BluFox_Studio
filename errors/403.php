<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3B82F6;
            --primary-purple: #8B5CF6;
            --primary-pink: #EC4899;
            --primary-orange: #F59E0B;
            --primary-cyan: #06B6D4;
            --primary-green: #10B981;
            --primary-red: #EF4444;
            
            --gradient-warning: linear-gradient(135deg, #F59E0B 0%, #EF4444 50%, #EC4899 100%);
            --gradient-danger: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
            
            --bg-primary: #0A0A0F;
            --bg-secondary: #1A1A2E;
            --bg-glass: rgba(255, 255, 255, 0.05);
            --bg-card: rgba(255, 255, 255, 0.03);
            
            --text-primary: #FFFFFF;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --text-warning: #F59E0B;
            
            --border-primary: rgba(255, 255, 255, 0.1);
            --border-warning: rgba(245, 158, 11, 0.5);
            --border-danger: rgba(239, 68, 68, 0.5);
            
            --shadow-xl: 0 16px 64px rgba(0, 0, 0, 0.6);
            --shadow-warning: 0 0 32px rgba(245, 158, 11, 0.4);
            --shadow-danger: 0 0 20px rgba(239, 68, 68, 0.3);
            
            --font-family-mono: 'JetBrains Mono', 'SF Mono', 'Monaco', monospace;
            
            --radius-lg: 0.5rem;
            --radius-2xl: 1rem;
            --radius-3xl: 1.5rem;
            
            --space-4: 1rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --space-12: 3rem;
            --space-16: 4rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family-mono);
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated warning background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(245, 158, 11, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(239, 68, 68, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(236, 72, 153, 0.08) 0%, transparent 50%);
            animation: warningPulse 4s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes warningPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }

        .error-container {
            text-align: center;
            background: var(--bg-glass);
            border: 2px solid var(--border-warning);
            border-radius: var(--radius-3xl);
            padding: var(--space-16) var(--space-8);
            max-width: 600px;
            width: 90%;
            backdrop-filter: blur(25px);
            box-shadow: var(--shadow-xl), var(--shadow-warning);
            position: relative;
            overflow: hidden;
            animation: containerShake 0.5s ease-in-out 3s;
        }

        @keyframes containerShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-warning);
            opacity: 0.03;
            border-radius: var(--radius-3xl);
            z-index: -1;
        }

        .lock-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--space-6);
            background: var(--gradient-warning);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-warning);
            animation: lockPulse 2s ease-in-out infinite;
        }

        @keyframes lockPulse {
            0%, 100% { transform: scale(1); box-shadow: var(--shadow-warning); }
            50% { transform: scale(1.1); box-shadow: var(--shadow-danger); }
        }

        .lock-icon svg {
            width: 40px;
            height: 40px;
            color: white;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: var(--gradient-warning);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--space-4);
            text-shadow: 0 0 30px rgba(245, 158, 11, 0.5);
            animation: codeFlicker 3s ease-in-out infinite;
        }

        @keyframes codeFlicker {
            0%, 100% { 
                filter: drop-shadow(0 0 20px rgba(245, 158, 11, 0.4));
                opacity: 1;
            }
            50% { 
                filter: drop-shadow(0 0 40px rgba(239, 68, 68, 0.6));
                opacity: 0.9;
            }
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: var(--space-6);
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .error-subtitle {
            font-size: 1.2rem;
            color: var(--text-warning);
            font-weight: 500;
            margin-bottom: var(--space-4);
            animation: accessDenied 2s ease-in-out infinite;
        }

        @keyframes accessDenied {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; color: var(--primary-red); }
        }

        .error-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-12);
            line-height: 1.6;
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
        }

        .security-notice {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--border-danger);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-8);
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .error-links {
            display: flex;
            gap: var(--space-4);
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: var(--space-4) var(--space-6);
            font-family: var(--font-family-mono);
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-warning);
            color: white;
            border: 1px solid transparent;
            box-shadow: var(--shadow-warning);
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-xl), var(--shadow-warning);
        }

        .btn-secondary {
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid var(--border-primary);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: var(--bg-card);
            border-color: var(--border-warning);
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-warning);
        }

        /* Security scanner effect */
        .scanner {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(245, 158, 11, 0.1) 45%, 
                rgba(239, 68, 68, 0.2) 50%, 
                rgba(245, 158, 11, 0.1) 55%, 
                transparent 100%);
            animation: securityScan 3s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes securityScan {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Warning indicators */
        .warning-indicators {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .warning-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-red);
            animation: warningBlink 1.5s ease-in-out infinite;
        }

        .warning-dot:nth-child(2) {
            background: var(--primary-orange);
            animation-delay: 0.5s;
        }

        .warning-dot:nth-child(3) {
            background: var(--primary-orange);
            animation-delay: 1s;
        }

        @keyframes warningBlink {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.2); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .error-code {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 2rem;
            }
            
            .error-container {
                padding: var(--space-8) var(--space-6);
            }
            
            .error-links {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
            }
            
            .lock-icon {
                width: 60px;
                height: 60px;
            }
            
            .lock-icon svg {
                width: 30px;
                height: 30px;
            }
        }

        @media (max-width: 480px) {
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-subtitle {
                font-size: 1rem;
            }
            
            .error-container {
                padding: var(--space-6) var(--space-4);
            }
            
            .warning-indicators {
                top: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="scanner"></div>
        <div class="warning-indicators">
            <div class="warning-dot"></div>
            <div class="warning-dot"></div>
            <div class="warning-dot"></div>
        </div>
        
        <div class="lock-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <circle cx="12" cy="16" r="1"></circle>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        
        <div class="error-code">403</div>
        <h1 class="error-title">Access Forbidden</h1>
        <div class="error-subtitle">// ERROR: PERMISSION_DENIED</div>
        
        <div class="security-notice">
            🔒 Security Protocol Active - This action has been logged
        </div>
        
        <p class="error-message">
            You don't have the required permissions to access this resource. 
            This incident has been recorded for security purposes.
        </p>
        
        <div class="error-links">
            <a href="/" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                Return Home
            </a>
            <a href="/auth/login" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10,17 15,12 10,7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Sign In
            </a>
        </div>
    </div>

    <script>
        let scanCount = 0;
        setInterval(() => {
            scanCount++;
            if (scanCount % 10 === 0) {
                console.log('Security scan #' + scanCount + ' - Monitoring access attempts');
            }
        }, 3000);
    </script>
</body>
</html>