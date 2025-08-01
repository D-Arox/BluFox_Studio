<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$consent_given = isset($_COOKIE['cookie_consent']) ? json_decode($_COOKIE['cookie_consent'], true) : null;
$show_banner = !$consent_given;

$cookie_categories = [
    'necessary' => [
        'name' => 'Necessary',
        'description' => 'Essential cookies required for website functionality and security.',
        'required' => true,
        'cookies' => ['session_id', 'csrf_token', 'cookie_consent']
    ],
    'functional' => [
        'name' => 'Functional', 
        'description' => 'Cookies that enhance your experience and remember your preferences.',
        'required' => false,
        'cookies' => ['theme_preference', 'language_preference', 'nav_state']
    ],
    'analytics' => [
        'name' => 'Analytics',
        'description' => 'Cookies that help us understand how visitors interact with our website.',
        'required' => false,
        'cookies' => ['page_views', 'user_analytics', 'performance_metrics']
    ],
    'marketing' => [
        'name' => 'Marketing',
        'description' => 'Cookies used to deliver personalized content and track marketing effectiveness.',
        'required' => false,
        'cookies' => ['marketing_id', 'ad_preferences', 'social_tracking']
    ]
];
?>
<?php if ($show_banner): ?>
<div class="cookie-consent-overlay" id="cookie-consent-banner">
    <div class="cookie-consent-container">
        <div class="cookie-consent-header">
            <div class="cookie-icon">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
            </div>
            <h3>Cookie Preferences</h3>
            <button class="cookie-close" id="cookie-close-btn" aria-label="Close cookie banner">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>
        <div class="cookie-consent-content">
            <p class="cookie-description">
                We use cookies to enhance your browsing experience, provide personalized content, and analyze our traffic. 
                By clicking "Accept All", you consent to our use of cookies as described in our 
                <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a>.
            </p>

            <div class="cookie-quick-actions">
                <button class="btn-accept-all" id="accept-all-cookies">Accept All</button>
                <button class="btn-reject-optional" id="reject-optional-cookies">Reject Optional</button>
                <button class="btn-customize" id="customize-cookies">Customize</button>
            </div>
            <div class="cookie-categories" id="cookie-categories" style="display: none;">
                <?php foreach ($cookie_categories as $category_id => $category): ?>
                <div class="cookie-category">
                    <div class="category-header">
                        <div class="category-info">
                            <label class="category-label">
                                <input 
                                    type="checkbox" 
                                    id="cookie-<?php echo $category_id; ?>"
                                    name="cookie_categories[]" 
                                    value="<?php echo $category_id; ?>"
                                    <?php echo $category['required'] ? 'checked disabled' : ''; ?>
                                    class="category-checkbox"
                                >
                                <span class="checkmark"></span>
                                <span class="category-name"><?php echo escape_html($category['name']); ?></span>
                                <?php if ($category['required']): ?>
                                    <span class="required-badge">Required</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <button class="category-toggle" data-category="<?php echo $category_id; ?>" aria-label="Toggle category details">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.41 8.84L12 13.42l4.59-4.58L18 10.25l-6 6-6-6z"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="category-details" id="details-<?php echo $category_id; ?>" style="display: none;">
                        <p class="category-description"><?php echo escape_html($category['description']); ?></p>
                        <div class="cookie-list">
                            <strong>Cookies used:</strong>
                            <ul>
                                <?php foreach ($category['cookies'] as $cookie): ?>
                                    <li><code><?php echo escape_html($cookie); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="cookie-actions">
                    <button class="btn-save-preferences" id="save-preferences">Save Preferences</button>
                    <button class="btn-cancel" id="cancel-customize">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php if ($consent_given): ?>
<button id="cookie-settings-btn" class="cookie-settings-button" aria-label="Cookie Settings">
    <svg viewBox="0 0 24 24" fill="currentColor">
        <path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11.03L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11.03C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/>
    </svg>
</button>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.CookieConsent.init();
});
</script>