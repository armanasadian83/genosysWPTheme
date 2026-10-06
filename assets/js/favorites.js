/* Hearts save product IDs in this browser only; the catalog always comes from WooCommerce. */
(function () {
    'use strict';
    var buttons = document.querySelectorAll('[data-favorite-id]');
    if (!buttons.length) return;
    var key = 'genosys-favorite-products', favorites = [];
    try {
        var saved = JSON.parse(localStorage.getItem(key) || '[]');
        if (Array.isArray(saved)) favorites = saved.filter(function (id) { return /^\d+$/.test(String(id)); }).map(String);
    } catch (error) { /* Storage can be disabled; controls still work for this visit. */ }
    function refresh() {
        buttons.forEach(function (button) {
            var selected = favorites.indexOf(button.dataset.favoriteId) !== -1;
            button.setAttribute('aria-pressed', String(selected));
            button.setAttribute('aria-label', (selected ? 'حذف ' : 'افزودن ') + button.dataset.productName + (selected ? ' از علاقه‌مندی‌ها' : ' به علاقه‌مندی‌ها'));
            button.hidden = false;
        });
    }
    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.dataset.favoriteId, index = favorites.indexOf(id);
            if (index === -1) favorites.push(id); else favorites.splice(index, 1);
            try { localStorage.setItem(key, JSON.stringify(favorites)); } catch (error) { /* In-memory fallback. */ }
            refresh();
            var status = document.querySelector('[data-favorites-status]');
            if (status) status.textContent = button.dataset.productName + (index === -1 ? ' به علاقه‌مندی‌ها اضافه شد.' : ' از علاقه‌مندی‌ها حذف شد.');
        });
    });
    window.addEventListener('storage', function (event) {
        if (event.key !== key && event.key !== null) return;
        try {
            var saved = JSON.parse(event.newValue || '[]');
            favorites = Array.isArray(saved) ? saved.filter(function (id) { return /^\d+$/.test(String(id)); }).map(String) : [];
            refresh();
        } catch (error) { /* Ignore malformed external storage changes. */ }
    });
    refresh();
}());
