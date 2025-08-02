<?php
/**
 * OAuth Callback Handler
 * This file should be placed in /auth/callback.php
 * Handles the OAuth callback from Roblox and processes the login
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../config/config.php';

// Function to redirect with error message
function redirectWithError($message) {
    $_SESSION['auth_error'] = $message;
    header('Location: /auth/login');
    exit;
}

// Function to redirect with success message
function redirectWithSuccess($message, $destination = '/dashboard') {
    $_SESSION['auth_success'] = $message;
    header('Location: ' . $destination);
    exit;
}

// Check for OAuth error parameters
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorDescription = $_GET['error_description'] ?? 'OAuth authentication failed';
    redirectWithError("$error: $errorDescription");
}

// Check for required OAuth success parameters
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    redirectWithError('Missing required OAuth parameters');
}

$code = $_GET['code'];
$state = $_GET['state'];

try {
    // Prepare data for API call
    $callbackData = [
        'code' => $code,
        'state' => $state
    ];

    // Check if remember me was selected (stored in session by login form)
    if (isset($_SESSION['remember_me']) && $_SESSION['remember_me']) {
        $callbackData['remember_me'] = true;
        unset($_SESSION['remember_me']); // Clean up
    }

    // Call the OAuth callback API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => SITE_URL . '/api/v1/auth/roblox/callback',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($callbackData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: ' . $_SERVER['HTTP_COOKIE'] // Forward session cookie
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => ENVIRONMENT === 'production'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception('Network error: ' . $curlError);
    }
    
    if (!$response) {
        throw new Exception('No response from authentication service');
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Invalid response from authentication service');
    }
    
    if ($httpCode !== 200 || !$data['success']) {
        $errorMessage = $data['message'] ?? 'Authentication failed';
        throw new Exception($errorMessage);
    }
    
    // Success! The API should have set the session variables
    // Check if user is now logged in
    if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
        redirectWithSuccess('Login successful! Welcome to BluFox Studio.');
    } else {
        // API succeeded but session not set - try to set it manually if user data is provided
        if (isset($data['data']['user']) && isset($data['data']['user']['id'])) {
            $user = $data['data']['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            
            redirectWithSuccess('Login successful! Welcome to BluFox Studio.');
        } else {
            throw new Exception('Authentication succeeded but session could not be established');
        }
    }
    
} catch (Exception $e) {
    error_log('OAuth callback error: ' . $e->getMessage());
    redirectWithError('Login failed: ' . $e->getMessage());
}