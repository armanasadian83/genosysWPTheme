(function () {
	'use strict';

	var checkout = document.querySelector('.genosys-checkout');
	if (!checkout) return;

	var copyTimers = new WeakMap();

	function couponNotice(wrapper, message, isHtml) {
		var notice = wrapper.nextElementSibling;
		if (!notice || !notice.classList.contains('gco-coupon-notices')) {
			notice = document.createElement('div');
			notice.className = 'gco-coupon-notices';
			notice.setAttribute('aria-live', 'polite');
			wrapper.insertAdjacentElement('afterend', notice);
		}
		notice.classList.toggle('woocommerce-error', !isHtml);
		notice.setAttribute('role', isHtml ? 'status' : 'alert');
		if (isHtml) {
			notice.innerHTML = message;
		} else {
			notice.textContent = message;
		}
	}

	function applyCoupon(button) {
		var wrapper = button.closest('.gco-summary__coupon');
		var input = wrapper && wrapper.querySelector('input[name="gco_coupon_code"]');
		if (!wrapper || !input || button.disabled) return;

		var code = input.value.trim();
		if (!code) {
			couponNotice(wrapper, 'کد تخفیف را وارد کنید.', false);
			input.focus();
			return;
		}

		var params = window.wc_checkout_params;
		if (!params || !params.wc_ajax_url || !params.apply_coupon_nonce) {
			couponNotice(wrapper, 'امکان اعمال کد تخفیف در حال حاضر وجود ندارد.', false);
			return;
		}

		var form = button.closest('form.checkout');
		var email = form && form.querySelector('input[name="billing_email"]');
		var request = new URLSearchParams();
		request.set('security', params.apply_coupon_nonce);
		request.set('coupon_code', code);
		request.set('billing_email', email ? email.value : '');
		button.disabled = true;
		button.setAttribute('aria-busy', 'true');

		fetch(String(params.wc_ajax_url).replace('%%endpoint%%', 'apply_coupon'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: request.toString()
		}).then(function (response) {
			if (!response.ok) throw new Error('coupon_request_failed');
			return response.text();
		}).then(function (html) {
			couponNotice(wrapper, html || 'پاسخی از فروشگاه دریافت نشد.', Boolean(html));
			if (!html) return;
			var failed = /woocommerce-error|is-error/i.test(html);
			if (!failed) input.value = '';
			if (window.jQuery) {
				var $body = window.jQuery(document.body);
				$body.one('updated_checkout', function () {
					var refreshed = checkout.querySelector('.gco-summary__coupon');
					if (refreshed) couponNotice(refreshed, html, true);
				});
				$body.trigger('applied_coupon_in_checkout', [code]);
				$body.trigger('update_checkout', { update_shipping_method: false });
			}
		}).catch(function () {
			couponNotice(wrapper, 'اعمال کد تخفیف انجام نشد. دوباره تلاش کنید.', false);
		}).finally(function () {
			button.disabled = false;
			button.removeAttribute('aria-busy');
		});
	}

	function normalizeCardNumber(value) {
		return String(value || '').replace(/[۰-۹]/g, function (digit) {
			return String(digit.charCodeAt(0) - 1776);
		}).replace(/[٠-٩]/g, function (digit) {
			return String(digit.charCodeAt(0) - 1632);
		}).replace(/\D/g, '');
	}

	function reportCopy(button, message) {
		var label = button.querySelector('span');
		if (label && !button.hasAttribute('data-gco-original-text')) {
			button.setAttribute('data-gco-original-text', label.textContent);
		}
		if (label) label.textContent = message;
		var live = button.querySelector('[data-gco-copy-feedback]');
		if (!live) {
			live = button.parentElement.querySelector('.gco-copy-live');
			if (!live) {
				live = document.createElement('span');
				live.className = 'gco-copy-live screen-reader-text';
				live.setAttribute('aria-live', 'polite');
				button.insertAdjacentElement('afterend', live);
			}
		}
		live.textContent = message;
		var originalLabel = button.getAttribute('data-gco-original-label');
		if (originalLabel === null) {
			button.setAttribute('data-gco-original-label', button.getAttribute('aria-label') || 'کپی شماره کارت');
		}
		button.setAttribute('aria-label', message);
		button.classList.toggle('is-copied', message === 'شماره کارت کپی شد');
		if (copyTimers.has(button)) window.clearTimeout(copyTimers.get(button));
		copyTimers.set(button, window.setTimeout(function () {
			button.setAttribute('aria-label', button.getAttribute('data-gco-original-label'));
			button.classList.remove('is-copied');
			if (label) label.textContent = button.getAttribute('data-gco-original-text') || 'کپی شماره کارت';
			live.textContent = '';
		}, 2500));
	}

	function fallbackCopy(value) {
		var field = document.createElement('textarea');
		field.value = value;
		field.setAttribute('readonly', '');
		field.style.position = 'fixed';
		field.style.opacity = '0';
		document.body.appendChild(field);
		field.select();
		var copied = document.execCommand('copy');
		field.remove();
		if (!copied) throw new Error('copy_failed');
	}

	function copyCard(button) {
		var card = button.closest('[data-card-number]');
		var number = normalizeCardNumber(button.getAttribute('data-card-number') || (card && card.getAttribute('data-card-number')));
		if (!number) {
			reportCopy(button, 'شماره کارت تنظیم نشده است');
			return;
		}
		var copy = navigator.clipboard && navigator.clipboard.writeText
			? navigator.clipboard.writeText(number)
			: Promise.resolve().then(function () { fallbackCopy(number); });
		copy.then(function () {
			reportCopy(button, 'شماره کارت کپی شد');
		}).catch(function () {
			try {
				fallbackCopy(number);
				reportCopy(button, 'شماره کارت کپی شد');
			} catch (error) {
				reportCopy(button, 'کپی شماره کارت انجام نشد');
			}
		});
	}

	// WooCommerce remains responsible for accepting or rejecting the order. These
	// messages only place its required-field feedback beside the visible fields.
	var requiredFields = {
		billing_first_name: 'نام را وارد کنید.',
		billing_last_name: 'نام خانوادگی را وارد کنید.',
		billing_phone: 'شماره موبایل معتبر نیست.',
		billing_state: 'استان را انتخاب کنید.',
		billing_city: 'شهر را وارد کنید.',
		billing_address_1: 'آدرس کامل را وارد کنید.',
		billing_postcode: 'کد پستی را وارد کنید.'
	};
	var checkoutForm = checkout.querySelector('form.checkout.gco-layout');
	var validationAttempted = false;
	var touchedFields = new Set();

	function latinDigits(value) {
		return String(value || '').replace(/[۰-۹]/g, function (digit) {
			return String(digit.charCodeAt(0) - 1776);
		}).replace(/[٠-٩]/g, function (digit) {
			return String(digit.charCodeAt(0) - 1632);
		});
	}

	function fieldMessage(field) {
		var value = String(field.value || '').trim();
		if (!value) return requiredFields[field.name];
		if (field.name === 'billing_phone') {
			var phone = latinDigits(value).replace(/[\s()\-]/g, '');
			if (!/^(?:09\d{9}|(?:\+98|98|0098)9\d{9}|9\d{9})$/.test(phone)) {
				return 'شماره موبایل معتبر نیست.';
			}
		}
		return '';
	}

	function renderFieldMessage(field, preserveServerMessage) {
		var row = field.closest('.form-row');
		if (!row) return false;
		var message = fieldMessage(field);
		var existing = row.querySelector('.checkout-inline-error-message');
		if (!message) {
			if (preserveServerMessage && existing && existing.textContent.trim()) {
				row.classList.add('woocommerce-invalid');
				field.setAttribute('aria-invalid', 'true');
				return true;
			}
			if (existing) existing.remove();
			row.classList.remove('woocommerce-invalid', 'woocommerce-invalid-required-field', 'woocommerce-invalid-phone');
			field.removeAttribute('aria-invalid');
			if (field.getAttribute('aria-describedby') === field.id + '_description') {
				field.removeAttribute('aria-describedby');
			}
			return false;
		}
		if (!existing) {
			existing = document.createElement('p');
			existing.className = 'checkout-inline-error-message';
			row.appendChild(existing);
		}
		existing.id = field.id + '_description';
		existing.textContent = message;
		row.classList.remove('woocommerce-validated');
		row.classList.add('woocommerce-invalid');
		row.classList.toggle('woocommerce-invalid-required-field', !String(field.value || '').trim());
		field.setAttribute('aria-invalid', 'true');
		field.setAttribute('aria-describedby', existing.id);
		return true;
	}

	function renderAllFieldMessages(preserveServerMessages) {
		if (!checkoutForm) return;
		Object.keys(requiredFields).forEach(function (name) {
			var field = checkoutForm.elements.namedItem(name);
			if (field && field.nodeType === 1) renderFieldMessage(field, preserveServerMessages);
		});
	}

	function collapseDuplicatedFieldNotices() {
		if (!checkoutForm) return;
		checkoutForm.querySelectorAll('.woocommerce-NoticeGroup-checkout li[data-id]').forEach(function (item) {
			var name = item.getAttribute('data-id');
			if (!Object.prototype.hasOwnProperty.call(requiredFields, name)) return;
			var field = checkoutForm.elements.namedItem(name);
			if (field && field.nodeType === 1 && (fieldMessage(field) || field.closest('.form-row').querySelector('.checkout-inline-error-message'))) item.remove();
		});
		var group = checkoutForm.querySelector('.woocommerce-NoticeGroup-checkout');
		if (group && !group.textContent.trim()) group.remove();
	}

	if (checkoutForm) {
		checkoutForm.addEventListener('submit', function () {
			validationAttempted = true;
			renderAllFieldMessages();
		}, true);
		checkoutForm.addEventListener('input', function (event) {
			var field = event.target;
			if (field && Object.prototype.hasOwnProperty.call(requiredFields, field.name) && (validationAttempted || touchedFields.has(field.name))) {
				renderFieldMessage(field);
			}
		});
		checkoutForm.addEventListener('change', function (event) {
			var field = event.target;
			if (field && Object.prototype.hasOwnProperty.call(requiredFields, field.name)) {
				touchedFields.add(field.name);
				renderFieldMessage(field);
			}
		});
		checkoutForm.addEventListener('focusout', function (event) {
			var field = event.target;
			if (field && Object.prototype.hasOwnProperty.call(requiredFields, field.name)) {
				touchedFields.add(field.name);
				renderFieldMessage(field);
			}
		});
		if (window.jQuery) {
			window.jQuery(document.body).on('checkout_error', function () {
				validationAttempted = true;
				renderAllFieldMessages(true);
				collapseDuplicatedFieldNotices();
			});
		}
	}

	checkout.addEventListener('click', function (event) {
		var couponButton = event.target.closest('[data-gco-apply-coupon]');
		if (couponButton && checkout.contains(couponButton)) {
			event.preventDefault();
			applyCoupon(couponButton);
			return;
		}
		var copyButton = event.target.closest('[data-gco-copy-card]');
		if (copyButton && checkout.contains(copyButton)) {
			event.preventDefault();
			copyCard(copyButton);
		}
	});

	checkout.addEventListener('keydown', function (event) {
		if (event.key !== 'Enter') return;
		var input = event.target.closest('.gco-summary__coupon input[name="gco_coupon_code"]');
		if (!input || !checkout.contains(input)) return;
		var button = input.closest('.gco-summary__coupon').querySelector('[data-gco-apply-coupon]');
		if (!button) return;
		event.preventDefault();
		applyCoupon(button);
	});
})();
