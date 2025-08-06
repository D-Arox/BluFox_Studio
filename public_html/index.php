<?php
require_once __DIR__ . '/classes/MainClass.php';
require_once __DIR__ . '/models/BaseModel.php';

$mainClass = new MainClass();
$portfolio = new Portfolio();
$product = new Product();

$featuredPortfolio = $portfolio->getFeatured(6);
$featuredProducts = $product->getFeatured(3);

$pageTitle = 'Professional Roblox Development Tools & Services';
$pageDescription = 'BluFox Studio offers premium Roblox development tools including the Vault DataStore System. Get real-time analytics, advanced data management, and professional support.';
$pageKeywords = 'Roblox development, DataStore system, Roblox tools, Vault DataStore, Roblox scripting, game development, Lua programming, BluFox Studio';
$ogImage = SITE_URL . '/assets/images/og-home.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/components/global/head.php'; ?>
    <?php echo generateSEOTags($pageTitle, $pageDescription, $pageKeywords, $ogImage); ?>
    
    <?php echo generateJSONLD('WebPage', [
        'name' => $pageTitle,
        'description' => $pageDescription,
        'url' => SITE_URL,
        'mainEntity' => [
            '@type' => 'Organization',
            'name' => 'BluFox Studio'
        ]
    ]); ?>
    
    <?php echo generateJSONLD('FAQPage', [
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'What is the Vault DataStore System?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Vault is a professional DataStore management system for Roblox that provides automatic backups, real-time analytics, memory optimization, and advanced error handling.'
                ]
            ],
            [
                '@type' => 'Question',
                'name' => 'How does the web dashboard work?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'The web dashboard provides real-time monitoring of your game\'s DataStore performance, player statistics, error tracking, and detailed analytics accessible from any device.'
                ]
            ]
        ]
    ]); ?>
