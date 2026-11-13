(() => {
    'use strict';
    const navigation = document.querySelector('#main-navigation');
    const menuButton = document.querySelector('.menu-toggle');
    const closeMenu = () => {
        navigation.classList.remove('is-open');
        document.body.classList.remove('navigation-is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', 'Open navigation');
    };
    menuButton.addEventListener('click', () => {
        const expanded = menuButton.getAttribute('aria-expanded') !== 'true';
        navigation.classList.toggle('is-open', expanded);
        document.body.classList.toggle('navigation-is-open', expanded);
        menuButton.setAttribute('aria-expanded', String(expanded));
        menuButton.setAttribute('aria-label', expanded ? 'Close navigation' : 'Open navigation');
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && menuButton.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menuButton.focus();
        }
    });
    const desktopNavigation = window.matchMedia('(min-width: 1121px)');
    desktopNavigation.addEventListener('change', closeMenu);
    const updateHeaderHeight = () => {
        const height = document.querySelector('.site-header').getBoundingClientRect().height;
        document.documentElement.style.setProperty('--site-header-height', `${height}px`);
    };
    updateHeaderHeight();
    if ('ResizeObserver' in window) {
        new ResizeObserver(updateHeaderHeight).observe(document.querySelector('.site-header'));
    } else {
        window.addEventListener('resize', updateHeaderHeight, { passive: true });
    }
    document.addEventListener('click', event => { if (!event.target.closest('.navigation')) { closeMenu(); } });

    document.addEventListener('visionx:close-navigation', closeMenu);
    navigation.addEventListener('click', event => { if (event.target.closest('a')) { closeMenu(); } });
})();
