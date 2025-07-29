<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "Professional Roblox Development Studio";
$page_description = "BluFox Studio - Professional Roblox game development, scripting services, and the revolutionary Vantara Framework.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        <span class="gradient-text">BluFox Studio</span>
                        <br>Professional Roblox Development
                    </h1>
                    <p class="hero-subtitle">
                        Elevate your Roblox projects with our cutting-edge development services, 
                        featuring the revolutionary Vantara Framework and expert scripting solutions.
                    </p>
                    <div class="hero-buttons">
                        <a href="#services" class="btn btn-primary">Explore Services</a>
                        <a href="/vantara" class="btn btn-secondary">Vantara Framework</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="logo-container">
                        <img src="assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio Logo" class="hero-logo">
                        <div class="logo-glow"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-bg-elements">
            <div class="floating-element element-1"></div>
            <div class="floating-element element-2"></div>
            <div class="floating-element element-3"></div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Services</h2>
                <p class="section-subtitle">Professional Roblox development solutions tailored to your needs</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                            <path d="M2 17l10 5 10-5"/>
                            <path d="M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <h3>Game Development</h3>
                    <p>Full-scale Roblox game development from concept to deployment, featuring engaging gameplay mechanics and polished user experiences.</p>
                    <ul class="service-features">
                        <li>Custom gameplay systems</li>
                        <li>UI/UX design</li>
                        <li>Performance optimization</li>
                        <li>Cross-platform compatibility</li>
                    </ul>
                </div>

                <div class="service-card featured">
                    <div class="service-badge">Most Popular</div>
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="16 18 22 12 16 6"/>
                            <polyline points="8 6 2 12 8 18"/>
                        </svg>
                    </div>
                    <h3>Vantara Framework</h3>
                    <p>Revolutionary Roblox development framework that accelerates development with enterprise-grade architecture and pure Luau implementation.</p>
                    <ul class="service-features">
                        <li>Enterprise-grade architecture</li>
                        <li>Pure Luau implementation</li>
                        <li>Advanced data management</li>
                        <li>Studio plugin integration</li>
                    </ul>
                    <a href="/vantara" class="service-cta">Learn More</a>
                </div>

                <div class="service-card">
                    <div class="service-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"/>
                            <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                            <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                            <path d="M3 12h6m6 0h6"/>
                        </svg>
                    </div>
                    <h3>Professional Scripting</h3>
                    <p>Expert Luau scripting services for complex game mechanics, data systems, and performance-critical components.</p>
                    <ul class="service-features">
                        <li>Advanced scripting solutions</li>
                        <li>Data management systems</li>
                        <li>Security implementations</li>
                        <li>Code optimization</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section class="portfolio">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Featured Projects</h2>
                <p class="section-subtitle">Showcasing our finest Roblox development work</p>
            </div>
            
            <div class="portfolio-grid">
                <div class="portfolio-item">
                    <div class="portfolio-image">
                        <div class="portfolio-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21,15 16,10 5,21"/>
                            </svg>
                        </div>
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h4>Project Alpha</h4>
                                <p>Advanced RPG system with custom combat mechanics</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portfolio-item">
                    <div class="portfolio-image">
                        <div class="portfolio-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                                <polyline points="2,17 12,22 22,17"/>
                                <polyline points="2,12 12,17 22,12"/>
                            </svg>
                        </div>
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h4>Vantara Showcase</h4>
                                <p>Demonstration of framework capabilities</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="portfolio-item">
                    <div class="portfolio-image">
                        <div class="portfolio-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10,9 9,9 8,9"/>
                            </svg>
                        </div>
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h4>Enterprise Solution</h4>
                                <p>Large-scale multiplayer experience</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Build Something Amazing?</h2>
                <p>Let's discuss your next Roblox project and how BluFox Studio can bring your vision to life.</p>
                <div class="cta-buttons">
                    <a href="#contact" class="btn btn-primary">Start Your Project</a>
                    <a href="mailto:<?php echo CONTACT_EMAIL; ?>" class="btn btn-secondary">Get in Touch</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>