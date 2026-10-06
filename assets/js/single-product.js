/* Enhance WooCommerce's real quantity input without replacing its cart form. */
(function () {
    'use strict';

    var persianDigits = '۰۱۲۳۴۵۶۷۸۹';
    function persianNumber(value) {
        return String(value).replace(/[0-9]/g, function (digit) { return persianDigits[Number(digit)]; });
    }

    function bounds(input) {
        var step = Number(input.step);
        if (!Number.isFinite(step) || step <= 0) step = 1;
        var min = Number(input.min);
        if (!Number.isFinite(min) || input.min === '') min = 0;
        var max = Number(input.max);
        if (!Number.isFinite(max) || input.max === '') max = Infinity;
        return { step: step, min: min, max: max };
    }

    function adjust(input, direction) {
        if (input.disabled) return;
        var limits = bounds(input);
        var value = Number(input.value);
        if (!Number.isFinite(value)) value = Math.max(limits.min, 1);
        var next = Math.max(limits.min, Math.min(limits.max, value + direction * limits.step));
        if (next === value) return;
        var decimals = String(limits.step).split('.')[1];
        input.value = String(Number(next.toFixed(decimals ? decimals.length : 0)));
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.querySelectorAll('.genosys-product form.cart .quantity').forEach(function (quantity) {
        var input = quantity.querySelector('input.qty');
        if (!input || quantity.querySelector('.genosys-qty-button')) return;
        var plus = document.createElement('button');
        var minus = document.createElement('button');
        plus.type = minus.type = 'button';
        plus.className = minus.className = 'genosys-qty-button';
        plus.textContent = '+';
        minus.textContent = '−';
        plus.setAttribute('aria-label', 'افزایش تعداد');
        minus.setAttribute('aria-label', 'کاهش تعداد');
        if (input.type === 'hidden') {
            var display = document.createElement('span');
            input.value = input.value || input.min || '1';
            display.className = 'genosys-qty-value';
            display.textContent = persianNumber(input.value);
            display.setAttribute('aria-label', 'تعداد: ' + persianNumber(input.value));
            plus.disabled = minus.disabled = true;
            quantity.insertBefore(plus, input);
            quantity.insertBefore(display, input);
            quantity.insertBefore(minus, input);
            return;
        }
        if (input.type !== 'number') return;
        if (input.value === '') input.value = String(Math.max(bounds(input).min, 1));
        input.readOnly = true;
        input.tabIndex = -1;
        input.inputMode = 'none';
        quantity.insertBefore(plus, input);
        input.insertAdjacentElement('afterend', minus);
        function updateButtons() {
            var limits = bounds(input);
            var value = Number(input.value);
            plus.disabled = input.disabled || value >= limits.max;
            minus.disabled = input.disabled || value <= limits.min;
        }
        plus.addEventListener('click', function () { adjust(input, 1); });
        minus.addEventListener('click', function () { adjust(input, -1); });
        input.addEventListener('input', updateButtons);
        input.addEventListener('change', updateButtons);
        updateButtons();
    });

    var product = document.querySelector('.genosys-product');
    var form = product && product.querySelector('form.variations_form');
    var price = product && product.querySelector('.genosys-product__price > .price');
    if (!form || !price || !window.jQuery) return;

    var discount = product.querySelector('.genosys-product__discount');
    var badge = product.querySelector('.genosys-product__gallery-badge');
    var skuBox = product.querySelector('.genosys-product__sku');
    var skuWrapper = skuBox && skuBox.querySelector('.sku_wrapper');
    var original = {
        price: price.innerHTML,
        unavailable: price.classList.contains('genosys-product__price--unavailable'),
        discount: discount ? discount.textContent : '',
        discountHidden: discount ? discount.hidden : true,
        badge: badge ? badge.textContent : '',
        badgeHidden: badge ? badge.hidden : true,
        badgeStock: badge ? badge.classList.contains('genosys-product__gallery-badge--stock') : false,
        skuHidden: skuWrapper ? skuWrapper.hidden : true
    };
    function restore() {
        price.innerHTML = original.price;
        price.classList.toggle('genosys-product__price--unavailable', original.unavailable);
        product.classList.remove('is-variation-synced');
        if (discount) {
            discount.textContent = original.discount;
            discount.hidden = original.discountHidden;
        }
        if (badge) {
            badge.textContent = original.badge;
            badge.hidden = original.badgeHidden;
            badge.classList.toggle('genosys-product__gallery-badge--stock', original.badgeStock);
        }
        if (skuWrapper) skuWrapper.hidden = original.skuHidden;
    }
    window.jQuery(form).on('found_variation', function (event, variation) {
        if (!variation) return;
        var inStock = variation.is_in_stock !== false;
        var regular = Number(variation.display_regular_price);
        var current = Number(variation.display_price);
        var percentage = inStock && regular > 0 && current < regular
            ? Math.round(100 * (regular - current) / regular) : 0;
        if (!inStock) {
            price.textContent = 'تماس بگیرید';
            price.classList.add('genosys-product__price--unavailable');
            product.classList.add('is-variation-synced');
        } else if (variation.price_html) {
            var markup = document.createElement('div');
            markup.innerHTML = variation.price_html;
            var nestedPrice = markup.querySelector('.price');
            var visiblePrice = nestedPrice || markup;
            var walker = document.createTreeWalker(visiblePrice, NodeFilter.SHOW_TEXT);
            while (walker.nextNode()) walker.currentNode.textContent = persianNumber(walker.currentNode.textContent);
            price.innerHTML = visiblePrice.innerHTML;
            price.classList.remove('genosys-product__price--unavailable');
            product.classList.add('is-variation-synced');
        } else {
            price.innerHTML = original.price;
            price.classList.toggle('genosys-product__price--unavailable', original.unavailable);
            product.classList.remove('is-variation-synced');
        }
        if (discount) {
            discount.textContent = percentage ? persianNumber(percentage) + '٪ تخفیف' : '';
            discount.hidden = !percentage;
        }
        if (badge) {
            badge.textContent = !inStock ? 'ناموجود' : (percentage ? persianNumber(percentage) + '٪ تخفیف' : '');
            badge.hidden = inStock && !percentage;
            badge.classList.toggle('genosys-product__gallery-badge--stock', !inStock);
        }
        if (skuWrapper) skuWrapper.hidden = !(variation.sku || skuBox.dataset.baseSku);
    }).on('hide_variation reset_data', restore);
}());
