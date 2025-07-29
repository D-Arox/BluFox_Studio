'use strict';

window.BluFox = {
    config: {
        apiUrl: '/api/v1',
        csrfToken: window.csrfToken || '',
        robloxClientId: '6692844983306448575',
        robloxRedirectUri: window.location.origin + '/auth/callback'
    },
    user: null,
    analytics: {
        events: [],
        pageStartTime: Date.now()
    }
};

document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('code') && urlParams.get('state')) {
        console.log('OAuth callback detected');
    }
});

function initializeApp() {
    console.log('BluFox Studio - Initializing...');

    initNavigation();
    initFlashMessage();
    initAnalytics();
    initScrollEffects();
    initAnimations();
    initAuth();

    console.log('BluFox Studio ready!')
}

function initNavigation() {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }
    
    const userToggle = document.querySelector('.user-toggle');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (userToggle && userDropdown) {
        userToggle.addEventListener('click', toggleUserDropdown);
        
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }
    
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const hamburgerLines = document.querySelectorAll('.hamburger-line');
    
    mobileMenu.classList.toggle('open');
    
    if (mobileMenu.classList.contains('open')) {
        hamburgerLines[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        hamburgerLines[1].style.opacity = '0';
        hamburgerLines[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
    } else {
        hamburgerLines[0].style.transform = 'none';
        hamburgerLines[1].style.opacity = '1';
        hamburgerLines[2].style.transform = 'none';
    }
    
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
}

function toggleUserDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');
    if (userDropdown) {
        userDropdown.classList.toggle('open');
    }
}

function initAuth() {
    handleOAuthCallback();
    checkUserSession();
}

function handleOAuthCallback() {
    const urlParams = new URLSearchParams(window.location.search);
    const code = urlParams.get('code');
    const state = urlParams.get('state');
    const error = urlParams.get('error');
    const savedState = sessionStorage.getItem('oauth_state');

    if (error) {
        console.error('OAuth Error:', error);
        showFlashMessage(`Authentication failed: ${error}`, 'error');
        return;
    }

    if (code && state && state === savedState) {
        console.log('OAuth callback received');
        exchangeCodeForToken(code);
        
        window.history.replaceState({}, document.title, window.location.pathname);
        sessionStorage.removeItem('oauth_state');
    }
}

async function exchangeCodeForToken(code) {
    try {
        showFlashMessage('Completing login...', 'info');
        
        const response = await apiRequest('/auth/roblox', {
            method: 'POST',
            body: JSON.stringify({
                code: code,
                redirect_uri: BluFox.config.robloxRedirectUri
            })
        });
        
        if (response.success) {
            BluFox.user = response.user;
            localStorage.setItem('blufox_user', JSON.stringify(response.user));
            updateUserUI();
            showFlashMessage(`Welcome back, ${response.user.display_name}!`, 'success');
            trackEvent('login_success', { method: 'roblox' });
        } else {
            throw new Error(response.message || 'Login failed');
        }
    } catch (error) {
        console.error('Login error:', error);
        showFlashMessage('Login failed. Please try again.', 'error');
        trackEvent('login_error', { method: 'roblox', error: error.message });
    }
}

function checkUserSession() {
    const savedUser = localStorage.getItem('blufox_user');
    if (savedUser) {
        try {
            BluFox.user = JSON.parse(savedUser);
            updateUserUI();
        } catch (e) {
            localStorage.removeItem('blufox_user');
        }
    }
}

