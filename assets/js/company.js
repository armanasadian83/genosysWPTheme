/* Accessible RTL history carousel and the company's export counter. */
(function () {
    'use strict';
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var track = document.querySelector('[data-history-track]');
    if (track) {
        var controls = document.querySelector('[data-history-controls]');
        var previous = controls && controls.querySelector('[data-history-prev]');
        var next = controls && controls.querySelector('[data-history-next]');
        var years = Array.prototype.slice.call(track.children);
        var pending = false;
        function step() {
            return years.length > 1 ? Math.abs(years[1].offsetLeft - years[0].offsetLeft) : track.clientWidth;
        }
        function index() { return Math.round(Math.abs(track.scrollLeft) / step()); }
        function maximum() { return Math.max(0, Math.ceil((track.scrollWidth - track.clientWidth - 2) / step())); }
        function update() {
            pending = false;
            if (previous) { previous.disabled = index() <= 0; }
            if (next) { next.disabled = index() >= maximum(); }
        }
        function move(to) {
            var destination = Math.max(0, Math.min(maximum(), to));
            track.scrollTo({ left: -destination * step(), behavior: reduceMotion.matches ? 'auto' : 'smooth' });
        }
        if (controls) { controls.hidden = false; }
        if (previous) { previous.addEventListener('click', function () { move(index() - 1); }); }
        if (next) { next.addEventListener('click', function () { move(index() + 1); }); }
        track.addEventListener('scroll', function () {
            if (!pending) { pending = true; window.requestAnimationFrame(update); }
        }, { passive: true });
        track.addEventListener('keydown', function (event) {
            var keys = { ArrowLeft: index() + 1, ArrowRight: index() - 1, Home: 0, End: maximum() };
            if (Object.prototype.hasOwnProperty.call(keys, event.key)) {
                event.preventDefault(); move(keys[event.key]);
            }
        });
        var drag = null;
        track.addEventListener('pointerdown', function (event) {
            if (event.pointerType !== 'mouse' || event.button !== 0) { return; }
            drag = { x: event.clientX, left: track.scrollLeft, id: event.pointerId, moved: false };
            track.setPointerCapture(event.pointerId);
        });
        track.addEventListener('pointermove', function (event) {
            if (!drag || drag.id !== event.pointerId) { return; }
            var distance = event.clientX - drag.x;
            if (Math.abs(distance) > 5) { drag.moved = true; track.classList.add('is-dragging'); }
            if (drag.moved) { event.preventDefault(); track.scrollLeft = drag.left - distance; }
        });
        function endDrag() {
            if (!drag) { return; }
            var moved = drag.moved;
            drag = null;
            track.classList.remove('is-dragging');
            if (moved) { move(index()); }
        }
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);
        track.addEventListener('lostpointercapture', endDrag);
        window.addEventListener('resize', update, { passive: true });
        update();
    }

    var counter = document.querySelector('[data-company-counter]');
    if (counter && 'IntersectionObserver' in window && !reduceMotion.matches) {
        var format = new Intl.NumberFormat('fa-IR', { useGrouping: false });
        var observer = new IntersectionObserver(function (entries) {
            if (!entries.some(function (entry) { return entry.isIntersecting; })) { return; }
            observer.disconnect();
            var start = performance.now();
            function count(now) {
                var fraction = Math.min(1, (now - start) / 1200);
                counter.textContent = format.format(Math.round(50 * (1 - Math.pow(1 - fraction, 2))));
                if (fraction < 1) { window.requestAnimationFrame(count); }
            }
            window.requestAnimationFrame(count);
        }, { threshold: .5 });
        observer.observe(counter);
    }
}());
