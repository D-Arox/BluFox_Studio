<?php

?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<title><?php echo isset($page_title) ? escape_html($page_title) . ' | ' . SITE_NAME : SITE_NAME; ?></title>
<meta name="description" content="<?php echo escape_html($page_description ?? 'Professional Roblox development studio offering game development, scripting services, and the revolutionary Vantara Framework.'); ?>">
<meta name="keywords" content="Roblox, Game Development, Scripting, Luau, Vantara Framework, Professional Development">
<meta name="author" content="<?php echo SITE_NAME; ?>">
<meta name="robots" content="index, follow">

<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo escape_html($page_title ?? SITE_NAME); ?>">
<meta property="og:description" content="<?php echo escape_html($page_description ?? 'Professional Roblox development studio offering cutting-edge solutions.'); ?>">
<meta property="og:url" content="<?php echo get_base_url() . $_SERVER['REQUEST_URI']; ?>">
<meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
<meta property="og:image" content="<?php echo asset_url('images/og-image.jpg'); ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo escape_html($page_title ?? SITE_NAME); ?>">
<meta name="twitter:description" content="<?php echo escape_html($page_description ?? 'Professional Roblox development studio offering cutting-edge solutions.'); ?>">
<meta name="twitter:image" content="<?php echo asset_url('images/og-image.jpg'); ?>">

<link rel="icon" type="image/svg+xml" href="<?php echo asset_url('images/logo/BluFox_Studio_Logo.svg'); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo asset_url('images/favicon-32x32.png'); ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo asset_url('images/favicon-16x16.png'); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo asset_url('images/apple-touch-icon.png'); ?>">
<link rel="manifest" href="<?php echo asset_url('manifest.json'); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?php echo asset_url('css/reset.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('css/variables.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('css/components.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('css/main.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('css/responsive.css'); ?>">

<meta name="theme-color" content="#4F9AD6">
<meta name="msapplication-TileColor" content="#4F9AD6">

<link rel="preload" href="<?php echo asset_url('images/logo/BluFox_Studio_Logo.svg'); ?>" as="image" type="image/svg+xml">
<link rel="preload" href="<?php echo asset_url('css/main.css'); ?>" as="style">

<link rel="dns-prefetch" href="//www.google-analytics.com">

<!-- Security Headers -->
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google-analytics.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';">

<!-- CSRF Token -->
<meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<?php echo SITE_NAME; ?>",
  "url": "<?php echo SITE_URL; ?>",
  "logo": "<?php echo asset_url('images/logo/BluFox_Studio_Logo.svg'); ?>",
  "description": "Professional Roblox development studio offering game development, scripting services, and the revolutionary Vantara Framework.",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "",
    "contactType": "customer service",
    "email": "<?php echo CONTACT_EMAIL; ?>"
  }
}
</script>

<?php if (defined('GOOGLE_ANALYTICS_ID') && GOOGLE_ANALYTICS_ID): ?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo GOOGLE_ANALYTICS_ID; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?php echo GOOGLE_ANALYTICS_ID; ?>');
</script>
<?php endif; ?>