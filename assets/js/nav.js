// Improved navigation script with theme integration
document.addEventListener("DOMContentLoaded", () => {
    const navbar = document.getElementById("navbar");
    const mobileToggle = document.getElementById("mobile-menu-toggle");
    const navMenu = document.getElementById("nav-menu");
    const userMenu = document.getElementById("user-menu");
    const userToggle = document.getElementById("user-menu-toggle");

    // Create floating particles with theme awareness
    function createParticles() {
        const particlesContainer = document.getElementById("particles");
        if (!particlesContainer) return;

        // Clear existing particles
        particlesContainer.innerHTML = '';

        const particleCount = window.innerWidth < 768 ? 10 : 20;
        const currentTheme = document.documentElement.getAttribute('data-theme');

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement("div");
            particle.className = "particle";
            particle.style.cssText = `
                position: absolute;
                width: ${Math.random() * 4 + 2}px;
                height: ${Math.random() * 4 + 2}px;
                background: ${currentTheme === 'light'
                    ? 'rgba(59, 130, 246, 0.3)'
                    : 'rgba(0, 255, 255, 0.3)'};
                border-radius: 50%;
                pointer-events: none;
                left: ${Math.random() * 100}%;
                top: ${Math.random() * 100}%;
                animation: float ${Math.random() * 10 + 10}s infinite ease-in-out;
                animation-delay: ${Math.random() * 5}s;
            `;
            particlesContainer.appendChild(particle);
        }
    }

    // Create particle animation keyframes
    if (!document.getElementById('particle-styles')) {
        const style = document.createElement('style');
        style.id = 'particle-styles';
        style.textContent = `
            @keyframes float {
                0%, 100% {
                    transform: translateY(0px) translateX(0px) scale(1);
                    opacity: 0.7;
                }
                25% {
                    transform: translateY(-20px) translateX(10px) scale(1.1);
                    opacity: 1;
                }
                50% {
                    transform: translateY(-10px) translateX(-15px) scale(0.9);
                    opacity: 0.8;
                }
                75% {
                    transform: translateY(-30px) translateX(5px) scale(1.05);
                    opacity: 0.9;
                }
            }
        `;
        document.head.appendChild(style);
    }

    createParticles();

    // Recreate particles when theme changes
    document.addEventListener('themechange', () => {
        setTimeout(createParticles, 200);
    });

    // Enhanced navbar scroll effect with theme awareness
    let lastScrollY = window.scrollY;
    let ticking = false;

    function updateNavbar() {
        const scrollY = window.scrollY;
        const currentTheme = document.documentElement.getAttribute('data-theme');

        if (scrollY > 100) {
            navbar?.classList.add("scrolled");
        } else {
            navbar?.classList.remove("scrolled");
        }

        // Hide navbar on scroll down, show on scroll up
        if (scrollY > lastScrollY && scrollY > 200) {
            if (navbar) navbar.style.transform = "translateY(-100%)";
        } else {
            if (navbar) navbar.style.transform = "translateY(0)";
        }

        // Add theme-aware glow effect when scrolling
        if (navbar && scrollY > 50) {
            navbar.style.boxShadow = currentTheme === 'light'
                ? '0 4px 20px rgba(0, 0, 0, 0.1)'
                : '0 4px 20px rgba(0, 0, 0, 0.3)';
        } else if (navbar) {
            navbar.style.boxShadow = 'none';
        }

        lastScrollY = scrollY;
        ticking = false;
    }

    window.addEventListener("scroll", () => {
        if (!ticking) {
            requestAnimationFrame(updateNavbar);
            ticking = true;
        }
    });

    // Mobile menu toggle with enhanced animations
    mobileToggle?.addEventListener("click", (e) => {
        e.stopPropagation();
        const isActive = navMenu?.classList.contains("active");

        navMenu?.classList.toggle("active");
        mobileToggle.classList.toggle("active");
        document.body.style.overflow = !isActive ? "hidden" : "";

        // Add ripple effect
        createRippleEffect(e.target, e);
    });

    // User menu toggle with enhanced feedback
    userToggle?.addEventListener("click", (e) => {
        e.stopPropagation();
        e.preventDefault();

        userMenu?.classList.toggle("active");
        createRippleEffect(e.currentTarget, e);

        console.log("User menu clicked, active:", userMenu?.classList.contains("active"));
    });

    // Enhanced click outside handling
    document.addEventListener("click", (e) => {
        // Close user dropdown if clicking outside
        if (userMenu && !userMenu.contains(e.target)) {
            userMenu.classList.remove("active");
        }

        // Close mobile menu if clicking outside
        if (navMenu && !navMenu.contains(e.target) && !mobileToggle?.contains(e.target)) {
            navMenu.classList.remove("active");
            mobileToggle?.classList.remove("active");
            document.body.style.overflow = "";
        }
    });

    // Close mobile menu when clicking nav links
    const navLinks = document.querySelectorAll(".nav-link");
    navLinks.forEach(link => {
        link.addEventListener("click", () => {
            navMenu?.classList.remove("active");
            mobileToggle?.classList.remove("active");
            document.body.style.overflow = "";
        });
    });

    // Active link management with theme awareness
    const currentPath = window.location.pathname;
    navLinks.forEach(link => {
        if (link.getAttribute("href") === currentPath) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });

    // Enhanced smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            const targetId = this.getAttribute("href");
            const target = document.querySelector(targetId);

            if (target) {
                const offset = navbar?.offsetHeight || 80;
                const targetPosition = target.offsetTop - offset;

                window.scrollTo({
                    top: targetPosition,
                    behavior: "smooth"
                });
            }
        });
    });

    // Enhanced keyboard navigation
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            navMenu?.classList.remove("active");
            mobileToggle?.classList.remove("active");
            userMenu?.classList.remove("active");
            document.body.style.overflow = "";
        }
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
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: ripple 0.6s ease-out;
            z-index: 1;
        `;

        // Add ripple animation if not exists
        if (!document.getElementById('ripple-animation')) {
            const style = document.createElement('style');
            style.id = 'ripple-animation';
            style.textContent = `
                @keyframes ripple {
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

    // Add intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
            }
        });
    }, observerOptions);

    // Observe elements for scroll animations
    document.querySelectorAll('.card, .btn, section').forEach(el => {
        observer.observe(el);
    });

    // Performance optimization for resize events
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            createParticles();
        }, 250);
    });
});