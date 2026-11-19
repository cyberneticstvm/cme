(() => {
    'use strict';
    const toggle = document.querySelector('[data-password-toggle]');
    toggle?.addEventListener('click', () => {
        const input = document.getElementById(toggle.getAttribute('aria-controls'));
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', String(show));
    });
    document.querySelector('[data-error-summary]')?.focus();
    document.querySelectorAll('[data-admin-submit]').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });
    });
    window.addEventListener('pageshow', () => document.querySelectorAll('[data-admin-submit] button[type="submit"]').forEach(button => {
        if (button.hasAttribute('aria-busy')) { button.disabled = false; }
        button.removeAttribute('aria-busy');
    }));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') { return; }
        const menu = document.querySelector('.admin-mobile-menu[open]');
        if (menu) { menu.open = false; menu.querySelector('summary').focus(); }
    });

    const deleteDialog = document.querySelector('#admin-delete-dialog');
    if (!deleteDialog) { return; }
    const deleteForm = deleteDialog.querySelector('[data-delete-form]');
    const deleteSubmit = deleteForm.querySelector('button[type="submit"]');
    const deleteSubmitLabel = deleteDialog.querySelector('[data-delete-submit-label]');
    const deleteProgress = deleteDialog.querySelector('[data-delete-progress]');
    const cancelButtons = [...deleteDialog.querySelectorAll('[data-delete-cancel]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let deleteTrigger;
    let deleting = false;
    let closeTimer;

    const finishClose = () => {
        clearTimeout(closeTimer);
        deleteDialog.close();
        deleteDialog.classList.remove('is-closing');
        document.body.classList.remove('admin-confirmation-open');
        deleteForm.removeAttribute('action');
        if (deleteTrigger?.isConnected) { deleteTrigger.focus({ preventScroll: true }); }
    };
    const closeDeleteDialog = () => {
        if (deleting || !deleteDialog.open || deleteDialog.classList.contains('is-closing')) { return; }
        deleteDialog.classList.add('is-closing');
        closeTimer = setTimeout(finishClose, reducedMotion.matches ? 0 : 180);
    };
    document.querySelectorAll('[data-delete-registration]').forEach(trigger => {
        trigger.addEventListener('click', () => {
            if (deleteDialog.open) { return; }
            deleteTrigger = trigger;
            deleteForm.setAttribute('action', trigger.dataset.deleteUrl);
            deleteDialog.querySelector('[data-delete-name]').textContent = trigger.dataset.delegateName;
            deleteDialog.querySelector('[data-delete-email]').textContent = trigger.dataset.delegateEmail;
            deleteDialog.querySelector('[data-delete-reference]').textContent = trigger.dataset.delegateReference;
            deleteDialog.querySelector('[data-delete-initial]').textContent = [...trigger.dataset.delegateName][0]?.toLocaleUpperCase() || '';
            deleteDialog.showModal();
            document.body.classList.add('admin-confirmation-open');
            deleteDialog.querySelector('[autofocus]').focus({ preventScroll: true });
        });
    });
    cancelButtons.forEach(button => button.addEventListener('click', closeDeleteDialog));
    deleteDialog.addEventListener('cancel', event => {
        event.preventDefault();
        closeDeleteDialog();
    });
    deleteDialog.addEventListener('keydown', event => {
        if (event.key !== 'Tab') { return; }
        const controls = [...deleteDialog.querySelectorAll('button:not([disabled])')];
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    });
    deleteDialog.addEventListener('click', event => {
        if (event.target !== deleteDialog) { return; }
        const bounds = deleteDialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) {
            closeDeleteDialog();
        }
    });
    deleteForm.addEventListener('submit', event => {
        if (deleting || !deleteDialog.open || !deleteForm.getAttribute('action')) {
            event.preventDefault();
            return;
        }
        deleting = true;
        deleteSubmit.disabled = true;
        deleteSubmit.setAttribute('aria-busy', 'true');
        deleteSubmitLabel.textContent = 'Deleting...';
        deleteProgress.textContent = 'Deleting the registration. Please wait.';
        cancelButtons.forEach(button => { button.disabled = true; });
    });
    window.addEventListener('pageshow', () => {
        deleting = false;
        deleteSubmit.disabled = false;
        deleteSubmit.removeAttribute('aria-busy');
        deleteSubmitLabel.textContent = 'Delete registration';
        deleteProgress.textContent = '';
        cancelButtons.forEach(button => { button.disabled = false; });
        if (deleteDialog.open) { finishClose(); }
    });
})();
