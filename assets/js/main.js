
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