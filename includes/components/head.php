<?php
// includes/components/head.php
// Meta and asset management for BluFox Studio
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- SEO Meta Tags -->
<title><?php echo $page_title ?? 'BluFox Studio - Professional Roblox Development'; ?></title>
<meta name="description" content="<?php echo $page_description ?? 'BluFox Studio - Professional Roblox game development, scripting services'; ?>">
<meta name="keywords" content="Roblox development, game scripting, Lua programming, game studio">
<meta name="author" content="BluFox Studio">

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="<?php echo $page_title ?? 'BluFox Studio - Professional Roblox Development'; ?>">
<meta property="og:description" content="<?php echo $page_description ?? 'BluFox Studio - Professional Roblox game development, scripting services.'; ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="https://blufox-studio.com<?php echo $_SERVER['REQUEST_URI']; ?>">
<meta property="og:image" content="https://blufox-studio.com/assets/images/og-image.png">
<meta property="og:site_name" content="BluFox Studio">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $page_title ?? 'BluFox Studio - Professional Roblox Development'; ?>">
<meta name="twitter:description" content="<?php echo $page_description ?? 'BluFox Studio - Professional Roblox game development, scripting services.'; ?>">
<meta name="twitter:image" content="https://blufox-studio.com/assets/images/twitter-card.png">

<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="/assets/images/logo/BluFox_Studio_Logo.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/logo/BluFox_Studio_Logo.svg">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/logo/BluFox_Studio_Logo.svg">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/logo/BluFox_Studio_Logo.svg">

<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- CSS Files - Order matters -->
<link rel="stylesheet" href="/assets/css/global.css">
<link rel="stylesheet" href="/assets/css/components.css">
<link rel="stylesheet" href="/assets/css/nav.css">
<link rel="stylesheet" href="/assets/css/header.css">
<link rel="stylesheet" href="/assets/css/hero/hero.css">

<?php
// Page-specific CSS
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_css_files = [
    'home' => '/assets/css/pages/home.css',
    'about' => '/assets/css/pages/about.css',
    'contact' => '/assets/css/pages/contact.css',
    'projects' => '/assets/css/pages/projects.css',
    'services' => '/assets/css/pages/services.css'
];

if (isset($page_css_files[$current_page])) {
    echo '<link rel="stylesheet" href="' . $page_css_files[$current_page] . '">';
}
?>

<!-- Theme Color -->
<meta name="theme-color" content="#0A0A0F">
<meta name="msapplication-TileColor" content="#0A0A0F">

<!-- Canonical URL -->
<link rel="canonical" href="https://blufox-studio.com<?php echo $_SERVER['REQUEST_URI']; ?>">

<!-- Preload critical resources -->
<link rel="preload" href="/assets/images/logo/BluFox_Studio_Logo.svg" as="image" type="image/svg+xml">

<!-- Security Headers -->
<meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
<meta http-equiv="X-Content-Type-Options" content="nosniff">

<!-- Scripts -->
<script src="/assets/js/main.js"></script>
<script src="/assets/js/nav.js"></script>

<!-- JSON-LD Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "BluFox Studio",
  "url": "https://blufox-studio.com",
  "logo": "https://blufox-studio.com/assets/images/logo/BluFox_Studio_Logo.svg",
  "description": "Professional Roblox game development studio specializing in scripting services.",
  "sameAs": [
    "https://www.roblox.com/users/YourRobloxProfile"
  ],
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "",
    "contactType": "Customer Service",
    "email": "contact@blufox-studio.com"
  }
}
</script>