<?php

?>
</main>
    
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-section">
                    <div class="footer-brand">
                        <img src="/assets/images/logo/BluFox_Studio_Logo.svg" alt="BluFox Studio" class="footer-logo">
                        <h3>BluFox Studio</h3>
                        <p>Professional Roblox Development Studio creating amazing games, frameworks, and custom solutions.</p>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <ul class="footer-links">
                        <li><a href="/services#game-development">Game Development</a></li>
                        <li><a href="/services#scripting">Lua Scripting</a></li>
                        <li><a href="/services#frameworks">Custom Frameworks</a></li>
                        <li><a href="/services#consulting">Consulting</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Products</h4>
                    <ul class="footer-links">
                        <li><a href="/vantara">Vantara Framework</a></li>
                        <li><a href="/projects">Our Games</a></li>
                        <li><a href="/tools">Development Tools</a></li>
                        <li><a href="/assets">Roblox Assets</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul class="footer-links">
                        <li><a href="/about">About Us</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/blog">Blog</a></li>
                        <li><a href="/careers">Careers</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="https://discord.com/invite/gYSNjEG6g7" target="_blank" class="social-link discord" title="Discord">
                            <img class="footer-button-icon" src="/assets/images/icons/discord_icon.png" alt="Roblox">
                        </a>
                        <a href="https://www.roblox.com/communities/16787120/BluFox#!/about" target="_blank" class="social-link roblox" title="Roblox Profile">
                            <img class="footer-button-icon" src="/assets/images/icons/roblox_icon.png" alt="Roblox">
                        </a>
                    </div>
                    <div class="contact-info">
                        <p><strong>Email:</strong> <?php echo CONTACT_EMAIL; ?></p>
                        <p><strong>Response Time:</strong> 24-48 hours</p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-left">
                    <p>&copy; <?php echo date('Y'); ?> BluFox Studio. All rights reserved.</p>
                </div>
                <div class="footer-bottom-right">
                    <a href="/privacy" class="footer-link">Privacy Policy</a>
                    <a href="/terms" class="footer-link">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/components.js"></script>
    <?php if (file_exists("assets/js/pages/{$currentPage}.js")): ?>
    <script src="/assets/js/pages/<?php echo $currentPage; ?>.js"></script>
    <?php endif; ?>
    
    <script>
        if (typeof initAnalytics === 'function') {
            initAnalytics('<?php echo $currentPage; ?>', <?php echo Auth::check() ? 'true' : 'false'; ?>);
        }
    </script>
    
    <script>
        window.csrfToken = '<?php echo generateCSRFToken(); ?>';
    </script>
</body>
</html>