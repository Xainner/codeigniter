(() => {
    'use strict';

    const menuButton = document.querySelector('[data-menu-toggle]');
    const sidebar = document.getElementById('dashboard-sidebar');
    if (menuButton && sidebar) {
        const closeMenu = () => {
            document.body.classList.remove('menu-open');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Abrir navegación');
        };
        menuButton.addEventListener('click', () => {
            const open = !document.body.classList.contains('menu-open');
            document.body.classList.toggle('menu-open', open);
            menuButton.setAttribute('aria-expanded', String(open));
            menuButton.setAttribute('aria-label', open ? 'Cerrar navegación' : 'Abrir navegación');
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenu();
        });
        document.addEventListener('click', (event) => {
            if (document.body.classList.contains('menu-open') && !sidebar.contains(event.target) && !menuButton.contains(event.target)) closeMenu();
        });
        sidebar.addEventListener('click', (event) => {
            if (event.target.closest('a')) closeMenu();
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const colorInput = document.getElementById('brand_color');
    const colorOutput = document.querySelector('[data-color-output]');
    if (colorInput && colorOutput) {
        colorInput.addEventListener('input', () => {
            colorOutput.value = colorInput.value.toUpperCase();
        });
    }
})();
