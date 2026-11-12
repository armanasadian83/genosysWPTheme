(function () {
	'use strict';

	var form = document.querySelector('[data-genosys-cart-form]');
	if (!form) return;

	var updateButton = form.querySelector('[name="update_cart"]');
	if (!updateButton) return;
	var couponInput = document.getElementById('genosys-cart-coupon');
	var couponButton = document.querySelector('[name="apply_coupon"][form="genosys-cart-form"]');

	var timer;
	var quantityPending = false;
	function quantityBounds(input) {
		var step = Number(input.step);
		if (!Number.isFinite(step) || step <= 0) step = 1;
		var min = Number(input.min);
		if (!Number.isFinite(min) || input.min === '') min = 0;
		var max = Number(input.max);
		if (!Number.isFinite(max) || input.max === '' || max < 0) max = Infinity;
		return { step: step, min: min, max: max };
	}
	function updateQuantityButtons(input) {
		var control = input.closest('.genosys-cart-item__quantity');
		if (!control) return;
		var bounds = quantityBounds(input);
		var lowestQuantity = Math.max(bounds.min, bounds.step);
		var value = Number(input.value);
		var unavailable = input.disabled || input.readOnly || !Number.isFinite(value);
		var minus = control.querySelector('[data-cart-quantity-change="-1"]');
		var plus = control.querySelector('[data-cart-quantity-change="1"]');
		if (minus) minus.disabled = unavailable || value <= lowestQuantity;
		if (plus) plus.disabled = unavailable || value >= bounds.max;
	}
	form.querySelectorAll('.genosys-cart-item__quantity input.qty').forEach(updateQuantityButtons);
	form.addEventListener('click', function (event) {
		var button = event.target.closest('[data-cart-quantity-change]');
		if (!button || button.disabled) return;
		var input = button.closest('.genosys-cart-item__quantity').querySelector('input.qty');
		if (!input || input.disabled || input.readOnly) return;
		var bounds = quantityBounds(input);
		var lowestQuantity = Math.max(bounds.min, bounds.step);
		var value = Number(input.value);
		if (!Number.isFinite(value)) value = Math.max(bounds.min, 1);
		var direction = Number(button.getAttribute('data-cart-quantity-change'));
		var next = Math.max(lowestQuantity, Math.min(bounds.max, value + direction * bounds.step));
		if (next === value) return;
		var decimals = String(bounds.step).split('.')[1];
		input.value = String(Number(next.toFixed(decimals ? decimals.length : 0)));
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
	form.addEventListener('input', function (event) {
		if (event.target.matches('input.qty')) updateQuantityButtons(event.target);
	});
	function submitQuantity() {
		if (couponInput && document.activeElement === couponInput) return;
		quantityPending = false;
		if (form.requestSubmit) {
			form.requestSubmit(updateButton);
		} else {
			updateButton.click();
		}
	}
	form.addEventListener('submit', function () {
		window.clearTimeout(timer);
		quantityPending = false;
	});
	form.addEventListener('change', function (event) {
		var input = event.target;
		if (!input.matches('input.qty') || input.value === '' || !input.checkValidity()) return;
		quantityPending = true;
		window.clearTimeout(timer);
		timer = window.setTimeout(submitQuantity, 300);
	});
	if (couponInput && couponButton) {
		couponInput.addEventListener('focus', function () {
			window.clearTimeout(timer);
		});
		couponInput.addEventListener('blur', function () {
			if (quantityPending) timer = window.setTimeout(submitQuantity, 300);
		});
		couponInput.addEventListener('keydown', function (event) {
			if (event.key !== 'Enter') return;
			event.preventDefault();
			if (form.requestSubmit) {
				form.requestSubmit(couponButton);
			} else {
				couponButton.click();
			}
		});
	}
}());
