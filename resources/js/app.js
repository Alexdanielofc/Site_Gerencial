// Theme toggle
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.querySelector('[data-theme-toggle]');
    if (!btn) return;

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
});

