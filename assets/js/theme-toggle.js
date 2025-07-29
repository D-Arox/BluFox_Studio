class ThemeToggler {
    constructor() {
        this.currentTheme = this.getStoredTheme() || this.getPreferredTheme();
        this.toggleButton = null;
        this.init();
    }

    init() {
        // Set initial theme
        this.setTheme(this.currentTheme);

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupToggleButton());
        } else {
            this.setupToggleButton();
        }
    }

    getPreferredTheme() {
        return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }

    getStoredTheme() {
        return localStorage.getItem('theme');
    }

    setStoredTheme(theme) {
        localStorage.setItem('theme', theme);
    }

    setTheme(theme) {
        this.currentTheme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        this.setStoredTheme(theme);
        this.updateToggleButton();
        this.dispatchThemeChangeEvent(theme);
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';

        // Add switching animation class
        if (this.toggleButton) {
            this.toggleButton.classList.add('switching');
            setTimeout(() => {
                this.toggleButton.classList.remove('switching');
            }, 600);
        }

        // Add page transition effect
        this.addPageTransition();

        // Set new theme after a brief delay for smooth transition
        setTimeout(() => {
            this.setTheme(newTheme);
        }, 150);
    }

    addPageTransition() {
        // Create a smooth transition overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1), transparent);
            pointer-events: none;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;

        document.body.appendChild(overlay);

        // Fade in and out
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            setTimeout(() => {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 300);
            }, 150);
        });
    }

    setupToggleButton() {
        this.toggleButton = document.getElementById('theme-toggle');

        if (this.toggleButton) {
            this.toggleButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });

            // Add keyboard support
            this.toggleButton.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleTheme();
                }
            });

            this.updateToggleButton();
        }
    }

    updateToggleButton() {
        if (!this.toggleButton) return;

        const lightIcon = this.toggleButton.querySelector('.theme-icon-light');
        const darkIcon = this.toggleButton.querySelector('.theme-icon-dark');

        if (lightIcon && darkIcon) {
            if (this.currentTheme === 'dark') {
                lightIcon.style.display = 'block';
                darkIcon.style.display = 'none';
                this.toggleButton.setAttribute('aria-label', 'Switch to light mode');
            } else {
                lightIcon.style.display = 'none';
                darkIcon.style.display = 'block';
                this.toggleButton.setAttribute('aria-label', 'Switch to dark mode');
            }
        }
    }

    dispatchThemeChangeEvent(theme) {
        const event = new CustomEvent('themechange', {
            detail: { theme }
        });
        document.dispatchEvent(event);
    }
}

// Initialize theme system
const themeToggler = new ThemeToggler();

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addListener((e) => {
    if (!themeToggler.getStoredTheme()) {
        themeToggler.setTheme(e.matches ? 'dark' : 'light');
    }
});

// Export for global access
window.themeToggler = themeToggler;

// Enhanced theme-aware animations
document.addEventListener('themechange', (e) => {
    const theme = e.detail.theme;

    // Animate elements on theme change
    const animatedElements = document.querySelectorAll('.card, .btn, .form-input');
    animatedElements.forEach((el, index) => {
        el.style.transform = 'scale(0.98)';
        el.style.transition = 'all 0.3s ease';

        setTimeout(() => {
            el.style.transform = 'scale(1)';
        }, 50 + (index * 20));
    });

    console.log(`Theme changed to: ${theme}`);
});