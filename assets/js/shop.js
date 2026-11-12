(function () {
    'use strict';

    function init() {
        var page = document.querySelector('.genosys-shop');
        if (!page) return;
        var form = page.querySelector('[data-shop-filter-form]');
        if (!form) return;
        var sidebar = page.querySelector('[data-shop-sidebar]');
        var openButton = page.querySelector('[data-shop-open]');
        var closeButton = page.querySelector('[data-shop-close]');
        var backdrop = page.querySelector('[data-shop-backdrop]');
        var mobile = window.matchMedia('(max-width: 820px)');
        var ranges = page.querySelectorAll('[data-price-range]');
        var minInput = form.querySelector('[data-price-input="min"]');
        var maxInput = form.querySelector('[data-price-input="max"]');
        var minRange = form.querySelector('[data-price-range="min"]');
        var maxRange = form.querySelector('[data-price-range="max"]');
        var priceTrack = page.querySelector('[data-shop-price]');
        var drawerOpen = false;
        var submitted = false;
        var previousFocus = null;
        var savedBody = null;
        var priceEdited = false;
        var backdropTimer = null;

        function number(value) {
            var normalized = String(value || '').replace(/[\u06f0-\u06f9]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x06f0);
            }).replace(/[\u0660-\u0669]/g, function (digit) {
                return String(digit.charCodeAt(0) - 0x0660);
            }).replace(/[\s,\u066c]/g, '').replace(/\u066b/g, '.');
            if (!/^\d+(?:\.\d+)?$/.test(normalized)) return NaN;
            return Number(normalized);
        }

        function formatted(value, input) {
            if (input.type === 'number') return String(value);
            try { return new Intl.NumberFormat('fa-IR', { maximumFractionDigits: 6 }).format(value); }
            catch (ignored) { return String(value); }
        }

        function bounds() {
            return minRange && maxRange ? {
                min: Number(minRange.min || 0),
                max: Number(maxRange.max || 0)
            } : null;
        }

        function displayPrices(writeValues) {
            if (!minRange || !maxRange || !minInput || !maxInput) return;
            var limits = bounds();
            var span = limits.max - limits.min;
            var low = Number(minRange.value);
            var high = Number(maxRange.value);
            if (writeValues) {
                minInput.value = formatted(low, minInput);
                maxInput.value = formatted(high, maxInput);
            }
            if (priceTrack) {
                priceTrack.style.setProperty('--price-min', (span > 0 ? (low - limits.min) / span * 100 : 0) + '%');
                priceTrack.style.setProperty('--price-max', (span > 0 ? (high - limits.min) / span * 100 : 100) + '%');
            }
            minRange.setAttribute('aria-valuetext', formatted(low, minInput));
            maxRange.setAttribute('aria-valuetext', formatted(high, maxInput));
        }

        function commitPrices(changedInput) {
            if (!minRange || !maxRange || !minInput || !maxInput) return;
            if (!priceEdited && !minInput.value.trim() && !maxInput.value.trim()) {
                displayPrices(false);
                return;
            }
            var limits = bounds();
            var low = number(minInput.value);
            var high = number(maxInput.value);
            if (!Number.isFinite(low)) low = Number(minRange.value);
            if (!Number.isFinite(high)) high = Number(maxRange.value);
            low = Math.max(limits.min, Math.min(limits.max, low));
            high = Math.max(limits.min, Math.min(limits.max, high));
            if (low > high) {
                if (changedInput === maxInput) low = high;
                else high = low;
            }
            minRange.value = String(low);
            maxRange.value = String(high);
            displayPrices(true);
        }

        function normalizePriceFields() {
            if (priceEdited) commitPrices();
            [minInput, maxInput].forEach(function (input) {
                if (!input) return;
                var value = number(input.value);
                if (Number.isFinite(value)) input.value = String(value);
            });
            var limits = bounds();
            if (priceEdited && limits && number(minInput.value) === limits.min && number(maxInput.value) === limits.max) {
                minInput.value = '';
                maxInput.value = '';
            }
            // Every change starts from the first result page, including pretty permalink pagination.
            Array.prototype.forEach.call(form.querySelectorAll('[name="paged"], [name="product-page"]'), function (input) {
                input.disabled = true;
            });
        }

        function submit() {
            if (submitted) return;
            submitted = true;
            normalizePriceFields();
            if (drawerOpen) closeDrawer(false);
            page.setAttribute('aria-busy', 'true');
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else HTMLFormElement.prototype.submit.call(form);
        }

        function shouldSubmit(input) {
            return !mobile.matches || !sidebar || !sidebar.contains(input);
        }

        function visibleFocusable() {
            if (!sidebar) return [];
            return Array.prototype.filter.call(sidebar.querySelectorAll('button, a[href], input, select, textarea, summary, [tabindex]'), function (node) {
                return !node.disabled && node.tabIndex >= 0 && !node.hidden && node.getClientRects().length > 0;
            });
        }

        function openDrawer() {
            if (!sidebar || !mobile.matches || drawerOpen) return;
            previousFocus = document.activeElement;
            drawerOpen = true;
            window.clearTimeout(backdropTimer);
            if (backdrop) backdrop.hidden = false;
            savedBody = {
                overflow: document.body.style.overflow,
                paddingRight: document.body.style.paddingRight
            };
            var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            if (scrollbarWidth > 0) document.body.style.paddingRight = scrollbarWidth + 'px';
            document.body.style.overflow = 'hidden';
            sidebar.removeAttribute('inert');
            sidebar.removeAttribute('aria-hidden');
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            page.classList.add('is-filter-open');
            if (openButton) openButton.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(function () {
                var first = closeButton || visibleFocusable()[0];
                if (first) first.focus();
            });
        }

        function closeDrawer(restoreFocus) {
            if (!drawerOpen) return;
            drawerOpen = false;
            page.classList.remove('is-filter-open');
            if (backdrop) backdropTimer = window.setTimeout(function () {
                if (!drawerOpen) backdrop.hidden = true;
            }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 280);
            if (openButton) openButton.setAttribute('aria-expanded', 'false');
            if (savedBody) {
                document.body.style.overflow = savedBody.overflow;
                document.body.style.paddingRight = savedBody.paddingRight;
                savedBody = null;
            }
            if (sidebar) {
                sidebar.removeAttribute('role');
                sidebar.removeAttribute('aria-modal');
                if (mobile.matches) {
                    sidebar.setAttribute('inert', '');
                    sidebar.setAttribute('aria-hidden', 'true');
                }
            }
            if (restoreFocus !== false && previousFocus && previousFocus.isConnected) previousFocus.focus();
            previousFocus = null;
        }

        function resize() {
            if (!sidebar) return;
            if (!mobile.matches) {
                closeDrawer(false);
                sidebar.removeAttribute('inert');
                sidebar.removeAttribute('aria-hidden');
            } else if (!drawerOpen) {
                sidebar.setAttribute('inert', '');
                sidebar.setAttribute('aria-hidden', 'true');
            }
            var top = 24;
            var adminBar = document.getElementById('wpadminbar');
            var header = document.querySelector('.site-header');
            if (adminBar && window.getComputedStyle(adminBar).position === 'fixed') top += adminBar.getBoundingClientRect().height;
            if (header) {
                var position = window.getComputedStyle(header).position;
                if (position === 'sticky' || position === 'fixed') top += header.getBoundingClientRect().height;
            }
            page.style.setProperty('--shop-sticky-top', top + 'px');
        }

        page.classList.add('is-enhanced');
        if (openButton) openButton.addEventListener('click', openDrawer);
        if (closeButton) closeButton.addEventListener('click', function () { closeDrawer(true); });
        if (backdrop) backdrop.addEventListener('click', function () { closeDrawer(true); });
        document.addEventListener('keydown', function (event) {
            if (!drawerOpen) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDrawer(true);
            } else if (event.key === 'Tab') {
                var nodes = visibleFocusable();
                if (!nodes.length) return;
                var first = nodes[0];
                var last = nodes[nodes.length - 1];
                if (event.shiftKey && (document.activeElement === first || !sidebar.contains(document.activeElement))) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && (document.activeElement === last || !sidebar.contains(document.activeElement))) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        Array.prototype.forEach.call(ranges, function (range) {
            range.addEventListener('input', function () {
                var low = Number(minRange.value);
                var high = Number(maxRange.value);
                if (low > high) {
                    if (range === minRange) minRange.value = String(high);
                    else maxRange.value = String(low);
                }
                range.style.zIndex = '3';
                (range === minRange ? maxRange : minRange).style.zIndex = '2';
                priceEdited = true;
                displayPrices(true);
            });
            range.addEventListener('change', function () {
                if (priceEdited && shouldSubmit(range)) submit();
            });
        });
        [minInput, maxInput].forEach(function (input) {
            if (!input) return;
            input.addEventListener('input', function () { priceEdited = true; });
            input.addEventListener('change', function () {
                commitPrices(input);
                if (priceEdited && shouldSubmit(input)) submit();
            });
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                commitPrices(input);
                submit();
            });
        });

        page.addEventListener('change', function (event) {
            var input = event.target;
            if (input.matches('[data-shop-autosubmit]') && shouldSubmit(input)) submit();
        });
        form.addEventListener('submit', function () {
            normalizePriceFields();
            if (drawerOpen) closeDrawer(false);
        });
        if (mobile.addEventListener) mobile.addEventListener('change', resize);
        else mobile.addListener(resize);
        window.addEventListener('resize', resize, { passive: true });
        window.addEventListener('pageshow', function () {
            submitted = false;
            page.removeAttribute('aria-busy');
            displayPrices();
        });
        displayPrices();
        resize();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
