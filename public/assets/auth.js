document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-ajax-form="true"]');
    if (!form) return;

    const button = form.querySelector('[data-submit-button]');
    const notice = document.createElement('div');
    notice.className = 'auth-alert';
    notice.setAttribute('role', 'status');
    notice.hidden = true;
    form.before(notice);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const oldLabel = button.textContent;
        button.disabled = true;
        button.textContent = button.dataset.loadingLabel || 'Procesando...';
        notice.hidden = true;
        try {
            const result = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const contentType = result.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) throw new Error('Unexpected response');
            const data = await result.json();
            notice.textContent = typeof data.message === 'string' && data.message.trim()
                ? data.message : 'No fue posible completar la solicitud.';
            notice.setAttribute('role', data.success ? 'status' : 'alert');
            notice.hidden = false;
            if (result.ok && data.success && typeof data.redirect === 'string') {
                window.location.assign(data.redirect);
            }
        } catch (_) {
            notice.textContent = 'Ocurrió un problema al procesar la solicitud. Inténtalo otra vez.';
            notice.setAttribute('role', 'alert');
            notice.hidden = false;
        } finally {
            button.disabled = false;
            button.textContent = oldLabel;
        }
    });
});