function updateUserUI(user = null) {
    const loginBtns = document.querySelectorAll('.auth-btn, .login-btn');
    const userDropdowns = document.querySelectorAll('.user-dropdown');
    
    if (user) {
        loginBtns.forEach(btn => {
            if (btn) btn.style.display = 'none';
        });
        
        userDropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.style.display = 'block';
                
                const userAvatar = dropdown.querySelector('.user-avatar');
                const userName = dropdown.querySelector('.user-name');
                
                if (userAvatar) {
                    userAvatar.src = user.avatar_url || '/assets/images/team/placeholder-avatar.jpg';
                    userAvatar.alt = user.display_name;
                }
                if (userName) {
                    userName.textContent = user.display_name;
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

function loginWithRoblox() {
    try {
        const state = generateRandomString(32);
        sessionStorage.setItem('oauth_state', state);
        
        sessionStorage.setItem('login_redirect', window.location.pathname);
        
        const clientId = document.querySelector('meta[name="roblox-client-id"]')?.content || 
                        window.BluFox?.config?.robloxClientId;
        
        if (!clientId) {
            console.error('Roblox Client ID not found');
            showFlashMessage('Configuration error. Please contact support.', 'error');
            return;
        }
        
        const params = new URLSearchParams({
            client_id: clientId,
            redirect_uri: window.location.origin + '/auth/callback',
            scope: 'openid profile',
            response_type: 'code',
            state: state
        });
        
        const authUrl = `https://apis.roblox.com/oauth/v1/authorize?${params.toString()}`;
        
        if (typeof trackEvent === 'function') {
            trackEvent('login_attempt', { method: 'roblox' });
        }
        
        const loginBtn = document.querySelector('.auth-btn, .login-btn');
        if (loginBtn) {
            loginBtn.style.opacity = '0.7';
            loginBtn.style.pointerEvents = 'none';
            const originalText = loginBtn.innerHTML;
            loginBtn.innerHTML = '<span>Redirecting...</span>';
            
            setTimeout(() => {
                if (loginBtn) {
                    loginBtn.style.opacity = '1';
                    loginBtn.style.pointerEvents = 'auto';
                    loginBtn.innerHTML = originalText;
                }
            }, 5000);
        }
        
        console.log('Redirecting to Roblox OAuth:', authUrl);
        
        window.location.href = authUrl;
        
    } catch (error) {
        console.error('Login error:', error);
        showFlashMessage('Login failed. Please try again.', 'error');
        
        if (typeof trackEvent === 'function') {
            trackEvent('login_error', { method: 'roblox', error: error.message });
        }
    }
}

function logout() {
    try {
        if (window.BluFox) {
            window.BluFox.user = null;
        }
        localStorage.removeItem('blufox_user');
        sessionStorage.clear();
        
        if (typeof trackEvent === 'function') {
            trackEvent('logout');
        }
        
        showFlashMessage('Logged out successfully', 'info');
        
        window.location.href = '/auth/logout';
        
    } catch (error) {
        console.error('Logout error:', error);
        window.location.href = '/auth/logout';
    }
}

function initFlashMessages() {
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        setTimeout(() => {
            closeFlashMessage();
        }, 5000);
    }
}

function closeFlashMessage() {
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        flashMessage.style.transform = 'translateY(-100%)';
        flashMessage.style.opacity = '0';
        setTimeout(() => {
            flashMessage.remove();
        }, 300);
    }
}

function showFlashMessage(message, type = 'info', duration = 5000) {
    const existingFlash = document.querySelector('.flash-message');
    if (existingFlash) {
        existingFlash.remove();
    }
    
    const flashDiv = document.createElement('div');
    flashDiv.className = `flash-message flash-${type}`;
    flashDiv.innerHTML = `
        <div class="container">
            <span class="flash-text">${message}</span>
            <button class="flash-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
        </div>
    `;
    
    document.body.insertBefore(flashDiv, document.body.firstChild);
    
    if (duration > 0) {
        setTimeout(() => {
            if (flashDiv && flashDiv.parentNode) {
                flashDiv.remove();
            }
        }, duration);
    }
}

function initAnalytics() {
    trackEvent('page_view', {
        page: window.location.pathname,
        title: document.title,
        timestamp: Date.now()
    });
    
    let maxScroll = 0;
    window.addEventListener('scroll', debounce(() => {
        const scrollPercent = Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100);
        if (scrollPercent > maxScroll) {
            maxScroll = scrollPercent;
            if (maxScroll % 25 === 0) {
                trackEvent('scroll_depth', { percent: maxScroll });
            }
        }
    }, 100));
    
    window.addEventListener('beforeunload', () => {
        const timeOnPage = Date.now() - BluFox.analytics.pageStartTime;
        trackEvent('time_on_page', { duration: timeOnPage });
        
        sendAnalytics();
    });
}

function trackEvent(eventType, eventData = {}) {
    BluFox.analytics.events.push({
        type: eventType,
        data: eventData,
        timestamp: Date.now(),
        url: window.location.href,
        userAgent: navigator.userAgent
    });
    
    console.log(`📊 Event tracked: ${eventType}`, eventData);
}

function sendAnalytics() {
    if (BluFox.analytics.events.length === 0) return;
    
    const data = {
        events: BluFox.analytics.events,
        session_id: getSessionId(),
        csrf_token: BluFox.config.csrfToken
    };
    
    if (navigator.sendBeacon) {
        navigator.sendBeacon(`${BluFox.config.apiUrl}/analytics`, JSON.stringify(data));
    } else {
        fetch(`${BluFox.config.apiUrl}/analytics`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': BluFox.config.csrfToken
            },
            body: JSON.stringify(data)
        }).catch(console.error);
    }
    
    BluFox.analytics.events = [];
}

