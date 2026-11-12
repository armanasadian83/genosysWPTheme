/* Decorative feedback after a confirmed single-product addition. */
(function () {
    'use strict';

    var config = window.genosysCartAnimation;
    var product = document.querySelector('body.single-product .genosys-product');
    if (!config || !config.endpoint || !config.nonce || !product || !window.fetch) return;

    var inFlight = false;
    var lastOverlay = null;
    var persianDigits = '۰۱۲۳۴۵۶۷۸۹';
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function persianNumber(number) {
        return String(number).replace(/[0-9]/g, function (digit) { return persianDigits[Number(digit)]; });
    }

    function delay(ms) {
        return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
    }

    function play(element, frames, options) {
        if (!element || !element.animate) return delay(options.duration);
        var animation = element.animate(frames, options);
        return animation.finished;
    }

    function renderedRect(element) {
        if (!element || !element.isConnected || element.closest('[inert]') || !element.getClientRects().length) return null;
        for (var node = element; node && node.nodeType === 1; node = node.parentElement) {
            var style = window.getComputedStyle(node);
            if (style.display === 'none' || style.visibility === 'hidden' || style.visibility === 'collapse' || Number(style.opacity) === 0 || node.hidden || node.getAttribute('aria-hidden') === 'true') return null;
        }
        var rect = element.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 ? rect : null;
    }

    function visibleRect(element, fullyVisible) {
        var rect = renderedRect(element);
        if (!rect || rect.right <= 0 || rect.bottom <= 0 || rect.left >= window.innerWidth || rect.top >= window.innerHeight) return null;
        if (fullyVisible && (rect.left < 0 || rect.top < 0 || rect.right > window.innerWidth || rect.bottom > window.innerHeight)) return null;
        return rect;
    }

    function imageOnPage() {
        var images = Array.prototype.slice.call(product.querySelectorAll('.genosys-product__gallery .woocommerce-product-gallery__image img'));
        return images.find(function (image) {
            return image.closest('.flex-active-slide') && renderedRect(image);
        }) || images.find(function (image) {
            return visibleRect(image);
        }) || images.find(function (image) {
            return renderedRect(image);
        });
    }

    function animationSourceRect(source) {
        var rect = visibleRect(source, true);
        if (rect) return rect;
        rect = renderedRect(source);
        if (!rect) throw new Error('Animation source unavailable');
        // On mobile the gallery is normally above the purchase form. Bring only
        // its decorative clone into view; keep the real image and page in place.
        var scale = Math.min(Math.min(160, window.innerWidth * .4) / rect.width, Math.min(160, window.innerHeight * .28) / rect.height);
        var width = rect.width * scale;
        var height = rect.height * scale;
        return {
            left: (window.innerWidth - width) / 2,
            top: Math.max(16, window.innerHeight / 2 - 85 - height - 24),
            width: width,
            height: height
        };
    }

    function basketMarkup() {
        return "<div class=\"gca-veil\"></div>" +
            "<div class=\"gca-basket\" aria-hidden=\"true\">" +
            "<svg class=\"gca-basket-back\" viewBox=\"0 0 200 170\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\">" +
            "<defs><linearGradient id=\"gca-rear-cord\" x1=\"57\" y1=\"14\" x2=\"142\" y2=\"79\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#6e102b\"/><stop offset=\".4\" stop-color=\"#cc3c5e\"/><stop offset=\".73\" stop-color=\"#9d183b\"/><stop offset=\"1\" stop-color=\"#5c0b24\"/></linearGradient><linearGradient id=\"gca-inner\" x1=\"100\" y1=\"46\" x2=\"100\" y2=\"87\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#84213c\"/><stop offset=\".45\" stop-color=\"#4b1024\"/><stop offset=\"1\" stop-color=\"#260912\"/></linearGradient><linearGradient id=\"gca-back-edge\" x1=\"25\" y1=\"53\" x2=\"174\" y2=\"75\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#f17b94\"/><stop offset=\".45\" stop-color=\"#cd365b\"/><stop offset=\"1\" stop-color=\"#851332\"/></linearGradient></defs>" +
            "<path d=\"M61 72V47C61 7 139 7 139 47V72\" fill=\"none\" stroke=\"#4a0c23\" stroke-opacity=\".22\" stroke-width=\"8\" stroke-linecap=\"round\"/>" +
            "<path d=\"M61 71V46C61 10 139 10 139 46V71\" fill=\"none\" stroke=\"url(#gca-rear-cord)\" stroke-width=\"5.5\" stroke-linecap=\"round\"/>" +
            "<path d=\"M60 57V46C60 11 137 11 138 46\" fill=\"none\" stroke=\"#f0a0b1\" stroke-opacity=\".3\" stroke-width=\"1\" stroke-linecap=\"round\"/>" +
            "<ellipse cx=\"100\" cy=\"68\" rx=\"77\" ry=\"21\" fill=\"url(#gca-inner)\"/>" +
            "<path d=\"M23 68C29 41 171 41 177 68\" fill=\"none\" stroke=\"url(#gca-back-edge)\" stroke-width=\"2.5\"/>" +
            "<path d=\"M34 64C57 49 143 49 166 64\" fill=\"none\" stroke=\"#ed8ca5\" stroke-opacity=\".16\" stroke-width=\"1.5\"/>" +
            "</svg>" +
            "<div class=\"gca-product\"></div>" +
            "<svg class=\"gca-basket-front\" viewBox=\"0 0 200 170\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\">" +
            "<defs><linearGradient id=\"gca-body\" x1=\"26\" y1=\"87\" x2=\"172\" y2=\"147\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#dc2d55\"/><stop offset=\".3\" stop-color=\"#cc1644\"/><stop offset=\".72\" stop-color=\"#b21139\"/><stop offset=\"1\" stop-color=\"#86112f\"/></linearGradient><linearGradient id=\"gca-left-fold\" x1=\"24\" y1=\"110\" x2=\"56\" y2=\"112\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#a81339\"/><stop offset=\".6\" stop-color=\"#c82048\"/><stop offset=\"1\" stop-color=\"#8c1030\"/></linearGradient><linearGradient id=\"gca-right-fold\" x1=\"148\" y1=\"113\" x2=\"176\" y2=\"114\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#8d1030\"/><stop offset=\".6\" stop-color=\"#ab173b\"/><stop offset=\"1\" stop-color=\"#650c26\"/></linearGradient><linearGradient id=\"gca-satin\" x1=\"47\" y1=\"80\" x2=\"153\" y2=\"156\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#fff\" stop-opacity=\".14\"/><stop offset=\".4\" stop-color=\"#fff\" stop-opacity=\".025\"/><stop offset=\"1\" stop-color=\"#390717\" stop-opacity=\".08\"/></linearGradient><linearGradient id=\"gca-front-cord\" x1=\"62\" y1=\"32\" x2=\"139\" y2=\"95\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#8c1736\"/><stop offset=\".35\" stop-color=\"#d25270\"/><stop offset=\".66\" stop-color=\"#b93153\"/><stop offset=\"1\" stop-color=\"#64102b\"/></linearGradient><radialGradient id=\"gca-eyelet\"><stop stop-color=\"#6d1530\"/><stop offset=\".46\" stop-color=\"#6d1530\"/><stop offset=\".52\" stop-color=\"#f3bdca\"/><stop offset=\".72\" stop-color=\"#ae6376\"/><stop offset=\"1\" stop-color=\"#f0acbe\"/></radialGradient><linearGradient id=\"gca-lip\" x1=\"24\" y1=\"72\" x2=\"177\" y2=\"76\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#f18aa2\"/><stop offset=\".35\" stop-color=\"#ed5d81\"/><stop offset=\"1\" stop-color=\"#b12a4c\"/></linearGradient></defs>" +
            "<path d=\"M24 71Q100 89 176 71L165 151Q164 159 155 160Q100 169 45 160Q36 159 35 151Z\" fill=\"url(#gca-body)\" stroke=\"#76102d\" stroke-opacity=\".5\" stroke-width=\".8\"/>" +
            "<path d=\"M24 72L45 83L53 160L45 160Q36 159 35 151Z\" fill=\"url(#gca-left-fold)\"/>" +
            "<path d=\"M176 72L155 83L147 160L155 160Q164 159 165 151Z\" fill=\"url(#gca-right-fold)\"/>" +
            "<path d=\"M45 83Q100 94 155 83L147 160Q100 167 53 160Z\" fill=\"url(#gca-satin)\"/>" +
            "<path d=\"M45 84L53 157M155 84L147 157\" fill=\"none\" stroke=\"#ffb3c5\" stroke-opacity=\".22\" stroke-width=\".75\"/>" +
            "<path d=\"M39 150Q100 163 161 150\" fill=\"none\" stroke=\"#690e28\" stroke-opacity=\".3\" stroke-width=\".9\"/>" +
            "<path d=\"M45 156Q100 166 155 156\" fill=\"none\" stroke=\"#f795ad\" stroke-opacity=\".2\" stroke-width=\".7\"/>" +
            "<path d=\"M24 71Q100 90 176 71\" fill=\"none\" stroke=\"#851533\" stroke-width=\"4.5\" stroke-linecap=\"round\"/>" +
            "<path d=\"M24 70Q100 88 176 70\" fill=\"none\" stroke=\"url(#gca-lip)\" stroke-width=\"2.3\" stroke-linecap=\"round\"/>" +
            "<ellipse cx=\"63\" cy=\"94\" rx=\"4\" ry=\"3.5\" fill=\"#77112e\" fill-opacity=\".23\"/>" +
            "<ellipse cx=\"137\" cy=\"94\" rx=\"4\" ry=\"3.5\" fill=\"#77112e\" fill-opacity=\".23\"/>" +
            "<circle cx=\"63\" cy=\"92\" r=\"3\" fill=\"url(#gca-eyelet)\"/><circle cx=\"137\" cy=\"92\" r=\"3\" fill=\"url(#gca-eyelet)\"/>" +
            "<path d=\"M63 92V54C63 23 137 23 137 54V92\" fill=\"none\" stroke=\"#610d28\" stroke-opacity=\".2\" stroke-width=\"6\" stroke-linecap=\"round\"/>" +
            "<path d=\"M63 91V53C63 24 137 24 137 53V91\" fill=\"none\" stroke=\"url(#gca-front-cord)\" stroke-width=\"4\" stroke-linecap=\"round\"/>" +
            "<path d=\"M62 83V53C62 25 135 25 136 53\" fill=\"none\" stroke=\"#ffd0db\" stroke-opacity=\".35\" stroke-width=\".8\" stroke-linecap=\"round\"/>" +
            "<text x=\"100\" y=\"131.5\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"8.2\" font-weight=\"600\" letter-spacing=\"1.6\" fill=\"#6b0d2a\" fill-opacity=\".32\">GENOSYS</text>" +
            "<text x=\"100\" y=\"131\" text-anchor=\"middle\" font-family=\"Arial, sans-serif\" font-size=\"8.2\" font-weight=\"600\" letter-spacing=\"1.6\" fill=\"#ffe2e9\" fill-opacity=\".85\">GENOSYS</text>" +
            "</svg>" +
            "<svg class=\"gca-flap gca-flap--left\" viewBox=\"0 0 100 50\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\"><defs><linearGradient id=\"gca-fold-left\" x1=\"14\" y1=\"39\" x2=\"91\" y2=\"19\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#8d1434\"/><stop offset=\".4\" stop-color=\"#d8476a\"/><stop offset=\"1\" stop-color=\"#b52149\"/></linearGradient></defs><path d=\"M24 42Q55 20 100 31L100 53Q56 47 24 48Z\" fill=\"url(#gca-fold-left)\" stroke=\"#7c1532\" stroke-opacity=\".5\" stroke-width=\".8\"/><path d=\"M27 41Q57 24 96 32\" fill=\"none\" stroke=\"#ffb1c4\" stroke-opacity=\".45\" stroke-width=\"1\"/><path d=\"M28 46Q59 43 96 50\" fill=\"none\" stroke=\"#7b1031\" stroke-opacity=\".3\" stroke-width=\".8\"/></svg>" +
            "<svg class=\"gca-flap gca-flap--right\" viewBox=\"0 0 100 50\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\"><defs><linearGradient id=\"gca-fold-right\" x1=\"86\" y1=\"39\" x2=\"9\" y2=\"19\" gradientUnits=\"userSpaceOnUse\"><stop stop-color=\"#73102e\"/><stop offset=\".6\" stop-color=\"#c43258\"/><stop offset=\"1\" stop-color=\"#a41b40\"/></linearGradient></defs><path d=\"M76 42Q45 20 0 31L0 53Q44 47 76 48Z\" fill=\"url(#gca-fold-right)\" stroke=\"#6c102b\" stroke-opacity=\".5\" stroke-width=\".8\"/><path d=\"M73 41Q43 24 4 32\" fill=\"none\" stroke=\"#f9a2b9\" stroke-opacity=\".4\" stroke-width=\"1\"/><path d=\"M72 46Q41 43 4 50\" fill=\"none\" stroke=\"#650d28\" stroke-opacity=\".3\" stroke-width=\".8\"/></svg>" +
            "</div>" +
            "<div class=\"gca-toast\" role=\"status\" aria-live=\"polite\"><span class=\"gca-toast-message\"></span><a class=\"gca-toast-link\">مشاهده سبد</a></div>";
    }

    function makeOverlay() {
        if (lastOverlay) lastOverlay.remove();
        var overlay = document.createElement('div');
        overlay.className = 'genosys-add-animation';
        overlay.innerHTML = basketMarkup();
        document.body.appendChild(overlay);
        lastOverlay = overlay;
        return overlay;
    }

    function showToast(overlay, message, cartUrl) {
        var toast = overlay.querySelector('.gca-toast');
        toast.querySelector('.gca-toast-message').textContent = message;
        var link = toast.querySelector('.gca-toast-link');
        link.href = cartUrl || config.cartUrl || '/cart/';
        toast.classList.add('is-visible');
        function removeToast() {
            // Keep the cart link available while a keyboard user is using it.
            if (toast.contains(document.activeElement)) {
                toast.addEventListener('focusout', function () { window.setTimeout(removeToast, 0); }, { once: true });
                return;
            }
            if (lastOverlay === overlay) lastOverlay = null;
            overlay.remove();
        }
        window.setTimeout(removeToast, 4500);
    }

    function showInlineFeedback(form, message) {
        var feedback = form.parentElement.querySelector('.gca-inline-feedback');
        if (!feedback) {
            feedback = document.createElement('p');
            feedback.className = 'gca-inline-feedback';
            feedback.setAttribute('role', 'status');
            form.insertAdjacentElement('afterend', feedback);
        }
        feedback.textContent = message;
    }

    function currentCartTarget() {
        var target = document.querySelector('.site-header .nav-cart-link');
        return visibleRect(target, true) ? target : null;
    }

    async function animateBasket(overlay, source, checkMotion) {
        var basket = overlay.querySelector('.gca-basket');
        var clone = overlay.querySelector('.gca-product');
        var centerX = window.innerWidth / 2;
        var centerY = window.innerHeight / 2;
        basket.style.left = centerX + 'px';
        basket.style.top = centerY + 'px';
        overlay.offsetWidth;
        overlay.classList.add('is-active');

        // 1. The woven basket rises in front of the softened page.
        await play(basket, [
            { transform: 'translate(-50%, -50%) scale(.55)', opacity: 0 },
            { transform: 'translate(-50%, -50%) scale(1.08)', opacity: 1, offset: .72 },
            { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 }
        ], { duration: 420, easing: 'cubic-bezier(.18,.86,.28,1)', fill: 'forwards' });
        checkMotion();

        // Keep the clone in the basket's layer stack even while it starts at the gallery image.
        var sourceRect = animationSourceRect(source);
        var width = Math.max(1, sourceRect.width);
        var height = Math.max(1, sourceRect.height);
        var localLeft = sourceRect.left - (centerX - 100);
        var localTop = sourceRect.top - (centerY - 85);
        clone.style.left = localLeft + 'px';
        clone.style.top = localTop + 'px';
        clone.style.width = width + 'px';
        clone.style.height = height + 'px';
        var image = document.createElement('img');
        image.src = source.currentSrc || source.src;
        image.alt = '';
        clone.appendChild(image);
        var moveX = 100 - (localLeft + width / 2);
        var moveY = 65 - (localTop + height / 2);
        var atOpening = 'translate(' + moveX + 'px, ' + moveY + 'px) scale(.25)';

        // 2. Lift, shrink, and arc to the opening.
        await play(clone, [
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: 'translate(' + (moveX * .55) + 'px, ' + (moveY * .55 - 96) + 'px) scale(.55)', opacity: 1, offset: .55 },
            { transform: atOpening, opacity: 1 }
        ], { duration: 700, easing: 'cubic-bezier(.25,.1,.25,1)', fill: 'forwards' });
        checkMotion();

        // 3. Fall behind the opaque front panel.
        await play(clone, [
            { transform: atOpening, opacity: 1 },
            { transform: 'translate(' + moveX + 'px, ' + (moveY + 16) + 'px) scale(.24)', opacity: 1, offset: .37 },
            { transform: 'translate(' + moveX + 'px, ' + (moveY + 90) + 'px) scale(.19)', opacity: 0 }
        ], { duration: 410, easing: 'cubic-bezier(.4,0,.86,.6)', fill: 'forwards' });
        checkMotion();

        // 4. Squash, recover, and shut both hinged flaps.
        await play(basket, [
            { transform: 'translate(-50%, -50%) scale(1, 1)' },
            { transform: 'translate(-50%, -50%) scale(1.07, .91)', offset: .45 },
            { transform: 'translate(-50%, -50%) scale(1, 1)' }
        ], { duration: 180, easing: 'ease-out', fill: 'forwards' });
        checkMotion();
        basket.classList.add('is-closed');
        await delay(280);
        checkMotion();

        // 5. Only use the cart if it is already visible; never reveal or pin it.
        var target = currentCartTarget();
        var targetRect = target && target.getBoundingClientRect();
        overlay.classList.remove('is-active');
        var frames = targetRect ? [
            { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
            { transform: 'translate(-50%, -50%) translateY(-29px) scale(1.04)', opacity: 1, offset: .22 },
            { transform: 'translate(-50%, -50%) translate(' + (targetRect.left + targetRect.width / 2 - centerX) + 'px, ' + (targetRect.top + targetRect.height / 2 - centerY) + 'px) scale(.12)', opacity: 0 }
        ] : [
            { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
            { transform: 'translate(-50%, -50%) scale(.7)', opacity: 0 }
        ];
        await play(basket, frames, { duration: targetRect ? 820 : 280, easing: 'cubic-bezier(.4,0,.5,1)', fill: 'forwards' });
        basket.style.visibility = 'hidden';
    }

    async function showBasket(overlay) {
        var source = imageOnPage();
        if (config.enabled === false || reducedMotion.matches || !source || document.hidden || document.body.classList.contains('mobile-menu-open')) return;
        var interrupted = false;
        function interrupt() {
            interrupted = true;
            overlay.classList.remove('is-active');
            overlay.querySelector('.gca-basket').style.visibility = 'hidden';
            if (overlay.getAnimations) overlay.getAnimations({ subtree: true }).forEach(function (animation) { animation.cancel(); });
        }
        function checkMotion() {
            if (interrupted || !overlay.isConnected || document.body.classList.contains('mobile-menu-open') || !renderedRect(source)) throw new Error('Animation interrupted');
        }
        window.addEventListener('resize', interrupt);
        window.addEventListener('scroll', interrupt, { passive: true });
        window.addEventListener('pagehide', interrupt);
        document.addEventListener('visibilitychange', interrupt);
        if (reducedMotion.addEventListener) reducedMotion.addEventListener('change', interrupt);
        try {
            await animateBasket(overlay, source, checkMotion);
        } catch (error) {
            // A decorative animation failure cannot undo a confirmed purchase.
        } finally {
            interrupt();
            window.removeEventListener('resize', interrupt);
            window.removeEventListener('scroll', interrupt);
            window.removeEventListener('pagehide', interrupt);
            document.removeEventListener('visibilitychange', interrupt);
            if (reducedMotion.removeEventListener) reducedMotion.removeEventListener('change', interrupt);
        }
    }

    function updateBadges(count) {
        var amount = Math.max(0, Number(count) || 0);
        var label = amount > 99 ? '۹۹+' : persianNumber(amount);
        document.querySelectorAll('.site-header .nav-cart-link').forEach(function (link) {
            link.setAttribute('aria-label', 'سبد خرید' + (amount ? '، ' + persianNumber(amount) + ' کالا' : ''));
            var badge = link.querySelector('.nav-count-badge');
            if (!amount) {
                if (badge) badge.remove();
                return;
            }
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nav-count-badge';
                link.appendChild(badge);
            }
            badge.textContent = label;
            badge.classList.remove('gca-badge-pop');
            void badge.offsetWidth;
            badge.classList.add('gca-badge-pop');
        });
        document.querySelectorAll('.mobile-menu-commerce__card--cart').forEach(function (link) {
            link.setAttribute('aria-label', 'سبد خرید' + (amount ? '، ' + persianNumber(amount) + ' کالا' : ''));
            var small = link.querySelector('.mobile-menu-commerce__card-copy small');
            if (small) small.textContent = amount ? persianNumber(amount) + ' کالا در سبد' : 'مشاهده‌ی سبد خرید';
            var badge = link.querySelector('.mobile-menu-commerce__count');
            if (!amount) {
                if (badge) badge.remove();
                return;
            }
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'mobile-menu-commerce__count';
                badge.setAttribute('aria-hidden', 'true');
                link.appendChild(badge);
            }
            badge.textContent = label;
        });
    }

    async function submitToWoo(data) {
        var result;
        try {
            var response = await window.fetch(config.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            });
            result = await response.json();
        } catch (error) {
            throw new Error('نتیجه افزودن محصول مشخص نشد. سبد خرید را بررسی کنید.');
        }
        if (!result || !result.success) {
            var message = result && result.data && result.data.message;
            throw new Error(message || 'افزودن محصول انجام نشد. دوباره تلاش کنید.');
        }
        return result.data || {};
    }

    product.addEventListener('submit', async function (event) {
        var form = event.target;
        if (!form.matches('form.cart')) return;
        // Grouped products have multiple quantity fields and keep Woo's native form flow.
        if (form.matches('.grouped_form') || form.querySelector('[name^="quantity["]')) return;
        if (inFlight) {
            event.preventDefault();
            return;
        }
        var button = event.submitter || form.querySelector('.single_add_to_cart_button');
        if (!button || !button.matches('.single_add_to_cart_button') || button.disabled || button.classList.contains('disabled')) return;
        if (form.matches('.variations_form') && Number(form.querySelector('[name="variation_id"]')?.value || 0) <= 0) return;
        if (!form.checkValidity()) return;
        var data;
        try { data = new FormData(form, button); }
        catch (error) { data = new FormData(form); }
        var productId = data.get('add-to-cart') || button.value || form.querySelector('[name="add-to-cart"]')?.value;
        if (!productId || !/^\d+$/.test(String(productId))) return;
        event.preventDefault();
        data.delete('add-to-cart');
        data.set('genosys_product_id', String(productId));
        data.set('security', config.nonce);
        var submittedVariation = String(data.get('variation_id') || '');
        inFlight = true;
        var originalMarkup = button.innerHTML;
        var originalBusy = form.getAttribute('aria-busy');
        var originalAriaDisabled = button.getAttribute('aria-disabled');
        var pendingText = 'در حال افزودن...';
        // WooCommerce owns the native disabled state as the variation changes.
        button.setAttribute('aria-disabled', 'true');
        button.textContent = pendingText;
        form.setAttribute('aria-busy', 'true');

        try {
            var result = null;
            var message = 'به سبد خرید اضافه شد';
            try {
                result = await submitToWoo(data);
            } catch (error) {
                message = error.message || 'نتیجه افزودن محصول مشخص نشد. سبد خرید را بررسی کنید.';
            }
            var previousFeedback = form.parentElement.querySelector('.gca-inline-feedback');
            if (previousFeedback) previousFeedback.remove();
            if (result) {
                // A third-party event handler cannot turn a confirmed addition into a failure.
                try {
                    // Passing no button avoids Woo's extra "view cart" link beside the form.
                    if (window.jQuery) window.jQuery(document.body).trigger('added_to_cart', [result.fragments || {}, result.cart_hash || '', false]);
                } catch (error) {}
                try { updateBadges(result.cart_count); } catch (error) {}
            }
            var overlay = null;
            try {
                overlay = makeOverlay();
                var currentVariation = String(form.querySelector('[name="variation_id"]')?.value || '');
                // A changed selection must not animate the new image for the previous item.
                if (result && submittedVariation === currentVariation) await showBasket(overlay);
                showToast(overlay, message, result && result.cart_url);
            } catch (error) {
                if (overlay) overlay.remove();
                if (lastOverlay === overlay) lastOverlay = null;
                showInlineFeedback(form, message);
            }
        } finally {
            if (originalBusy === null) form.removeAttribute('aria-busy');
            else form.setAttribute('aria-busy', originalBusy);
            if (originalAriaDisabled === null) button.removeAttribute('aria-disabled');
            else button.setAttribute('aria-disabled', originalAriaDisabled);
            if (button.textContent === pendingText) button.innerHTML = originalMarkup;
            inFlight = false;
        }
    }, true);
}());
