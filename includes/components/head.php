<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- SEO Meta Tags -->
<title><?php echo isset($page_title) ? escape_html(string: $page_title) : SITE_NAME; ?></title>
<meta name="description" content="<?php echo escape_html($page_description ?? 'Professional Roblox development studio offering game development, scripting services, and the revolutionary Vantara Framework.'); ?>">
<meta name="keywords" content="Roblox, Game Development, Scripting, Luau, Vantara Framework, Professional Development">
<meta name="author" content="<?php echo SITE_NAME; ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo get_base_url() . $_SERVER['REQUEST_URI']; ?>">

<link rel="shortcut icon" href="/assets/images/logo/BluFox_Studio_Logo.svg" type="image/x-icon">
<link rel="stylesheet" href="/assets/css/global.css">
<link rel="stylesheet" href="/assets/css/nav.css">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/assets/css/components.css">
<link rel="stylesheet" href="/assets/css/footer.css">

<link rel="stylesheet" href="/assets/css/pages/admin.css">
<link rel="stylesheet" href="/assets/css/pages/home.css">
<link rel="stylesheet" href="/assets/css/pages/about.css">
<link rel="stylesheet" href="/assets/css/pages/contact.css">
<link rel="stylesheet" href="/assets/css/pages/projects.css">
<link rel="stylesheet" href="/assets/css/pages/services.css">

<link rel="stylesheet" href="/assets/css/hero/hero.css">

<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<script src="/assets/js/main.js"></script>
<script src="/assets/js/nav.js"></script>