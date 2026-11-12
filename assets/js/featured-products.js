/* Switch between WooCommerce category panels without replacing product data. */
(function () {
    'use strict';

    var section = document.querySelector('[data-featured-products]');
    if (!section) return;

    var tabs = Array.prototype.slice.call(section.querySelectorAll('[data-featured-tab]'));
    var panels = Array.prototype.slice.call(section.querySelectorAll('[data-featured-panel]'));
    if (!tabs.length || tabs.length !== panels.length) return;

    function select(index, focus) {
        tabs.forEach(function (tab, current) {
            var active = current === index;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            panels[current].hidden = !active;
        });
        if (focus) tabs[index].focus();
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { select(index, false); });
        tab.addEventListener('keydown', function (event) {
            var next = index;
            if (event.key === 'ArrowDown') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowUp') next = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault();
            select(next, true);
        });
    });
}());
