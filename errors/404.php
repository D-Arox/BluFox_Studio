<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - BluFox Studio</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3B82F6;
            --primary-purple: #8B5CF6;
            --primary-pink: #EC4899;
            --primary-orange: #F59E0B;
            --primary-cyan: #06B6D4;
            --primary-green: #10B981;
            
            --gradient-primary: linear-gradient(135deg, #3B82F6 0%, #8B5CF6 50%, #EC4899 100%);
            --gradient-neon: linear-gradient(135deg, #00FFFF 0%, #0080FF 50%, #FF00FF 100%);
            
            --bg-primary: #0A0A0F;
            --bg-secondary: #1A1A2E;
            --bg-glass: rgba(255, 255, 255, 0.05);
            --bg-card: rgba(255, 255, 255, 0.03);
            
            --text-primary: #FFFFFF;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --text-muted: rgba(255, 255, 255, 0.6);
            --text-neon: #00FFFF;
            
            --border-primary: rgba(255, 255, 255, 0.1);
            --border-neon: rgba(0, 255, 255, 0.5);
            
            --shadow-xl: 0 16px 64px rgba(0, 0, 0, 0.6);
            --shadow-glow: 0 0 32px rgba(59, 130, 246, 0.4);
            --shadow-neon: 0 0 20px rgba(0, 255, 255, 0.3);
            
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

        /* Animated background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
            animation: backgroundShift 10s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 0.3; transform: scale(1) rotate(0deg); }
            50% { opacity: 0.6; transform: scale(1.1) rotate(5deg); }
        }

        .error-container {
            text-align: center;
            background: var(--bg-glass);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-3xl);
            padding: var(--space-16) var(--space-8);
            max-width: 600px;
            width: 90%;
            backdrop-filter: blur(25px);
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            animation: containerFloat 6s ease-in-out infinite;
        }

        @keyframes containerFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-neon);
            opacity: 0.02;
            border-radius: var(--radius-3xl);
            z-index: -1;
        }

        .error-code {
            font-size: 8rem;
            font-weight: 800;
            background: var(--gradient-primary);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--space-4);
            text-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
            animation: codeGlow 3s ease-in-out infinite;
            position: relative;
        }

        @keyframes codeGlow {
            0%, 100% { filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.4)); }
            50% { filter: drop-shadow(0 0 40px rgba(139, 92, 246, 0.6)); }
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
            color: var(--text-neon);
            font-weight: 500;
            margin-bottom: var(--space-4);
            animation: subtitlePulse 2s ease-in-out infinite;
        }

        @keyframes subtitlePulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }

        .error-message {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-12);
            line-height: 1.6;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
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
            background: var(--gradient-primary);
            color: white;
            border: 1px solid transparent;
            box-shadow: var(--shadow-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-xl), var(--shadow-glow);
        }

        .btn-secondary {
            background: var(--bg-glass);
            color: var(--text-primary);
            border: 1px solid var(--border-primary);
            backdrop-filter: blur(10px);
        }

        .btn-secondary:hover {
            background: var(--bg-card);
            border-color: var(--border-neon);
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--shadow-neon);
        }

        /* Glitch effect for 404 */
        .glitch {
            position: relative;
        }

        .glitch::before,
        .glitch::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .glitch::before {
            animation: glitch1 2s infinite;
            color: var(--primary-cyan);
            z-index: -1;
        }

        .glitch::after {
            animation: glitch2 2s infinite;
            color: var(--primary-pink);
            z-index: -2;
        }

        @keyframes glitch1 {
            0%, 14%, 15%, 49%, 50%, 99%, 100% { transform: translate(0); }
            15%, 49% { transform: translate(-2px, 2px); }
        }

        @keyframes glitch2 {
            0%, 20%, 21%, 62%, 63%, 99%, 100% { transform: translate(0); }
            21%, 62% { transform: translate(2px, -2px); }
        }

        /* Floating particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary-cyan);
            border-radius: 50%;
            animation: float 6s infinite ease-in-out;
        }

        .particle:nth-child(2) { 
            background: var(--primary-purple); 
            animation-delay: -1s; 
            left: 20%;
        }
        .particle:nth-child(3) { 
            background: var(--primary-pink); 
            animation-delay: -2s; 
            left: 40%;
        }
        .particle:nth-child(4) { 
            background: var(--primary-blue); 
            animation-delay: -3s; 
            left: 60%;
        }
        .particle:nth-child(5) { 
            background: var(--primary-orange); 
            animation-delay: -4s; 
            left: 80%;
        }

        @keyframes float {
            0%, 100% { 
                transform: translateY(100vh) translateX(0px) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
                transform: translateY(90vh) translateX(10px) scale(1);
            }
            90% {
                opacity: 1;
                transform: translateY(10vh) translateX(-10px) scale(1);
            }
            100% {
                opacity: 0;
                transform: translateY(0vh) translateX(0px) scale(0);
            }
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
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="error-container">
        <div class="error-code glitch" data-text="404">404</div>
        <h1 class="error-title">Page Not Found</h1>
        <div class="error-subtitle">// ERROR: RESOURCE_NOT_FOUND</div>
        <p class="error-message">
            The page you're looking for seems to have vanished into the digital void. 
            Let's navigate you back to familiar territory.
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

    <script>
        console.log('404 Error - Page not found:', window.location.href);
        document.addEventListener('mousemove', (e) => {
            const particles = document.querySelector('.particles');
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = e.clientX + 'px';
            particle.style.top = e.clientY + 'px';
            particle.style.background = `hsl(${Math.random() * 360}, 70%, 60%)`;
            particle.style.animation = 'float 2s ease-out forwards';
            particles.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 2000);
        });
    </script>
</body>
</html>