function initScrollEffects() {
    const navbar = document.querySelector('.main-nav');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(15, 15, 35, 0.95)';
            navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
        } else {
            navbar.style.background = 'rgba(15, 15, 35, 0.8)';
            navbar.style.boxShadow = 'none';
        }
    });
    
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                const sectionName = entry.target.id || entry.target.className;
                trackEvent('section_view', { section: sectionName });
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.card, .project-card, .service-card, section').forEach(el => {
        observer.observe(el);
    });
}

function initAnimations() {
    if (!document.querySelector('#animation-styles')) {
        const style = document.createElement('style');
        style.id = 'animation-styles';
        style.textContent = `
            .animate-in {
                animation: slideInUp 0.6s ease-out forwards;
            }
            
            .project-card,
            .service-card,
            .card {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s ease-out;
            }
            
            .project-card.animate-in,
            .service-card.animate-in,
            .card.animate-in {
                opacity: 1;
                transform: translateY(0);
            }
            
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    }
}

async function apiRequest(endpoint, options = {}) {
    const url = `${BluFox.config.apiUrl}${endpoint}`;
    const config = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': BluFox.config.csrfToken,
            ...options.headers
        },
        ...options
    };
    
    try {
        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        }
        
        return await response.text();
    } catch (error) {
        console.error('API Request failed:', error);
        throw error;
    }
}

async function handleContactForm(formData) {
    try {
        showFlashMessage('Sending message...', 'info');
        
        const response = await apiRequest('/contact', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        if (response.success) {
            showFlashMessage('Message sent successfully! We\'ll get back to you soon.', 'success');
            trackEvent('contact_form_submit', { success: true });
            return true;
        } else {
            throw new Error(response.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Contact form error:', error);
        showFlashMessage('Failed to send message. Please try again.', 'error');
        trackEvent('contact_form_submit', { success: false, error: error.message });
        return false;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function generateRandomString(length) {
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

function getSessionId() {
    let sessionId = sessionStorage.getItem('session_id');
    if (!sessionId) {
        sessionId = generateRandomString(32);
        sessionStorage.setItem('session_id', sessionId);
    }
    return sessionId;
}

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showFlashMessage('Copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Failed to copy:', err);
            showFlashMessage('Failed to copy to clipboard', 'error');
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showFlashMessage('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy:', err);
            showFlashMessage('Failed to copy to clipboard', 'error');
        }
        document.body.removeChild(textArea);
    }
}

function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        const value = input.value.trim();
        const errorElement = input.parentNode.querySelector('.field-error');
        
        if (errorElement) {
            errorElement.remove();
        }
        input.classList.remove('error');
        
        if (!value) {
            showFieldError(input, 'This field is required');
            isValid = false;
            return;
        }
        
        if (input.type === 'email' && !isValidEmail(value)) {
            showFieldError(input, 'Please enter a valid email address');
            isValid = false;
            return;
        }
        
        const minLength = input.getAttribute('minlength');
        if (minLength && value.length < parseInt(minLength)) {
            showFieldError(input, `Must be at least ${minLength} characters`);
            isValid = false;
            return;
        }
    });
    
    return isValid;
}

function showFieldError(input, message) {
    input.classList.add('error');
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    input.parentNode.appendChild(errorElement);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showLoading(element, text = 'Loading...') {
    element.disabled = true;
    element.classList.add('loading');
    element.setAttribute('data-original-text', element.textContent);
    element.textContent = text;
}

function hideLoading(element) {
    element.disabled = false;
    element.classList.remove('loading');
    const originalText = element.getAttribute('data-original-text');
    if (originalText) {
        element.textContent = originalText;
        element.removeAttribute('data-original-text');
    }
}

function checkAuthStatus() {
    fetch('/api/auth/status', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.authenticated) {
            if (window.BluFox) {
                window.BluFox.user = data.user;
            }
            updateUserUI(data.user);
        } else {
            localStorage.removeItem('blufox_user');
            if (window.BluFox) {
                window.BluFox.user = null;
            }
        }
    })
    .catch(error => {
        console.log('Auth status check failed:', error);
    });
}

window.toggleMobileMenu = toggleMobileMenu;
window.toggleUserDropdown = toggleUserDropdown;
window.closeFlashMessage = closeFlashMessage;
window.loginWithRoblox = loginWithRoblox;
window.logout = logout;
window.handleContactForm = handleContactForm;
window.copyToClipboard = copyToClipboard;
window.showFlashMessage = showFlashMessage;
window.generateRandomString = generateRandomString;
window.checkAuthStatus = checkAuthStatus;
window.updateUserUI = updateUserUI;

window.BluFox.utils = {
    escapeHtml,
    debounce,
    generateRandomString,
    formatNumber,
    copyToClipboard,
    validateForm,
    showLoading,
    hideLoading
};

window.BluFox.api = {
    request: apiRequest
};

window.BluFox.analytics = {
    track: trackEvent,
    send: sendAnalytics
};