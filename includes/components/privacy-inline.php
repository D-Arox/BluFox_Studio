<?php
/**
 * INLINE Privacy Management System for BluFox Studio
 * Updated to use existing API v1 endpoint and database structure
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has already made a choice
$consent_given = isset($_COOKIE['user_prefs']) ? json_decode($_COOKIE['user_prefs'], true) : null;
$show_banner = !$consent_given;

// Privacy categories configuration
$privacy_categories = [
    'necessary' => [
        'name' => 'Necessary',
        'description' => 'Essential for website functionality and security.',
        'required' => true,
        'items' => ['session_id', 'csrf_token', 'user_prefs']
    ],
    'functional' => [
        'name' => 'Functional', 
        'description' => 'Enhance your experience and remember your preferences.',
        'required' => false,
        'items' => ['theme_preference', 'language_preference', 'nav_state']
    ],
    'analytics' => [
        'name' => 'Analytics',
        'description' => 'Help us understand how visitors interact with our website.',
        'required' => false,
        'items' => ['page_views', 'user_analytics', 'performance_metrics']
    ],
    'marketing' => [
        'name' => 'Marketing',
        'description' => 'Used to deliver personalized content and track marketing effectiveness.',
        'required' => false,
        'items' => ['marketing_id', 'ad_preferences', 'social_tracking']
    ]
];
?>

<!-- INLINE STYLES to avoid CSS blocking -->
<style>
/* Privacy Banner Styles - Inline to avoid ad-blocker interference */
.prefs-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    animation: overlayIn 0.4s ease-out;
}

@keyframes overlayIn {
    from { opacity: 0; backdrop-filter: blur(0px); }
    to { opacity: 1; backdrop-filter: blur(8px); }
}

