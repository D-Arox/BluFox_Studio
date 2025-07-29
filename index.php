<?php
require_once 'includes/config.php';

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

$segments = explode('/', $path);
$page = $segments[0] ?: 'home';

$routes = [
    'home' => 'pages/home.php',
    'projects' => 'pages/projects.php',
    'services' => 'pages/services.php',
    'about' => 'pages/about.php',
    'contact' => 'pages/contact.php',
    'privacy' => 'pages/privacy.php',
    'terms' => 'pages/terms.php'
];

if ($page === 'vantara') {
    include 'vantara/index.php';
    exit;
}

if ($page === 'admin') {
    include 'admin/index.php';
    exit;
}

if ($page === 'auth') {
    $action = $segments[1] ?? 'login';
    if (file_exists("auth/{$action}.php")) {
        include "auth/{$action}.php";
    } else {
        include 'auth/login.php';
    }
    exit;
}

if ($page === 'api') {
    include 'api/index.php';
    exit;
}

if (!isset($routes[$page])) {
    http_response_code(404);
    $page = '404';
    $pageFile = 'pages/404.php';
} else {
    $pageFile = $routes[$page];
}

logActivity('page_view', [
    'page' => $page,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referrer' => $_SERVER['HTTP_REFERER'] ?? ''
]);

$pageTitle = getPageTitle($page);
$metaDescription = getMetaDescription($page);
$currentPage = $page;

include 'includes/components/header.php';

if (file_exists($pageFile)) {
    include $pageFile;
} else {
    include 'pages/404.php';
}

include 'includes/components/footer.php';
?>