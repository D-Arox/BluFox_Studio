<?php
require_once __DIR__ . '/../../classes/MainClass.php';
$mainClass = new MainClass();
$currentUser = $mainClass->getCurrentUser();
$isAuthenticated = $mainClass->isAuthenticated();
?>
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="header-brand">
                <a href="/" class="logo-link">
                    <img src="/assets/images/logos/blufox-logo.svg" alt="BluFox Studio" class="logo">
                    <span class="brand-text">BluFox Studio</span>
                </a>
            </div>

            <nav class="main-nav" role="navigation" aria-label="Main navigation">
                <ul class="nav-list">
                    <li><a href="/" class="nav-link">Home</a></li>
                    <li><a href="/portfolio" class="nav-link">Portfolio</a></li>
                    <li><a href="/products" class="nav-link">Products</a></li>
                    <li><a href="/vault" class="nav-link">Vault</a></li>
                    <li><a href="/documentation" class="nav-link">Documentation</a></li>
                    <li><a href="/contact" class="nav-link">Contact</a></li>
                </ul>
            </nav>

            <div class="header-actions">
                <?php if ($isAuthenticated): ?>
                    <div class="user-menu">
                        <button class="user-menu-toggle" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars($currentUser['avatar_url'] ?? '/assets/images/default-avatar.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($currentUser['username']); ?>" 
                                 class="user-avatar">
                            <span class="user-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                            <i class="icon-chevron-down"></i>
                        </button>

                        <div class="user-dropdown">
                            <a href="/dashboard" class="dropdown-item">
                                <i class="icon-dashboard"></i> Dashboard
                            </a>
                            <a href="/profile" class="dropdown-item">
                                <i class="icon-user"></i> Profile
                            </a>
                            <a href="/downloads" class="dropdown-item">
                                <i class="icon-download"></i> Downloads
                            </a>
                            <hr class="dropdown-divider">
                            <a href="/auth/logout" class="dropdown-item">
                                <i class="icon-logout"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/auth/login" class="btn btn-primary">
                        <i class="icon-roblox"></i> Login
                    </a>
                <?php endif; ?>
                <button class="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</header>