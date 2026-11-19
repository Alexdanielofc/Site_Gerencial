// Theme toggle
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.querySelector('[data-theme-toggle]');
    if (btn) {
        var html = document.documentElement;

        function syncIcons() {
            var isDark = html.getAttribute('data-theme') === 'dark';
            btn.setAttribute('aria-pressed', String(isDark));
            var sunEl = btn.querySelector('[data-theme-icon-sun]');
            var moonEl = btn.querySelector('[data-theme-icon-moon]');
            if (sunEl) sunEl.classList.toggle('hidden', !isDark);
            if (moonEl) moonEl.classList.toggle('hidden', isDark);
        }

        syncIcons();

        btn.addEventListener('click', function () {
            var isDark = html.getAttribute('data-theme') === 'dark';
            var next = isDark ? 'light' : 'dark';
            if (next === 'dark') {
                html.setAttribute('data-theme', 'dark');
            } else {
                html.removeAttribute('data-theme');
            }
            try { localStorage.setItem('theme', next); } catch (e) {}
            syncIcons();
        });
    }
});

// 1) Confirmation listener (runs BEFORE submission lock)
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form || form.tagName !== 'FORM') return;

    var submitter = event.submitter;
    var message = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');

    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

// 2) Double-submit protection and loading feedback (non-GET forms)
var SPINNER_SVG = '<svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" /></svg>';

document.addEventListener('submit', function (event) {
    if (event.defaultPrevented) return;

    var form = event.target;
    if (!form || form.tagName !== 'FORM') return;

    var method = (form.getAttribute('method') || 'GET').toUpperCase();
    if (method === 'GET') return;

    if (form.getAttribute('data-submitting') === 'true') {
        event.preventDefault();
        return;
    }

    form.setAttribute('data-submitting', 'true');
    form.setAttribute('aria-busy', 'true');

    var loadingText = form.getAttribute('data-loading-text') || 'Aguarde...';
    var buttons = form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])');

    setTimeout(function () {
        buttons.forEach(function (button) {
            if (button.disabled) return;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');

            if (button.tagName === 'BUTTON') {
                if (!button.hasAttribute('data-original-html')) {
                    button.setAttribute('data-original-html', button.innerHTML);
                }
                var btnText = button.getAttribute('data-loading-text') || loadingText;
                button.textContent = '';
                button.insertAdjacentHTML('afterbegin', SPINNER_SVG + ' ');
                button.appendChild(document.createTextNode(btnText));
            }
        });
    }, 0);
});

// Restore forms when returning via browser history (bfcache)
window.addEventListener('pageshow', function (event) {
    if (!event.persisted) return;

    document.querySelectorAll('form[data-submitting]').forEach(function (form) {
        form.removeAttribute('data-submitting');
        form.removeAttribute('aria-busy');
        form.querySelectorAll('button[disabled], input[type="submit"][disabled]').forEach(function (button) {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.hasAttribute('data-original-html')) {
                button.innerHTML = button.getAttribute('data-original-html');
                button.removeAttribute('data-original-html');
            }
        });
    });
});


