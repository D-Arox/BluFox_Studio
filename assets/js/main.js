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
            updateUserUI(response.user);
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
            updateUserUI(BluFox.user);
        } catch (e) {
            localStorage.removeItem('blufox_user');
        }
    }
}

function updateUserUI(user = null) {
    const actualUser = user || BluFox.user;
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

async function loginWithRobloxPKCE() {
    try {
        const codeVerifier = generateCodeVerifier();
        const codeChallenge = await generateCodeChallenge(codeVerifier);
        const state = generateRandomString(32);
        
        
        sessionStorage.setItem('oauth_code_verifier', codeVerifier);
        sessionStorage.setItem('oauth_state', state);
        sessionStorage.setItem('login_redirect', window.location.pathname);
        
        const clientId = window.robloxClientId || window.BluFox?.config?.robloxClientId || '6692844983306448575';
        const redirectUri = window.location.origin + '/auth/callback';
        
        const authParams = {
            'client_id': clientId,
            'code_challenge': codeChallenge,
            'code_challenge_method': 'S256',
            'redirect_uri': redirectUri,
            'scope': 'openid profile',
            'response_type': 'code',
            'state': state
        };
        
        console.log('📝 Authorization parameters:', authParams);
        
        const baseUrl = 'https://apis.roblox.com/oauth/v1/authorize';
        const queryString = Object.entries(authParams)
            .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
            .join('&');
        
        const authUrl = `${baseUrl}?${queryString}`;
        const urlObj = new URL(authUrl);
  
        safeTrackEvent('pkce_login_attempt', {
            method: 'roblox_pkce',
            client_id: clientId,
            redirect_uri: redirectUri,
            challenge_method: 'S256'
        });
        
        const loginBtn = document.querySelector('.auth-btn, .login-btn, button[onclick*="loginWithRoblox"]');
        let originalContent = null;
        
        if (loginBtn) {
            originalContent = loginBtn.innerHTML;
            loginBtn.style.opacity = '0.7';
            loginBtn.style.pointerEvents = 'none';
            loginBtn.innerHTML = '<span>🔐 Redirecting with PKCE...</span>';
        }
        
        setTimeout(() => {
            window.location.href = authUrl;
        }, 1000);
        
        setTimeout(() => {
            if (loginBtn && originalContent) {
                loginBtn.style.opacity = '1';
                loginBtn.style.pointerEvents = 'auto';
                loginBtn.innerHTML = originalContent;
            }
        }, 15000);
        
    } catch (error) {
        showFlashMessage('PKCE Login failed: ' + error.message, 'error');
        
        safeTrackEvent('pkce_login_error', {
            method: 'roblox_pkce',
            error: error.message
        });
    }
}

function loginWithRobloxLegacy() {    
    try {
        const state = generateRandomString(32);
        sessionStorage.setItem('oauth_state', state);
        sessionStorage.setItem('login_redirect', window.location.pathname);
        
        const clientId = window.robloxClientId || window.BluFox?.config?.robloxClientId || '6692844983306448575';
        const redirectUri = window.location.origin + '/auth/callback';
        
        const authParams = {
            'client_id': clientId,
            'redirect_uri': redirectUri,
            'scope': 'openid profile',
            'response_type': 'code',
            'state': state
        };
        
        const baseUrl = 'https://apis.roblox.com/oauth/v1/authorize';
        const queryString = Object.entries(authParams)
            .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
            .join('&');
        
        const authUrl = `${baseUrl}?${queryString}`;
        safeTrackEvent('legacy_login_attempt', {
            method: 'roblox_legacy',
            client_id: clientId
        });
        
        window.location.href = authUrl;
    } catch (error) {
        showFlashMessage('Legacy login failed: ' + error.message, 'error');
    }
}

function loginWithRoblox() {
    loginWithRobloxPKCE();
}

function setupOAuthConfig() {
    const metaTags = {
        clientId: document.querySelector('meta[name="roblox-client-id"]')?.content,
        redirectUri: document.querySelector('meta[name="roblox-redirect-uri"]')?.content,
        siteUrl: document.querySelector('meta[name="site-url"]')?.content
    };
    
    if (window.BluFox && window.BluFox.config) {
        if (metaTags.clientId) {
            window.BluFox.config.robloxClientId = metaTags.clientId;
        }
        if (metaTags.redirectUri) {
            window.BluFox.config.robloxRedirectUri = metaTags.redirectUri;
        }
        
    }
    
    window.robloxClientId = metaTags.clientId || window.BluFox?.config?.robloxClientId || '6692844983306448575';
    window.robloxRedirectUri = metaTags.redirectUri || window.BluFox?.config?.robloxRedirectUri || (window.location.origin + '/auth/callback');
}

function testBothOAuthMethods() {
    console.log('🧪 Testing both OAuth methods...');
    
    const clientId = '6692844983306448575';
    const redirectUri = window.location.origin + '/auth/callback';
    const state = generateRandomString(32);
    
    console.log('\n🔐 PKCE OAuth URL:');
    generateCodeChallenge(generateCodeVerifier()).then(challenge => {
        const pkceUrl = `https://apis.roblox.com/oauth/v1/authorize?` +
            `client_id=${encodeURIComponent(clientId)}&` +
            `code_challenge=${encodeURIComponent(challenge)}&` +
            `code_challenge_method=S256&` +
            `redirect_uri=${encodeURIComponent(redirectUri)}&` +
            `scope=${encodeURIComponent('openid profile')}&` +
            `response_type=code&` +
            `state=${encodeURIComponent(state)}`;
        
        console.log(pkceUrl);
        
        console.log('\n🔄 Legacy OAuth URL:');
        const legacyUrl = `https://apis.roblox.com/oauth/v1/authorize?` +
            `client_id=${encodeURIComponent(clientId)}&` +
            `redirect_uri=${encodeURIComponent(redirectUri)}&` +
            `scope=${encodeURIComponent('openid profile')}&` +
            `response_type=code&` +
            `state=${encodeURIComponent(state)}`;
        
        console.log(legacyUrl);
        
        // Make them available for manual testing
        window.testPKCEUrl = () => window.location.href = pkceUrl;
        window.testLegacyUrl = () => window.location.href = legacyUrl;
        
        console.log('\n🚀 Run testPKCEUrl() to test PKCE method');
        console.log('🚀 Run testLegacyUrl() to test legacy method');
    });
}


function logout() {
    try {
        if (window.BluFox) {
            window.BluFox.user = null;
        }
        localStorage.removeItem('blufox_user');
        sessionStorage.clear();
        
        safeTrackEvent('logout');
        
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
    if (!window.BluFox.analytics) {
        window.BluFox.analytics = {
            events: [],
            pageStartTime: Date.now()
        };
    }
    
    if (!window.BluFox.analytics.events) {
        window.BluFox.analytics.events = [];
    }
    
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

function safeTrackEvent(eventType, eventData = {}) {
    try {
        if (!window.BluFox.analytics) {
            window.BluFox.analytics = { events: [], pageStartTime: Date.now() };
        }
        if (!window.BluFox.analytics.events) {
            window.BluFox.analytics.events = [];
        }
        trackEvent(eventType, eventData);
    } catch (error) {
        console.warn('Failed to track event:', eventType, error);
    }
}

function trackEvent(eventType, eventData = {}) {
    if (!window.BluFox.analytics) {
        window.BluFox.analytics = { events: [], pageStartTime: Date.now() };
    }
    
    if (!window.BluFox.analytics.events) {
        window.BluFox.analytics.events = [];
    }
    
    window.BluFox.analytics.events.push({
        type: eventType,
        data: eventData,
        timestamp: Date.now(),
        url: window.location.href,
        userAgent: navigator.userAgent
    });
    
    console.log(`📊 Event tracked: ${eventType}`, eventData);
}

function sendAnalytics() {
    if (!window.BluFox.analytics || !window.BluFox.analytics.events || window.BluFox.analytics.events.length === 0) {
        return;
    }
    
    const data = {
        events: window.BluFox.analytics.events,
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
    
    window.BluFox.analytics.events = [];
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
                safeTrackEvent('section_view', { section: sectionName });
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
            safeTrackEvent('contact_form_submit', { success: true });
            return true;
        } else {
            throw new Error(response.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Contact form error:', error);
        showFlashMessage('Failed to send message. Please try again.', 'error');
        safeTrackEvent('contact_form_submit', { success: false, error: error.message });
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

function generateCodeVerifier() {
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

async function generateCodeChallenge(codeVerifier) {
    if (!window.crypto || !window.crypto.subtle) {
        throw new Error('Web Crypto API not supported - PKCE requires modern browser');
    }
    
    const encoder = new TextEncoder();
    const data = encoder.encode(codeVerifier);
    const digest = await window.crypto.subtle.digest('SHA-256', data);
    
    return base64URLEncode(digest);
}

function base64URLEncode(buffer) {
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
            updateUserUI(null);
        }
    })
    .catch(error => {
        console.log('Auth status check failed:', error);
        updateUserUI(null);
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
window.safeTrackEvent = safeTrackEvent;
window.setupOAuthConfig = setupOAuthConfig;
window.loginWithRobloxPKCE = loginWithRobloxPKCE;
window.loginWithRobloxLegacy = loginWithRobloxLegacy;
window.testBothOAuthMethods = testBothOAuthMethods;
window.generateCodeVerifier = generateCodeVerifier;
window.generateCodeChallenge = generateCodeChallenge;

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

if (!window.BluFox.analytics.track) {
    window.BluFox.analytics.track = safeTrackEvent;
}
if (!window.BluFox.analytics.send) {
    window.BluFox.analytics.send = sendAnalytics;
}

document.addEventListener('DOMContentLoaded', function() {
    setupOAuthConfig();
    
    // Test configuration in development
    if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
        testOAuthConfig();
    }
});

function debugLoginWithRoblox() {
    console.log('🔬 DEBUG MODE: Enhanced OAuth login debugging');
    
    // Run configuration test first
    if (!testOAuthConfig()) {
        showFlashMessage('OAuth configuration test failed. Check console for details.', 'error');
        return;
    }
    
    // Proceed with normal login
    loginWithRoblox();
}

window.debugLoginWithRoblox = debugLoginWithRoblox;