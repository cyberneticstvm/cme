(() => {
    'use strict';
    const form = document.querySelector('[data-registration-form]');
    const email = form?.querySelector('[name="email"]');
    const status = form?.querySelector('[data-email-status]');
    let checkController;
    let sequence = 0;
    const emailValue = () => email.value.trim().toLowerCase();
    const setStatus = (message, kind = '') => {
        status.textContent = message;
        status.className = `enrolment-email-check ${kind}`;
    };
    const checkEmail = async () => {
        checkController?.abort();
        const current = ++sequence;
        const value = emailValue();
        email.setCustomValidity('');
        if (!value || !email.validity.valid) { setStatus(''); return null; }
        checkController = new AbortController();
        const timeout = setTimeout(() => checkController?.abort(), 8000);
        setStatus('Checking email address...');
        try {
            const response = await fetch(form.dataset.emailCheckUrl, {
                method: 'POST', credentials: 'same-origin', signal: checkController.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ email: value })
            });
            if (!response.ok) { throw new Error('Email check unavailable'); }
            const result = await response.json();
            if (current !== sequence || value !== emailValue()) { return null; }
            if (result.registered) {
                const message = 'This email is already registered. Use Forgot registration ID below.';
                email.setCustomValidity(message);
                setStatus(message, 'is-error');
                email.setAttribute('aria-invalid', 'true');
                return true;
            }
            setStatus('This email can be used for a new registration.', 'is-available');
            email.removeAttribute('aria-invalid');
            return false;
        } catch (error) {
            if (current === sequence) { setStatus('Your email will be checked when you submit.'); }
            return null;
        } finally { clearTimeout(timeout); }
    };
    email?.setAttribute('aria-describedby', [email.getAttribute('aria-describedby'), 'email-availability'].filter(Boolean).join(' '));
    email?.addEventListener('input', () => {
        sequence += 1;
        checkController?.abort();
        email.setCustomValidity('');
        email.removeAttribute('aria-invalid');
        setStatus('');
    });
    email?.addEventListener('blur', checkEmail);
    const forms = [...document.querySelectorAll('[data-registration-form], [data-recovery-form]')];
    forms.forEach(currentForm => {
        const button = currentForm.querySelector('button[type="submit"]');
        const label = button.querySelector('[data-submit-label]');
        const initialLabel = label.textContent;
        let submitting = false;
        const reset = () => {
            submitting = false;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            label.textContent = initialLabel;
        };
        currentForm.addEventListener('submit', async event => {
            event.preventDefault();
            if (submitting) { return; }
            submitting = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = currentForm === form ? 'Checking your details...' : 'Sending your reminder...';
            const submittedEmail = currentForm === form ? emailValue() : '';
            if (currentForm === form && await checkEmail() === true) {
                reset(); email.focus(); email.reportValidity(); return;
            }
            if (currentForm === form && submittedEmail !== emailValue()) { reset(); return; }
            if (!currentForm.reportValidity()) { reset(); return; }
            label.textContent = currentForm === form ? 'Submitting your details...' : 'Sending your reminder...';
            HTMLFormElement.prototype.submit.call(currentForm);
        });
        window.addEventListener('pageshow', reset);
    });
    document.querySelector('#form-errors')?.focus();
})();
