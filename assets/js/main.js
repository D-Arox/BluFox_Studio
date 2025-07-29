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

function updateUserUI() {
    const loginBtn = document.querySelector('.auth-btn');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (BluFox.user && loginBtn && userDropdown) {
        loginBtn.style.display = 'none';
        userDropdown.style.display = 'block';
        
        const userAvatar = userDropdown.querySelector('.user-avatar');
        const userName = userDropdown.querySelector('.user-name');
        
        if (userAvatar) userAvatar.src = BluFox.user.avatar_url || '/assets/images/team/placeholder-avatar.jpg';
        if (userName) userName.textContent = BluFox.user.display_name;
    }
}

function loginWithRoblox() {
    const state = generateRandomString(32);
    sessionStorage.setItem('oauth_state', state);
    
    const params = new URLSearchParams({
        client_id: BluFox.config.robloxClientId,
        redirect_uri: BluFox.config.robloxRedirectUri,
        scope: 'openid profile',
        response_type: 'code',
        state: state
    });
    
    const authUrl = `https://apis.roblox.com/oauth/v1/authorize?${params.toString()}`;
    
    trackEvent('login_attempt', { method: 'roblox' });
    
    window.location.href = authUrl;
}

function logout() {
    BluFox.user = null;
    localStorage.removeItem('blufox_user');
    
    const loginBtn = document.querySelector('.auth-btn');
    const userDropdown = document.querySelector('.user-dropdown');
    
    if (loginBtn && userDropdown) {
        loginBtn.style.display = 'flex';
        userDropdown.style.display = 'none';
    }
    
    showFlashMessage('Logged out successfully', 'info');
    trackEvent('logout');
    
    if (window.location.pathname.startsWith('/dashboard') || window.location.pathname.startsWith('/admin')) {
        window.location.href = '/';
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

function showFlashMessage(message, type = 'info') {
    const existing = document.getElementById('flash-message');
    if (existing) {
        existing.remove();
    }
    
    const flashHtml = `
        <div class="flash-message flash-${type}" id="flash-message">
            <div class="container">
                <span class="flash-text">${escapeHtml(message)}</span>
                <button class="flash-close" onclick="closeFlashMessage()">&times;</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('afterbegin', flashHtml);
    
    setTimeout(() => {
        closeFlashMessage();
    }, 5000);
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
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
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

window.toggleMobileMenu = toggleMobileMenu;
window.toggleUserDropdown = toggleUserDropdown;
window.closeFlashMessage = closeFlashMessage;
window.loginWithRoblox = loginWithRoblox;
window.logout = logout;
window.handleContactForm = handleContactForm;
window.copyToClipboard = copyToClipboard;
window.showFlashMessage = showFlashMessage;

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