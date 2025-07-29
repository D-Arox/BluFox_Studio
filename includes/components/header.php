<?php

$currentUser = Auth::user();
$flashMessage = getFlashMessage();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($pageTitle); ?></title>
    <meta name="description" content="<?php echo escape($metaDescription); ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo escape($pageTitle); ?>">
    <meta property="og:description" content="<?php echo escape($metaDescription); ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/logo/logo.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo escape($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo escape($metaDescription); ?>">
    <meta property="twitter:image" content="<?php echo SITE_URL; ?>/assets/images/logo/logo.png">
    
    <link rel="icon" type="image/x-icon" href="/assets/images/logo/BluFox_Studio_Logo.svg">
    <link rel="apple-touch-icon" href="/assets/images/logo/BluFox_Studio_Logo.svg">

    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <?php if (file_exists("assets/css/pages/{$currentPage}.css")): ?>
    <link rel="stylesheet" href="/assets/css/pages/<?php echo $currentPage; ?>.css">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo SITE_NAME; ?>",
        "url": "<?php echo SITE_URL; ?>",
        "logo": "<?php echo SITE_URL; ?>/assets/images/logo/logo.png",
        "description": "<?php echo SITE_DESCRIPTION; ?>",
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+1-XXX-XXX-XXXX",
            "contactType": "customer service",
            "email": "<?php echo CONTACT_EMAIL; ?>"
        },
        "sameAs": [
            "https://github.com/blufox-studio",
            "https://discord.gg/blufox-studio"
        ]
    }
    </script>
</head>
<body class="<?php echo $currentPage; ?>-page">
    <?php include 'navigation.php'; ?>
    
    <?php if ($flashMessage): ?>
    <div class="flash-message flash-<?php echo $flashMessage['type']; ?>" id="flash-message">
        <div class="container">
            <span class="flash-text"><?php echo escape($flashMessage['text']); ?></span>
            <button class="flash-close" onclick="closeFlashMessage()">&times;</button>
        </div>
    </div>
    <?php endif; ?>
    
    <main class="main-content">