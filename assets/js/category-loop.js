/* Slow, seamless looping for the homepage category rail only. */
(function () {
    'use strict';

    var row = document.getElementById('genosys-category-row');
    if (!row) return;

    var originals = Array.prototype.slice.call(row.children);
    if (originals.length < 2) return;

    function copyCard(card) {
        var copy = card.cloneNode(true);
        copy.setAttribute('aria-hidden', 'true');
        copy.tabIndex = -1;
        return copy;
    }

    var before = document.createDocumentFragment();
    for (var first = 0; first < 2; first++) {
        originals.forEach(function (card) { before.appendChild(copyCard(card)); });
    }
    row.insertBefore(before, row.firstChild);
    for (var last = 0; last < 2; last++) {
        originals.forEach(function (card) { row.appendChild(copyCard(card)); });
    }

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var cycleWidth = 0;
    var frame = 0;
    var lastTime = 0;
    var position = 0;
    var moving = false;
    var pointerId = null;
    var resumeAt = 0;
    var nativeActive = false;
    var nativeSettling = false;
    var nativeStart = 0;
    var settleFrame = 0;
    var speed = 12; // Pixels per second.

    function measure() {
        var nextSet = row.children[originals.length * 3];
        var width = originals[0].getBoundingClientRect().right - nextSet.getBoundingClientRect().right;
        if (width <= 0) return;

        var phase = cycleWidth ? (-row.scrollLeft - 2 * cycleWidth) / cycleWidth : 0;
        phase = ((phase % 1) + 1) % 1;
        cycleWidth = width;
        row.classList.add('is-auto-scrolling');
        getComputedStyle(row).scrollSnapType;
        row.scrollLeft = -(2 + phase) * cycleWidth;
        position = row.scrollLeft;
        moving = false;
        lastTime = 0;
        if (reducedMotion.matches) row.classList.remove('is-auto-scrolling');
    }

    function centered(left) {
        if (!cycleWidth) return left;
        while (left > -2 * cycleWidth + .5) left -= cycleWidth;
        while (left < -3 * cycleWidth - .5) left += cycleWidth;
        return left;
    }

    function wrap() {
        position = centered(row.scrollLeft);
        if (Math.abs(position - row.scrollLeft) > .5) row.scrollLeft = position;
    }

    function stop() {
        cancelAnimationFrame(frame);
        frame = 0;
        lastTime = 0;
        moving = false;
    }

    function finishNative() {
        nativeSettling = false;
        nativeActive = false;
        settleFrame = 0;
        wrap();
        lastTime = 0;
        moving = false;
    }

    function settleNative() {
        var bounds = row.getBoundingClientRect();
        var styles = getComputedStyle(row);
        var edge = bounds.right - parseFloat(styles.scrollPaddingRight || 0);
        var start = row.scrollLeft;
        var target = start;
        var distance = Infinity;

        Array.prototype.forEach.call(row.children, function (card) {
            var aligned = start + card.getBoundingClientRect().right - edge;
            if (Math.abs(aligned - start) < distance) {
                target = aligned;
                distance = Math.abs(aligned - start);
            }
        });
        if (distance < .5) {
            finishNative();
            return;
        }

        nativeSettling = true;
        var started = performance.now();
        function settle(time) {
            var progress = Math.min(1, (time - started) / 300);
            row.scrollLeft = start + (target - start) * (1 - Math.pow(1 - progress, 3));
            if (progress < 1) settleFrame = requestAnimationFrame(settle);
            else {
                row.scrollLeft = target;
                finishNative();
            }
        }
        settleFrame = requestAnimationFrame(settle);
    }

    function tick(time) {
        frame = 0;
        if (document.hidden || reducedMotion.matches) return;

        if (nativeActive && !nativeSettling && pointerId === null && time >= resumeAt &&
            !row.classList.contains('is-dragging') && !row.classList.contains('is-settling')) {
            if (Math.abs(row.scrollLeft - nativeStart) < 1) {
                nativeActive = false;
                lastTime = 0;
            } else {
                settleNative();
            }
        }
        var busy = pointerId !== null || nativeActive || nativeSettling || time < resumeAt ||
            row.classList.contains('is-dragging') || row.classList.contains('is-settling');
        if (!busy && cycleWidth) {
            row.classList.add('is-auto-scrolling');
            if (!moving) {
                wrap();
                moving = true;
            }
            if (lastTime) position = centered(position - speed * Math.min(50, time - lastTime) / 1000);
            row.scrollLeft = position;
        } else {
            moving = false;
        }
        lastTime = time;
        frame = requestAnimationFrame(tick);
    }

    function start() {
        if (!frame && !document.hidden && !reducedMotion.matches) {
            lastTime = 0;
            frame = requestAnimationFrame(tick);
        }
    }

    function pauseNative() {
        if (reducedMotion.matches) return;
        cancelAnimationFrame(settleFrame);
        settleFrame = 0;
        nativeSettling = false;
        if (!nativeActive) nativeStart = row.scrollLeft;
        nativeActive = true;
        moving = false;
        resumeAt = performance.now() + 500;
    }

    row.addEventListener('pointerdown', function (event) {
        pointerId = event.pointerId;
        if (event.pointerType !== 'mouse') pauseNative();
    });
    function endPointer(event) {
        if (event.pointerId !== pointerId) return;
        pointerId = null;
        resumeAt = performance.now() + (nativeActive ? 500 : 100);
    }
    window.addEventListener('pointerup', endPointer);
    window.addEventListener('pointercancel', endPointer);
    window.addEventListener('blur', function () {
        pointerId = null;
        resumeAt = performance.now() + (nativeActive ? 500 : 100);
    });
    row.addEventListener('wheel', function (event) {
        if (event.deltaX || event.shiftKey) pauseNative();
    }, { passive: true });
    row.addEventListener('keydown', function (event) {
        if (/^(ArrowLeft|ArrowRight|Home|End|PageUp|PageDown| )$/.test(event.key)) pauseNative();
    });
    row.addEventListener('scroll', function () {
        if (nativeActive && !nativeSettling) {
            resumeAt = performance.now() + 500;
        }
    }, { passive: true });

    window.addEventListener('resize', measure);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stop();
        else start();
    });
    function motionChanged() {
        if (reducedMotion.matches) {
            stop();
            cancelAnimationFrame(settleFrame);
            nativeActive = false;
            nativeSettling = false;
            row.classList.remove('is-auto-scrolling');
        } else {
            row.classList.add('is-auto-scrolling');
            start();
        }
    }
    if (reducedMotion.addEventListener) reducedMotion.addEventListener('change', motionChanged);
    else reducedMotion.addListener(motionChanged);

    measure();
    start();
}());
