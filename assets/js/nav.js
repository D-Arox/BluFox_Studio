// Enhanced navigation script with theme integration and particle effects
document.addEventListener('DOMContentLoaded', function() {
    const bgAnimation = document.querySelector('.bg-animation')
    const navbar = document.querySelector('.navbar');
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const userMenuToggle = document.querySelector('#user-menu-toggle');
    const userDropdown = document.querySelector('#user-dropdown');

    // Create floating particles with theme awareness
    function createParticles() {
        // Create particles container if it doesn't exist
        let particlesContainer = document.querySelector('.navbar-particles');
        if (!particlesContainer) {
            particlesContainer = document.createElement('div');
            particlesContainer.className = 'navbar-particles';
            particlesContainer.style.cssText = `
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: -1;
            `;
            bgAnimation?.appendChild(particlesContainer);
        }

        // Clear existing particles
        particlesContainer.innerHTML = '';

        const particleCount = window.innerWidth < 768 ? 8 : 15;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'navbar-particle';
            particle.style.cssText = `
                position: absolute;
                width: ${Math.random() * 3 + 1}px;
                height: ${Math.random() * 3 + 1}px;
                background: rgba(0, 255, 255, ${Math.random() * 0.3 + 0.1});
                border-radius: 50%;
                pointer-events: none;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: navFloat ${Math.random() * 8 + 6}s infinite ease-in-out;
                animation-delay: ${Math.random() * 4}s;
            `;
            particlesContainer.appendChild(particle);
        }
    }

    // Create particle animation keyframes
    if (!document.getElementById('navbar-particle-styles')) {
        const style = document.createElement('style');
        style.id = 'navbar-particle-styles';
        style.textContent = `
            @keyframes navFloat {
                0%, 100% {
                    transform: translateY(0px) translateX(0px) scale(1);
                    opacity: 0.4;
                }
                25% {
                    transform: translateY(-15px) translateX(8px) scale(1.1);
                    opacity: 0.8;
                }
                50% {
                    transform: translateY(-8px) translateX(-12px) scale(0.9);
                    opacity: 0.6;
                }
                75% {
                    transform: translateY(-20px) translateX(4px) scale(1.05);
                    opacity: 0.7;
                }
            }
        `;
        document.head.appendChild(style);
    }

    createParticles();

    // Enhanced navbar scroll effect
    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateNavbar() {
        const scrollY = window.scrollY;

        if (navbar) {
            if (scrollY > 50) {
                navbar.classList.add('scrolled');
                navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3), 0 0 40px rgba(0, 255, 255, 0.1)';
            } else {
                navbar.classList.remove('scrolled');
                navbar.style.boxShadow = 'none';
            }

            // Hide/show nav on scroll direction (optional)
            if (scrollY > lastScrollY && scrollY > 200) {
                navbar.classList.add('nav-hidden');
            } else {
                navbar.classList.remove('nav-hidden');
            }
        }

        lastScrollY = scrollY;
        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) {
            requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    });

    // Mobile menu functionality with enhanced animations
    if (mobileToggle && mobileMenu) {
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isExpanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            
            mobileToggle.setAttribute('aria-expanded', !isExpanded);
            mobileToggle.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            mobileMenu.setAttribute('aria-hidden', isExpanded);
            
            // Prevent body scroll when menu is open
            document.body.classList.toggle('mobile-menu-open', !isExpanded);
            document.body.style.overflow = !isExpanded ? 'hidden' : '';

            // Add ripple effect
            createRippleEffect(mobileToggle, e);
        });

        // Close mobile menu when clicking nav links
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-link, .mobile-user-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('mobile-menu-open');
                document.body.style.overflow = '';
            });
        });

        // Close mobile menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                mobileToggle.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobileMenu.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('mobile-menu-open');
                document.body.style.overflow = '';
            }
        });
    }

    // User dropdown functionality with enhanced feedback
    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            
            // Add ripple effect
            createRippleEffect(userMenuToggle, e);
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });

        // Close dropdown when pressing escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('active');
            }
        });
    }

    // Enhanced click outside handling
    document.addEventListener('click', function(e) {
        // Close mobile menu if clicking outside
        if (mobileMenu && !mobileMenu.contains(e.target) && !mobileToggle?.contains(e.target)) {
            mobileMenu.classList.remove('active');
            mobileToggle?.classList.remove('active');
            mobileToggle?.setAttribute('aria-expanded', 'false');
            mobileMenu.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('mobile-menu-open');
            document.body.style.overflow = '';
        }
    });

    // Active link management
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (href === '/' && (currentPath === '/' || currentPath === '/index.php'))) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Enhanced smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);

            if (target) {
                const offset = navbar?.offsetHeight || 80;
                const targetPosition = target.offsetTop - offset;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Ripple effect function
    function createRippleEffect(element, event) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: radial-gradient(circle, rgba(0, 255, 255, 0.4) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: navRipple 0.6s ease-out;
            z-index: 1;
        `;

        // Add ripple animation if not exists
        if (!document.getElementById('nav-ripple-animation')) {
            const style = document.createElement('style');
            style.id = 'nav-ripple-animation';
            style.textContent = `
                @keyframes navRipple {
                    from {
                        transform: scale(0);
                        opacity: 1;
                    }
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        const container = element.querySelector('.ripple-container') || element;
        container.style.position = 'relative';
        container.style.overflow = 'hidden';
        container.appendChild(ripple);

        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    }

    // Performance optimization for resize events
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            createParticles();
        }, 250);
    });

    // Add hover effects to social links
    const socialLinks = document.querySelectorAll('.social-link, .mobile-social-link');
    socialLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.1)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Add enhanced hover effects to navigation links
    const allNavLinks = document.querySelectorAll('.nav-link');
    allNavLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            // Create subtle glow effect
            this.style.textShadow = '0 0 10px rgba(0, 255, 255, 0.6)';
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.textShadow = 'none';
            }
        });
    });

    // Initialize particles on load
    setTimeout(createParticles, 500);
});