(() => {
    'use strict';

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const navigation = document.querySelector('#main-navigation');
    const closeMenu = () => document.dispatchEvent(new Event('visionx:close-navigation'));

    const loadImage = image => {
        if (!image.dataset.src) { return; }
        const source = image.dataset.src;
        delete image.dataset.src;
        image.addEventListener('load', () => image.classList.add('is-loaded'), { once: true });
        image.addEventListener('error', () => image.classList.add('has-image-error'), { once: true });
        image.src = source;
    };
    const lazyImages = [...document.querySelectorAll('[data-lazy-image]')];
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) { loadImage(entry.target); imageObserver.unobserve(entry.target); }
            });
        }, { rootMargin: '240px 0px' });
        lazyImages.forEach(image => imageObserver.observe(image));
    } else {
        lazyImages.forEach(loadImage);
    }

    const sections = [...document.querySelectorAll('[data-lazy-section]')];
    const revealSection = section => {
        if (!section) { return; }
        section.classList.remove('reveal-pending');
        section.classList.add('is-revealed');
    };
    if ('IntersectionObserver' in window) {
        const sectionObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                entry.target.classList.toggle('is-in-view', entry.isIntersecting);
                if (entry.isIntersecting) { revealSection(entry.target); }
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0 });
        sections.forEach(section => {
            if (!reducedMotion.matches && section.getBoundingClientRect().top > innerHeight - 40) {
                section.classList.add('reveal-pending');
            } else {
                revealSection(section);
            }
            sectionObserver.observe(section);
        });
    } else {
        sections.forEach(revealSection);
    }
    document.addEventListener('focusin', event => revealSection(event.target.closest('[data-lazy-section]')));

    let scrollFrame;
    const cancelScroll = () => {
        cancelAnimationFrame(scrollFrame);
        document.documentElement.classList.remove('is-animating-scroll');
    };
    const scrollToTarget = (target, animated = true) => {
        cancelScroll();
        const section = target.closest('[data-lazy-section]');
        revealSection(section);
        if (section) { section.querySelectorAll('[data-lazy-image]').forEach(loadImage); }
        const headerOffset = document.querySelector('.site-header').getBoundingClientRect().height + 18;
        const start = window.scrollY;
        const maxScroll = Math.max(0, document.documentElement.scrollHeight - innerHeight);
        const targetY = target.id === 'home' ? 0 : target.getBoundingClientRect().top + start - headerOffset;
        const destination = Math.max(0, Math.min(targetY, maxScroll));
        const distance = destination - start;
        document.documentElement.classList.add('is-animating-scroll');
        if (reducedMotion.matches || !animated || Math.abs(distance) < 2) {
            window.scrollTo(0, destination);
            cancelScroll();
            return;
        }
        const duration = Math.min(1250, Math.max(750, Math.abs(distance) * 0.25));
        let startTime;
        const tick = timestamp => {
            startTime ??= timestamp;
            const progress = Math.min(1, (timestamp - startTime) / duration);
            const eased = progress < 0.5 ? 4 * progress ** 3 : 1 - (-2 * progress + 2) ** 3 / 2;
            window.scrollTo(0, start + distance * eased);
            if (progress < 1) { scrollFrame = requestAnimationFrame(tick); }
            else { cancelScroll(); }
        };
        scrollFrame = requestAnimationFrame(tick);
    };
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', event => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) { return; }
            const target = document.getElementById(link.hash.slice(1));
            if (!target) { return; }
            event.preventDefault();
            closeMenu();
            if (location.hash !== link.hash) { history.pushState(null, '', link.hash); }
            scrollToTarget(target);
            if (event.detail === 0) {
                target.setAttribute('tabindex', '-1');
                target.focus({ preventScroll: true });
            }
        });
    });
    ['wheel', 'touchstart'].forEach(type => window.addEventListener(type, cancelScroll, { passive: true }));
    document.addEventListener('keydown', event => {
        if (['PageUp', 'PageDown', 'Home', 'End', 'ArrowUp', 'ArrowDown'].includes(event.key)) { cancelScroll(); }
    });
    window.addEventListener('popstate', () => {
        const target = document.getElementById(location.hash.slice(1) || 'home');
        if (target) { scrollToTarget(target); }
    });
    if (location.hash) {
        const target = document.getElementById(location.hash.slice(1));
        if (target) { scrollToTarget(target, false); }
    }

    const slides = [...document.querySelectorAll('.hero-slide')];
    const dots = [...document.querySelectorAll('[data-slide]')];
    const pauseButton = document.querySelector('.slider-pause');
    const playIcon = '<svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m7 4 14 8-14 8z"/></svg>';
    const pauseIcon = '<svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 4h4v16H6zm8 0h4v16h-4z"/></svg>';
    let currentSlide = 0;
    let paused = reducedMotion.matches;
    let interval;
    const showSlide = index => {
        currentSlide = index;
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === index);
            slide.setAttribute('aria-hidden', String(slideIndex !== index));
        });
        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('selected', dotIndex === index);
            dot.setAttribute('aria-pressed', String(dotIndex === index));
        });
        document.querySelector('.slide-number').innerHTML = `0${index + 1} <i>/ 02</i>`;
        document.querySelector('.iris-orbit').style.visibility = slides[index].hasAttribute('data-eye-slide') ? 'visible' : 'hidden';
    };
    const updateAutoplay = () => {
        clearInterval(interval);
        pauseButton.innerHTML = paused ? playIcon : pauseIcon;
        pauseButton.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
        if (!paused && !document.hidden && !document.body.classList.contains('poster-is-open')) { interval = setInterval(() => showSlide((currentSlide + 1) % slides.length), 10000); }
    };
    dots.forEach(dot => dot.addEventListener('click', () => { showSlide(Number(dot.dataset.slide)); updateAutoplay(); }));
    pauseButton.addEventListener('click', () => { paused = !paused; updateAutoplay(); });
    document.addEventListener('visibilitychange', updateAutoplay);
    document.addEventListener('visionx:poster-visibility', () => {
        if (document.body.classList.contains('poster-is-open')) { closeMenu(); }
        updateAutoplay();
    });
    reducedMotion.addEventListener('change', event => {
        paused = event.matches;
        updateAutoplay();
        if (event.matches) { cancelScroll(); sections.forEach(revealSection); }
    });
    const heroPhoto = document.querySelector('.hero-photo');
    let swipeStart;
    heroPhoto.addEventListener('touchstart', event => {
        if (event.touches.length !== 1) { swipeStart = null; return; }
        swipeStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    heroPhoto.addEventListener('touchend', event => {
        if (!swipeStart || !event.changedTouches.length) { return; }
        const deltaX = event.changedTouches[0].clientX - swipeStart.x;
        const deltaY = event.changedTouches[0].clientY - swipeStart.y;
        swipeStart = null;
        if (Math.abs(deltaX) > 50 && Math.abs(deltaX) > Math.abs(deltaY) * 1.5) {
            showSlide((currentSlide + (deltaX < 0 ? 1 : slides.length - 1)) % slides.length);
            updateAutoplay();
        }
    }, { passive: true });
    heroPhoto.addEventListener('touchcancel', () => { swipeStart = null; }, { passive: true });
    showSlide(0);
    updateAutoplay();

    document.querySelectorAll('.faq-item').forEach(details => {
        details.addEventListener('toggle', () => {
            if (!details.open) { return; }
            document.querySelectorAll('.faq-item').forEach(other => { if (other !== details) { other.open = false; } });
            if (!reducedMotion.matches && details.querySelector('.faq-panel').animate) {
                details.querySelector('.faq-panel').animate([
                    { opacity: 0, transform: 'translateY(-6px)' },
                    { opacity: 1, transform: 'translateY(0)' }
                ], { duration: 300, easing: 'ease-out' });
            }
        });
    });

    const eventDate = new Date('2026-12-06T00:00:00+05:30').getTime();
    const updateCountdown = () => {
        const remaining = Math.max(0, eventDate - Date.now());
        document.querySelector('#days').textContent = String(Math.floor(remaining / 86400000)).padStart(2, '0');
        document.querySelector('#hours').textContent = String(Math.floor(remaining / 3600000) % 24).padStart(2, '0');
        document.querySelector('#minutes').textContent = String(Math.floor(remaining / 60000) % 60).padStart(2, '0');
        if (!remaining) { document.querySelector('#countdown-heading').textContent = 'VISION X 2026 - 06 DECEMBER 2026'; }
    };
    updateCountdown();
    setInterval(updateCountdown, 60000);

    if ('IntersectionObserver' in window) {
        const navigationObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) { return; }
                navigation.querySelectorAll('a').forEach(link => {
                    const active = link.hash === '#' + entry.target.id;
                    link.classList.toggle('active', active);
                    if (active) { link.setAttribute('aria-current', 'location'); } else { link.removeAttribute('aria-current'); }
                });
            });
        }, { rootMargin: '-15% 0px -55% 0px' });
        navigation.querySelectorAll('a').forEach(link => {
            const section = link.hash ? document.querySelector(link.hash) : null;
            if (section) { navigationObserver.observe(section); }
        });
    }
})();
