(function () {
    'use strict';

    function init() {
        var page = document.querySelector('.gorders');
        if (!page) return;
        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var cards = Array.prototype.slice.call(page.querySelectorAll('.gorder'));
        var siteTimeZone = page.getAttribute('data-time-zone');
        var siteOffset = Number(page.getAttribute('data-time-offset') || 0);
        var shiftDate = false;
        try {
            new Intl.DateTimeFormat('en-US', { timeZone: siteTimeZone || undefined }).format();
        } catch (ignored) {
            siteTimeZone = 'UTC';
            shiftDate = true;
        }
        var restoreId = null;
        var uploadNotice = null;
        var uploadNoticeKey = 'genosys-orders-receipt-uploaded';
        try {
            restoreId = window.sessionStorage.getItem('genosys-orders-open');
            window.sessionStorage.removeItem('genosys-orders-open');
            var storedNotice = window.sessionStorage.getItem(uploadNoticeKey);
            window.sessionStorage.removeItem(uploadNoticeKey);
            if (storedNotice) {
                var notice = JSON.parse(storedNotice);
                var noticeAge = Date.now() - Number(notice.createdAt);
                if (/^\d+$/.test(String(notice.orderId)) && Number.isFinite(noticeAge) && noticeAge >= 0 && noticeAge < 120000) {
                    uploadNotice = notice;
                }
            }
        } catch (ignored) {}

        function showUploadNotice() {
            var previous = page.querySelector('.gorders__toast');
            if (previous) previous.remove();
            var toast = document.createElement('div');
            toast.className = 'gorders__toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.setAttribute('aria-atomic', 'true');
            var icon = document.createElement('span');
            icon.className = 'gorders__toast-icon';
            icon.setAttribute('aria-hidden', 'true');
            var ns = 'http://www.w3.org/2000/svg';
            function svgIcon(path) {
                var svg = document.createElementNS(ns, 'svg');
                svg.setAttribute('viewBox', '0 0 24 24');
                svg.setAttribute('fill', 'none');
                svg.setAttribute('stroke', 'currentColor');
                svg.setAttribute('stroke-width', '2');
                svg.setAttribute('stroke-linecap', 'round');
                svg.setAttribute('stroke-linejoin', 'round');
                svg.setAttribute('aria-hidden', 'true');
                var shape = document.createElementNS(ns, 'path');
                shape.setAttribute('d', path);
                svg.appendChild(shape);
                return svg;
            }
            icon.appendChild(svgIcon('m5 12 4 4 10-10'));
            var message = document.createElement('strong');
            message.className = 'gorders__toast-message';
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'gorders__toast-close';
            close.setAttribute('aria-label', 'بستن اعلان');
            close.appendChild(svgIcon('m7 7 10 10M17 7 7 17'));
            toast.appendChild(icon);
            toast.appendChild(message);
            toast.appendChild(close);
            page.appendChild(toast);
            var timer;
            function dismiss() {
                window.clearTimeout(timer);
                toast.classList.remove('is-visible');
                if (reducedMotion.matches) toast.remove();
                else window.setTimeout(function () { toast.remove(); }, 240);
            }
            function scheduleDismiss() {
                window.clearTimeout(timer);
                if (toast.contains(document.activeElement) || toast.matches(':hover')) return;
                timer = window.setTimeout(dismiss, 5000);
            }
            close.addEventListener('click', dismiss);
            toast.addEventListener('mouseenter', function () { window.clearTimeout(timer); });
            toast.addEventListener('mouseleave', scheduleDismiss);
            toast.addEventListener('focusin', function () { window.clearTimeout(timer); });
            toast.addEventListener('focusout', function (event) {
                if (!toast.contains(event.relatedTarget)) scheduleDismiss();
            });
            window.requestAnimationFrame(function () {
                message.textContent = 'رسید با موفقیت بارگذاری شد';
                toast.classList.add('is-visible');
                scheduleDismiss();
            });
        }

        function scrollTo(node) {
            node.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
        }

        function toggleCard(card, open, animate) {
            var button = card.querySelector('.gorder__toggle');
            var detail = card.querySelector('.gorder__details');
            if (!button || !detail) return;
            if (detail._ordersAnimation) {
                detail._ordersAnimation.cancel();
                detail._ordersAnimation = null;
            }
            var wasHidden = detail.hidden;
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            card.classList.toggle('is-open', open);
            var label = button.querySelector('[data-toggle-label]');
            if (label) label.textContent = open ? 'بستن جزئیات' : 'مشاهده جزئیات';
            if (!animate || reducedMotion.matches || typeof detail.animate !== 'function') {
                detail.hidden = !open;
                detail.style.removeProperty('overflow');
                return;
            }
            detail.hidden = false;
            var height = detail.getBoundingClientRect().height;
            var computed = window.getComputedStyle(detail);
            var padTop = computed.paddingTop;
            var padBottom = computed.paddingBottom;
            detail.style.overflow = 'hidden';
            var animation = detail.animate([
                { height: open ? '0px' : height + 'px', opacity: open ? 0 : 1,
                    paddingTop: open ? '0px' : padTop, paddingBottom: open ? '0px' : padBottom },
                { height: open ? height + 'px' : '0px', opacity: open ? 1 : 0,
                    paddingTop: open ? padTop : '0px', paddingBottom: open ? padBottom : '0px' }
            ], { duration: open ? 340 : 260, easing: 'cubic-bezier(.22,.7,.25,1)', fill: 'none' });
            detail._ordersAnimation = animation;
            animation.finished.then(function () {
                if (detail._ordersAnimation !== animation) return;
                detail._ordersAnimation = null;
                detail.hidden = !open;
                detail.style.removeProperty('overflow');
            }).catch(function () {});
            // A closed panel always starts at zero, even when users toggle rapidly.
            if (wasHidden && !open) detail.hidden = true;
        }

        cards.forEach(function (card) {
            var button = card.querySelector('.gorder__toggle');
            if (!button) return;
            toggleCard(card, String(card.getAttribute('data-order-id')) === restoreId, false);
            button.addEventListener('click', function () {
                toggleCard(card, button.getAttribute('aria-expanded') !== 'true', true);
            });
        });

        function openHashTarget() {
            var hashId;
            try { hashId = decodeURIComponent(window.location.hash.slice(1)); } catch (ignored) { return; }
            if (!/^gco-order-\d+$/.test(hashId)) return;
            var target = document.getElementById(hashId);
            if (!target || !page.contains(target) || !target.classList.contains('gorder')) return;
            toggleCard(target, true, false);
            window.requestAnimationFrame(function () { scrollTo(target); });
        }
        openHashTarget();
        window.addEventListener('hashchange', openHashTarget);

        page.querySelectorAll('[data-missing-receipts]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                var first = cards.find(function (card) {
                    return ['missing', 'rejected'].indexOf(card.getAttribute('data-receipt-state')) !== -1 &&
                        !!card.querySelector('[data-orders-picker]');
                });
                if (!first) return;
                event.preventDefault();
                toggleCard(first, true, false);
                var receipt = first.querySelector('.gorder__receipt');
                scrollTo(receipt || first);
                var picker = first.querySelector('[data-orders-picker]');
                if (picker) picker.focus({ preventScroll: true });
            });
        });

        page.querySelectorAll('time[data-orders-date]').forEach(function (node) {
            var value = node.getAttribute('datetime') || node.getAttribute('data-orders-date');
            var date = new Date(value);
            if (!Number.isFinite(date.getTime())) return;
            if (shiftDate && Number.isFinite(siteOffset)) date = new Date(date.getTime() + siteOffset * 60000);
            try {
                var dateText = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
                    day: 'numeric', month: 'long', year: 'numeric', timeZone: siteTimeZone || undefined
                }).format(date);
                var timeText = new Intl.DateTimeFormat('fa-IR', {
                    hour: '2-digit', minute: '2-digit', hour12: false, timeZone: siteTimeZone || undefined
                }).format(date);
                node.textContent = dateText + '، ساعت ' + timeText;
            } catch (ignored) {}
        });

        page.querySelectorAll('[data-orders-receipt]').forEach(function (box) {
            var input = box.querySelector('[data-orders-file]');
            var button = box.querySelector('[data-orders-picker]');
            var error = box.querySelector('[data-orders-error]') || box.querySelector('.gorder__receipt-error');
            var configNode = box.querySelector('[data-orders-receipt-config]');
            if (!input || !button || !error || !configNode) return;
            var config = null;
            try { config = JSON.parse(configNode.textContent); } catch (ignored) {}
            var locked = false;
            var dragDepth = 0;
            var originalLabel = button.textContent;
            var allowedTypes = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp', pdf: 'application/pdf' };

            function showError(message) {
                error.textContent = message;
                error.hidden = false;
                if (!reducedMotion.matches && typeof box.animate === 'function') {
                    box.animate([
                        { transform: 'translateX(0)' }, { transform: 'translateX(-5px)' },
                        { transform: 'translateX(5px)' }, { transform: 'translateX(-3px)' },
                        { transform: 'translateX(0)' }
                    ], { duration: 320, easing: 'ease-out' });
                }
            }

            function endpoint(value) {
                try {
                    var url = new URL(value, window.location.href);
                    return url.origin === window.location.origin && /^https?:$/.test(url.protocol) ? url.href : null;
                } catch (ignored) { return null; }
            }

            async function upload(file) {
                if (locked || !file || (config && config.canUpload === false)) return;
                input.value = '';
                var extension = (file.name.split('.').pop() || '').toLowerCase();
                var mime = allowedTypes[extension];
                if (!mime || (file.type && file.type.toLowerCase() !== mime)) {
                    showError('فقط فایل‌های JPG، PNG، WebP و PDF قابل بارگذاری هستند.');
                    return;
                }
                if (!file.size || file.size > 5 * 1024 * 1024) {
                    showError('حجم فایل باید بیشتر از صفر و حداکثر ۵ مگابایت باشد.');
                    return;
                }
                var ajaxUrl = config && endpoint(config.ajaxUrl);
                if (!ajaxUrl || !config.orderId || !config.nonce) {
                    showError('بارگذاری فیش در دسترس نیست. لطفاً صفحه را دوباره بارگذاری کنید.');
                    return;
                }
                error.hidden = true;
                locked = true;
                box.dataset.state = 'busy';
                box.setAttribute('aria-busy', 'true');
                button.disabled = true;
                button.textContent = 'در حال بارگذاری…';
                var body = new FormData();
                body.append('action', 'genosys_receipt_upload');
                body.append('order_id', String(config.orderId));
                body.append('order_key', String(config.orderKey || ''));
                body.append('nonce', String(config.nonce));
                body.append('receipt_id', config.receipt && config.receipt.id ? String(config.receipt.id) : '');
                body.append('receipt', file, file.name);
                var controller = typeof AbortController === 'function' ? new AbortController() : null;
                var timer = controller ? window.setTimeout(function () { controller.abort(); }, 30000) : null;
                var saved = false;
                try {
                    var response = await window.fetch(ajaxUrl, {
                        method: 'POST', body: body, credentials: 'same-origin', cache: 'no-store',
                        signal: controller ? controller.signal : undefined
                    });
                    var payload = await response.json();
                    if (!response.ok || !payload || payload.success !== true || !payload.data || !payload.data.receipt) {
                        var failure = new Error('receipt_failed');
                        failure.receiptMessage = payload && payload.data && typeof payload.data.message === 'string' ?
                            payload.data.message : 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.';
                        throw failure;
                    }
                    saved = true;
                    var noticeStored = false;
                    try {
                        window.sessionStorage.setItem('genosys-orders-open', String(config.orderId));
                        var noticeValue = JSON.stringify({ orderId: String(config.orderId), createdAt: Date.now() });
                        window.sessionStorage.setItem(uploadNoticeKey, noticeValue);
                        noticeStored = window.sessionStorage.getItem(uploadNoticeKey) === noticeValue;
                    } catch (ignored) {}
                    if (!noticeStored) {
                        showUploadNotice();
                        await new Promise(function (resolve) { window.setTimeout(resolve, 2200); });
                    }
                    window.location.reload();
                } catch (failure) {
                    showError(failure.receiptMessage || (failure.name === 'AbortError' ?
                        'بارگذاری فیش بیش از حد طول کشید. لطفاً دوباره تلاش کنید.' :
                        'ارتباط برقرار نشد. لطفاً دوباره تلاش کنید.'));
                } finally {
                    if (timer) window.clearTimeout(timer);
                    if (!saved) {
                        locked = false;
                        box.dataset.state = 'idle';
                        box.setAttribute('aria-busy', 'false');
                        button.disabled = false;
                        button.textContent = originalLabel;
                    }
                }
            }

            button.addEventListener('click', function () { if (!locked) input.click(); });
            input.addEventListener('change', function () { upload(input.files && input.files[0]); });
            box.addEventListener('dragenter', function (event) {
                event.preventDefault();
                if (locked) return;
                dragDepth++;
                box.classList.add('is-dragging');
            });
            box.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (event.dataTransfer) event.dataTransfer.dropEffect = locked ? 'none' : 'copy';
            });
            box.addEventListener('dragleave', function (event) {
                event.preventDefault();
                dragDepth = Math.max(0, dragDepth - 1);
                if (!dragDepth) box.classList.remove('is-dragging');
            });
            box.addEventListener('drop', function (event) {
                event.preventDefault();
                dragDepth = 0;
                box.classList.remove('is-dragging');
                if (!locked) upload(event.dataTransfer && event.dataTransfer.files[0]);
            });
        });
        if (uploadNotice) showUploadNotice();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
