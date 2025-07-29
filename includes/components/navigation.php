<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="bg-animation"></div>
<div class="matrix-grid"></div>
<div class="particles" id="particles"></div>

<nav class="navbar" id="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="/" class="brand-link">
                <img src="<?php echo asset_url('images/logo/BluFox_Studio_Logo.svg'); ?>" alt="BluFox Studio Logo"
                    class="brand-logo">
                <span class="brand-text">BluFox Studio</span>
            </a>
        </div>

        <div class="nav-menu" id="nav-menu">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="/" class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#services" class="nav-link">
                        Services
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/portfolio" class="nav-link <?php echo $current_page == 'portfolio' ? 'active' : ''; ?>">
                        Portfolio
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/about" class="nav-link <?php echo $current_page == 'about' ? 'active' : ''; ?>">
                        About
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/contact" class="nav-link <?php echo $current_page == 'contact' ? 'active' : ''; ?>">
                        Contact
                    </a>
                </li>
            </ul>

            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <div class="user-menu" id="user-menu">
                        <button class="user-menu-toggle" id="user-menu-toggle">
                            <img src="<?php echo escape_html($_SESSION['user_avatar'] ?? asset_url('images/default-avatar.png')); ?>"
                                alt="<?php echo escape_html($_SESSION['username'] ?? 'User'); ?>" class="user-avatar">
                            <span class="user-name"><?php echo escape_html($_SESSION['username'] ?? 'User'); ?></span>
                            <svg class="dropdown-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="/dashboard" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                Dashboard
                            </a>
                            <a href="/profile" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout" class="dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <path d="M21 12H9" />
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <a href="/auth/login" class="btn btn-roblox">
                        <img src="<?php echo asset_url('images/icons/roblox_icon.png'); ?>" alt="Roblox"
                            class="icon roblox-login-icon">
                        Login with Roblox
                    </a>
                <?php endif; ?>

                <button class="theme-toggle" id="theme-toggle" aria-label="Toggle dark mode">
                    <svg class="theme-icon theme-icon-light" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <circle cx="12" cy="12" r="5" />
                        <path
                            d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                    </svg>
                    <svg class="theme-icon theme-icon-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg>
                </button>
            </div>
        </div>

        <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle mobile menu">
            <div class="hamburger">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
        </button>
    </div>
</nav>