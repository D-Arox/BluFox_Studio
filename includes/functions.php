<?php
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || time() > ($_SESSION['csrf_token_time'] + CSRF_TOKEN_EXPIRE)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
        hash_equals($_SESSION['csrf_token'], $token) &&
        time() <= ($_SESSION['csrf_token_time'] + CSRF_TOKEN_EXPIRE);
}

function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . $url);
    exit;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];

        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }

    return null;
}

function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uploadFile($file, $destination = 'general') {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed'];
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, UPLOAD_ALLOWED_TYPES)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . $destination . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination . '/' . $filename,
            'url' => SITE_URL . '/uploads/' . $destination . '/' . $filename
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}

function getRobloxGameStats($gameId) {
    return [
        'visits' => 'Mock: ' . rand(10000, 1000000),
        'favorites' => 'Mock: ' . rand(1000, 50000),
        'likes' => 'Mock: ' . rand(5000, 100000),
        'active_players' => 'Mock: ' . rand(10, 500)
    ];
}

function logActivity($eventType, $eventData = [], $userId = null) {
    try {
        $data = [
            'event_type' => $eventType,
            'event_data' => json_encode($eventData),
            'user_id' => $userId,
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'page_url' => $_SERVER['REQUEST_URI'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null
        ];
        
        db()->insert('analytics', $data);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function getPageTitle($page = '') {
    $titles = [
        'home' => 'Professional Roblox Development Studio',
        'projects' => 'Our Portfolio - Roblox Games & Frameworks',
        'services' => 'Development Services - Custom Roblox Solutions',
        'about' => 'About Our Studio - Expert Roblox Developers',
        'contact' => 'Contact Us - Start Your Roblox Project',
        'vantara' => 'Vantara Framework - Advanced Roblox Development'
    ];
    
    $title = $titles[$page] ?? 'Professional Roblox Development';
    return SITE_NAME . ' - ' . $title;
}

function getMetaDescription($page = '') {
    $descriptions = [
        'home' => 'BluFox Studio - Professional Roblox game development, custom frameworks, and expert Lua scripting services. Creating amazing Roblox experiences since 2024.',
        'projects' => 'Explore our portfolio of successful Roblox games, frameworks, and custom development projects. See why clients choose BluFox Studio.',
        'services' => 'Professional Roblox development services including game development, framework creation, scripting, and consulting. Get your project started today.',
        'about' => 'Learn about BluFox Studio - our mission, team, and expertise in Roblox development, Lua programming, and game design.',
        'contact' => 'Ready to start your Roblox project? Contact BluFox Studio for professional development services and custom solutions.',
        'vantara' => 'Vantara Framework - The most advanced Roblox development framework with modular architecture and enterprise features.'
    ];
    
    return $descriptions[$page] ?? SITE_DESCRIPTION;
}
?>