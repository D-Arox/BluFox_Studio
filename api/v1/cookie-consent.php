<?php
function handleCookieConsent($method, $data) {
    switch ($method) {
        case 'GET':
            return getCookieConsent();
        case 'POST':
            return setCookieConsent($data);
        case 'DELETE':
            return revokeCookieConsent();
        default:
            return ApiResponse::error('Method not allowed', 405);
    }
}

function getCookieConsent() {
    try {
        // Check for existing consent cookie
        $consentCookie = $_COOKIE['cookie_consent'] ?? null;
        
        if ($consentCookie) {
            $consent = json_decode($consentCookie, true);
            if ($consent && isset($consent['timestamp'])) {
                return ApiResponse::success([
                    'has_consent' => true,
                    'preferences' => $consent['preferences'] ?? [],
                    'timestamp' => $consent['timestamp'],
                    'expires' => date('Y-m-d H:i:s', time() + (365 * 24 * 60 * 60)) // 1 year
                ], 'Cookie consent retrieved');
            }
        }
        
        return ApiResponse::success([
            'has_consent' => false,
            'preferences' => [],
            'timestamp' => null,
            'expires' => null
        ], 'No cookie consent found');
        
    } catch (Exception $e) {
        error_log("Get cookie consent error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve cookie consent');
    }
}

function setCookieConsent($data) {
    $validator = new ApiValidator($data);
    $validator->required(['preferences']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        // Validate preferences structure
        $preferences = $data['preferences'];
        $validCategories = ['necessary', 'analytics', 'marketing', 'functional'];
        
        $validatedPreferences = [];
        foreach ($validCategories as $category) {
            $validatedPreferences[$category] = isset($preferences[$category]) ? (bool)$preferences[$category] : false;
        }
        
        // Necessary cookies are always required
        $validatedPreferences['necessary'] = true;
        
        // Create consent data
        $consentData = [
            'preferences' => $validatedPreferences,
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'version' => '1.0'
        ];
        
        // Set consent cookie (1 year expiration)
        $cookieValue = json_encode($consentData);
        $expires = time() + (365 * 24 * 60 * 60);
        
        setcookie(
            'cookie_consent',
            $cookieValue,
            $expires,
            '/',
            '',
            ENVIRONMENT === 'production', // Secure in production
            true // HTTP only
        );
        
        // Log consent for compliance (optional)
        if (is_logged_in()) {
            $db = db();
            $db->insert('cookie_consent_log', [
                'user_id' => current_user()['id'],
                'preferences' => json_encode($validatedPreferences),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Set session variables for immediate use
        $_SESSION['cookie_consent'] = $validatedPreferences;
        
        return ApiResponse::success([
            'preferences' => $validatedPreferences,
            'timestamp' => $consentData['timestamp'],
            'expires' => date('Y-m-d H:i:s', $expires)
        ], 'Cookie consent saved successfully');
        
    } catch (Exception $e) {
        error_log("Set cookie consent error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to save cookie consent');
    }
}

function revokeCookieConsent() {
    try {
        // Remove consent cookie
        setcookie(
            'cookie_consent',
            '',
            time() - 3600,
            '/',
            '',
            ENVIRONMENT === 'production',
            true
        );
        
        // Clear session
        unset($_SESSION['cookie_consent']);
        
        // Log revocation
        if (is_logged_in()) {
            $db = db();
            $db->insert('cookie_consent_log', [
                'user_id' => current_user()['id'],
                'preferences' => json_encode(['revoked' => true]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return ApiResponse::success(null, 'Cookie consent revoked successfully');
        
    } catch (Exception $e) {
        error_log("Revoke cookie consent error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to revoke cookie consent');
    }
}

function getCookiePolicy() {
    try {
        $policy = [
            'categories' => [
                'necessary' => [
                    'name' => 'Necessary Cookies',
                    'description' => 'These cookies are essential for the website to function properly and cannot be disabled.',
                    'required' => true,
                    'cookies' => [
                        'PHPSESSID' => 'PHP session identifier for maintaining user sessions',
                        'remember_token' => 'Authentication token for "remember me" functionality',
                        'csrf_token' => 'Security token to prevent cross-site request forgery attacks'
                    ]
                ],
                'functional' => [
                    'name' => 'Functional Cookies',
                    'description' => 'These cookies enable enhanced functionality and personalization.',
                    'required' => false,
                    'cookies' => [
                        'theme_preference' => 'Stores user\'s preferred theme (light/dark mode)',
                        'language_preference' => 'Stores user\'s preferred language',
                        'sidebar_collapsed' => 'Remembers sidebar state in admin panel'
                    ]
                ],
                'analytics' => [
                    'name' => 'Analytics Cookies',
                    'description' => 'These cookies help us understand how visitors interact with our website.',
                    'required' => false,
                    'cookies' => [
                        '_ga' => 'Google Analytics main cookie for tracking website usage',
                        '_ga_*' => 'Google Analytics property-specific tracking cookies',
                        'page_views' => 'Internal tracking for page view analytics'
                    ]
                ],
                'marketing' => [
                    'name' => 'Marketing Cookies',
                    'description' => 'These cookies are used to track visitors and display relevant advertisements.',
                    'required' => false,
                    'cookies' => [
                        'marketing_consent' => 'Tracks whether user has consented to marketing cookies',
                        'referral_source' => 'Tracks where visitors came from for marketing analysis'
                    ]
                ]
            ],
            'retention_periods' => [
                'session_cookies' => 'Deleted when browser is closed',
                'persistent_cookies' => 'Up to 1 year unless manually deleted',
                'analytics_cookies' => 'Up to 2 years',
                'marketing_cookies' => 'Up to 1 year'
            ],
            'contact_info' => [
                'email' => SITE_EMAIL,
                'privacy_policy_url' => SITE_URL . '/privacy',
                'data_controller' => SITE_NAME
            ],
            'last_updated' => '2025-01-01',
            'version' => '1.0'
        ];
        
        return ApiResponse::success($policy, 'Cookie policy retrieved successfully');
        
    } catch (Exception $e) {
        error_log("Get cookie policy error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to retrieve cookie policy');
    }
}

function updateCookiePreferences($data) {
    $validator = new ApiValidator($data);
    $validator->required(['category', 'enabled']);
    
    if ($validator->fails()) {
        return ApiResponse::validationError($validator->getErrors());
    }
    
    try {
        $validCategories = ['analytics', 'marketing', 'functional'];
        
        if (!in_array($data['category'], $validCategories)) {
            return ApiResponse::error('Invalid cookie category', 400);
        }
        
        // Get existing consent
        $consentCookie = $_COOKIE['cookie_consent'] ?? null;
        $currentPreferences = ['necessary' => true];
        
        if ($consentCookie) {
            $consent = json_decode($consentCookie, true);
            if ($consent && isset($consent['preferences'])) {
                $currentPreferences = array_merge($currentPreferences, $consent['preferences']);
            }
        }
        
        // Update specific category
        $currentPreferences[$data['category']] = (bool)$data['enabled'];
        
        // Save updated preferences
        $consentData = [
            'preferences' => $currentPreferences,
            'timestamp' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'version' => '1.0'
        ];
        
        $cookieValue = json_encode($consentData);
        $expires = time() + (365 * 24 * 60 * 60);
        
        setcookie(
            'cookie_consent',
            $cookieValue,
            $expires,
            '/',
            '',
            ENVIRONMENT === 'production',
            true
        );
        
        $_SESSION['cookie_consent'] = $currentPreferences;
        
        return ApiResponse::success([
            'category' => $data['category'],
            'enabled' => (bool)$data['enabled'],
            'preferences' => $currentPreferences
        ], 'Cookie preference updated successfully');
        
    } catch (Exception $e) {
        error_log("Update cookie preferences error: " . $e->getMessage());
        return ApiResponse::serverError('Failed to update cookie preferences');
    }
}

// Helper function to check if specific cookie category is allowed
function isCookieCategoryAllowed($category) {
    // Necessary cookies are always allowed
    if ($category === 'necessary') {
        return true;
    }
    
    // Check session first
    if (isset($_SESSION['cookie_consent'][$category])) {
        return (bool)$_SESSION['cookie_consent'][$category];
    }
    
    // Check cookie
    $consentCookie = $_COOKIE['cookie_consent'] ?? null;
    if ($consentCookie) {
        $consent = json_decode($consentCookie, true);
        if ($consent && isset($consent['preferences'][$category])) {
            return (bool)$consent['preferences'][$category];
        }
    }
    
    // Default to false if no consent found
    return false;
}

// Helper function to get all cookie preferences
function getCookiePreferences() {
    $defaults = [
        'necessary' => true,
        'functional' => false,
        'analytics' => false,
        'marketing' => false
    ];
    
    // Check session first
    if (isset($_SESSION['cookie_consent'])) {
        return array_merge($defaults, $_SESSION['cookie_consent']);
    }
    
    // Check cookie
    $consentCookie = $_COOKIE['cookie_consent'] ?? null;
    if ($consentCookie) {
        $consent = json_decode($consentCookie, true);
        if ($consent && isset($consent['preferences'])) {
            return array_merge($defaults, $consent['preferences']);
        }
    }
    
    return $defaults;
}

// Main route handler for cookie consent
if (!function_exists('handleCookieConsentRoutes')) {
    function handleCookieConsentRoutes($method, $segments, $data) {
        $action = $segments[0] ?? '';
        
        switch ($action) {
            case '':
                return handleCookieConsent($method, $data);
            case 'policy':
                if ($method === 'GET') {
                    return getCookiePolicy();
                }
                break;
            case 'preferences':
                if ($method === 'PUT') {
                    return updateCookiePreferences($data);
                }
                break;
            default:
                return ApiResponse::notFound('Cookie consent endpoint not found');
        }
        
        return ApiResponse::error('Method not allowed', 405);
    }
}