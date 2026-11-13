export const posterDismissalKey = 'visionx.poster.dismissed';
const posterCookieName = 'visionx_poster_dismissed';

export function hasDismissedPoster(storage, cookies = '') {
    try {
        if (storage.getItem(posterDismissalKey) === '1') { return true; }
    } catch {
        // Cookie fallback keeps the site usable when local storage is unavailable.
    }
    return cookies.split(';').some(cookie => cookie.trim() === `${posterCookieName}=1`);
}

export function rememberPosterDismissal(storage, setCookie) {
    try {
        storage.setItem(posterDismissalKey, '1');
        return true;
    } catch {
        try {
            setCookie(`${posterCookieName}=1; Max-Age=315360000; Path=/; SameSite=Lax`);
            return true;
        } catch {
            return false;
        }
    }
}

function initializePosterPopup() {
    const dialog = document.querySelector('#welcome-poster');
    if (!dialog || typeof dialog.showModal !== 'function') { return; }

    const storage = {
        getItem: key => window.localStorage.getItem(key),
        setItem: (key, value) => window.localStorage.setItem(key, value),
    };
    const getCookies = () => {
        try { return document.cookie; } catch { return ''; }
    };
    const isDismissed = () => hasDismissedPoster(storage, getCookies());
    if (isDismissed()) { return; }

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let previousFocus;
    let closing = false;
    let closeTimer;
    let backdropPointerDown = false;
    const announceVisibility = () => document.dispatchEvent(new Event('visionx:poster-visibility'));
    const finishClose = () => {
        clearTimeout(closeTimer);
        if (dialog.open) { dialog.close(); }
        dialog.classList.remove('is-closing');
        document.body.classList.remove('poster-is-open');
        announceVisibility();
        if (previousFocus?.isConnected) { previousFocus.focus({ preventScroll: true }); }
    };
    const closePoster = (persist = true) => {
        if (!dialog.open || closing) { return; }
        closing = true;
        if (persist) {
            rememberPosterDismissal(storage, cookie => { document.cookie = cookie; });
        }
        if (reducedMotion.matches) { finishClose(); return; }
        dialog.classList.add('is-closing');
        closeTimer = setTimeout(finishClose, 320);
    };
    dialog.querySelectorAll('[data-dismiss-poster]').forEach(button => {
        button.addEventListener('click', () => closePoster());
    });
    dialog.addEventListener('cancel', event => { event.preventDefault(); closePoster(); });
    dialog.addEventListener('animationend', event => {
        if (event.target === dialog && event.animationName === 'poster-leave') { finishClose(); }
    });
    const isOutside = event => {
        const bounds = dialog.getBoundingClientRect();
        return event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
    };
    dialog.addEventListener('pointerdown', event => { backdropPointerDown = event.target === dialog && isOutside(event); });
    dialog.addEventListener('click', event => {
        if (backdropPointerDown && event.target === dialog && isOutside(event)) { closePoster(); }
        backdropPointerDown = false;
    });
    window.addEventListener('storage', event => {
        if (event.key === posterDismissalKey && event.newValue === '1') { closePoster(false); }
    });

    const image = dialog.querySelector('[data-popup-poster]');
    const imageReady = new Promise(resolve => {
        image.addEventListener('load', () => resolve(true), { once: true });
        image.addEventListener('error', () => resolve(false), { once: true });
    });
    image.src = image.dataset.src;
    Promise.all([imageReady, new Promise(resolve => setTimeout(resolve, 500))]).then(([loaded]) => {
        if (!loaded || isDismissed()) { return; }
        previousFocus = document.activeElement;
        document.body.classList.add('poster-is-open');
        dialog.showModal();
        announceVisibility();
    });
}

if (typeof document !== 'undefined') { initializePosterPopup(); }
