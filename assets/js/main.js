
// Utility function to get current theme
window.getCurrentTheme = () => {
    return document.documentElement.getAttribute('data-theme') || 'dark';
};

// Utility function to apply theme-aware styling
window.applyThemeAware = (element, lightStyles, darkStyles) => {
    const currentTheme = window.getCurrentTheme();
    const styles = currentTheme === 'light' ? lightStyles : darkStyles;

    Object.assign(element.style, styles);
};

function scrollToNextSection() {
    const heroSection = document.querySelector('.hero-section');
    const nextSection = heroSection.nextElementSibling;
    
    if (nextSection) {
        nextSection.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    } else {
        window.scrollTo({
            top: window.innerHeight,
            behavior: 'smooth'
        });
    }
}

let scrollTimeout;
window.addEventListener('scroll', function() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator && window.scrollY > 100) {
        scrollIndicator.classList.add('fade-out');
    } else if (scrollIndicator && window.scrollY <= 100) {
        scrollIndicator.classList.remove('fade-out');
    }
});

window.addEventListener('scroll', function() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    const heroSection = document.querySelector('.hero-section');
    
    if (scrollIndicator && heroSection) {
        const heroRect = heroSection.getBoundingClientRect();
        const isHeroVisible = heroRect.bottom > 100;
        
        if (isHeroVisible) {
            scrollIndicator.style.opacity = '1';
            scrollIndicator.style.pointerEvents = 'auto';
        } else {
            scrollIndicator.style.opacity = '0';
            scrollIndicator.style.pointerEvents = 'none';
        }
    }
});