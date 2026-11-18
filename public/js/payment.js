(() => {
    'use strict';

    const device = navigator.userAgent;
    const upiLink = document.querySelector('[data-upi-payment]');
    const iosLink = document.querySelector('[data-ios-payment]');
    const appLink = /iPhone|iPad|iPod/i.test(device) ? iosLink : /Android/i.test(device) ? upiLink : null;

    // Mobile browsers can require a tap, so both links remain available on the page.
    if (appLink && document.visibilityState === 'visible' && !performance.getEntriesByType('navigation').some(entry => entry.type === 'back_forward')) {
        window.location.assign(appLink.href);
    }
})();
