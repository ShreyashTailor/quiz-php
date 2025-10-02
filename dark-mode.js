// Dark Mode Toggle Functionality
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        // Apply saved theme
        this.applyTheme(this.theme);
        
        // Create toggle button
        this.createToggleButton();
        
        // Add event listeners
        this.addEventListeners();
    }

    createToggleButton() {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'theme-toggle';
        toggleButton.setAttribute('aria-label', 'Toggle theme');
        toggleButton.innerHTML = `
            <span class="icon sun-icon">☀️</span>
            <span class="icon moon-icon">🌙</span>
        `;
        
        document.body.appendChild(toggleButton);
        this.toggleButton = toggleButton;
    }

    addEventListeners() {
        this.toggleButton.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Add keyboard support
        this.toggleButton.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                this.applyTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    toggleTheme() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        
        // Add a subtle animation
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    setTheme(theme) {
        this.theme = theme;
        this.applyTheme(theme);
        localStorage.setItem('theme', theme);
    }

    applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.classList.add('dark');
            html.classList.remove('light');
        } else {
            html.classList.add('light');
            html.classList.remove('dark');
        }
        
        // Update meta theme-color for mobile browsers
        let themeColorMeta = document.querySelector('meta[name="theme-color"]');
        if (!themeColorMeta) {
            themeColorMeta = document.createElement('meta');
            themeColorMeta.name = 'theme-color';
            document.head.appendChild(themeColorMeta);
        }
        
        const primaryColor = theme === 'dark' ? '#0f172a' : '#ffffff';
        themeColorMeta.content = primaryColor;
    }

    // Method to get current theme
    getCurrentTheme() {
        return this.theme;
    }

    // Method to check if dark mode is active
    isDark() {
        return this.theme === 'dark';
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
    
    // Add smooth transitions to all interactive elements
    const style = document.createElement('style');
    style.textContent = `
        *, *::before, *::after {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease !important;
        }
        
        .card, .btn, .input, .select, .navbar {
            transition: all 0.2s ease !important;
        }
    `;
    document.head.appendChild(style);
    
    console.log('🌙 Dark mode toggle initialized!');
});

// Add some cool effects for dark mode
document.addEventListener('DOMContentLoaded', () => {
    // Add glow effect to primary buttons in dark mode
    const addGlowEffect = () => {
        const style = document.createElement('style');
        style.textContent = `
            .dark .btn-primary {
                box-shadow: 0 0 20px hsl(var(--primary) / 0.3);
            }
            
            .dark .btn-primary:hover {
                box-shadow: 0 0 25px hsl(var(--primary) / 0.4);
                transform: translateY(-1px);
            }
            
            .dark .quiz-card:hover {
                box-shadow: 0 0 30px hsl(var(--primary) / 0.2);
            }
            
            .dark .theme-toggle {
                box-shadow: 0 0 15px hsl(var(--primary) / 0.2);
            }
        `;
        document.head.appendChild(style);
    };
    
    setTimeout(addGlowEffect, 100);
});
