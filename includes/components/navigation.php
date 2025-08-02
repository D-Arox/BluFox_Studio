<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_uri = $_SERVER['REQUEST_URI'];

// Define navigation items
$nav_items = [
    'home' => ['url' => '/', 'label' => 'Home'],
    'about' => ['url' => '/about', 'label' => 'About'],
    'services' => ['url' => '/services', 'label' => 'Services'],
    'projects' => ['url' => '/projects', 'label' => 'Projects'],
    'contact' => ['url' => '/contact', 'label' => 'Contact']
];

// Helper function to check if nav item is active
function isActiveNav($url, $current_uri) {
    if ($url === '/' && ($current_uri === '/' || $current_uri === '/index.php')) {
        return true;
    }
    return strpos($current_uri, $url) === 0 && $url !== '/';
}
?>

<div class="bg-animation">
    <div class="matrix-grid"></div>
    <div class="particles" id="particles"></div>
</div>

<nav class="navbar" id="navbar">
    <div class="nav-container">
        <!-- Logo/Brand -->
        <div class="nav-brand">
            <a href="/" class="brand-link" aria-label="BluFox Studio Home">
                <img src="/assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio" class="brand-logo">
                <span class="brand-text">BluFox Studio</span>
            </a>
        </div>

        <!-- Main Navigation Menu -->
        <div class="nav-menu">
            <ul class="nav-list" role="menubar">
                <?php foreach ($nav_items as $key => $item): ?>
                    <li class="nav-item" role="none">
                        <a href="<?php echo $item['url']; ?>" 
                           class="nav-link <?php echo isActiveNav($item['url'], $current_uri) ? 'active' : ''; ?>"
                           role="menuitem"
                           aria-current="<?php echo isActiveNav($item['url'], $current_uri) ? 'page' : 'false'; ?>">
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Action Buttons -->
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    <div class="user-menu">
                        <button class="user-avatar" id="user-menu-toggle">
                            <img src="<?php echo $_SESSION['user_avatar'] ?? '/assets/images/default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" 
                                 class="avatar-img">
                            <span class="username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        
                        <div class="user-dropdown" id="user-dropdown">
                            <a href="/dashboard" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                    <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                    <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                    <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Dashboard
                            </a>
                            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                <a href="/admin" class="dropdown-item">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    Admin Panel
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout" class="dropdown-item">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- User is not logged in -->
                    <a href="/auth/login" class="btn btn-roblox">
                        <img src="/assets/images/icons/roblox_icon.png" alt="" class="roblox-login-icon">
                        Login with Roblox
                    </a>
                <?php endif; ?>

                <!-- Social Links -->
                <div class="social-links">
                    <a href="https://www.roblox.com/communities/16787120/BluFox#!/about" class="social-link roblox" aria-label="Roblox Group" target="_blank">
                        <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="social-nav-icon">
                    </a>
                    <a href="https://discord.gg/gYSNjEG6g7" class="social-link discord" aria-label="Discord Server" target="_blank">
                        <img src="/assets/images/icons/discord_icon.png" alt="Discord" class="social-nav-icon">
                    </a>
                    <a href="https://www.youtube.com/@BluFox-studio" class="social-link youtube" aria-label="YouTube Channel" target="_blank">
                        <img src="/assets/images/icons/youtube_icon.png" alt="YouTube" class="social-nav-icon">
                    </a>
                    <a href="https://x.com/blufox_studio" class="social-link twitter" aria-label="Twitter" target="_blank">
                        <img src="/assets/images/icons/twitter_icon.png" alt="Twitter" class="social-nav-icon">
                    </a>
                    <a href="https://www.instagram.com/blufox_studio/" class="social-link instagram" aria-label="Instagram" target="_blank">
                        <img src="/assets/images/icons/instagram_icon.png" alt="Instagram" class="social-nav-icon">
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" 
                aria-label="Toggle mobile menu" 
                aria-expanded="false"
                aria-controls="mobile-menu">
            <div class="hamburger">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobile-menu" aria-hidden="true">
        <div class="mobile-menu-content">
            <!-- Mobile Navigation Links -->
            <ul class="mobile-nav-list" role="menu">
                <?php foreach ($nav_items as $key => $item): ?>
                    <li class="mobile-nav-item" role="none">
                        <a href="<?php echo $item['url']; ?>" 
                           class="mobile-nav-link <?php echo isActiveNav($item['url'], $current_uri) ? 'active' : ''; ?>"
                           role="menuitem">
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Mobile Actions -->
            <div class="mobile-menu-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged in user options -->
                    <div class="mobile-user-info">
                        <img src="<?php echo $_SESSION['user_avatar'] ?? '/assets/images/default-avatar.png'; ?>" 
                             alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" 
                             class="mobile-user-avatar">
                        <span class="mobile-username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    </div>
                    
                    <div class="mobile-user-links">
                        <a href="/dashboard" class="mobile-user-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="3" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="3" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="14" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                                <rect x="3" y="14" width="7" height="7" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Dashboard
                        </a>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                            <a href="/admin" class="mobile-user-link">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="/auth/logout" class="mobile-user-link logout">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Not logged in - show login button -->
                    <a href="/auth/login" class="btn btn-roblox btn-mobile">
                        <img src="/assets/images/icons/roblox_icon.png" alt="" class="roblox-login-icon">
                        Login with Roblox
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Social Links -->
            <div class="mobile-social-links">
                <a href="https://www.roblox.com/groups/" class="mobile-social-link roblox" aria-label="Roblox Group">
                    <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="mobile-social-icon">
                </a>
                <a href="https://discord.gg/" class="mobile-social-link discord" aria-label="Discord Server">
                    <img src="/assets/images/icons/discord_icon.png" alt="Discord" class="mobile-social-icon">
                </a>
                <a href="https://youtube.com/" class="mobile-social-link youtube" aria-label="YouTube Channel">
                    <img src="/assets/images/icons/youtube_icon.png" alt="YouTube" class="mobile-social-icon">
                </a>
                <a href="https://twitter.com/" class="mobile-social-link twitter" aria-label="Twitter">
                    <img src="/assets/images/icons/twitter_icon.png" alt="Twitter" class="mobile-social-icon">
                </a>
                <a href="https://instagram.com/" class="mobile-social-link instagram" aria-label="Instagram">
                    <img src="/assets/images/icons/instagram_icon.png" alt="Instagram" class="mobile-social-icon">
                </a>
            </div>
        </div>
    </div>
</nav>