.prefs-container {
    background: var(--color-surface, #1a1a1a);
    border: 1px solid var(--color-border, #333);
    border-radius: 12px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    animation: containerIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes containerIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.prefs-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid var(--color-border, #333);
}

.prefs-header h3 {
    margin: 0;
    color: var(--color-text-primary, #fff);
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.prefs-icon {
    width: 24px;
    height: 24px;
    color: var(--color-primary, #00ff41);
}

.prefs-close {
    background: none;
    border: none;
    color: var(--color-text-secondary, #888);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.3s;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prefs-close:hover {
    background: var(--color-surface-elevated, #2a2a2a);
    color: var(--color-text-primary, #fff);
    transform: scale(1.1);
}

.prefs-content {
    padding: 1rem 1.5rem 1.5rem;
}

.prefs-description {
    color: var(--color-text-secondary, #ccc);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.prefs-description a {
    color: var(--color-primary, #00ff41);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 0.3s;
}

.prefs-description a:hover {
    border-bottom-color: var(--color-primary, #00ff41);
}

.prefs-actions {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.prefs-btn {
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    min-height: 44px;
    flex: 1;
    min-width: 120px;
}

.btn-accept {
    background: linear-gradient(135deg, #00FF41 0%, #0099FF 100%);
    color: #000;
}

.btn-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 255, 65, 0.3);
}

.btn-reject {
    background: var(--color-surface-elevated, #2a2a2a);
    color: var(--color-text-primary, #fff);
    border: 1px solid var(--color-border, #333);
}

.btn-reject:hover {
    background: var(--color-surface, #1a1a1a);
}

.btn-customize {
    background: transparent;
    color: var(--color-primary, #00ff41);
    border: 1px solid var(--color-primary, #00ff41);
    min-width: 100px;
    flex: none;
}

.btn-customize:hover {
    background: var(--color-primary, #00ff41);
    color: #000;
}

.prefs-categories {
    display: none;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.prefs-category {
    border: 1px solid var(--color-border, #333);
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
    transition: border-color 0.3s;
}

.prefs-category:hover {
    border-color: var(--color-primary, #00ff41);
}

.category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: var(--color-surface-elevated, #2a2a2a);
    cursor: pointer;
}

.category-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.category-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    user-select: none;
}

.category-checkbox {
    width: 18px;
    height: 18px;
    margin: 0;
    cursor: pointer;
}

.category-name {
    font-weight: 500;
    color: var(--color-text-primary, #fff);
}

.required-badge {
    background: var(--color-primary, #00ff41);
    color: #000;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.category-toggle {
    background: none;
    border: none;
    color: var(--color-text-secondary, #888);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 4px;
    transition: all 0.3s;
    width: 32px;
    height: 32px;
}

.category-toggle:hover {
    background: var(--color-surface, #1a1a1a);
    color: var(--color-text-primary, #fff);
}

.category-details {
    padding: 1rem;
    border-top: 1px solid var(--color-border, #333);
    background: var(--color-surface, #1a1a1a);
    display: none;
}

.category-description {
    color: var(--color-text-secondary, #ccc);
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.items-list {
    font-size: 0.875rem;
}

.items-list strong {
    color: var(--color-text-primary, #fff);
    display: block;
    margin-bottom: 0.5rem;
}

.items-list ul {
    margin: 0;
    padding-left: 1rem;
    color: var(--color-text-secondary, #ccc);
}

.items-list code {
    background: var(--color-surface-elevated, #2a2a2a);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.75rem;
    color: var(--color-primary, #00ff41);
}

.prefs-save-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border, #333);
}

.btn-save {
    background: linear-gradient(135deg, #00FF41 0%, #0099FF 100%);
    color: #000;
    flex: 1;
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(0, 255, 65, 0.3);
}

.btn-cancel {
    background: var(--color-surface-elevated, #2a2a2a);
    color: var(--color-text-secondary, #888);
    border: 1px solid var(--color-border, #333);
}

.btn-cancel:hover {
    color: var(--color-text-primary, #fff);
}

.prefs-settings-btn {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    width: 48px;
    height: 48px;
    background: var(--color-surface-elevated, #2a2a2a);
    border: 1px solid var(--color-border, #333);
    border-radius: 50%;
    color: var(--color-text-secondary, #888);
    cursor: pointer;
    transition: all 0.3s;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.prefs-settings-btn:hover {
    background: var(--color-primary, #00ff41);
    color: #000;
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 255, 65, 0.3);
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--color-surface-elevated, #2a2a2a);
    color: var(--color-text-primary, #fff);
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid var(--color-border, #333);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 10001;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideInFromRight 0.3s ease-out;
}

@keyframes slideInFromRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 768px) {
    .prefs-overlay { padding: 0.5rem; align-items: flex-end; }
    .prefs-container { max-height: 85vh; border-radius: 12px 12px 0 0; }
    .prefs-actions { flex-direction: column; }
    .prefs-btn { width: 100%; flex: none; }
    .prefs-save-actions { flex-direction: column; }
    .prefs-settings-btn { bottom: 1rem; right: 1rem; width: 44px; height: 44px; }
}
</style>

<?php if ($show_banner): ?>
<div id="prefs-banner" class="prefs-overlay">
    <div class="prefs-container">
        <div class="prefs-header">
            <div class="prefs-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <h3>Privacy Preferences</h3>
            <button class="prefs-close" id="prefs-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        <div class="prefs-content">
            <p class="prefs-description">
                We use various technologies to enhance your browsing experience, provide personalized content, and analyze our traffic. 
                By clicking "Accept All", you consent to our use of these technologies as described in our 
                <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.
            </p>

            <div class="prefs-actions">
                <button class="prefs-btn btn-accept" id="accept-all">Accept All</button>
                <button class="prefs-btn btn-reject" id="reject-optional">Reject Optional</button>
                <button class="prefs-btn btn-customize" id="customize">Customize</button>
            </div>

            <div class="prefs-categories" id="categories">
                <?php foreach ($privacy_categories as $category_id => $category): ?>
                <div class="prefs-category">
                    <div class="category-header" onclick="toggleDetails('<?php echo $category_id; ?>')">
                        <div class="category-info">
                            <label class="category-label">
                                <input 
                                    type="checkbox" 
                                    id="cat-<?php echo $category_id; ?>"
                                    value="<?php echo $category_id; ?>"
                                    <?php echo $category['required'] ? 'checked disabled' : ''; ?>
                                    class="category-checkbox"
                                >
                                <span class="category-name"><?php echo escape_html($category['name']); ?></span>
                                <?php if ($category['required']): ?>
                                    <span class="required-badge">Required</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <button class="category-toggle" type="button">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="category-details" id="details-<?php echo $category_id; ?>">
                        <p class="category-description"><?php echo escape_html($category['description']); ?></p>
                        <div class="items-list">
                            <strong>Technologies used:</strong>
                            <ul>
                                <?php foreach ($category['items'] as $item): ?>
                                    <li><code><?php echo escape_html($item); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="prefs-save-actions">
                    <button class="prefs-btn btn-save" id="save-prefs">Save Preferences</button>
                    <button class="prefs-btn btn-cancel" id="cancel-custom">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($consent_given): ?>
<button id="prefs-settings" class="prefs-settings-btn" aria-label="Privacy Settings">
    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
        <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
    </svg>
</button>
<?php endif; ?>

<!-- INLINE JAVASCRIPT to completely bypass ad-blockers -->
<script>
(function() {
    'use strict';
    
    // Configuration - Updated to use v1 API endpoint
    const CONFIG = {
        storeName: 'user_prefs',
        expires: 365,
        domain: window.location.hostname,
        path: '/',
        secure: window.location.protocol === 'https:',
        apiEndpoint: '/api/v1/cookie-consent' // Updated API endpoint
    };
    
    let currentPrefs = null;
    let elements = {};
    
    // Initialize
    function init() {
        cacheElements();
        loadCurrentPrefs();
        bindEvents();
        
        if (currentPrefs) {
            applyPrefs(currentPrefs);
        }
        
        console.log('🔒 Privacy system initialized');
    }
    
    function cacheElements() {
        elements = {
            banner: document.getElementById('prefs-banner'),
            closeBtn: document.getElementById('prefs-close'),
            acceptBtn: document.getElementById('accept-all'),
            rejectBtn: document.getElementById('reject-optional'),
            customizeBtn: document.getElementById('customize'),
            saveBtn: document.getElementById('save-prefs'),
            cancelBtn: document.getElementById('cancel-custom'),
            categories: document.getElementById('categories'),
            settingsBtn: document.getElementById('prefs-settings'),
            checkboxes: document.querySelectorAll('.category-checkbox')
        };
    }
    
    function bindEvents() {
        if (elements.closeBtn) elements.closeBtn.onclick = closeBanner;
        if (elements.acceptBtn) elements.acceptBtn.onclick = acceptAll;
        if (elements.rejectBtn) elements.rejectBtn.onclick = rejectOptional;
        if (elements.customizeBtn) elements.customizeBtn.onclick = showCustomize;
        if (elements.saveBtn) elements.saveBtn.onclick = saveCustom;
        if (elements.cancelBtn) elements.cancelBtn.onclick = hideCustomize;
        if (elements.settingsBtn) elements.settingsBtn.onclick = showBanner;
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && elements.banner && elements.banner.style.display !== 'none') {
                closeBanner();
            }
        });
    }
    
    function loadCurrentPrefs() {
        const stored = getStored(CONFIG.storeName);
        if (stored) {
            try {
                currentPrefs = JSON.parse(stored);
            } catch (e) {
                currentPrefs = null;
            }
        }
    }
    
    function acceptAll() {
        const prefs = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true
            }
        };
        savePrefs(prefs);
        closeBanner();
        showNotification('All preferences accepted', 'success');
    }
    
    function rejectOptional() {
        const prefs = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false
            }
        };
        savePrefs(prefs);
        closeBanner();
        showNotification('Optional preferences rejected', 'info');
    }
    
    function showCustomize() {
        if (elements.categories) {
            elements.categories.style.display = 'block';
            loadPrefsIntoForm();
        }
    }
    
    function hideCustomize() {
        if (elements.categories) {
            elements.categories.style.display = 'none';
        }
    }
    
    function loadPrefsIntoForm() {
        elements.checkboxes.forEach(checkbox => {
            const category = checkbox.value;
            if (currentPrefs && currentPrefs.categories) {
                checkbox.checked = currentPrefs.categories[category] || false;
            } else {
                checkbox.checked = category === 'necessary';
            }
        });
    }
    
    function saveCustom() {
        const prefs = {
            timestamp: Date.now(),
            version: '1.0',
            categories: {}
        };
        
        prefs.categories.necessary = true;
        
        savePrefs(prefs);
        closeBanner();
        showNotification('Preferences saved', 'success');
    }
    
    function savePrefs(prefs) {
        currentPrefs = prefs;
        setStored(CONFIG.storeName, JSON.stringify(prefs), CONFIG.expires);
        applyPrefs(prefs);
        
        // Send to server using updated API endpoint
        fetch(CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ consent: prefs })
        }).then(response => {
            if (!response.ok) {
                throw new Error('Failed to save to server');
            }
            return response.json();
        }).then(data => {
            if (data.success) {
                console.log('✅ Preferences saved to server:', data);
            }
        }).catch(error => {
            console.warn('⚠️ Failed to save to server:', error);
            // Still continue - local storage works
        });
        
        // Dispatch events
        document.dispatchEvent(new CustomEvent('privacyPrefsUpdated', { detail: prefs }));
        // Backward compatibility
        document.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: prefs }));
    }
    
    function applyPrefs(prefs) {
        if (!prefs || !prefs.categories) return;
        
        // Analytics
        if (prefs.categories.analytics) {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', { 'analytics_storage': 'granted' });
            }
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('analytics_enabled', 'true');
            }
            console.log('📊 Analytics enabled');
        } else {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', { 'analytics_storage': 'denied' });
            }
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('analytics_enabled');
            }
            console.log('📊 Analytics disabled');
        }
        
        // Marketing
        if (prefs.categories.marketing) {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', { 'ad_storage': 'granted' });
            }
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('marketing_enabled', 'true');
            }
            console.log('📢 Marketing enabled');
        } else {
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', { 'ad_storage': 'denied' });
            }
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('marketing_enabled');
            }
            console.log('📢 Marketing disabled');
        }
        
        // Functional
        if (prefs.categories.functional) {
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('functional_enabled', 'true');
            }
            console.log('⚙️ Functional enabled');
        } else {
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('functional_enabled');
            }
            console.log('⚙️ Functional disabled');
        }
    }
    
    function showBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'flex';
        }
    }
    
    function closeBanner() {
        if (elements.banner) {
            elements.banner.style.display = 'none';
        }
    }
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.innerHTML = message + ' <button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;cursor:pointer;font-size:18px;margin-left:8px;">×</button>';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 3000);
    }
    
    // Storage utilities
    function setStored(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = name + '=' + value + '; expires=' + expires.toUTCString() + '; path=' + CONFIG.path + '; domain=' + CONFIG.domain + (CONFIG.secure ? '; secure' : '') + '; samesite=lax';
    }
    
    function getStored(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Global toggle function for category details
    window.toggleDetails = function(categoryId) {
        const details = document.getElementById('details-' + categoryId);
        if (details) {
            details.style.display = details.style.display === 'none' ? 'block' : 'none';
        }
    };
    
    // Global API for compatibility
    window.UserPrefs = {
        hasConsent: function(category) {
            return currentPrefs && currentPrefs.categories && currentPrefs.categories[category];
        },
        showSettings: showBanner,
        getCurrentConsent: function() { return currentPrefs; },
        acceptAll: acceptAll,
        rejectOptional: rejectOptional
    };
    
    // Backward compatibility
    window.CookieConsent = window.UserPrefs;
    window.PrivacyManager = window.UserPrefs;
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>