</head>
<body>
    <?php include __DIR__ . '/components/global/header.php'; ?>
    
    <section class="hero" id="hero">
        <div class="hero-background">
            <div class="hero-particles"></div>
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Professional <span class="gradient-text">Roblox Development</span> Tools
                    </h1>
                    <p class="hero-description">
                        Elevate your Roblox games with our premium development tools. 
                        From advanced DataStore systems to real-time analytics, 
                        we provide everything you need to build professional games.
                    </p>
                    
                    <div class="hero-actions">
                        <a href="/vault" class="btn btn-primary btn-large">
                            <i class="icon-database"></i>
                            Explore Vault DataStore
                        </a>
                        <a href="/portfolio" class="btn btn-secondary btn-large">
                            <i class="icon-play"></i>
                            View Portfolio
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat">
                            <span class="stat-number">50K+</span>
                            <span class="stat-label">Operations/Hour</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">99.9%</span>
                            <span class="stat-label">Uptime</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Games Using Vault</span>
                        </div>
                    </div>
                </div>
                
                <div class="hero-visual">
                    <div class="hero-dashboard">
                        <!-- Animated dashboard preview -->
                        <div class="dashboard-window">
                            <div class="dashboard-header">
                                <div class="window-controls">
                                    <span class="control red"></span>
                                    <span class="control yellow"></span>
                                    <span class="control green"></span>
                                </div>
                                <span class="window-title">Vault Dashboard</span>
                            </div>
                            <div class="dashboard-content">
                                <div class="stats-row">
                                    <div class="stat-card">
                                        <span class="stat-value">1,247</span>
                                        <span class="stat-name">Active Players</span>
                                    </div>
                                    <div class="stat-card">
                                        <span class="stat-value">99.2%</span>
                                        <span class="stat-name">Performance</span>
                                    </div>
                                </div>
                                <div class="chart-area">
                                    <div class="chart-placeholder"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose BluFox Studio?</h2>
                <p>Professional tools built by developers, for developers</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-database"></i>
                    </div>
                    <h3>Advanced DataStore Management</h3>
                    <p>Professional-grade DataStore system with automatic backups, versioning, and conflict resolution.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-chart"></i>
                    </div>
                    <h3>Real-time Analytics</h3>
                    <p>Monitor your game's performance with detailed analytics, player statistics, and error tracking.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-shield"></i>
                    </div>
                    <h3>Enterprise Security</h3>
                    <p>Bank-level security with encryption, rate limiting, and comprehensive audit logs.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-support"></i>
                    </div>
                    <h3>Premium Support</h3>
                    <p>Get direct support from experienced Roblox developers with guaranteed response times.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-cloud"></i>
                    </div>
                    <h3>Web Dashboard</h3>
                    <p>Access your game statistics and manage settings from anywhere with our responsive web dashboard.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-code"></i>
                    </div>
                    <h3>Developer Friendly</h3>
                    <p>Easy integration with comprehensive documentation and code examples for rapid implementation.</p>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($featuredProducts)): ?>
    <section class="products" id="products">
        <div class="container">
            <div class="section-header">
                <h2>Our Products</h2>
                <p>Professional tools to supercharge your Roblox development</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($featuredProducts as $productItem): ?>
                <article class="product-card">
                    <div class="product-header">
                        <h3><?php echo htmlspecialchars($productItem['name']); ?></h3>
                        <div class="product-price">
                            $<?php echo number_format($productItem['price'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="product-content">
                        <p><?php echo htmlspecialchars($productItem['short_description']); ?></p>
                        
                        <?php if ($productItem['features']): ?>
                        <ul class="product-features">
                            <?php foreach (json_decode($productItem['features'], true) as $feature): ?>
                            <li><i class="icon-check"></i> <?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-actions">
                        <a href="/products/<?php echo $productItem['slug']; ?>" class="btn btn-primary">
                            Learn More
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <a href="/products" class="btn btn-outline">View All Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($featuredPortfolio)): ?>
    <section class="portfolio" id="portfolio">
        <div class="container">
            <div class="section-header">
                <h2>Our Work</h2>
                <p>Showcasing our best Roblox development projects</p>
            </div>
            
            <div class="portfolio-grid">
                <?php foreach ($featuredPortfolio as $portfolioItem): ?>
                <article class="portfolio-item">
                    <div class="portfolio-image">
                        <img src="<?php echo htmlspecialchars($portfolioItem['thumbnail_url'] ?? '/assets/images/portfolio/placeholder.png'); ?>" 
                             alt="<?php echo htmlspecialchars($portfolioItem['title']); ?>"
                             loading="lazy">
                        <div class="portfolio-overlay">
                            <div class="portfolio-actions">
                                <a href="/portfolio/<?php echo $portfolioItem['slug']; ?>" class="btn btn-primary">
                                    View Project
                                </a>
                                <?php if ($portfolioItem['roblox_game_id']): ?>
                                <a href="https://www.roblox.com/games/<?php echo $portfolioItem['roblox_game_id']; ?>" 
                                   class="btn btn-secondary" target="_blank" rel="noopener">
                                    Play Game
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="portfolio-content">
                        <h3><?php echo htmlspecialchars($portfolioItem['title']); ?></h3>
                        <p><?php echo htmlspecialchars($portfolioItem['short_description']); ?></p>
                        
                        <div class="portfolio-meta">
                            <span class="portfolio-category"><?php echo ucfirst($portfolioItem['category']); ?></span>
                            <?php if ($portfolioItem['view_count']): ?>
                            <span class="portfolio-views"><?php echo number_format($portfolioItem['view_count']); ?> views</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            
            <div class="section-footer">
                <a href="/portfolio" class="btn btn-outline">View Full Portfolio</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Elevate Your Roblox Games?</h2>
                <p>Join hundreds of developers who trust BluFox Studio for their professional Roblox development needs.</p>
                
                <div class="cta-actions">
                    <?php if ($mainClass->isAuthenticated()): ?>
                        <a href="/dashboard" class="btn btn-primary btn-large">
                            <i class="icon-dashboard"></i>
                            Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="/auth/login" class="btn btn-primary btn-large">
                            <i class="icon-roblox"></i>
                            Get Started Free
                        </a>
                    <?php endif; ?>
                    
                    <a href="/contact" class="btn btn-secondary btn-large">
                        <i class="icon-mail"></i>
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="social-links">
        <div class="container">
            <div class="social-content">
                <h3>Connect With Us</h3>
                <div class="social-icons">
                    <a href="<?php echo YOUTUBE_URL; ?>" class="social-link youtube" target="_blank" rel="noopener" aria-label="YouTube">
                        <i class="icon-youtube"></i>
                    </a>
                    <a href="<?php echo DISCORD_URL; ?>" class="social-link discord" target="_blank" rel="noopener" aria-label="Discord">
                        <i class="icon-discord"></i>
                    </a>
                    <a href="<?php echo TWITTER_URL; ?>" class="social-link twitter" target="_blank" rel="noopener" aria-label="Twitter">
                        <i class="icon-twitter"></i>
                    </a>
                    <a href="<?php echo INSTAGRAM_URL; ?>" class="social-link instagram" target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="icon-instagram"></i>
                    </a>
                    <a href="<?php echo ROBLOX_GROUP_URL; ?>" class="social-link roblox" target="_blank" rel="noopener" aria-label="Roblox Group">
                        <i class="icon-roblox"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <?php include __DIR__ . '/components/global/footer.php'; ?>
</body>
</html>