/* Mouse-only drag smoothing; touch, wheel and keyboard keep native scrolling. */
(function () {
    'use strict';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var mobileRail = window.matchMedia('(max-width: 820px)');
    document.querySelectorAll('.latest-products__row, [data-featured-rail]').forEach(function (row) {
        var gesture = null, frame = 0, suppressClick = false;
        var rtl = getComputedStyle(row).direction === 'rtl';

        function enabled() {
            return !row.hasAttribute('data-featured-rail') || mobileRail.matches;
        }

        function cards() {
            var track = row.querySelector('[data-category-track]');
            return track ? track.children : row.children;
        }

        function clamp(left) {
            var extent = Math.max(0, row.scrollWidth - row.clientWidth);
            return Math.max(rtl ? -extent : 0, Math.min(rtl ? 0 : extent, left));
        }

        function stopMotion() {
            cancelAnimationFrame(frame);
            frame = 0;
            row.classList.remove('is-settling');
        }

        function nearestCard(projected) {
            var bounds = row.getBoundingClientRect(), styles = getComputedStyle(row);
            var edge = rtl ? bounds.right - parseFloat(styles.scrollPaddingRight || 0) : bounds.left + parseFloat(styles.scrollPaddingLeft || 0);
            var target = clamp(projected), distance = Infinity;
            Array.prototype.forEach.call(cards(), function (card) {
                var cardBounds = card.getBoundingClientRect();
                var left = clamp(row.scrollLeft + (rtl ? cardBounds.right : cardBounds.left) - edge);
                if (Math.abs(left - projected) < distance) {
                    distance = Math.abs(left - projected);
                    target = left;
                }
            });
            return target;
        }

        function followPointer(time) {
            frame = 0;
            if (!gesture || !gesture.dragged) return;
            var elapsed = Math.min(32, Math.max(1, time - gesture.frameTime));
            gesture.frameTime = time;
            // A short response time removes event jitter without a floaty lag.
            var delta = gesture.target - row.scrollLeft;
            row.scrollLeft += Math.abs(delta) < .25 ? delta : delta * (1 - Math.exp(-elapsed / 18));
            if (Math.abs(gesture.target - row.scrollLeft) > .25) frame = requestAnimationFrame(followPointer);
        }

        row.addEventListener('pointerdown', function (event) {
            if (!enabled()) return;
            suppressClick = false;
            stopMotion();
            if (event.pointerType !== 'mouse' || event.button !== 0) return;
            var now = performance.now();
            gesture = { id: event.pointerId, x: event.clientX, y: event.clientY, left: row.scrollLeft, target: row.scrollLeft, velocity: 0, sampleTime: now, frameTime: now, dragged: false };
        });
        window.addEventListener('pointermove', function (event) {
            if (!gesture || event.pointerId !== gesture.id) return;
            if (!(event.buttons & 1)) { finish(event); return; }
            var dx = event.clientX - gesture.x, dy = event.clientY - gesture.y;
            if (!gesture.dragged) {
                if (Math.abs(dy) > 6 && Math.abs(dy) > Math.abs(dx)) { gesture = null; return; }
                if (Math.abs(dx) < 6 || Math.abs(dx) <= Math.abs(dy)) return;
                gesture.dragged = true;
                var selection = window.getSelection();
                if (selection) selection.removeAllRanges();
                row.classList.add('is-dragging');
                row.setPointerCapture(event.pointerId);
            }
            event.preventDefault();
            var now = performance.now(), target = clamp(gesture.left - dx);
            var elapsed = Math.max(1, now - gesture.sampleTime);
            gesture.velocity = elapsed > 80 ? 0 : gesture.velocity * .35 + (target - gesture.target) / elapsed * .65;
            gesture.sampleTime = now;
            gesture.target = target;
            if (reducedMotion.matches) row.scrollLeft = target;
            else if (!frame) { gesture.frameTime = now; frame = requestAnimationFrame(followPointer); }
        });
        function finish(event) {
            if (!gesture || event.pointerId !== gesture.id) return;
            var completed = gesture;
            gesture = null;
            stopMotion();
            if (completed.dragged) row.classList.add('is-settling');
            row.classList.remove('is-dragging');
            if (row.hasPointerCapture(event.pointerId)) row.releasePointerCapture(event.pointerId);
            if (!completed.dragged) return;
            suppressClick = true;

            // A small, capped throw biases the next snap; never coast through many cards.
            var recent = performance.now() - completed.sampleTime < 80;
            var items = cards();
            var limit = items.length ? items[0].getBoundingClientRect().width * .22 : 0;
            var throwDistance = !reducedMotion.matches && recent && event.type === 'pointerup' ? Math.max(-limit, Math.min(limit, completed.velocity * 85)) : 0;
            var target = nearestCard(clamp(completed.target + throwDistance));
            var start = row.scrollLeft, difference = target - start;
            if (reducedMotion.matches || Math.abs(difference) < .5) {
                row.scrollLeft = target;
                row.classList.remove('is-settling');
                return;
            }
            var started = performance.now(), duration = Math.min(320, 200 + Math.abs(difference) * .35);
            function settle(time) {
                var progress = Math.min(1, (time - started) / duration);
                row.scrollLeft = start + difference * (1 - Math.pow(1 - progress, 3));
                if (progress < 1 && !reducedMotion.matches) frame = requestAnimationFrame(settle);
                else {
                    row.scrollLeft = target;
                    frame = 0;
                    row.classList.remove('is-settling');
                }
            }
            frame = requestAnimationFrame(settle);
        }
        window.addEventListener('pointerup', finish);
        window.addEventListener('pointercancel', finish);
        row.addEventListener('lostpointercapture', finish);
        window.addEventListener('blur', function () {
            if (gesture) finish({ pointerId: gesture.id });
            else stopMotion();
        });
        row.addEventListener('wheel', stopMotion, { passive: true });
        row.addEventListener('keydown', stopMotion);
        row.addEventListener('dragstart', function (event) { if (enabled()) event.preventDefault(); });
        row.addEventListener('click', function (event) {
            if (!suppressClick || event.detail === 0) return;
            suppressClick = false;
            event.preventDefault();
            event.stopImmediatePropagation();
        }, true);
    });
}());
