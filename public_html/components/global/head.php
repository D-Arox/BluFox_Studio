<?php
$currentUrl = SITE_URL . $_SERVER['REQUEST_URI'];
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="index, follow">
<meta name="author" content="BluFox Studio">
<meta name="language" content="en">

<link rel="canonical" href="<?php echo htmlspecialchars($currentUrl); ?>">

<link rel="icon" type="image/png" sizes="32x32" href="/assets/images/icons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/images/icons/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/icons/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563eb">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link rel="stylesheet" href="/assets/css/global.css?v=<?php echo filemtime(__DIR__ . '/../../assets/css/global.css'); ?>">

<?php echo generateJSONLD('Organization', [
    'name' => 'BluFox Studio',
    'url' => SITE_URL,
    'logo' => SITE_URL . '/assets/images/logos/blufox-logo.png',
    'description' => 'Professional Roblox development tools and services including the Vault DataStore System.',
    'sameAs' => [
        YOUTUBE_URL,
        INSTAGRAM_URL,
        TWITTER_URL,
        DISCORD_URL,
        ROBLOX_GROUP_URL
    ],
    'foundingDate' => '2023',
    'knowsAbout' => [
        'Roblox Development',
        'DataStore Systems',
        'Game Development Tools',
        'Lua Programming'
    ]
]); ?>

<?php echo generateJSONLD('WebSite', [
    'name' => SITE_NAME,
    'url' => SITE_URL,
    'description' => 'Professional Roblox development tools and services.',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => [
            '@type' => 'EntryPoint',
            'urlTemplate' => SITE_URL . '/search?q={search_term_string}'
        ],
        'query-input' => 'required name=search_term_string'
    ]
]); ?>

<meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
<meta http-equiv="Content-Security-Policy" content="<?php echo getCSPPolicy(); ?>">