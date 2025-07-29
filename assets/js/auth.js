class BluFoxAuth {
    constructor() {
        this.user = null;
        this.config = null;
        this.init();
    }

    init() {
        this.setupConfig();
        this.handleOAuthCallback();
        this.checkUserSession();
    }

    setupConfig() {
        const metaTags = {
            clientId: document.querySelector('meta[name="roblox-client-id"]')?.content,
            redirectUri: document.querySelector('meta[name="roblox-redirect-uri"]')?.content,
            siteUrl: document.querySelector('meta[name="site-url"]')?.content
        };
        
        this.config = {
            robloxClientId: metaTags.clientId || window.BluFox?.config?.robloxClientId || '6692844983306448575',
            robloxRedirectUri: metaTags.redirectUri || window.BluFox?.config?.robloxRedirectUri || (window.location.origin + '/auth/callback'),
            apiUrl: window.BluFox?.config?.apiUrl || '/api',
            csrfToken: window.BluFox?.config?.csrfToken || window.csrfToken
        };

        if (window.BluFox && window.BluFox.config) {
            Object.assign(window.BluFox.config, this.config);
        }
    }

    async loginWithRoblox() {
        try {
            await this.loginWithRobloxPKCE();
        } catch (error) {
            this.showMessage('Login failed: ' + error.message, 'error');
            this.trackEvent('login_error', { method: 'roblox', error: error.message });
        }
    }

    async loginWithRobloxPKCE() {
        try {
            const codeVerifier = this.generateCodeVerifier();
            const codeChallenge = await this.generateCodeChallenge(codeVerifier);
            const state = this.generateRandomString(32);
            
            sessionStorage.setItem('oauth_code_verifier', codeVerifier);
            sessionStorage.setItem('oauth_state', state);
            sessionStorage.setItem('login_redirect', window.location.pathname);
            
            const authParams = {
                'client_id': this.config.robloxClientId,
                'code_challenge': codeChallenge,
                'code_challenge_method': 'S256',
                'redirect_uri': this.config.robloxRedirectUri,
                'scope': 'openid profile',
                'response_type': 'code',
                'state': state
            };
            
            const baseUrl = 'https://apis.roblox.com/oauth/v1/authorize';
            const queryString = Object.entries(authParams)
                .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
                .join('&');
            
            const authUrl = `${baseUrl}?${queryString}`;
            
            this.trackEvent('pkce_login_attempt', {
                method: 'roblox_pkce',
                client_id: this.config.robloxClientId,
                redirect_uri: this.config.robloxRedirectUri
            });
            
            const loginBtn = document.querySelector('.auth-btn, .login-btn, button[onclick*="loginWithRoblox"]');
            if (loginBtn) {
                const originalContent = loginBtn.innerHTML;
                loginBtn.style.opacity = '0.7';
                loginBtn.style.pointerEvents = 'none';
                loginBtn.innerHTML = '<span>🔐 Redirecting...</span>';
                
                setTimeout(() => {
                    loginBtn.style.opacity = '1';
                    loginBtn.style.pointerEvents = 'auto';
                    loginBtn.innerHTML = originalContent;
                }, 15000);
            }
            
            setTimeout(() => {
                window.location.href = authUrl;
            }, 1000);
            
        } catch (error) {
            this.showMessage('PKCE Login failed: ' + error.message, 'error');
            this.trackEvent('pkce_login_error', {
                method: 'roblox_pkce',
                error: error.message
            });
            throw error;
        }
    }

    handleOAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        const state = urlParams.get('state');
        const error = urlParams.get('error');
        const savedState = sessionStorage.getItem('oauth_state');

        if (error) {
            this.showMessage(`Authentication failed: ${error}`, 'error');
            return;
        }

        if (code && state && state === savedState) {
            this.exchangeCodeForToken(code);
            
            window.history.replaceState({}, document.title, window.location.pathname);
            sessionStorage.removeItem('oauth_state');
        }
    }

    async exchangeCodeForToken(code) {
        try {
            this.showMessage('Completing login...', 'info');
            
            const response = await this.apiRequest('/auth/roblox', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.config.csrfToken
                },
                body: JSON.stringify({
                    code: code,
                    redirect_uri: this.config.robloxRedirectUri
                })
            });
            
            if (response.success) {
                this.user = response.user;
                window.BluFox.user = response.user;
                localStorage.setItem('blufox_user', JSON.stringify(response.user));
                this.updateUserUI(response.user);
                this.showMessage(`Welcome back, ${response.user.display_name}!`, 'success');
                this.trackEvent('login_success', { method: 'roblox' });
                
                const redirectPath = sessionStorage.getItem('login_redirect') || '/';
                sessionStorage.removeItem('login_redirect');
                if (redirectPath !== window.location.pathname) {
                    setTimeout(() => window.location.href = redirectPath, 1500);
                }
            } else {
                throw new Error(response.message || 'Login failed');
            }
        } catch (error) {
            this.showMessage('Login failed. Please try again.', 'error');
            this.trackEvent('login_error', { method: 'roblox', error: error.message });
            throw error;
        }
    }

    async logout() {
        try {
            this.user = null;
            window.BluFox.user = null;
            localStorage.removeItem('blufox_user');
            sessionStorage.clear();
            
            this.trackEvent('logout');
            this.showMessage('Logged out successfully', 'info');
            
            await this.apiRequest('/auth/logout', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.config.csrfToken
                }
            });
            
            this.updateUserUI(null);
            
            if (window.location.pathname.includes('/admin') || window.location.pathname.includes('/dashboard')) {
                window.location.href = '/';
            }
            
        } catch (error) {
            this.updateUserUI(null);
            window.location.href = '/';
        }
    }

    checkUserSession() {
        const savedUser = localStorage.getItem('blufox_user');
        if (savedUser) {
            try {
                this.user = JSON.parse(savedUser);
                window.BluFox.user = this.user;
                this.updateUserUI(this.user);
                
                this.validateSession();
            } catch (e) {
                localStorage.removeItem('blufox_user');
                this.updateUserUI(null);
            }
        }
    }

    async validateSession() {
        try {
            const response = await fetch('/api/auth/status', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            
            if (data.success && data.authenticated) {
                this.user = data.user;
                window.BluFox.user = data.user;
                localStorage.setItem('blufox_user', JSON.stringify(data.user));
                this.updateUserUI(data.user);
            } else {
                this.user = null;
                window.BluFox.user = null;
                localStorage.removeItem('blufox_user');
                this.updateUserUI(null);
            }
        } catch (error) {

        }
    }

    updateUserUI(user = null) {
        const actualUser = user || this.user;
        const loginBtns = document.querySelectorAll('.auth-btn, .login-btn');
        const userDropdowns = document.querySelectorAll('.user-dropdown');
        
        if (actualUser) {
            loginBtns.forEach(btn => {
                if (btn) btn.style.display = 'none';
            });
            
            userDropdowns.forEach(dropdown => {
                if (dropdown) {
                    dropdown.style.display = 'block';
                    
                    const userAvatar = dropdown.querySelector('.user-avatar');
                    const userName = dropdown.querySelector('.user-name');
                    
                    if (userAvatar) {
                        userAvatar.src = actualUser.avatar_url || '/assets/images/team/placeholder-avatar.jpg';
                        userAvatar.alt = actualUser.display_name;
                    }
                    if (userName) {
                        userName.textContent = actualUser.display_name;
                    }
                }
            });
        } else {
            loginBtns.forEach(btn => {
                if (btn) btn.style.display = 'flex';
            });
            
            userDropdowns.forEach(dropdown => {
                if (dropdown) dropdown.style.display = 'none';
            });
        }
    }

    toggleUserDropdown() {
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown) {
            userDropdown.classList.toggle('open');
        }
    }

    generateCodeVerifier() {
        const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
        let result = '';
        const length = 128;
        
        if (window.crypto && window.crypto.getRandomValues) {
            const array = new Uint8Array(length);
            window.crypto.getRandomValues(array);
            for (let i = 0; i < length; i++) {
                result += charset[array[i] % charset.length];
            }
        } else {
            for (let i = 0; i < length; i++) {
                result += charset.charAt(Math.floor(Math.random() * charset.length));
            }
        }
        
        return result;
    }

    async generateCodeChallenge(codeVerifier) {
        if (!window.crypto || !window.crypto.subtle) {
            throw new Error('Web Crypto API not supported - PKCE requires modern browser');
        }
        
        const encoder = new TextEncoder();
        const data = encoder.encode(codeVerifier);
        const digest = await window.crypto.subtle.digest('SHA-256', data);
        
        return this.base64URLEncode(digest);
    }

    base64URLEncode(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        
        return btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=/g, '');
    }

    generateRandomString(length) {
        const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
        let result = '';
        
        if (window.crypto && window.crypto.getRandomValues) {
            const array = new Uint8Array(length);
            window.crypto.getRandomValues(array);
            for (let i = 0; i < length; i++) {
                result += charset[array[i] % charset.length];
            }
        } else {
            for (let i = 0; i < length; i++) {
                result += charset.charAt(Math.floor(Math.random() * charset.length));
            }
        }
        
        return result;
    }

    async apiRequest(endpoint, options = {}) {
        const url = `${this.config.apiUrl}${endpoint}`;
        
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        if (mergedOptions.headers && this.config.csrfToken) {
            mergedOptions.headers['X-CSRF-Token'] = this.config.csrfToken;
        }
        
        const response = await fetch(url, mergedOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return await response.json();
    }

    showMessage(message, type = 'info', duration = 5000) {
        const existingFlash = document.querySelector('.flash-message');
        if (existingFlash) {
            existingFlash.remove();
        }
        
        const flashDiv = document.createElement('div');
        flashDiv.className = `flash-message flash-${type}`;
        flashDiv.id = 'flash-message';
        flashDiv.innerHTML = `
            <div class="flash-content">
                <span class="flash-text">${this.escapeHtml(message)}</span>
                <button class="flash-close" onclick="closeFlashMessage()">&times;</button>
            </div>
        `;
        
        document.body.appendChild(flashDiv);
        
        setTimeout(() => {
            if (flashDiv.parentNode) {
                flashDiv.style.transform = 'translateY(-100%)';
                flashDiv.style.opacity = '0';
                setTimeout(() => flashDiv.remove(), 300);
            }
        }, duration);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    trackEvent(eventName, properties = {}) {
        if (window.BluFox && window.BluFox.analytics && window.BluFox.analytics.track) {
            window.BluFox.analytics.track(eventName, properties);
        }
    }
}

const bluFoxAuth = new BluFoxAuth();

window.loginWithRoblox = () => bluFoxAuth.loginWithRoblox();
window.logout = () => bluFoxAuth.logout();
window.toggleUserDropdown = () => bluFoxAuth.toggleUserDropdown();
window.updateUserUI = (user) => bluFoxAuth.updateUserUI(user);
window.checkAuthStatus = () => bluFoxAuth.validateSession();
window.closeFlashMessage = () => {
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        flashMessage.style.transform = 'translateY(-100%)';
        flashMessage.style.opacity = '0';
        setTimeout(() => flashMessage.remove(), 300);
    }
};

window.BluFoxAuth = bluFoxAuth;