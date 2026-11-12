/* A compositor-driven loop; native scrolling takes over during interaction. */
(function () {
    'use strict';

    var row = document.getElementById('genosys-category-row');
    if (!row || typeof row.animate !== 'function') return;
    var originals = Array.prototype.slice.call(row.children);
    if (originals.length < 2) return;

    var track = document.createElement('div');
    track.className = 'genosys-categories__track';
    track.setAttribute('data-category-track', '');
    originals.forEach(function (card) { track.appendChild(card); });
    row.appendChild(track);
    row.classList.add('is-loop-ready');

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var speed = 8; // CSS pixels per second, independent of display refresh rate.
    var cycleWidth = 0;
    var copies = 0;
    var measuredWidth = 0;
    var animation = null;
    var pointerId = null;
    var touches = 0;
    var keyboardNavigation = false;
    var visible = !('IntersectionObserver' in window);
    var resumeTimer = 0;
    var resizeFrame = 0;
    var pendingMeasure = false;
    var ignoreScrollUntil = 0;

    function modulo(value, width) { return ((value % width) + width) % width; }

    function writeScroll(left) {
        ignoreScrollUntil = performance.now() + 80;
        row.scrollLeft = left;
    }

    function offset() {
        if (!animation || !cycleWidth) return 0;
        return modulo(Number(animation.currentTime) || 0, cycleWidth / speed * 1000) * speed / 1000;
    }

    function pause() {
        clearTimeout(resumeTimer);
        resumeTimer = 0;
        if (!animation) return;
        var left = row.scrollLeft - offset();
        animation.cancel();
        animation = null;
        row.classList.remove('is-auto-scrolling');
        // One handoff to native scrolling, never a scroll write on every frame.
        writeScroll(left);
    }

    function canPlay() {
        return cycleWidth && visible && !document.hidden && !reducedMotion.matches &&
            pointerId === null && !touches &&
            !(keyboardNavigation && row.contains(document.activeElement)) &&
            !row.classList.contains('is-dragging') && !row.classList.contains('is-settling');
    }

    function play() {
        resumeTimer = 0;
        if (animation || !canPlay()) return;
        if (pendingMeasure) {
            measure();
            if (pendingMeasure || animation || !canPlay()) return;
        }
        var previous = row.scrollLeft;
        // Identical copies let us recenter only when the user has finished.
        writeScroll(-copies * cycleWidth);
        var phase = modulo(row.scrollLeft - previous, cycleWidth);
        animation = track.animate([
            { transform: 'translate3d(0, 0, 0)' },
            { transform: 'translate3d(' + cycleWidth + 'px, 0, 0)' }
        ], { duration: cycleWidth / speed * 1000, iterations: Infinity, easing: 'linear' });
        animation.pause();
        animation.currentTime = phase / speed * 1000;
        row.classList.add('is-auto-scrolling');
        animation.play();
    }

    function resume(delay) {
        clearTimeout(resumeTimer);
        resumeTimer = window.setTimeout(play, delay === undefined ? 1000 : delay);
    }

    function copyCard(card) {
        var copy = card.cloneNode(true);
        copy.setAttribute('data-category-copy', '');
        copy.setAttribute('aria-hidden', 'true');
        copy.tabIndex = -1;
        copy.removeAttribute('id');
        copy.querySelectorAll('[id]').forEach(function (node) { node.removeAttribute('id'); });
        return copy;
    }

    function measure() {
        resizeFrame = 0;
        if (pointerId !== null || touches || row.classList.contains('is-dragging') || row.classList.contains('is-settling')) {
            pendingMeasure = true;
            return;
        }
        var first = originals[0].getBoundingClientRect();
        var last = originals[originals.length - 1].getBoundingClientRect();
        var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        var width = first.right - last.right + last.width + gap;
        var viewportWidth = row.clientWidth;
        if (width <= 0 || !viewportWidth) return;
        pendingMeasure = false;
        if (Math.abs(width - cycleWidth) < .01 && viewportWidth === measuredWidth) return;
        var phase = cycleWidth ? modulo(-row.scrollLeft + offset(), cycleWidth) / cycleWidth : 0;
        pause();
        cycleWidth = width;
        measuredWidth = viewportWidth;
        var needed = Math.ceil(viewportWidth / cycleWidth) + 1;
        if (copies !== needed) {
            track.querySelectorAll('[data-category-copy]').forEach(function (card) { card.remove(); });
            var before = document.createDocumentFragment();
            var after = document.createDocumentFragment();
            for (var set = 0; set < needed; set++) {
                originals.forEach(function (card) { before.appendChild(copyCard(card)); after.appendChild(copyCard(card)); });
            }
            track.insertBefore(before, track.firstChild);
            track.appendChild(after);
            copies = needed;
        }
        writeScroll(-(copies + phase) * cycleWidth);
        resume(0);
    }

    function queueMeasure() {
        if (!resizeFrame) resizeFrame = requestAnimationFrame(measure);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Tab' || event.altKey || event.ctrlKey || event.metaKey) return;
        keyboardNavigation = true;
        if (row.contains(document.activeElement)) pause();
    }, true);
    document.addEventListener('pointerdown', function () {
        keyboardNavigation = false;
        if (row.contains(document.activeElement)) resume();
    }, true);
    // Capture before the shared mouse-drag helper records its starting position.
    row.addEventListener('pointerdown', function (event) {
        if (event.isPrimary === false || (event.pointerType === 'mouse' && event.button !== 0)) return;
        pause();
        pointerId = event.pointerId;
    }, true);
    function endPointer(event) {
        if (event.pointerId !== pointerId) return;
        pointerId = null;
        resume();
    }
    window.addEventListener('pointerup', endPointer);
    window.addEventListener('pointercancel', endPointer);
    row.addEventListener('lostpointercapture', endPointer);
    window.addEventListener('pointermove', function (event) {
        if (event.pointerType === 'mouse' && event.pointerId === pointerId && !(event.buttons & 1)) endPointer(event);
    }, { passive: true });
    row.addEventListener('touchstart', function (event) {
        touches = event.touches.length;
        pause();
    }, { passive: true });
    function endTouch(event) {
        touches = event.touches.length;
        if (!touches) resume();
    }
    row.addEventListener('touchend', endTouch, { passive: true });
    row.addEventListener('touchcancel', endTouch, { passive: true });
    row.addEventListener('wheel', function (event) {
        if (!event.deltaX && !event.shiftKey) return;
        pause();
        resume();
    }, { passive: true });
    row.addEventListener('scroll', function () {
        if (animation || performance.now() < ignoreScrollUntil) return;
        // Wait for native touch/trackpad momentum instead of adding another snap.
        resume();
    }, { passive: true });
    row.addEventListener('keydown', function (event) {
        if (/^(ArrowLeft|ArrowRight|Home|End|PageUp|PageDown| )$/.test(event.key)) {
            keyboardNavigation = true;
            pause();
            resume();
        }
    }, true);
    row.addEventListener('focusin', function () { if (keyboardNavigation) pause(); });
    row.addEventListener('focusout', function () {
        window.setTimeout(function () {
            if (!row.contains(document.activeElement)) resume();
        }, 0);
    });
    window.addEventListener('blur', function () { pointerId = null; touches = 0; pause(); });
    window.addEventListener('focus', function () { resume(); });
    document.addEventListener('visibilitychange', function () { if (document.hidden) pause(); else resume(250); });
    function motionChanged() { if (reducedMotion.matches) pause(); else resume(250); }
    if (reducedMotion.addEventListener) reducedMotion.addEventListener('change', motionChanged);
    else reducedMotion.addListener(motionChanged);
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            visible = entries[0].isIntersecting;
            if (visible) {
                originals.forEach(function (card) {
                    card.querySelectorAll('img').forEach(function (img) { img.loading = 'eager'; });
                });
                resume(150);
            } else pause();
        }, { rootMargin: '120px 0px' }).observe(row);
    }
    if ('ResizeObserver' in window) new ResizeObserver(queueMeasure).observe(row);
    window.addEventListener('resize', queueMeasure);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(queueMeasure);
    measure();
}());
