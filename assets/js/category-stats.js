/* Animate on scroll, and replay an individual counter on mouse hover. */
(function () {
    'use strict';

    document.documentElement.classList.add('genosys-stats-js');

    function init() {
        var items = document.querySelectorAll('.genosys-category-stats__item');
        if (!items.length || !('IntersectionObserver' in window) ||
            (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
            document.documentElement.classList.remove('genosys-stats-js');
            return;
        }

        var formatter = new Intl.NumberFormat('fa-IR', { useGrouping: false });
        var duration = 2100;
        var frames = new WeakMap();
        var played = new WeakSet();

        function replay(item) {
            var number = item.querySelector('.genosys-category-stats__number');
            var ring = item.querySelector('.genosys-category-stats__progress');
            var target = Number(number.getAttribute('data-count'));
            var start = null;
            var previousFrame = frames.get(item);

            if (previousFrame !== undefined) window.cancelAnimationFrame(previousFrame);
            number.textContent = formatter.format(0);
            item.classList.remove('is-visible');
            void window.getComputedStyle(ring).strokeDashoffset;

            function tick(time) {
                if (start === null) start = time;
                var progress = Math.min((time - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                number.textContent = formatter.format(Math.floor(target * eased));
                if (progress < 1) {
                    frames.set(item, window.requestAnimationFrame(tick));
                } else {
                    number.textContent = formatter.format(target);
                    frames.delete(item);
                }
            }

            frames.set(item, window.requestAnimationFrame(function () {
                item.classList.add('is-visible');
                frames.set(item, window.requestAnimationFrame(tick));
            }));
        }

        items.forEach(function (item) {
            item.querySelector('.genosys-category-stats__number').textContent = formatter.format(0);
        });
        items[0].parentElement.classList.add('is-ready');

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting || played.has(entry.target)) return;
                observer.unobserve(entry.target);
                played.add(entry.target);
                replay(entry.target);
            });
        }, { threshold: 0.35 });

        if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
            items.forEach(function (item) {
                item.addEventListener('pointerenter', function (event) {
                    if (event.pointerType === 'mouse' && played.has(item)) replay(item);
                });
            });
        }

        window.requestAnimationFrame(function () {
            items.forEach(function (item) { observer.observe(item); });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
}());
