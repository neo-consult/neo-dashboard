(function() {
    'use strict';

    if (!document.body.classList.contains('neo-dashboard-standalone')) {
        return;
    }

    const html = document.documentElement;
    const toggleButton = document.getElementById('theme-toggle-navbar');
    const storageKey = 'neo-dashboard-theme';
    const themeMeta = {
        light: {
            icon: '\uD83C\uDF19',
            label: 'Zu dunklem Theme wechseln'
        },
        dark: {
            icon: '\u2600\uFE0F',
            label: 'Zu hellem Theme wechseln'
        }
    };

    function updateToggleMetadata(theme) {
        if (!toggleButton) {
            return;
        }

        const meta = theme === 'dark' ? themeMeta.dark : themeMeta.light;

        toggleButton.textContent = meta.icon;
        toggleButton.title = meta.label;
        toggleButton.setAttribute('aria-label', meta.label);
        toggleButton.setAttribute('data-bs-title', meta.label);
    }

    function updateTheme(theme) {
        html.setAttribute('data-neo-theme', theme);
        localStorage.setItem(storageKey, theme);
        updateToggleMetadata(theme);
    }

    function initTheme() {
        const savedTheme = localStorage.getItem(storageKey);

        if (savedTheme) {
            updateTheme(savedTheme);
            return;
        }

        const systemTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';

        updateTheme(systemTheme);
    }

    function setupToggleButton() {
        if (!toggleButton || toggleButton.dataset.neoThemeBound === '1') {
            return;
        }

        toggleButton.dataset.neoThemeBound = '1';
        toggleButton.addEventListener('click', function() {
            const currentTheme = html.getAttribute('data-neo-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            updateTheme(newTheme);

            this.style.transform = 'scale(0.9)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 150);
        });
    }

    function boot() {
        initTheme();
        setupToggleButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})();
