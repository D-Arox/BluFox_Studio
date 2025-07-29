window.BluFox = window.BluFox || {
    config: {},
    user: null,
    analytics: {
        events: [],
        pageStartTime: Date.now()
    }
};

function toggleMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const hamburger = document.querySelector('.hamburger');
    const body = document.body;
    
    if (mobileMenu && hamburger) {
        mobileMenu.classList.toggle('active');
        hamburger.classList.toggle('active');
        body.classList.toggle('menu-open');
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
    flashDiv.id = 'flash-message';
    flashDiv.innerHTML = `
        <div class="flash-content">
            <span class="flash-text">${escapeHtml(message)}</span>
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

async function handleContactForm(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    if (!validateForm(form)) {
        showFlashMessage('Please fill in all required fields correctly.', 'error');
        return false;
    }
    
    const formData = new FormData(form);
    
    showLoading(submitBtn, 'Sending...');
    
    try {
        const response = await apiRequest('/contact', {
            method: 'POST',
            body: formData
        });
        
        if (response.success) {
            form.reset();
            showFlashMessage('Message sent successfully! We\'ll get back to you soon.', 'success');
            safeTrackEvent('contact_form_submit', { success: true });
            return true;
        } else {
            throw new Error(response.message || 'Failed to send message');
        }
    } catch (error) {
        showFlashMessage('Failed to send message. Please try again.', 'error');
        safeTrackEvent('contact_form_submit', { success: false, error: error.message });
        return false;
    } finally {
        hideLoading(submitBtn);
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

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

function copyToClipboard(text, successMessage = 'Copied to clipboard!') {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showFlashMessage(successMessage, 'success', 2000);
        }).catch(() => {
            fallbackCopyToClipboard(text, successMessage);
        });
    } else {
        fallbackCopyToClipboard(text, successMessage);
    }
}

function fallbackCopyToClipboard(text, successMessage) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showFlashMessage(successMessage, 'success', 2000);
    } catch (err) {
        showFlashMessage('Failed to copy text', 'error');
    }
    
    document.body.removeChild(textArea);
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        const value = field.value.trim();
        
        if (!value) {
            isValid = false;
            field.classList.add('error');
        } else {
            field.classList.remove('error');
            
            if (field.type === 'email' && !isValidEmail(value)) {
                isValid = false;
                field.classList.add('error');
            }
        }
    });
    
    return isValid;
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

async function apiRequest(endpoint, options = {}) {
    const apiUrl = window.BluFox?.config?.apiUrl || '/api';
    const url = `${apiUrl}${endpoint}`;
    
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        },
        credentials: 'include'
    };
    
    if (options.body && typeof options.body === 'string') {
        defaultOptions.headers['Content-Type'] = 'application/json';
    }
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    const csrfToken = window.BluFox?.config?.csrfToken || window.csrfToken;
    if (csrfToken && mergedOptions.headers) {
        mergedOptions.headers['X-CSRF-Token'] = csrfToken;
    }
    
    const response = await fetch(url, mergedOptions);
    
    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    return await response.json();
}

function safeTrackEvent(eventName, properties = {}) {
    try {
        if (window.BluFox && window.BluFox.analytics) {
            window.BluFox.analytics.events.push({
                event: eventName,
                properties: {
                    ...properties,
                    timestamp: Date.now(),
                    page: window.location.pathname,
                    referrer: document.referrer || 'direct'
                }
            });
            
            if (window.BluFox.analytics.events.length >= 5) {
                sendAnalytics();
            }
        }
    } catch (error) {

    }
}

function sendAnalytics() {
    try {
        if (window.BluFox && window.BluFox.analytics && window.BluFox.analytics.events.length > 0) {
            const events = [...window.BluFox.analytics.events];
            window.BluFox.analytics.events = [];
            
            const existingEvents = JSON.parse(sessionStorage.getItem('blufox_analytics') || '[]');
            existingEvents.push(...events);
            sessionStorage.setItem('blufox_analytics', JSON.stringify(existingEvents.slice(-100))); // Keep last 100 events
        }
    } catch (error) {

    }
}

function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function initPageInteractions() {
    document.addEventListener('click', (e) => {
        const mobileMenu = document.querySelector('.mobile-menu');
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.nav');
        
        if (mobileMenu && mobileMenu.classList.contains('active') && 
            !nav.contains(e.target)) {
            toggleMobileMenu();
        }
    });
    
    document.addEventListener('click', (e) => {
        const userDropdown = document.querySelector('.user-dropdown');
        if (userDropdown && userDropdown.classList.contains('open') && 
            !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('open');
        }
    });
    
    document.querySelectorAll('form[data-contact-form]').forEach(form => {
        form.addEventListener('submit', handleContactForm);
    });
    
    initFlashMessages();
    initSmoothScrolling();
}

function initPerformanceMonitoring() {
    window.addEventListener('load', () => {
        setTimeout(() => {
            const loadTime = Date.now() - window.BluFox.analytics.pageStartTime;
            safeTrackEvent('page_load', { 
                load_time: loadTime,
                page: window.location.pathname 
            });
        }, 100);
    });
    
    window.addEventListener('beforeunload', () => {
        sendAnalytics();
    });
    
    setInterval(sendAnalytics, 30000);
}

function initFeatureDetection() {
    const features = {
        fetch: typeof fetch !== 'undefined',
        crypto: !!(window.crypto && window.crypto.subtle),
        localStorage: (() => {
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
                return true;
            } catch (e) {
                return false;
            }
        })(),
        webp: (() => {
            const canvas = document.createElement('canvas');
            return canvas.toDataURL('image/webp').indexOf('webp') > -1;
        })()
    };
    
    window.BluFox.features = features;
    
    document.body.classList.add(features.webp ? 'webp' : 'no-webp');
    document.body.classList.add(features.crypto ? 'crypto' : 'no-crypto');
}

function initAnimationObserver() {
    if ('IntersectionObserver' in window) {
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    animationObserver.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        document.querySelectorAll('.fade-in, .slide-up, .slide-in').forEach(el => {
            animationObserver.observe(el);
        });
    }
}

function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    
                    if (src) {
                        img.src = src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            img.classList.add('loaded');
        });
    }
}

function initThemeHandler() {
    const themeToggle = document.querySelector('[data-theme-toggle]');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
    
    let currentTheme = localStorage.getItem('blufox-theme') || 
                      (prefersDark.matches ? 'dark' : 'light');
    
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('blufox-theme', theme);
        currentTheme = theme;
        
        if (themeToggle) {
            themeToggle.setAttribute('aria-label', 
                theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        }
        
        safeTrackEvent('theme_change', { theme });
    }
    
    setTheme(currentTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }
    
    prefersDark.addEventListener('change', (e) => {
        if (!localStorage.getItem('blufox-theme')) {
            setTheme(e.matches ? 'dark' : 'light');
        }
    });
}

function initErrorHandling() {
    window.addEventListener('error', (e) => {
        safeTrackEvent('javascript_error', {
            message: e.message,
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            stack: e.error?.stack
        });
    });
    
    window.addEventListener('unhandledrejection', (e) => {
        safeTrackEvent('unhandled_promise_rejection', {
            reason: e.reason?.toString()
        });
    });
}

function initAccessibility() {
    const skipLink = document.querySelector('.skip-link');
    if (skipLink) {
        skipLink.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(skipLink.getAttribute('href'));
            if (target) {
                target.focus();
                target.scrollIntoView();
            }
        });
    }
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.user-dropdown.open').forEach(dropdown => {
                dropdown.classList.remove('open');
            });
            
            const mobileMenu = document.querySelector('.mobile-menu.active');
            if (mobileMenu) {
                toggleMobileMenu();
            }
        }
    });
    
    const modals = document.querySelectorAll('[data-modal]');
    modals.forEach(modal => {
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            modal.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey && document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    } else if (!e.shiftKey && document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            });
        }
    });
}

function initPageVisibility() {
    let isVisible = !document.hidden;
    let visibilityStart = Date.now();
    
    function handleVisibilityChange() {
        const now = Date.now();
        
        if (document.hidden && isVisible) {
            const visibleTime = now - visibilityStart;
            safeTrackEvent('page_visibility', {
                action: 'hidden',
                visible_time: visibleTime
            });
            isVisible = false;
        } else if (!document.hidden && !isVisible) {
            safeTrackEvent('page_visibility', { action: 'visible' });
            isVisible = true;
            visibilityStart = now;
        }
    }
    
    document.addEventListener('visibilitychange', handleVisibilityChange);
}

function initServiceWorker() {
    if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    safeTrackEvent('service_worker_registered', {
                        scope: registration.scope
                    });
                })
                .catch(error => {
                    safeTrackEvent('service_worker_error', {
                        error: error.message
                    });
                });
        });
    }
}

window.BluFox.utils = {
    escapeHtml,
    debounce,
    generateRandomString,
    formatNumber,
    copyToClipboard,
    validateForm,
    showLoading,
    hideLoading,
    showFlashMessage,
    closeFlashMessage
};

window.BluFox.api = {
    request: apiRequest
};

window.toggleMobileMenu = toggleMobileMenu;
window.closeFlashMessage = closeFlashMessage;
window.handleContactForm = handleContactForm;
window.copyToClipboard = copyToClipboard;
window.showFlashMessage = showFlashMessage;
window.generateRandomString = generateRandomString;
window.safeTrackEvent = safeTrackEvent;

document.addEventListener('DOMContentLoaded', function() {
    initFeatureDetection();
    initPageInteractions();
    initPerformanceMonitoring();
    initAnimationObserver();
    initLazyLoading();
    initThemeHandler();
    initErrorHandling();
    initAccessibility();
    initPageVisibility();
    initServiceWorker();
    
    safeTrackEvent('page_view', {
        page: window.location.pathname,
        title: document.title,
        referrer: document.referrer || 'direct'
    });
});

window.trackEvent = safeTrackEvent;