<?php
// includes/components/head.php
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- Primary Meta Tags -->
<title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'BluFox Studio - Professional Roblox Development'; ?></title>
<meta name="title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'BluFox Studio - Professional Roblox Development'; ?>">
<meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional Roblox game development, scripting services, and the revolutionary Vantara Framework.'; ?>">
<meta name="keywords" content="Roblox, Game Development, Scripting, Lua, Programming, Vantara Framework, BluFox Studio">
<meta name="author" content="BluFox Studio">
<meta name="robots" content="index, follow">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
<meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'BluFox Studio'; ?>">
<meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional Roblox Development Studio'; ?>">
<meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.png">
<meta property="og:site_name" content="BluFox Studio">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
<meta property="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) : 'BluFox Studio'; ?>">
<meta property="twitter:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional Roblox Development Studio'; ?>">
<meta property="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/twitter-image.png">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/assets/images/logo/BluFox_Studio_Logo.svg">
<!-- <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png"> -->
<!-- <link rel="manifest" href="/assets/images/site.webmanifest"> -->

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- CSS Files -->
<link rel="stylesheet" href="/assets/css/global.css">
<link rel="stylesheet" href="/assets/css/nav.css">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/assets/css/components.css">
<link rel="stylesheet" href="/assets/css/pages/home.css">
<link rel="stylesheet" href="/assets/css/hero/hero.css">

<!-- Page-specific CSS -->
<?php if (isset($page_css) && is_array($page_css)): ?>
    <?php foreach ($page_css as $css_file): ?>
        <link rel="stylesheet" href="/assets/css/<?php echo $css_file; ?>">
    <?php endforeach; ?>
<?php endif; ?>

<!-- Security Headers -->
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.roblox.com;">
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="X-XSS-Protection" content="1; mode=block">
<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

<!-- Theme -->
<meta name="theme-color" content="#0a0e27">
<meta name="color-scheme" content="dark">

<!-- Preload critical resources -->
<!-- <link rel="preload" href="/assets/css/global.css" as="style">
<link rel="preload" href="/assets/css/nav.css" as="style">
<link rel="preload" href="/assets/js/main.js" as="script"> -->

<!-- DNS prefetch for external domains -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//api.roblox.com">

<!-- Global site configuration -->
<script>
window.BLUFOX_CONFIG = {
    siteUrl: '<?php echo SITE_URL; ?>',
    apiUrl: '<?php echo API_URL; ?>',
    assetsUrl: '<?php echo ASSETS_URL; ?>',
    isLoggedIn: <?php echo isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'true' : 'false'; ?>,
    currentUser: <?php echo isset($_SESSION['user_id']) ? json_encode([
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['user_role'] ?? 'user'
    ]) : 'null'; ?>,
    csrfToken: '<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''; ?>',
    debug: <?php echo DEBUG_MODE ? 'true' : 'false'; ?>
};
</script>

<?php if (GOOGLE_ANALYTICS_ID && feature_enabled('analytics')): ?>
<!-- Google Analytics -->
<!-- <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script> -->
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>', {
    anonymize_ip: true,
    cookie_flags: 'SameSite=Lax;Secure'
});
</script>
<?php endif; ?>