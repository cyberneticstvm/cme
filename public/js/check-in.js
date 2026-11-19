(() => {
    'use strict';
    const root = document.querySelector('[data-check-in]');
    if (!root) { return; }
    const get = selector => root.querySelector(selector);
    const codeForm = get('[data-code-form]');
    const codeInput = codeForm.querySelector('[name="code"]');
    const csrf = codeForm.querySelector('[name="_token"]').value;
    const resultPanel = get('[data-checkin-result]');
    const confirm = get('[data-confirm-checkin]');
    const next = get('[data-scan-next]');
    const start = get('[data-camera-start]');
    const stop = get('[data-camera-stop]');
    const fileInput = get('[data-qr-image]');
    const video = get('[data-camera-video]');
    const camera = get('[data-camera-panel]');
    const note = get('[data-scanner-note]');
    let scannerControls;
    let cameraGeneration = 0;
    let requestGeneration = 0;
    let busy = false;
    let verifiedCode = '';
    const text = (selector, value) => { get(selector).textContent = value ?? ''; };
    const setBusy = value => {
        busy = value;
        codeInput.disabled = value;
        codeForm.querySelector('button').disabled = value;
        start.disabled = value;
        fileInput.disabled = value;
        confirm.disabled = value;
        next.disabled = value;
        root.setAttribute('aria-busy', String(value));
    };
    const stopCamera = () => {
        cameraGeneration += 1;
        scannerControls?.stop();
        scannerControls = null;
        const stream = video.srcObject;
        stream?.getTracks().forEach(track => track.stop());
        video.srcObject = null;
        camera.classList.remove('is-scanning');
        get('[data-camera-placeholder]').hidden = false;
        text('[data-camera-status]', 'CAMERA OFF');
        start.hidden = false;
        stop.hidden = true;
    };
    const reset = () => {
        requestGeneration += 1;
        verifiedCode = '';
        resultPanel.dataset.state = 'idle';
        text('[data-result-eyebrow]', 'READY FOR THE NEXT DELEGATE');
        text('[data-result-heading]', 'Ready when you are.');
        text('[data-result-message]', 'Scan a pass to verify registration and attendance.');
        get('[data-delegate-details]').hidden = true;
        confirm.hidden = true;
        next.hidden = true;
        get('[data-session-login]').hidden = true;
    };
    const showError = (message, sessionExpired = false) => {
        verifiedCode = '';
        resultPanel.dataset.state = 'error';
        text('[data-result-eyebrow]', 'CHECK-IN NOT CONFIRMED');
        text('[data-result-heading]', sessionExpired ? 'Please sign in again.' : 'Unable to verify this pass.');
        text('[data-result-message]', message);
        get('[data-delegate-details]').hidden = true;
        confirm.hidden = true;
        next.hidden = false;
        get('[data-session-login]').hidden = !sessionExpired;
    };
    const render = result => {
        resultPanel.dataset.state = result.state;
        text('[data-result-heading]', result.heading);
        text('[data-result-message]', result.message);
        text('[data-result-eyebrow]', result.state === 'checked_in' ? 'WELCOME TO VISION X 2026' : result.state === 'ready' ? 'DELEGATE PASS VERIFIED' : 'ENTRY NOT AUTHORISED');
        const delegate = result.registration;
        get('[data-delegate-details]').hidden = !delegate;
        if (delegate) {
            text('[data-delegate-initial]', delegate.full_name.substring(0, 1).toLocaleUpperCase());
            text('[data-delegate-name]', delegate.full_name);
            text('[data-delegate-profession]', delegate.profession);
            text('[data-delegate-reference]', delegate.reference);
            text('[data-delegate-status]', delegate.status);
            text('[data-delegate-email]', delegate.email);
            text('[data-delegate-attendance]', delegate.checked_in_at ? `${delegate.checked_in_display} · ${delegate.checked_in_by}` : '');
            get('[data-attendance-row]').hidden = !delegate.checked_in_at;
            get('[data-manage-delegate]').href = delegate.manage_url;
        }
        confirm.hidden = result.can_check_in !== true;
        next.hidden = false;
        get('[data-session-login]').hidden = true;
        if (window.matchMedia('(max-width: 900px)').matches) {
            resultPanel.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        }
    };
    const post = async (url, code) => {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ code })
            });
            if (response.redirected || response.status === 401 || response.status === 419) {
                const error = new Error('Your admin session expired. Sign in again before continuing.');
                error.sessionExpired = true;
                throw error;
            }
            if (!(response.headers.get('content-type') || '').includes('application/json')) { throw new Error('The server could not verify this pass. Please try again.'); }
            const data = await response.json();
            if (response.status === 422) { throw new Error(data.errors?.code?.[0] || Object.values(data.errors || {}).flat()[0] || 'Please enter a valid pass code.'); }
            if (response.status === 429) { throw new Error('Too many requests. Wait a moment, then verify again.'); }
            if (!response.ok && response.status !== 409) { throw new Error('The server could not confirm attendance. Verify the pass again before admitting the delegate.'); }
            if (typeof data.state !== 'string' || typeof data.can_check_in !== 'boolean') { throw new Error('Verification could not be completed. Please try again.'); }
            return data;
        } finally { clearTimeout(timeout); }
    };
    const verify = async code => {
        if (busy) { return; }
        stopCamera();
        reset();
        const generation = requestGeneration;
        codeInput.value = code.trim();
        setBusy(true);
        text('[data-result-heading]', 'Checking this pass...');
        text('[data-result-message]', 'Checking the current registration and previous attendance.');
        try {
            const result = await post(root.dataset.verifyUrl, codeInput.value);
            if (generation !== requestGeneration) { return; }
            verifiedCode = result.can_check_in ? codeInput.value : '';
            render(result);
        } catch (error) {
            if (generation === requestGeneration) { showError(error.name === 'AbortError' ? 'Connection timed out. Attendance has not been confirmed. Verify again before admitting the delegate.' : error.message, error.sessionExpired); }
        } finally { setBusy(false); }
    };
    codeInput.addEventListener('input', () => { if (!busy) { reset(); } });
    codeForm.addEventListener('submit', event => { event.preventDefault(); if (codeForm.reportValidity()) { verify(codeInput.value); } });
    confirm.addEventListener('click', async () => {
        if (busy || !verifiedCode) { return; }
        setBusy(true);
        confirm.setAttribute('aria-busy', 'true');
        try {
            const result = await post(root.dataset.confirmUrl, verifiedCode);
            verifiedCode = '';
            render(result);
            if (result.state === 'checked_in') {
                if (Number.isInteger(result.checked_in_total)) { document.querySelector('[data-checked-in-total]').textContent = result.checked_in_total; }
                const recent = document.querySelector('[data-recent-checkins]');
                recent.querySelector('[data-no-arrivals]')?.remove();
                const row = document.createElement('article');
                const avatar = document.createElement('span');
                const details = document.createElement('div');
                const name = document.createElement('strong');
                const reference = document.createElement('p');
                const time = document.createElement('time');
                avatar.textContent = result.registration.full_name.substring(0, 1).toLocaleUpperCase();
                name.textContent = result.registration.full_name;
                reference.textContent = `${result.registration.reference} · ${result.registration.profession}`;
                time.textContent = result.registration.checked_in_display;
                details.append(name, reference);
                row.append(avatar, details, time);
                recent.prepend(row);
                while (recent.children.length > 6) { recent.lastElementChild.remove(); }
            }
        } catch (error) {
            showError(error.name === 'AbortError' ? 'Connection lost during confirmation. Verify this pass again to check whether attendance was recorded. Do not admit on an unconfirmed result.' : error.message, error.sessionExpired);
        } finally { setBusy(false); confirm.removeAttribute('aria-busy'); }
    });
    const startCamera = async () => {
        if (busy) { return; }
        stopCamera();
        reset();
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            note.textContent = 'Camera scanning needs HTTPS or localhost. Scan a QR image or paste the pass code instead.';
            return;
        }
        if (!window.ZXingBrowser) { note.textContent = 'The scanner could not load. Refresh the page or paste the pass code.'; return; }
        const generation = cameraGeneration;
        start.disabled = true;
        start.hidden = true;
        stop.hidden = false;
        note.textContent = 'Allow camera access, then hold the QR code inside the frame.';
        try {
            const reader = new ZXingBrowser.BrowserQRCodeReader(undefined, { delayBetweenScanAttempts: 200 });
            const controls = await reader.decodeFromConstraints({audio:false, video:{facingMode:{ideal:'environment'}}}, video, (result, error, liveControls) => {
                if (!result || generation !== cameraGeneration || busy) { return; }
                liveControls.stop();
                verify(result.getText());
            });
            if (generation !== cameraGeneration) { controls.stop(); return; }
            scannerControls = controls;
            camera.classList.add('is-scanning');
            get('[data-camera-placeholder]').hidden = true;
            text('[data-camera-status]', 'CAMERA LIVE');
            note.textContent = 'Scanning for a VISION X 2026 pass. Attendance is not recorded until you confirm.';
        } catch (error) {
            if (generation !== cameraGeneration) { return; }
            stopCamera();
            note.textContent = error.name === 'NotAllowedError' ? 'Camera access was declined. Allow camera access in your browser, scan an image or paste the pass code.' : 'No usable camera was found. Scan a QR image or paste the pass code instead.';
        } finally { start.disabled = false; }
    };
    start.addEventListener('click', startCamera);
    stop.addEventListener('click', () => { stopCamera(); note.textContent = 'Camera stopped. Start it again when you are ready.'; });
    next.addEventListener('click', () => { stopCamera(); reset(); codeInput.value = ''; codeInput.focus(); note.textContent = 'Ready for the next delegate. Start the camera, scan an image or paste a pass code.'; });
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file || busy) { return; }
        stopCamera(); reset();
        if (!['image/png', 'image/jpeg', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) { showError('Choose a PNG, JPEG or WebP QR image smaller than 10 MB.'); fileInput.value = ''; return; }
        if (!window.ZXingBrowser) { showError('The image scanner could not load. Refresh the page or paste the pass code.'); return; }
        const imageUrl = URL.createObjectURL(file);
        let code;
        setBusy(true);
        try {
            const reader = new ZXingBrowser.BrowserQRCodeReader();
            const result = await reader.decodeFromImageUrl(imageUrl);
            code = result.getText();
        } catch { showError('No readable QR code was found. Try a clearer image showing the complete QR code.'); }
        finally { URL.revokeObjectURL(imageUrl); fileInput.value = ''; setBusy(false); }
        if (code) { verify(code); }
    });
    window.addEventListener('pagehide', stopCamera);
    document.addEventListener('visibilitychange', () => { if (document.hidden) { stopCamera(); } });
})();
