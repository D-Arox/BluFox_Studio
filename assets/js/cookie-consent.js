window.CookieConsent = (function() {
    'use strict';

    const CONFIG = {
        cookieName: 'cookie_consent',
        cookieExpiry: 365,
        domain: window.location.hostname,
        path: '/',
        secure: window.location.protocol === 'https:',
        apiEndpoint: '/api/cookie-consent',
        categories: {
            necessary: { required: true, default: true },
            functional: { required: false, default: false },
            analytics: { required: false, default: false },
            marketing: { required: false, default: false }
        }
    };

    let currentConsent = null;
    let isInitialized = false;
    let elements = {};

    function init() {
        if (isInitialized) return;
        
        cacheElements();
        loadCurrentConsent();
        bindEvents();
        
        if (currentConsent) {
            applyConsentSettings(currentConsent);
        }
        
        isInitialized = true;
    }

    function cacheElements() {
        elements = {
            banner: document.getElementById('cookie-consent-banner'),
            closeBtn: document.getElementById('cookie-close-btn'),
            acceptAllBtn: document.getElementById('accept-all-cookies'),
            rejectOptionalBtn: document.getElementById('reject-optional-cookies'),
            customizeBtn: document.getElementById('customize-cookies'),
            savePreferencesBtn: document.getElementById('save-preferences'),
            cancelBtn: document.getElementById('cancel-customize'),
            categoriesContainer: document.getElementById('cookie-categories'),
            settingsBtn: document.getElementById('cookie-settings-btn'),
            categoryToggles: document.querySelectorAll('.category-toggle'),
            categoryCheckboxes: document.querySelectorAll('.category-checkbox')
        };
    }

    function bindEvents() {
        if (elements.closeBtn) {
            elements.closeBtn.addEventListener('click', closeBanner);
        }
        
        if (elements.acceptAllBtn) {
            elements.acceptAllBtn.addEventListener('click', acceptAllCookies);
        }
        
        if (elements.rejectOptionalBtn) {
            elements.rejectOptionalBtn.addEventListener('click', rejectOptionalCookies);
        }
        
        if (elements.customizeBtn) {
            elements.customizeBtn.addEventListener('click', showCustomization);
        }
        
        if (elements.savePreferencesBtn) {
            elements.savePreferencesBtn.addEventListener('click', saveCustomPreferences);
        }
        
        if (elements.cancelBtn) {
            elements.cancelBtn.addEventListener('click', hideCustomization);
        }
        
        if (elements.settingsBtn) {
            elements.settingsBtn.addEventListener('click', showBanner);
        }
        
        elements.categoryToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const categoryId = this.dataset.category;
                toggleCategoryDetails(categoryId);
            });
        });
        
        document.addEventListener('keydown', handleKeyboardNavigation);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && elements.banner && elements.banner.style.display !== 'none') {
                closeBanner();
            }
        });
    }

    function handleKeyboardNavigation(e) {
        if (!elements.banner || elements.banner.style.display === 'none') return;
        
        const focusableElements = elements.banner.querySelectorAll(
            'button, input, a, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    function loadCurrentConsent() {
        const consentCookie = getCookie(CONFIG.cookieName);
        if (consentCookie) {
            try {
                currentConsent = JSON.parse(consentCookie);
            } catch (e) {
                currentConsent = null;
            }
        }
    }

    function acceptAllCookies() {
        const consent = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {}
        };
        
        Object.keys(CONFIG.categories).forEach(category => {
            consent.categories[category] = true;
        });
        
        saveConsent(consent);
        closeBanner();
        showNotification('All cookies accepted', 'success');
    }

    function rejectOptionalCookies() {
        const consent = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {}
        };
        
        Object.keys(CONFIG.categories).forEach(category => {
            consent.categories[category] = CONFIG.categories[category].required;
        });
        
        saveConsent(consent);
        closeBanner();
        showNotification('Optional cookies rejected', 'info');
    }

    function showCustomization() {
        if (elements.categoriesContainer) {
            elements.categoriesContainer.style.display = 'block';
            
            loadPreferencesIntoForm();
            
            const firstCheckbox = elements.categoriesContainer.querySelector('input:not([disabled])');
            if (firstCheckbox) {
                setTimeout(() => firstCheckbox.focus(), 100);
            }
        }
    }

    function hideCustomization() {
        if (elements.categoriesContainer) {
            elements.categoriesContainer.style.display = 'none';
        }
    }

    function loadPreferencesIntoForm() {
        elements.categoryCheckboxes.forEach(checkbox => {
            const category = checkbox.value;
            
            if (currentConsent && currentConsent.categories) {
                checkbox.checked = currentConsent.categories[category] || false;
            } else {
                checkbox.checked = CONFIG.categories[category].default;
            }
        });
    }

    function saveCustomPreferences() {
        const consent = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {}
        };
        
        elements.categoryCheckboxes.forEach(checkbox => {
            const category = checkbox.value;
            consent.categories[category] = checkbox.checked;
        });
        
        consent.categories.necessary = true;
        
        saveConsent(consent);
        closeBanner();
        showNotification('Cookie preferences saved', 'success');
    }

    function saveConsent(consent) {
        currentConsent = consent;
        
        const cookieValue = JSON.stringify(consent);

        setCookie(CONFIG.cookieName, cookieValue, CONFIG.cookieExpiry);
        applyConsentSettings(consent);
        sendConsentToServer(consent);
    }

    function applyConsentSettings(consent) {
        if (!consent || !consent.categories) return;
        
        if (consent.categories.analytics) {
            enableAnalytics();
        } else {
            disableAnalytics();
        }
        
        if (consent.categories.marketing) {
            enableMarketing();
        } else {
            disableMarketing();
        }
        
        // Functional
        if (consent.categories.functional) {
            enableFunctional();
        } else {
            disableFunctional();
        }
        
        document.dispatchEvent(new CustomEvent('cookieConsentUpdated', {
            detail: consent
        }));
    }

    function enableAnalytics() {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }
        
        localStorage.setItem('analytics_enabled', 'true');
    }

    function disableAnalytics() {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'analytics_storage': 'denied'
            });
        }
        
        localStorage.removeItem('analytics_enabled');
        removeCookiesByCategory('analytics');
    }

    function enableMarketing() {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'ad_storage': 'granted'
            });
        }
        
        localStorage.setItem('marketing_enabled', 'true');
    }

    function disableMarketing() {
        if (typeof gtag !== 'undefined') {
            gtag('consent', 'update', {
                'ad_storage': 'denied'
            });
        }
        
        localStorage.removeItem('marketing_enabled');
        removeCookiesByCategory('marketing');
    }

    function enableFunctional() {
        localStorage.setItem('functional_enabled', 'true');
    }

    function disableFunctional() {
        localStorage.removeItem('functional_enabled');
        removeCookiesByCategory('functional');
    }

    function removeCookiesByCategory(category) {
        const cookiesToRemove = {
            analytics: ['_ga', '_gid', '_gat', 'page_views', 'user_analytics'],
            marketing: ['_fbp', '_fbc', 'marketing_id', 'ad_preferences'],
            functional: ['theme_preference', 'language_preference', 'nav_state']
        };
        
        if (cookiesToRemove[category]) {
            cookiesToRemove[category].forEach(cookieName => {
                deleteCookie(cookieName);
            });
        }
    }

    function toggleCategoryDetails(categoryId) {
        const details = document.getElementById(`details-${categoryId}`);
        const toggle = document.querySelector(`[data-category="${categoryId}"]`);
        
        if (details && toggle) {
            const isVisible = details.style.display !== 'none';
            details.style.display = isVisible ? 'none' : 'block';
            toggle.classList.toggle('expanded', !isVisible);
        }
    }

    function showBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'flex';
            
            const firstButton = elements.banner.querySelector('button');
            if (firstButton) {
                setTimeout(() => firstButton.focus(), 100);
            }
        }
    }

    function closeBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'none';
        }
    }

    function sendConsentToServer(consent) {
        if (!CONFIG.apiEndpoint) return;
        
        fetch(CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                consent: consent,
                userAgent: navigator.userAgent,
                timestamp: Date.now()
            })
        }).catch(error => {
            console.warn('Failed to send consent to server:', error);
        });
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `cookie-notification cookie-notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" aria-label="Close notification">×</button>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--color-surface-elevated);
            color: var(--color-text-primary);
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 10001;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInFromRight 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }

    function setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        
        document.cookie = `${name}=${value}; expires=${expires.toUTCString()}; path=${CONFIG.path}; domain=${CONFIG.domain}${CONFIG.secure ? '; secure' : ''}; samesite=lax`;
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    function deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=${CONFIG.path}; domain=${CONFIG.domain}`;
    }

    return {
        init: init,
        showBanner: showBanner,
        closeBanner: closeBanner,
        acceptAll: acceptAllCookies,
        rejectOptional: rejectOptionalCookies,
        getCurrentConsent: () => currentConsent,
        hasConsent: (category) => currentConsent && currentConsent.categories && currentConsent.categories[category],
        updateConsent: saveConsent,
        
        enableCategory: function(category) {
            if (!currentConsent) currentConsent = { categories: {} };
            currentConsent.categories[category] = true;
            applyConsentSettings(currentConsent);
        },
        
        disableCategory: function(category) {
            if (!currentConsent) return;
            if (CONFIG.categories[category] && CONFIG.categories[category].required) return;
            currentConsent.categories[category] = false;
            applyConsentSettings(currentConsent);
        }
    };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.CookieConsent.init();
    });
} else {
    window.CookieConsent.init();
}

if (!document.querySelector('#cookie-notification-styles')) {
    const styles = document.createElement('style');
    styles.id = 'cookie-notification-styles';
    styles.textContent = `
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .cookie-notification button {
            background: none;
            border: none;
            color: var(--color-text-secondary);
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cookie-notification button:hover {
            color: var(--color-text-primary);
        }
    `;
    document.head.appendChild(styles);
}