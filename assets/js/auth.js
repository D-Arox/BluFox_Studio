/**
 * BluFox Studio - Authentication Handler
 * Handles Roblox OAuth login flow
 */

class AuthHandler {
    constructor() {
        this.apiBase = '/api/v1';
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.bindEvents();
            this.handleOAuthCallback();
        });
    }

    bindEvents() {
        const robloxLoginBtn = document.getElementById('robloxLoginBtn');
        if (robloxLoginBtn) {
            robloxLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.initiateRobloxLogin();
            });
        }
    }

    /**
     * Initiate Roblox OAuth login
     */
    async initiateRobloxLogin() {
        const robloxLoginBtn = document.getElementById('robloxLoginBtn');
        const rememberMeCheckbox = document.getElementById('remember_me');
        
        if (!robloxLoginBtn) return;

        try {
            // Disable button and show loading state
            robloxLoginBtn.disabled = true;
            robloxLoginBtn.innerHTML = '<i class="loading-spinner"></i> Connecting to Roblox...';

            // Get authorization URL from API
            const response = await this.makeRequest('POST', `${this.apiBase}/auth/roblox/url`, {});
            
            if (!response.success) {
                throw new Error(response.message || 'Failed to get authorization URL');
            }

            // Store remember me preference
            if (rememberMeCheckbox && rememberMeCheckbox.checked) {
                localStorage.setItem('remember_me', 'true');
            }

            // Store state for security
            if (response.data.state) {
                sessionStorage.setItem('oauth_state', response.data.state);
            }

            // Redirect to Roblox OAuth
            window.location.href = response.data.authorization_url;

        } catch (error) {
            console.error('Roblox login error:', error);
            this.showError('Failed to initiate login: ' + error.message);
            
            // Reset button
            robloxLoginBtn.disabled = false;
            robloxLoginBtn.innerHTML = '<img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="roblox-login-icon"> Login with Roblox';
        }
    }

    /**
     * Handle OAuth callback from Roblox
     */
    async handleOAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        const code = urlParams.get('code');
        const state = urlParams.get('state');
        const error = urlParams.get('error');

        // Check if this is an OAuth callback
        if (!code && !error) return;

        // Handle OAuth error
        if (error) {
            const errorDescription = urlParams.get('error_description') || 'OAuth authentication failed';
            this.showError(`Login failed: ${errorDescription}`);
            this.cleanupOAuth();
            return;
        }

        try {
            // Verify state parameter for security
            const storedState = sessionStorage.getItem('oauth_state');
            if (state && storedState && state !== storedState) {
                throw new Error('Invalid state parameter. Possible CSRF attack.');
            }

            // Show processing message
            this.showInfo('Processing login...');

            // Get remember me preference
            const rememberMe = localStorage.getItem('remember_me') === 'true';

            // Exchange code for tokens
            const response = await this.makeRequest('POST', `${this.apiBase}/auth/roblox/callback`, {
                code: code,
                state: state,
                remember_me: rememberMe
            });

            if (!response.success) {
                throw new Error(response.message || 'Authentication failed');
            }

            // Store tokens if provided
            if (response.data.access_token) {
                sessionStorage.setItem('access_token', response.data.access_token);
            }
            if (response.data.refresh_token) {
                localStorage.setItem('refresh_token', response.data.refresh_token);
            }

            // Show success and redirect
            this.showSuccess('Login successful! Redirecting...');
            
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1500);

        } catch (error) {
            console.error('OAuth callback error:', error);
            this.showError('Login failed: ' + error.message);
        } finally {
            this.cleanupOAuth();
        }
    }

    /**
     * Clean up OAuth-related storage
     */
    cleanupOAuth() {
        sessionStorage.removeItem('oauth_state');
        localStorage.removeItem('remember_me');
        
        // Clean URL without page reload
        if (window.history && window.history.replaceState) {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }

    /**
     * Make API request with proper error handling
     */
    async makeRequest(method, url, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            options.headers['X-CSRF-Token'] = csrfToken;
        }

        // Add auth token if available
        const accessToken = sessionStorage.getItem('access_token');
        if (accessToken) {
            options.headers['Authorization'] = `Bearer ${accessToken}`;
        }

        // Add body for POST/PUT requests
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            
            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            // Handle HTTP errors
            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}: ${response.statusText}`);
            }

            return result;

        } catch (error) {
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Network error. Please check your connection.');
            }
            throw error;
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showAlert(message, 'error');
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showAlert(message, 'success');
    }

    /**
     * Show info message
     */
    showInfo(message) {
        this.showAlert(message, 'info');
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert.dynamic');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} dynamic`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            <strong>${type === 'error' ? 'Error:' : type === 'success' ? 'Success:' : 'Info:'}</strong>
            ${this.escapeHtml(message)}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
        `;

        // Insert after auth-header
        const authHeader = document.querySelector('.auth-header');
        if (authHeader) {
            authHeader.insertAdjacentElement('afterend', alert);
        } else {
            // Fallback: insert at top of auth-card
            const authCard = document.querySelector('.auth-card');
            if (authCard) {
                authCard.insertBefore(alert, authCard.firstChild);
            }
        }

        // Auto-remove after 5 seconds for non-error messages
        if (type !== 'error') {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, (m) => map[m]);
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            // Call logout API
            await this.makeRequest('POST', `${this.apiBase}/auth/logout`);
        } catch (error) {
            console.error('Logout API error:', error);
        } finally {
            // Clear all auth data
            sessionStorage.clear();
            localStorage.removeItem('refresh_token');
            
            // Redirect to login
            window.location.href = '/auth/login';
        }
    }

    /**
     * Refresh access token using refresh token
     */
    async refreshToken() {
        const refreshToken = localStorage.getItem('refresh_token');
        if (!refreshToken) {
            throw new Error('No refresh token available');
        }

        try {
            const response = await this.makeRequest('POST', `${this.apiBase}/auth/refresh`, {
                refresh_token: refreshToken
            });

            if (response.success && response.data.access_token) {
                sessionStorage.setItem('access_token', response.data.access_token);
                return response.data.access_token;
            } else {
                throw new Error('Failed to refresh token');
            }
        } catch (error) {
            // Clear invalid tokens
            localStorage.removeItem('refresh_token');
            sessionStorage.removeItem('access_token');
            throw error;
        }
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!sessionStorage.getItem('access_token') || !!localStorage.getItem('refresh_token');
    }
}

// Initialize auth handler
const authHandler = new AuthHandler();

// Export for global access
window.AuthHandler = authHandler;