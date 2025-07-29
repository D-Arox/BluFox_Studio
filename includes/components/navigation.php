<?php

$currentUser = Auth::user();
$isAdmin = Auth::isAdmin();

?>

<nav class="main-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="/" class="brand-link">
                <img src="/assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio" class="brand-logo">
                <span class="brand-text">BluFox Studio</span>
            </a>
        </div>

        <div class="nav-links">
            <a href="/" class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>">Home</a>
            <a href="/projects" class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>">Projects</a>
            <a href="/services" class="nav-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>">Services</a>
            <a href="/vantara" class="nav-link <?php echo $currentPage === 'vantara' ? 'active' : ''; ?>">Vantara</a>
            <a href="/about" class="nav-link <?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a>
            <a href="/contact" class="nav-link <?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a>
        </div>

        <div class="nav-user">
            <?php if ($currentUser): ?>
                <div class="user-dropdown">
                    <button class="user-toggle" onclick="toggleUserDropdown()">
                        <img src="<?php echo escape($currentUser['avatar_url'] ?: '/assets/images/team/placeholder-avatar.jpg'); ?>" 
                             alt="<?php echo escape($currentUser['display_name']); ?>" 
                             class="user-avatar">
                        <span class="user-name"><?php echo escape($currentUser['display_name']); ?></span>
                        <svg class="dropdown-arrow" viewBox="0 0 24 24" width="16" height="16">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    <div class="user-menu" id="user-menu">
                        <a href="/dashboard" class="menu-item">Dashboard</a>
                        <a href="/downloads" class="menu-item">Downloads</a>
                        <?php if ($isAdmin): ?>
                        <a href="/admin" class="menu-item">Admin Panel</a>
                        <?php endif; ?>
                        <hr class="menu-divider">
                        <a href="/auth/logout" class="menu-item">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/auth/login" class="auth-btn login-btn">
                    <img src="/assets/images/icons/roblox_icon.png" alt="Roblox" class="roblox-icon">
                    Login with Roblox
                </a>
            <?php endif; ?>
        </div>

         <button class="mobile-toggle" onclick="toggleMobileMenu()">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>

    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-links">
            <a href="/" class="mobile-link">Home</a>
            <a href="/projects" class="mobile-link">Projects</a>
            <a href="/services" class="mobile-link">Services</a>
            <a href="/vantara" class="mobile-link">Vantara</a>
            <a href="/about" class="mobile-link">About</a>
            <a href="/contact" class="mobile-link">Contact</a>
            
            <?php if ($currentUser): ?>
                <hr class="mobile-divider">
                <a href="/dashboard" class="mobile-link">Dashboard</a>
                <?php if ($isAdmin): ?>
                <a href="/admin" class="mobile-link">Admin Panel</a>
                <?php endif; ?>
                <a href="/auth/logout" class="mobile-link">Logout</a>
            <?php else: ?>
                <hr class="mobile-divider">
                <a href="/auth/login" class="mobile-link auth-link">
                    <svg class="roblox-icon" viewBox="0 0 24 24" width="18" height="18">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                    Login with Roblox
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script>
    window.loginWithRoblox = function() {
    const state = generateRandomString(32);
    sessionStorage.setItem('oauth_state', state);
    
    const params = new URLSearchParams({
        client_id: '<?php echo ROBLOX_CLIENT_ID; ?>',
        redirect_uri: '<?php echo ROBLOX_REDIRECT_URI; ?>',
        scope: 'openid profile',
        response_type: 'code',
        state: state
    });
    
    const authUrl = `https://apis.roblox.com/oauth/v1/authorize?${params.toString()}`;
    
    if (typeof trackEvent === 'function') {
        trackEvent('login_attempt', { method: 'roblox' });
    }
    
    sessionStorage.setItem('login_redirect', window.location.pathname);
    
    window.location.href = authUrl;
};

function generateRandomString(length) {
    const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    return result;
}
</script>