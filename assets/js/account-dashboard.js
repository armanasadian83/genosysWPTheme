(function () {
    'use strict';

    function init() {
        var root = document.querySelector('[data-account-dashboard]');
        if (!root) return;
        var config = window.genosysAccountDashboard || {};
        var form = root.querySelector('[data-profile-form]');
        var notice = root.querySelector('[data-account-notice]');
        var edit = root.querySelector('[data-profile-edit]');
        var cancel = root.querySelector('[data-profile-cancel]');
        var actions = root.querySelector('[data-profile-actions]');
        var formError = root.querySelector('[data-profile-error]');
        var avatarFile = root.querySelector('[data-avatar-file]');
        var avatarPick = root.querySelector('[data-avatar-pick]');
        var avatarRemove = root.querySelector('[data-avatar-remove]');
        var avatarPresent = !!root.querySelector('[data-account-avatar] img');
        var pending = false;
        var toastTimeout = null;
        var profileFields = ['first_name', 'last_name', 'email', 'birth_day', 'birth_month', 'birth_year'];
        var confirmed = getValues();

        function text(selector, value) {
            if (value === undefined || value === null) return;
            root.querySelectorAll(selector).forEach(function (node) { node.textContent = String(value); });
        }
        function persianDigits(value) {
            return String(value).replace(/[0-9]/g, function (digit) { return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]; });
        }
        function latinDigits(value) {
            return String(value || '').replace(/[۰-۹]/g, function (digit) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)); }).replace(/[٠-٩]/g, function (digit) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)); });
        }
        function field(name) { return form ? form.elements.namedItem(name) : null; }
        function getValues() {
            var result = {};
            profileFields.forEach(function (name) { var node = field(name); result[name] = node ? node.value : ''; });
            return result;
        }
        function setValues(values) {
            if (!values) return;
            profileFields.forEach(function (name) {
                var node = field(name);
                if (node && Object.prototype.hasOwnProperty.call(values, name)) node.value = String(values[name] === null ? '' : values[name]);
            });
        }
        function showNotice(message, error) {
            if (!notice) return;
            window.clearTimeout(toastTimeout);
            notice.textContent = message;
            notice.dataset.tone = error ? 'error' : 'success';
            notice.hidden = false;
            toastTimeout = window.setTimeout(function () { notice.hidden = true; }, error ? 9000 : 5500);
        }
        function showFormError(message) {
            if (!formError) return;
            formError.textContent = message || '';
            formError.hidden = !message;
        }
        function setEditing(editing) {
            if (!form || pending) return;
            form.dataset.mode = editing ? 'edit' : 'view';
            profileFields.forEach(function (name) {
                var node = field(name);
                if (!node) return;
                if (node.tagName === 'SELECT') node.disabled = !editing;
                else node.readOnly = !editing;
                node.removeAttribute('aria-invalid');
            });
            if (edit) edit.hidden = editing;
            if (actions) actions.hidden = !editing;
            showFormError('');
            if (editing && field('first_name')) field('first_name').focus();
        }
        function setBusy(busy) {
            pending = busy;
            root.setAttribute('aria-busy', busy ? 'true' : 'false');
            [edit, cancel, avatarPick, phoneOpen].forEach(function (button) { if (button) button.disabled = busy; });
            if (avatarRemove) avatarRemove.disabled = busy || !avatarPresent;
            if (form) form.querySelectorAll('button[type="submit"]').forEach(function (button) { button.disabled = busy; });
            profileFields.forEach(function (name) {
                var node = field(name);
                if (!node) return;
                var locked = busy || form.dataset.mode !== 'edit';
                if (node.tagName === 'SELECT') node.disabled = locked;
                else node.readOnly = locked;
            });
        }
        function safeImageUrl(value) {
            if (typeof value !== 'string' || !value) return '';
            try {
                var url = new URL(value, window.location.href);
                return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : '';
            } catch (ignored) { return ''; }
        }
        function applyContext(context, preserveValues) {
            if (!context || typeof context !== 'object') return;
            var unsaved = preserveValues ? getValues() : null;
            setValues({ first_name: context.firstName, last_name: context.lastName, email: context.email, birth_day: context.birth ? context.birth.day : '', birth_month: context.birth ? context.birth.month : '', birth_year: context.birth ? context.birth.year : '' });
            text('[data-account-display-name]', context.displayName);
            text('[data-account-phone]', persianDigits(context.phone || ''));
            text('[data-account-orders-count]', persianDigits(context.ordersCount || 0));
            text('[data-account-membership-days]', persianDigits(context.daysMember || 0));
            text('[data-account-member-since]', context.memberDurationText);
            var avatarUrl = safeImageUrl(context.avatarUrl);
            var initial = context.displayName ? Array.from(String(context.displayName))[0] : '';
            if (context.avatarUrl !== undefined) {
                root.querySelectorAll('[data-account-avatar]').forEach(function (container) {
                    container.replaceChildren();
                    if (avatarUrl) {
                        var image = document.createElement('img');
                        image.src = avatarUrl;
                        image.alt = context.displayName ? String(context.displayName) : '';
                        image.width = 130;
                        image.height = 130;
                        container.appendChild(image);
                    } else {
                        var initialNode = document.createElement('span');
                        initialNode.dataset.accountAvatarInitial = '';
                        initialNode.textContent = initial;
                        container.appendChild(initialNode);
                    }
                });
                avatarPresent = !!avatarUrl;
                if (avatarRemove) avatarRemove.disabled = pending || !avatarPresent;
            }
            confirmed = getValues();
            if (unsaved) setValues(unsaved);
        }
        function request(payload, signal) {
            if (!config.ajaxUrl || !config.nonce) return Promise.reject(new Error('ارتباط با حساب کاربری برقرار نشد. صفحه را دوباره بارگذاری کنید.'));
            var url;
            try { url = new URL(config.ajaxUrl, window.location.href); } catch (ignored) { return Promise.reject(new Error('ارتباط با حساب کاربری برقرار نشد.')); }
            if (url.origin !== window.location.origin) return Promise.reject(new Error('ارتباط با حساب کاربری برقرار نشد.'));
            return window.fetch(url.href, { method: 'POST', credentials: 'same-origin', body: payload, signal: signal, headers: { Accept: 'application/json' } }).then(function (response) {
                return response.json().catch(function () { throw new Error('پاسخ سرور قابل خواندن نیست. لطفاً دوباره تلاش کنید.'); }).then(function (result) {
                    if (!response.ok || !result || !result.success) {
                        var failure = new Error(result && result.data && result.data.message ? String(result.data.message) : 'ذخیره تغییرات انجام نشد. لطفاً دوباره تلاش کنید.');
                        failure.accountData = result && result.data && typeof result.data === 'object' ? result.data : {};
                        throw failure;
                    }
                    return result.data || {};
                });
            }).catch(function (error) {
                if (error instanceof TypeError) throw new Error('ارتباط با سرور برقرار نشد. لطفاً دوباره تلاش کنید.');
                throw error;
            });
        }
        if (edit) edit.addEventListener('click', function () { setEditing(true); });
        if (cancel) cancel.addEventListener('click', function () {
            if (pending) return;
            setValues(confirmed);
            setEditing(false);
            if (edit) edit.focus();
        });
        if (form) form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (pending || form.dataset.mode !== 'edit') return;
            showFormError('');
            if (!form.reportValidity()) return;
            var values = getValues();
            var payload = new FormData();
            payload.set('action', config.saveAction || 'genosys_account_save');
            payload.set('nonce', config.nonce);
            profileFields.forEach(function (name) { payload.set(name, values[name].trim()); });
            setBusy(true);
            request(payload).then(function (data) {
                applyContext(data.context);
                confirmed = getValues();
                setBusy(false);
                setEditing(false);
                showNotice(data.message || 'اطلاعات شما با موفقیت ذخیره شد.');
            }).catch(function (error) { showFormError(error.message); }).finally(function () { setBusy(false); });
        });
        function saveAvatar(operation, file) {
            if (pending) return;
            var payload = new FormData();
            payload.set('action', config.avatarAction || 'genosys_account_avatar');
            payload.set('nonce', config.nonce);
            payload.set('operation', operation);
            if (file) payload.set('avatar', file);
            setBusy(true);
            request(payload).then(function (data) { applyContext(data.context, form && form.dataset.mode === 'edit'); showNotice(data.message || 'تصویر حساب شما به‌روزرسانی شد.'); }).catch(function (error) { showNotice(error.message, true); }).finally(function () {
                setBusy(false);
                if (avatarFile) avatarFile.value = '';
            });
        }
        if (avatarPick && avatarFile) avatarPick.addEventListener('click', function () { if (!pending) avatarFile.click(); });
        if (avatarFile) avatarFile.addEventListener('change', function () {
            var file = avatarFile.files && avatarFile.files[0];
            if (!file || pending) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { showNotice('لطفاً تصویر JPG، PNG یا WebP انتخاب کنید.', true); avatarFile.value = ''; return; }
            if (file.size > 5 * 1024 * 1024) { showNotice('حجم تصویر باید حداکثر ۵ مگابایت باشد.', true); avatarFile.value = ''; return; }
            saveAvatar('upload', file);
        });
        if (avatarRemove) avatarRemove.addEventListener('click', function () { saveAvatar('remove'); });

        var notificationPanel = root.querySelector('[data-notification-preferences]');
        if (notificationPanel) {
            var notificationStatus = notificationPanel.querySelector('[data-notification-status]');
            var preferenceKeys = ['order_status_sms', 'payment_sms', 'promotions', 'newsletter'];
            var notificationControllers = [];
            var notificationRefreshRevision = 0;
            var notificationConfigValid = false;
            try { notificationConfigValid = !!config.ajaxUrl && !!config.nonce && new URL(config.ajaxUrl, window.location.href).origin === window.location.origin; } catch (ignored) { notificationConfigValid = false; }
            notificationPanel.querySelectorAll('[data-notification-preference]').forEach(function (input) {
                var key = input.dataset.notificationPreference;
                if (!preferenceKeys.includes(key)) return;
                var row = input.closest('[data-notification-row]');
                var feedback = row ? row.querySelector('[data-notification-feedback]') : null;
                var savedValue = input.checked;
                var saving = false;
                var revision = 0;
                if (!notificationConfigValid) {
                    if (feedback) { feedback.textContent = 'ارتباط با حساب کاربری برقرار نشد. صفحه را دوباره بارگذاری کنید.'; feedback.hidden = false; }
                    return;
                }
                input.disabled = false;
                notificationControllers.push({
                    key: key,
                    revision: function () { return revision; },
                    adopt: function (enabled, expectedRevision) {
                        if (saving || revision !== expectedRevision) return;
                        savedValue = enabled;
                        input.checked = savedValue;
                    }
                });
                input.addEventListener('change', function () {
                    if (saving) { input.checked = savedValue; return; }
                    var desired = input.checked;
                    var currentRevision = ++revision;
                    var restoreControlFocus = document.activeElement === input;
                    var label = document.getElementById(input.getAttribute('aria-labelledby'));
                    var labelText = label ? label.textContent : 'تنظیم اعلان';
                    var payload = new FormData();
                    payload.set('action', config.notificationAction || 'genosys_account_notifications_save');
                    payload.set('nonce', config.nonce);
                    payload.set('preference', key);
                    payload.set('enabled', desired ? '1' : '0');
                    saving = true;
                    input.disabled = true;
                    input.setAttribute('aria-busy', 'true');
                    if (row) row.dataset.saving = 'true';
                    if (feedback) { feedback.hidden = true; feedback.textContent = ''; }
                    if (notificationStatus) notificationStatus.textContent = 'در حال ذخیره ' + labelText + '…';
                    request(payload).then(function (data) {
                        if (currentRevision !== revision) return;
                        if (data.preference !== key || typeof data.enabled !== 'boolean') throw new Error('پاسخ ذخیره تنظیمات معتبر نیست. صفحه را دوباره بارگذاری کنید.');
                        savedValue = data.enabled;
                        input.checked = savedValue;
                        if (notificationStatus) notificationStatus.textContent = labelText + ' ذخیره شد.';
                        showNotice(data.message || 'تنظیم اعلان‌ها ذخیره شد.');
                    }).catch(function (error) {
                        if (currentRevision !== revision) return;
                        input.checked = savedValue;
                        var message = error.message || 'ذخیره این تنظیم انجام نشد. لطفاً دوباره تلاش کنید.';
                        if (feedback) { feedback.textContent = message; feedback.hidden = false; }
                        if (notificationStatus) notificationStatus.textContent = 'ذخیره ' + labelText + ' انجام نشد.';
                        showNotice(message, true);
                    }).finally(function () {
                        if (currentRevision !== revision) return;
                        saving = false;
                        input.disabled = false;
                        input.removeAttribute('aria-busy');
                        if (row) delete row.dataset.saving;
                        if (restoreControlFocus && document.activeElement === document.body) input.focus();
                    });
                });
            });
            window.addEventListener('pageshow', function (event) {
                if (!event.persisted || !notificationConfigValid) return;
                var refresh = ++notificationRefreshRevision;
                var snapshots = notificationControllers.map(function (controller) { return { controller: controller, revision: controller.revision() }; });
                var payload = new FormData();
                payload.set('action', config.notificationGetAction || 'genosys_account_notifications_get');
                payload.set('nonce', config.nonce);
                request(payload).then(function (data) {
                    if (refresh !== notificationRefreshRevision) return;
                    var preferences = data.preferences;
                    if (!preferences || preferenceKeys.some(function (key) { return typeof preferences[key] !== 'boolean'; })) throw new Error('تنظیمات اعلان‌ها قابل خواندن نیست. صفحه را دوباره بارگذاری کنید.');
                    snapshots.forEach(function (snapshot) { snapshot.controller.adopt(preferences[snapshot.controller.key], snapshot.revision); });
                }).catch(function (error) { if (refresh === notificationRefreshRevision) showNotice(error.message, true); });
            });
        }

        // Only an authenticated server verification can change the trusted account phone.
        var dialog = root.querySelector('[data-phone-dialog]');
        var phoneOpen = root.querySelector('[data-phone-open]');
        if (!dialog || !phoneOpen) return;
        var numberInput = dialog.querySelector('[data-phone-number]');
        var numberStep = dialog.querySelector('[data-phone-step="number"]');
        var codeStep = dialog.querySelector('[data-phone-step="code"]');
        var requestCode = dialog.querySelector('[data-phone-request]');
        var back = dialog.querySelector('[data-phone-back]');
        var resend = dialog.querySelector('[data-phone-resend]');
        var timer = dialog.querySelector('[data-phone-timer]');
        var phoneNotice = dialog.querySelector('[data-phone-notice]');
        var phoneConfirm = dialog.querySelector('[data-phone-confirm]');
        var digits = Array.from(dialog.querySelectorAll('[data-phone-digit]'));
        var closeButtons = Array.from(dialog.querySelectorAll('[data-phone-close]'));
        var challenge = null;
        var cooldownUntil = 0;
        var interval = null;
        var restoreFocus = null;
        var phoneBusy = false;
        var phoneOperation = '';
        var phoneRequestRevision = 0;
        var expiryNoticeShown = false;
        var numberDescription = 'کد تأیید به شماره‌ی جدید ارسال می‌شود.';

        function phoneMessage(message, error) {
            if (!phoneNotice) return;
            phoneNotice.textContent = message || numberDescription;
            phoneNotice.style.color = error ? '#c90a35' : '';
            phoneNotice.hidden = false;
        }
        function normalizePhone(value) {
            var phone = latinDigits(value).replace(/[\s()-]/g, '');
            if (/^\+98[0-9]{10}$/.test(phone)) phone = '0' + phone.slice(3);
            if (/^0098[0-9]{10}$/.test(phone)) phone = '0' + phone.slice(4);
            return /^09[0-9]{9}$/.test(phone) ? phone : '';
        }
        function resetDigits() {
            digits.forEach(function (digit) { digit.value = ''; digit.removeAttribute('aria-invalid'); });
        }
        function hasLiveChallenge() {
            return challenge && !challenge.invalid && Date.now() < challenge.expiresAt;
        }
        function timeLabel(seconds) {
            if (seconds >= 3600) return persianDigits(Math.floor(seconds / 3600) + ':' + String(Math.floor(seconds / 60) % 60).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0'));
            return persianDigits(Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0'));
        }
        function updatePhoneControls() {
            var remaining = Math.max(0, Math.ceil((cooldownUntil - Date.now()) / 1000));
            if (requestCode) {
                requestCode.disabled = phoneBusy || remaining > 0;
                requestCode.textContent = phoneBusy && phoneOperation === 'request' ? 'در حال ارسال…' : 'دریافت کد' + (remaining > 0 ? ' ' + timeLabel(remaining) : '');
            }
            if (timer) timer.textContent = remaining > 0 ? timeLabel(remaining) : '';
            if (resend) resend.disabled = phoneBusy || !challenge || remaining > 0;
            if (phoneConfirm) {
                phoneConfirm.disabled = phoneBusy || !hasLiveChallenge();
                phoneConfirm.textContent = phoneBusy && phoneOperation === 'verify' ? 'در حال تأیید…' : 'تأیید شماره';
            }
            if (back) back.disabled = phoneBusy;
            closeButtons.forEach(function (button) { button.disabled = phoneBusy; });
            if (numberInput) numberInput.disabled = phoneBusy;
            digits.forEach(function (digit) { digit.disabled = phoneBusy; });
            phoneOpen.disabled = phoneBusy || pending;
            if (challenge && !hasLiveChallenge() && !phoneBusy && codeStep && !codeStep.hidden && !expiryNoticeShown) {
                expiryNoticeShown = true;
                phoneMessage('اعتبار کد پایان یافته است. لطفاً کد جدید دریافت کنید.', true);
            }
        }
        function startPhoneTimer() {
            window.clearInterval(interval);
            updatePhoneControls();
            interval = window.setInterval(updatePhoneControls, 1000);
        }
        function setPhoneBusy(busy, operation) {
            phoneBusy = busy;
            phoneOperation = busy ? operation : '';
            dialog.setAttribute('aria-busy', busy ? 'true' : 'false');
            updatePhoneControls();
        }
        function phonePost(action, values) {
            var payload = new FormData();
            payload.set('action', action);
            payload.set('nonce', config.nonce);
            Object.keys(values).forEach(function (key) { payload.set(key, values[key]); });
            var controller = typeof AbortController === 'function' ? new AbortController() : null;
            var timeout = controller ? window.setTimeout(function () { controller.abort(); }, 45000) : null;
            return request(payload, controller ? controller.signal : undefined).catch(function (error) {
                if (error.name === 'AbortError') throw new Error('پاسخ سرور دریافت نشد. اتصال را بررسی کنید و دوباره تلاش کنید.');
                throw error;
            }).finally(function () { if (timeout) window.clearTimeout(timeout); });
        }
        function adoptChallenge(data, expectedPhone) {
            var serverTime = Number(data.serverTime);
            var expiresAt = Number(data.expiresAt);
            var resendAt = Number(data.resendAt);
            var phone = normalizePhone(data.phone);
            if (!/^[a-f0-9]{64}$/i.test(String(data.challengeId || '')) || phone !== expectedPhone || !Number.isFinite(serverTime) || !Number.isFinite(expiresAt) || !Number.isFinite(resendAt) || expiresAt <= serverTime || resendAt < serverTime) {
                throw new Error('پاسخ دریافت کد معتبر نیست. لطفاً دوباره تلاش کنید.');
            }
            var receivedAt = Date.now();
            challenge = { id: String(data.challengeId), phone: phone, expiresAt: receivedAt + (expiresAt - serverTime) * 1000, invalid: false };
            cooldownUntil = receivedAt + (resendAt - serverTime) * 1000;
            expiryNoticeShown = false;
            if (numberInput) numberInput.value = phone;
        }
        function showNumber(message) {
            if (phoneBusy) return;
            if (numberStep) numberStep.hidden = false;
            if (codeStep) codeStep.hidden = true;
            resetDigits();
            phoneMessage(message || numberDescription);
            startPhoneTimer();
            if (numberInput) { numberInput.removeAttribute('aria-invalid'); numberInput.focus(); }
        }
        function showCode(message) {
            if (!challenge) return;
            if (numberStep) numberStep.hidden = true;
            if (codeStep) codeStep.hidden = false;
            dialog.querySelectorAll('[data-phone-preview-number]').forEach(function (node) { node.textContent = persianDigits(challenge.phone); });
            resetDigits();
            phoneMessage(message || 'کد تأیید به شماره‌ی جدید ارسال شد.');
            startPhoneTimer();
            if (digits[0] && !phoneBusy) digits[0].focus();
        }
        function closeDialog() {
            if (phoneBusy) return;
            if (typeof dialog.close === 'function') dialog.close();
            else dialog.removeAttribute('open');
            document.body.classList.remove('gaccount-modal-open');
            window.clearInterval(interval);
            resetDigits();
            if (restoreFocus && typeof restoreFocus.focus === 'function') restoreFocus.focus();
        }
        phoneOpen.addEventListener('click', function () {
            if (phoneBusy || pending) return;
            restoreFocus = document.activeElement;
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
            document.body.classList.add('gaccount-modal-open');
            if (hasLiveChallenge()) showCode('کد تأیید ارسال‌شده را وارد کنید.');
            else showNumber();
        });
        closeButtons.forEach(function (button) { button.addEventListener('click', closeDialog); });
        dialog.addEventListener('cancel', function (event) { event.preventDefault(); closeDialog(); });
        dialog.addEventListener('click', function (event) {
            if (event.target !== dialog || phoneBusy) return;
            var rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeDialog();
        });
        dialog.addEventListener('close', function () {
            document.body.classList.remove('gaccount-modal-open');
            window.clearInterval(interval);
            resetDigits();
        });
        function handlePhoneError(error, operation) {
            var detail = error.accountData || {};
            var serverTime = Number(detail.serverTime);
            var resendAt = Number(detail.resendAt);
            var expiresAt = Number(detail.expiresAt);
            if (Number.isFinite(serverTime) && serverTime > 0) {
                if (Number.isFinite(resendAt) && resendAt >= serverTime) cooldownUntil = Math.max(cooldownUntil, Date.now() + Math.min(resendAt - serverTime, 86400) * 1000);
                if (challenge && Number.isFinite(expiresAt) && expiresAt > 0) challenge.expiresAt = Date.now() + (expiresAt - serverTime) * 1000;
            }
            var retry = Number(detail.retry_after);
            if (Number.isFinite(retry) && retry > 0) cooldownUntil = Math.max(cooldownUntil, Date.now() + Math.min(retry, 86400) * 1000);
            if (detail.code === 'expired_code' || detail.code === 'attempts_exhausted') {
                if (challenge) challenge.invalid = true;
                expiryNoticeShown = true;
            }
            if (operation === 'verify') {
                resetDigits();
                digits.forEach(function (digit) { digit.setAttribute('aria-invalid', 'true'); });
            }
            phoneMessage(error.message || 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.', true);
        }
        function sendPhoneCode(event, resending) {
            if (event) event.preventDefault();
            if (phoneBusy || Date.now() < cooldownUntil) return;
            var phone = resending && challenge ? challenge.phone : normalizePhone(numberInput ? numberInput.value : '');
            if (!phone) { if (numberInput) { numberInput.setAttribute('aria-invalid', 'true'); numberInput.focus(); } phoneMessage('لطفاً شماره موبایل معتبر وارد کنید.', true); return; }
            if (resending && !challenge) return;
            var revision = ++phoneRequestRevision;
            var operation = resending ? 'resend' : 'request';
            var previousId = challenge ? challenge.id : '';
            setPhoneBusy(true, operation);
            phoneMessage('در حال ارسال کد تأیید…');
            var action = resending ? (config.phoneResendAction || 'genosys_account_phone_resend') : (config.phoneRequestAction || 'genosys_account_phone_request');
            var values = resending ? { challengeId: previousId } : { phone: phone };
            phonePost(action, values).then(function (data) {
                if (revision !== phoneRequestRevision || (resending && (!challenge || challenge.id !== previousId))) return;
                adoptChallenge(data, phone);
                showCode(data.message);
            }).catch(function (error) {
                if (revision === phoneRequestRevision) handlePhoneError(error, operation);
            }).finally(function () {
                if (revision !== phoneRequestRevision) return;
                setPhoneBusy(false);
                if (codeStep && !codeStep.hidden && digits[0] && hasLiveChallenge()) digits[0].focus();
                else if (numberInput && numberStep && !numberStep.hidden) numberInput.focus();
            });
        }
        function applyVerifiedPhone(context, expectedPhone) {
            var phone = context && normalizePhone(context.phone);
            if (!context || context.phoneVerified !== true || phone !== expectedPhone) throw new Error('تأیید شماره ثبت نشد. صفحه را دوباره بارگذاری کنید.');
            text('[data-account-phone]', persianDigits(phone));
            text('[data-account-phone-description]', 'شماره‌ی شما برای ورود با کد تأیید و دریافت پیامک سفارش‌ها استفاده می‌شود.');
            root.querySelectorAll('[data-account-phone-verified]').forEach(function (badge) { badge.hidden = false; });
            document.querySelectorAll('[data-account-header-identity], [data-account-menu]:not(.nav-account-menu--guest) .account-panel-identity-copy').forEach(function (headerIdentity) {
                var headerPhone = headerIdentity.querySelector('small');
                if (!headerPhone) { headerPhone = document.createElement('small'); headerPhone.dir = 'auto'; headerIdentity.appendChild(headerPhone); }
                headerPhone.textContent = persianDigits(phone.slice(0, 4) + ' ' + phone.slice(4, 7) + ' ' + phone.slice(7));
            });
        }
        function verifyPhoneCode(event) {
            if (event) event.preventDefault();
            if (phoneBusy) return;
            if (!hasLiveChallenge()) { updatePhoneControls(); return; }
            var code = digits.map(function (digit) { return latinDigits(digit.value); }).join('');
            if (!/^[0-9]{6}$/.test(code)) {
                phoneMessage('کد تأیید شش رقمی را کامل وارد کنید.', true);
                var missing = digits.find(function (digit) { return !/^[0-9]$/.test(latinDigits(digit.value)); });
                if (missing) missing.focus();
                return;
            }
            var revision = ++phoneRequestRevision;
            var verifyingId = challenge.id;
            var verifyingPhone = challenge.phone;
            setPhoneBusy(true, 'verify');
            phoneMessage('در حال تأیید شماره موبایل…');
            phonePost(config.phoneVerifyAction || 'genosys_account_phone_verify', { challengeId: verifyingId, code: code }).then(function (data) {
                if (revision !== phoneRequestRevision || !challenge || challenge.id !== verifyingId) return;
                applyVerifiedPhone(data.context, verifyingPhone);
                challenge = null;
                cooldownUntil = 0;
                setPhoneBusy(false);
                closeDialog();
                showNotice(data.message || 'شماره موبایل با موفقیت تغییر کرد.');
            }).catch(function (error) {
                if (revision === phoneRequestRevision) handlePhoneError(error, 'verify');
            }).finally(function () {
                if (revision !== phoneRequestRevision) return;
                setPhoneBusy(false);
                if (dialog.open && hasLiveChallenge() && digits[0]) digits[0].focus();
            });
        }
        if (requestCode) requestCode.addEventListener('click', function (event) { sendPhoneCode(event, false); });
        if (numberInput) numberInput.addEventListener('keydown', function (event) { if (event.key === 'Enter') sendPhoneCode(event, false); });
        if (back) back.addEventListener('click', function () { showNumber(); });
        if (resend) resend.addEventListener('click', function (event) { sendPhoneCode(event, true); });
        function fillDigits(value, start) {
            var normalized = latinDigits(value).replace(/\D/g, '');
            if (!normalized || phoneBusy) return;
            normalized.slice(0, digits.length - start).split('').forEach(function (value, index) { digits[start + index].value = value; digits[start + index].removeAttribute('aria-invalid'); });
            var destination = Math.min(digits.length - 1, start + normalized.length);
            if (digits[destination]) digits[destination].focus();
        }
        digits.forEach(function (digit, index) {
            digit.addEventListener('input', function () {
                var normalized = latinDigits(digit.value).replace(/\D/g, '');
                digit.value = normalized.slice(0, 1);
                digit.removeAttribute('aria-invalid');
                if (normalized.length > 1) fillDigits(normalized, index);
                else if (normalized && digits[index + 1]) digits[index + 1].focus();
            });
            digit.addEventListener('paste', function (event) { event.preventDefault(); fillDigits(event.clipboardData ? event.clipboardData.getData('text') : '', index); });
            digit.addEventListener('keydown', function (event) {
                if (phoneBusy) return;
                if (event.key === 'Backspace' && !digit.value && digits[index - 1]) { event.preventDefault(); digits[index - 1].value = ''; digits[index - 1].focus(); }
                if (event.key === 'ArrowLeft' && digits[index - 1]) { event.preventDefault(); digits[index - 1].focus(); }
                if (event.key === 'ArrowRight' && digits[index + 1]) { event.preventDefault(); digits[index + 1].focus(); }
                if (event.key === 'Enter') verifyPhoneCode(event);
            });
            digit.addEventListener('focus', function () { digit.select(); });
        });
        if (phoneConfirm) phoneConfirm.addEventListener('click', verifyPhoneCode);
        updatePhoneControls();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
