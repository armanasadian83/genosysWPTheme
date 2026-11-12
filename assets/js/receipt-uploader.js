(function () {
	'use strict';

	var receiptFile = null;
	var uploader = null;
	var validate = null;
	var maxBytes = 5 * 1024 * 1024;
	var allowedTypes = {
		jpg: 'image/jpeg',
		jpeg: 'image/jpeg',
		png: 'image/png',
		webp: 'image/webp',
		pdf: 'application/pdf'
	};

	// Keep the chosen File available for integrations; restored receipts are already saved.
	window.getGenosysReceipt = function () {
		return receiptFile;
	};
	window.validateGenosysReceipt = function () {
		return validate ? validate() : false;
	};

	function init() {
		uploader = document.querySelector('[data-gco-receipt-upload]');
		if (!uploader) return;

		var box = uploader.querySelector('[data-gco-receipt-box]');
		var input = uploader.querySelector('[data-gco-receipt-input]');
		var idle = uploader.querySelector('[data-gco-receipt-idle]');
		var busy = uploader.querySelector('[data-gco-receipt-busy]');
		var result = uploader.querySelector('[data-gco-receipt-result]');
		var thumb = uploader.querySelector('[data-gco-receipt-thumb]');
		var fileName = uploader.querySelector('[data-gco-receipt-name]');
		var meta = uploader.querySelector('[data-gco-receipt-meta]');
		var change = uploader.querySelector('[data-gco-receipt-change]');
		var remove = uploader.querySelector('[data-gco-receipt-remove]');
		var error = uploader.querySelector('[data-gco-receipt-error]');
		var status = uploader.querySelector('[data-gco-receipt-status]');
		var envelope = uploader.querySelector('[data-gco-envelope]');
		var paper = uploader.querySelector('[data-gco-envelope-paper]');
		var paperPreview = uploader.querySelector('[data-gco-envelope-preview]');
		var flap = uploader.querySelector('[data-gco-envelope-flap]');
		var seal = uploader.querySelector('[data-gco-envelope-seal]');
		var ripple = uploader.querySelector('[data-gco-envelope-ripple]');
		if (!box || !input || !idle || !busy || !result || !thumb || !error) return;

		var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
		var animations = new Set();
		var waits = new Set();
		var locked = false;
		var dragDepth = 0;
		var errorTimer = null;
		var selectedPreview = null;
		var savedReceipt = null;
		var sequenceInterrupted = false;
		var skipEnvelope = false;
		var busyMessage = document.createElement('p');
		busyMessage.className = 'gco-receipt-idle__title';
		busyMessage.textContent = 'در حال ذخیره فیش واریزی…';
		busyMessage.hidden = true;
		busy.appendChild(busyMessage);
		var config = null;
		var configNode = uploader.querySelector('[data-gco-receipt-config]');
		try {
			config = configNode ? JSON.parse(configNode.textContent) : null;
		} catch (ignored) {
			config = null;
		}

		function receiptError(message) {
			var failure = new Error('receipt_request_failed');
			failure.receiptMessage = message;
			return failure;
		}

		function protectedUrl(value) {
			if (typeof value !== 'string' || !value) return null;
			try {
				var url = new URL(value, window.location.href);
				if (url.origin !== window.location.origin || !/^https?:$/.test(url.protocol)) return null;
				return url.href;
			} catch (ignored) {
				return null;
			}
		}

		function normalizeReceipt(receipt) {
			if (!receipt || !receipt.id || typeof receipt.name !== 'string' ||
				typeof receipt.mime !== 'string' || !Number.isFinite(Number(receipt.size))) return null;
			return {
				id: String(receipt.id),
				name: receipt.name,
				type: receipt.mime.toLowerCase(),
				size: Math.max(0, Number(receipt.size)),
				previewUrl: protectedUrl(receipt.previewUrl),
				reviewStatus: ['pending', 'approved', 'rejected'].indexOf(receipt.reviewStatus) !== -1 ? receipt.reviewStatus : 'pending'
			};
		}

		function canUpload() {
			return !!config && config.canUpload !== false && (!savedReceipt || savedReceipt.reviewStatus === 'rejected');
		}

		function canRemove() {
			return canUpload() && config.canRemove !== false && !!savedReceipt;
		}

		function syncControls() {
			input.disabled = locked || !canUpload();
			if (change) { change.disabled = locked || !canUpload(); change.hidden = !canUpload(); }
			if (remove) { remove.disabled = locked || !canRemove(); remove.hidden = !canRemove(); }
		}

		async function request(action, file, expectedReceipt) {
			var ajaxUrl = config && protectedUrl(config.ajaxUrl);
			if (!ajaxUrl || !config.orderId || !config.orderKey || !config.nonce) {
				throw receiptError('بارگذاری فیش در دسترس نیست. لطفاً صفحه را دوباره بارگذاری کنید.');
			}
			var body = new FormData();
			body.append('action', action);
			body.append('order_id', String(config.orderId));
			body.append('order_key', String(config.orderKey));
			body.append('nonce', String(config.nonce));
			body.append('receipt_id', expectedReceipt ? expectedReceipt.id : '');
			if (file) body.append('receipt', file, file.name);
			var controller = typeof AbortController === 'function' ? new AbortController() : null;
			var timer = controller ? window.setTimeout(function () { controller.abort(); }, 30000) : null;
			try {
				var response = await window.fetch(ajaxUrl, {
					method: 'POST', body: body, credentials: 'same-origin', cache: 'no-store',
					signal: controller ? controller.signal : undefined
				});
				var payload;
				try {
					payload = await response.json();
				} catch (ignored) {
					throw receiptError('ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.');
				}
				if (!response.ok || !payload || payload.success !== true) {
					throw receiptError(payload && payload.data && typeof payload.data.message === 'string' ?
						payload.data.message : 'ذخیره فیش انجام نشد. لطفاً دوباره تلاش کنید.');
				}
				return payload.data || {};
			} catch (failure) {
				if (failure.receiptMessage) throw failure;
				throw receiptError(failure.name === 'AbortError' ?
					'زمان بارگذاری به پایان رسید. اتصال اینترنت را بررسی کنید و دوباره تلاش کنید.' :
					'ارتباط با فروشگاه برقرار نشد. اتصال اینترنت را بررسی کنید و دوباره تلاش کنید.');
			} finally {
				if (timer) window.clearTimeout(timer);
			}
		}

		function savedPreview(receipt) {
			return receipt.type === 'application/pdf' ? { type: 'pdf', dataUrl: null } :
				{ type: 'image', dataUrl: receipt.previewUrl };
		}

		function syncBusyPresentation() {
			var useMessage = box.dataset.state === 'busy' && skipEnvelope;
			if (envelope) envelope.hidden = useMessage;
			busyMessage.hidden = !useMessage;
		}

		function setState(state) {
			box.dataset.state = state;
			if (state === 'busy') skipEnvelope = motion.matches || !envelope || typeof envelope.animate !== 'function';
			syncBusyPresentation();
			box.setAttribute('aria-busy', locked || state === 'busy' ? 'true' : 'false');
			idle.hidden = state !== 'idle';
			busy.hidden = state !== 'busy';
			result.hidden = state !== 'done';
			idle.setAttribute('aria-hidden', state === 'idle' ? 'false' : 'true');
			busy.setAttribute('aria-hidden', state === 'busy' ? 'false' : 'true');
			result.setAttribute('aria-hidden', state === 'done' ? 'false' : 'true');
			if (state === 'idle' && canUpload()) {
				box.setAttribute('role', 'button');
				box.setAttribute('tabindex', '0');
			} else {
				// The result has its own controls; avoid a button containing buttons.
				box.removeAttribute('role');
				box.removeAttribute('tabindex');
			}
			syncControls();
			if (status) {
				status.textContent = state === 'busy' ? 'در حال ذخیره فیش واریزی…' :
					(state === 'done' ? 'فیش واریزی ذخیره شد.' : '');
			}
		}

		function clearError() {
			error.textContent = '';
			error.hidden = true;
			box.classList.remove('is-error', 'is-shaking');
			box.removeAttribute('aria-invalid');
			if (errorTimer) window.clearTimeout(errorTimer);
			errorTimer = null;
		}

		function showError(message, shake) {
			error.textContent = message;
			error.hidden = false;
			box.classList.add('is-error');
			box.setAttribute('aria-invalid', 'true');
			if (shake && !motion.matches && !locked) {
				box.classList.remove('is-shaking');
				// Restart the brief shake even if two invalid files are picked in succession.
				void box.offsetWidth;
				box.classList.add('is-shaking');
				if (errorTimer) window.clearTimeout(errorTimer);
				errorTimer = window.setTimeout(function () {
					box.classList.remove('is-shaking');
					errorTimer = null;
				}, 460);
			}
		}

		function resetDrag() {
			dragDepth = 0;
			box.classList.remove('is-dragover');
		}

		function cancelAnimations() {
			animations.forEach(function (animation) { animation.cancel(); });
			animations.clear();
			waits.forEach(function (finish) { finish(); });
			waits.clear();
		}

		async function animate(element, frames, options) {
			if (!element || motion.matches || typeof element.animate !== 'function') return;
			var animation = element.animate(frames, Object.assign({ fill: 'forwards' }, options));
			animations.add(animation);
			try {
				await animation.finished;
			} catch (ignored) {
				// A live reduced-motion preference change cancels movement safely.
			}
		}

		function pause(milliseconds) {
			if (motion.matches) return Promise.resolve();
			return new Promise(function (resolve) {
				var timer;
				function finish() {
					window.clearTimeout(timer);
					waits.delete(finish);
					resolve();
				}
				waits.add(finish);
				timer = window.setTimeout(finish, milliseconds);
			});
		}

		function receiptIcon() {
			var ns = 'http://www.w3.org/2000/svg';
			var svg = document.createElementNS(ns, 'svg');
			svg.setAttribute('viewBox', '0 0 48 48');
			svg.setAttribute('class', 'gco-receipt-fallback');
			svg.setAttribute('aria-hidden', 'true');
			svg.setAttribute('fill', 'none');
			svg.setAttribute('stroke', 'currentColor');
			svg.setAttribute('stroke-width', '2.5');
			svg.setAttribute('stroke-linecap', 'round');
			svg.setAttribute('stroke-linejoin', 'round');
			var path = document.createElementNS(ns, 'path');
			path.setAttribute('d', 'M13 5h16l7 7v31l-4-3-4 3-4-3-4 3-4-3-4 3V5Zm16 0v9h7M18 21h12M18 28h12M18 35h7');
			svg.appendChild(path);
			return svg;
		}

		function renderPreview(holder, preview) {
			if (!holder) return;
			holder.replaceChildren();
			if (preview && preview.type === 'pdf') {
				var pdf = document.createElement('span');
				pdf.className = 'gco-receipt-pdf';
				pdf.textContent = 'PDF';
				pdf.setAttribute('aria-hidden', 'true');
				holder.appendChild(pdf);
			} else if (preview && preview.dataUrl) {
				var image = document.createElement('img');
				image.className = 'gco-receipt-preview-image';
				image.alt = 'پیش‌نمایش فیش واریزی';
				image.addEventListener('error', function () {
					if (image.parentNode === holder) holder.replaceChildren(receiptIcon());
				}, { once: true });
				image.src = preview.dataUrl;
				holder.appendChild(image);
			} else {
				holder.appendChild(receiptIcon());
			}
		}

		function readPreview(file) {
			if (file.type.toLowerCase() === 'application/pdf') {
				return Promise.resolve({ type: 'pdf', dataUrl: null });
			}
			return new Promise(function (resolve, reject) {
				var reader = new FileReader();
				reader.onerror = function () { reject(new Error('receipt_read_failed')); };
				reader.onabort = function () { reject(new Error('receipt_read_aborted')); };
				reader.onload = function () {
					var dataUrl = typeof reader.result === 'string' ? reader.result : '';
					if (!dataUrl) {
						reject(new Error('receipt_read_failed'));
						return;
					}
					var image = new Image();
					image.onload = function () { resolve({ type: 'image', dataUrl: dataUrl }); };
					image.onerror = function () { resolve({ type: 'image', dataUrl: null }); };
					image.src = dataUrl;
				};
				reader.readAsDataURL(file);
			});
		}

		function formatSize(size) {
			var value = size < 1024 * 1024 ? Math.max(1, Math.round(size / 1024)) :
				Math.round(size / (1024 * 1024) * 10) / 10;
			return value.toLocaleString('fa-IR') + (size < 1024 * 1024 ? ' کیلوبایت' : ' مگابایت');
		}

		function updateResult(file, preview) {
			// Never interpret user-controlled file names as markup.
			if (fileName) fileName.textContent = file.name;
			if (meta) meta.textContent = formatSize(file.size) + ' · ' +
				(file.type.toLowerCase() === 'application/pdf' ? 'PDF' : 'تصویر');
			renderPreview(thumb, preview);
			renderPreview(paperPreview, preview);
		}

		async function envelopeSequence(upload) {
			if (motion.matches || sequenceInterrupted || skipEnvelope) return;
			await animate(envelope, [
				{ opacity: 0, transform: 'translateY(40px) scale(.7)' },
				{ opacity: 1, transform: 'translateY(0) scale(1)' }
			], { duration: 380, easing: 'cubic-bezier(.2,.8,.2,1.32)' });
			if (sequenceInterrupted) return;
			await animate(paper, [
				{ transform: 'translateY(-230px) rotate(-9deg)', offset: 0 },
				{ transform: 'translateY(90px) rotate(-2deg)', offset: .76 },
				{ transform: 'translateY(245px) rotate(0deg)', offset: 1 }
			], { duration: 720, easing: 'cubic-bezier(.4,0,.8,.45)' });
			if (sequenceInterrupted) return;
			await animate(envelope, [
				{ transform: 'translateY(0) scale(1)' },
				{ transform: 'translateY(7px) scale(1.02,.96)', offset: .5 },
				{ transform: 'translateY(0) scale(1)' }
			], { duration: 200, easing: 'ease-in-out' });
			if (sequenceInterrupted) return;
			await animate(flap, [
				{ transform: 'scaleY(-1)' }, { transform: 'scaleY(1)' }
			], { duration: 400, easing: 'cubic-bezier(.3,.05,.2,1)' });
			if (sequenceInterrupted) return;
			await animate(envelope, [
				{ transform: 'translateY(0) scale(1)' },
				{ transform: 'translateY(-16px) scale(1)', offset: .45 },
				{ transform: 'translateY(0) scale(1)' }
			], { duration: 300, easing: 'ease-in-out' });
			if (sequenceInterrupted) return;
			await Promise.all([
				animate(seal, [
					{ opacity: 0, transform: 'scale(0)' },
					{ opacity: 1, transform: 'scale(1.25)', offset: .6 },
					{ opacity: 1, transform: 'scale(1)' }
				], { duration: 450, easing: 'cubic-bezier(.2,.8,.2,1)' }),
				animate(ripple, [
					{ opacity: .65, transform: 'scale(.5)' },
					{ opacity: 0, transform: 'scale(1.9)' }
				], { duration: 450, easing: 'ease-out' })
			]);
			if (sequenceInterrupted) return;
			await pause(450);
			// Keep the sealed envelope visible during a slower transfer, preserving
			// every animation phase and its duration before the final fade.
			if (upload) await upload;
			if (sequenceInterrupted) return;
			await animate(envelope, [
				{ opacity: 1, transform: 'translateY(0) scale(1)' },
				{ opacity: 0, transform: 'translateY(0) scale(.9)' }
			], { duration: 260, easing: 'ease-in' });
		}

		async function revealResult() {
			await Promise.all([
				animate(result, [
					{ opacity: 0, transform: 'translateY(20px)' },
					{ opacity: 1, transform: 'translateY(0)' }
				], { duration: 360, easing: 'cubic-bezier(.2,.8,.2,1)' }),
				animate(thumb, [
					{ opacity: 0, transform: 'scale(.6) rotate(-7deg)' },
					{ opacity: 1, transform: 'scale(1.06) rotate(2deg)', offset: .65 },
					{ opacity: 1, transform: 'scale(1) rotate(0)' }
				], { duration: 500, easing: 'cubic-bezier(.2,.8,.2,1)' })
			]);
		}

		async function acceptFiles(files) {
			if (locked || !canUpload()) return;
			resetDrag();
			input.value = '';
			if (!files || !files.length) return;
			if (files.length !== 1) {
				showError('فقط یک فایل فیش واریزی انتخاب کنید.', true);
				return;
			}
			var file = files[0];
			var extension = String(file.name || '').split('.').pop().toLowerCase();
			var mime = String(file.type || '').toLowerCase();
			if (!allowedTypes[extension] || allowedTypes[extension] !== mime) {
				showError('فقط فایل‌های JPG، PNG، WebP و PDF مجاز هستند.', true);
				return;
			}
			if (file.size > maxBytes) {
				showError('حجم فایل نباید بیشتر از ۵ مگابایت باشد.', true);
				return;
			}
			if (!file.size) {
				showError('فایل انتخاب‌شده خالی است. یک فایل معتبر انتخاب کنید.', true);
				return;
			}

			var previousFile = receiptFile;
			var previousPreview = selectedPreview;
			var previousReceipt = savedReceipt;
			locked = true;
			sequenceInterrupted = false;
			clearError();
			cancelAnimations();
			setState('busy');
			try {
				var preview = await readPreview(file);
				updateResult(file, preview);
				// Upload during the existing envelope sequence. Handle rejection immediately,
				// then inspect it after the sequence, so no success state can precede saving.
				var upload = request('genosys_receipt_upload', file, previousReceipt).then(function (data) {
					return { receipt: normalizeReceipt(data.receipt) };
				}, function (failure) {
					sequenceInterrupted = true;
					cancelAnimations();
					return { error: failure };
				});
				// A browser animation failure must not undo a receipt already saved by WordPress.
				try {
					await envelopeSequence(upload);
				} catch (ignored) {
					skipEnvelope = true;
					syncBusyPresentation();
					cancelAnimations();
				}
				var uploaded = await upload;
				if (uploaded.error) throw uploaded.error;
				if (!uploaded.receipt) throw receiptError('ذخیره فیش تأیید نشد. لطفاً صفحه را دوباره بارگذاری کنید.');
				savedReceipt = uploaded.receipt;
				receiptFile = file;
				selectedPreview = preview;
				updateResult(savedReceipt, preview);
				clearError();
				setState('done');
				try { await revealResult(); } catch (ignored) { cancelAnimations(); }
			} catch (failure) {
				receiptFile = previousFile;
				selectedPreview = previousPreview;
				savedReceipt = previousReceipt;
				cancelAnimations();
				if (previousReceipt) updateResult(previousReceipt, previousPreview);
				setState(previousReceipt ? 'done' : 'idle');
				showError(failure.receiptMessage || 'خواندن فایل انجام نشد. دوباره فایل را انتخاب کنید.', false);
			} finally {
				locked = false;
				box.setAttribute('aria-busy', 'false');
				syncControls();
				input.value = '';
			}
		}

		function pickFile() {
			if (locked || !canUpload()) return;
			input.value = '';
			input.click();
		}

		box.addEventListener('click', function (event) {
			if (event.target === input || event.target.closest('button, a')) return;
			if (box.dataset.state === 'idle') pickFile();
		});
		box.addEventListener('keydown', function (event) {
			if (event.target !== box || box.dataset.state !== 'idle') return;
			if (event.key === 'Enter' || event.key === ' ' || event.key === 'Spacebar') {
				event.preventDefault();
				pickFile();
			}
		});
		input.addEventListener('change', function () {
			// Copy before clearing the input: FileList is tied to its value.
			var files = Array.from(input.files || []);
			input.value = '';
			acceptFiles(files);
		});
		box.addEventListener('dragenter', function (event) {
			event.preventDefault();
			if (locked || !canUpload()) return;
			dragDepth += 1;
			box.classList.add('is-dragover');
		});
		box.addEventListener('dragover', function (event) {
			event.preventDefault();
			if (event.dataTransfer) event.dataTransfer.dropEffect = locked || !canUpload() ? 'none' : 'copy';
		});
		box.addEventListener('dragleave', function (event) {
			event.preventDefault();
			if (locked) return;
			dragDepth = Math.max(0, dragDepth - 1);
			if (!dragDepth) box.classList.remove('is-dragover');
		});
		box.addEventListener('drop', function (event) {
			event.preventDefault();
			var files = event.dataTransfer ? Array.from(event.dataTransfer.files || []) : [];
			resetDrag();
			acceptFiles(files);
		});
		if (change) change.addEventListener('click', function (event) {
			event.stopPropagation();
			pickFile();
		});
		if (remove) remove.addEventListener('click', async function (event) {
			event.stopPropagation();
			if (locked || !canRemove()) return;
			locked = true;
			box.setAttribute('aria-busy', 'true');
			if (change) change.disabled = true;
			remove.disabled = true;
			cancelAnimations();
			clearError();
			resetDrag();
			if (status) status.textContent = 'در حال حذف فیش واریزی…';
			try {
				await request('genosys_receipt_remove', null, savedReceipt);
				receiptFile = null;
				savedReceipt = null;
				selectedPreview = null;
				input.value = '';
				if (fileName) fileName.textContent = '';
				if (meta) meta.textContent = '';
				renderPreview(thumb, null);
				renderPreview(paperPreview, null);
				setState('idle');
				box.focus({ preventScroll: true });
			} catch (failure) {
				showError(failure.receiptMessage || 'حذف فیش انجام نشد. لطفاً دوباره تلاش کنید.', false);
				if (status) status.textContent = 'فیش واریزی ذخیره‌شده حفظ شد.';
			} finally {
				locked = false;
				box.setAttribute('aria-busy', 'false');
				syncControls();
			}
		});

		validate = function () {
			if (!locked && savedReceipt) return true;
			showError(locked ? 'لطفاً منتظر ذخیره فایل بمانید.' :
				'لطفاً فیش واریزی را بارگذاری کنید.', !locked);
			box.scrollIntoView({ behavior: motion.matches ? 'auto' : 'smooth', block: 'center' });
			if (box.dataset.state === 'idle') box.focus({ preventScroll: true });
			return false;
		};
		var form = uploader.closest('form');
		if (form) form.addEventListener('submit', function (event) {
			if (!validate()) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		}, true);

		function motionChange() {
			if (motion.matches) {
				if (box.dataset.state === 'busy') skipEnvelope = true;
				cancelAnimations();
				syncBusyPresentation();
			}
		}
		if (typeof motion.addEventListener === 'function') motion.addEventListener('change', motionChange);
		else if (typeof motion.addListener === 'function') motion.addListener(motionChange);

		savedReceipt = normalizeReceipt(config && config.receipt);
		if (savedReceipt) {
			selectedPreview = savedPreview(savedReceipt);
			updateResult(savedReceipt, selectedPreview);
		}
		setState(savedReceipt ? 'done' : 'idle');
		clearError();
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
	else init();
}());
