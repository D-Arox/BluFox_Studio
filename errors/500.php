<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - BluFox Studio</title>
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
            
            --gradient-error: linear-gradient(135deg, #EF4444 0%, #DC2626 50%, #B91C1C 100%);
            --gradient-critical: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
            
            --bg-primary: #0A0A0F;
            --bg-secondary: #1A1A2E;
            --bg-glass: rgba(255, 255, 255, 0.05);
            --bg-card: rgba(255, 255, 255, 0.03);
            
            --text-primary: #FFFFFF;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --text-error: #EF4444;
            
            --border-primary: rgba(255, 255, 255, 0.1);
            --border-error: rgba(239, 68, 68, 0.5);
            --border-critical: rgba(220, 38, 38, 0.6);
            
            --shadow-xl: 0 16px 64px rgba(0, 0, 0, 0.6);
            --shadow-error: 0 0 32px rgba(239, 68, 68, 0.4);
            --shadow-critical: 0 0 20px rgba(220, 38, 38, 0.5);
            
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

        /* Critical error background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(239, 68, 68, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(220, 38, 38, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(185, 28, 28, 0.1) 0%, transparent 50%);
            animation: errorFlash 2s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes errorFlash {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.8; }
        }

        .error-container {
            text-align: center;
            background: var(--bg-glass);
            border: 2px solid var(--border-error);
            border-radius: var(--radius-3xl);
            padding: var(--space-16) var(--space-8);
            max-width: 600px;
            width: 90%;
            backdrop-filter: blur(25px);
            box-shadow: var(--shadow-xl), var(--shadow-error);
            position: relative;
            overflow: hidden;
            animation: systemError 1s ease-in-out;
        }

        @keyframes systemError {
            0% { transform: scale(0.8) rotate(-2deg); opacity: 0; }
            50% { transform: scale(1.05) rotate(1deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-error);
            opacity: 0.05;
            border-radius: var(--radius-3xl);
            z-index: -1;
            animation: errorPulse 3s ease-in-out infinite;
        }

        @keyframes errorPulse {
            0%, 100% { opacity: 0.05; }
            50% { opacity: 0.1; }
        }

        .server-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto var(--space-6);
            background: var(--gradient-error);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-error);
            animation: serverCrash 2s ease-in-out infinite;
            position: relative;
        }

        @keyframes serverCrash {
            0%, 100% { 
                transform: scale(1) rotate(0deg); 
                box-shadow: var(--shadow-error); 
            }
            25% { 
                transform: scale(1.05) rotate(2deg); 
                box-shadow: var(--shadow-critical); 
            }
            75% { 
                transform: scale(0.95) rotate(-2deg); 
                box-shadow: var(--shadow-error); 
            }
        }

        .server-icon::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            border: 2px solid var(--primary-red);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: errorRings 2s ease-out infinite;
        }

        @keyframes errorRings {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
            100% { transform: translate(-50%, -50%) scale(1.5); opacity: 0; }
        }

        .server-icon svg {
            width: 40px;
            height: 40px;
            color: white;
            z-index: 1;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: var(--gradient-error);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--space-4);
            text-shadow: 0 0 30px rgba(239, 68, 68, 0.5);
            animation: codeGlitch 4s ease-in-out infinite;
            position: relative;
        }

        @keyframes codeGlitch {
            0%, 90%, 100% { 
                filter: drop-shadow(0 0 20px rgba(239, 68, 68, 0.4));
                transform: translate(0);
            }
            5% { 
                transform: translate(-2px, 2px);
                filter: drop-shadow(0 0 30px rgba(220, 38, 38, 0.6)); 
            }
            10% { 
                transform: translate(2px, -2px);
                filter: drop-shadow(0 0 25px rgba(185, 28, 28, 0.8)); 
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
            color: var(--text-error);
            font-weight: 500;
            margin-bottom: var(--space-4);
            animation: systemAlert 2s ease-in-out infinite;
        }

        @keyframes systemAlert {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; transform: scale(1.02); }
        }

        .error-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-8);
            line-height: 1.6;
            max-width: 450px;
            margin-left: auto;
            margin-right: auto;
        }

        .status-panel {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--border-error);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-8);
            font-size: 0.9rem;
            font-family: var(--font-family-mono);
        }

        .status-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            opacity: 0;
            animation: statusLoad 0.5s ease-out forwards;
        }

        .status-line:nth-child(1) { animation-delay: 0.2s; }
        .status-line:nth-child(2) { animation-delay: 0.4s; }
        .status-line:nth-child(3) { animation-delay: 0.6s; }

        @keyframes statusLoad {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .status-value {
            color: var(--text-error);
            font-weight: 600;
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
            background: var(--gradient-error);
            color: white;
            border: 1px solid transparent;
            box-shadow: var(--shadow-error);
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-xl), var(--shadow-critical);
        }

        .btn-secondary {
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid var(--border-primary);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: var(--bg-card);
            border-color: var(--border-error);
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-error);
        }

        /* Critical system failure indicators */
        .error-indicators {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .error-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-red);
            animation: criticalBlink 0.8s ease-in-out infinite;
        }

        .error-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .error-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        .error-dot:nth-child(4) {
            animation-delay: 0.6s;
        }

        @keyframes criticalBlink {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.3); }
        }

        /* System malfunction lines */
        .malfunction-lines {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .malfunction-line {
            position: absolute;
            height: 1px;
            background: var(--gradient-error);
            opacity: 0.3;
            animation: malfunctionSweep 3s ease-in-out infinite;
        }

        .malfunction-line:nth-child(1) {
            top: 20%;
            left: 0;
            width: 60%;
            animation-delay: 0s;
        }

        .malfunction-line:nth-child(2) {
            top: 50%;
            right: 0;
            width: 40%;
            animation-delay: 1s;
        }

        .malfunction-line:nth-child(3) {
            bottom: 30%;
            left: 0;
            width: 80%;
            animation-delay: 2s;
        }

        @keyframes malfunctionSweep {
            0%, 100% { opacity: 0; transform: scaleX(0); }
            50% { opacity: 0.6; transform: scaleX(1); }
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
            
            .server-icon {
                width: 60px;
                height: 60px;
            }
            
            .server-icon svg {
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
            
            .error-indicators {
                top: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body>
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

    <script>
        console.error('500 Internal Server Error - System malfunction detected');
        
        let recoveryAttempts = 0;
        const statusInterval = setInterval(() => {
            recoveryAttempts++;
            console.log(`Recovery attempt ${recoveryAttempts} - System diagnostics running...`);
            
            if (recoveryAttempts >= 5) {
                console.log('Escalating to emergency response team...');
                clearInterval(statusInterval);
            }
        }, 5000);
        
        setTimeout(() => {
            if (confirm('Attempt to reconnect to server?')) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>