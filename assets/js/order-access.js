/* Recover guest order history without signing in or creating an account. */
(function () {
    'use strict';
    var box = document.querySelector('[data-orders-access]');
    if (!box) return;
    var config;
    try { config = JSON.parse(box.querySelector('[data-orders-access-config]').textContent); } catch (_) { return; }
    var form = box.querySelector('[data-orders-access-form]');
    var phone = box.querySelector('[data-access-phone]');
    var code = box.querySelector('[data-access-code]');
    var submit = box.querySelector('[data-access-submit]');
    var message = box.querySelector('[data-access-message]');
    var resend = box.querySelector('[data-access-resend]');
    var change = box.querySelector('[data-access-change]');
    var clear = box.querySelector('[data-access-clear]');
    var stage = 'phone', busy = false, requestId = '', expiresAt = 0, timer = null;

    function digits(value) {
        return String(value).replace(/[۰-۹٠-٩]/g, function (d) {
            var persian = '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
            return persian >= 0 ? persian : '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
        });
    }
    function localDigits(value) {
        return String(value).replace(/[0-9]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[Number(d)]; });
    }
    function normalizedPhone(value) {
        value = digits(value).replace(/[^0-9+]/g, '');
        value = value.replace(/^(?:\+98|0098|98)/, '0');
        if (/^9[0-9]{9}$/.test(value)) value = '0' + value;
        return /^09[0-9]{9}$/.test(value) ? value : '';
    }
    function showMessage(text, success) {
        message.textContent = text;
        message.hidden = !text;
        message.classList.toggle('is-success', !!success);
    }
    function countdown() {
        var seconds = Math.max(0, Math.ceil((expiresAt - Date.now()) / 1000));
        resend.disabled = busy || seconds > 0;
        resend.textContent = 'ارسال دوباره کد' + (seconds ? ' (' + localDigits(seconds) + ')' : '');
        if (!seconds && timer) { window.clearInterval(timer); timer = null; }
    }
    function startCountdown(seconds) {
        if (timer) window.clearInterval(timer);
        expiresAt = Date.now() + Math.max(0, Number(seconds) || 0) * 1000;
        countdown();
        timer = window.setInterval(countdown, 1000);
    }
    function setBusy(value) {
        busy = value;
        form.setAttribute('aria-busy', String(value));
        submit.disabled = value;
        change.disabled = value;
        phone.disabled = value || stage === 'code';
        code.disabled = value || stage !== 'code';
        if (clear) clear.disabled = value;
        submit.textContent = value ? 'لطفاً صبر کنید…' : (stage === 'code' ? 'تأیید و مشاهده سفارش‌ها' : 'دریافت کد تأیید');
        countdown();
    }
    async function send(action, fields) {
        var body = new URLSearchParams(Object.assign({ action: action, nonce: config.nonce }, fields));
        var result;
        try {
            var response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body });
            result = await response.json();
        } catch (_) { throw new Error('ارتباط برقرار نشد. دوباره تلاش کنید.'); }
        if (!result.success) {
            var error = new Error(result.data && typeof result.data.message === 'string' ? result.data.message : 'درخواست انجام نشد. دوباره تلاش کنید.');
            error.retryAfter = result.data && result.data.retry_after;
            throw error;
        }
        return result.data;
    }
    function showOrders() {
        var destination = new URL(box.dataset.ordersUrl, window.location.href);
        if (destination.origin === window.location.origin) window.location.assign(destination.href);
    }
    async function requestCode() {
        var value = normalizedPhone(phone.value);
        phone.setAttribute('aria-invalid', value ? 'false' : 'true');
        if (!value) { showMessage('شماره موبایل معتبر وارد کنید.'); phone.focus(); return; }
        showMessage('');
        setBusy(true);
        try {
            var data = await send(config.requestAction, { phone: value });
            requestId = data.request_id;
            stage = 'code';
            code.value = '';
            code.setAttribute('aria-invalid', 'false');
            box.querySelector('[data-access-phone-step]').hidden = true;
            box.querySelector('[data-access-code-step]').hidden = false;
            box.querySelector('[data-access-actions]').hidden = false;
            box.querySelector('[data-access-sent]').textContent = 'کد تأیید به ' + localDigits(value) + ' ارسال شد.';
            startCountdown(data.expires_in);
        } catch (error) {
            showMessage(error.message || 'ارتباط برقرار نشد. دوباره تلاش کنید.');
            if (error.retryAfter) startCountdown(error.retryAfter);
        } finally { setBusy(false); }
        if (stage === 'code') code.focus();
    }
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (busy) return;
        if (stage === 'phone') { await requestCode(); return; }
        var value = digits(code.value).trim();
        if (!/^[0-9]{6}$/.test(value)) {
            code.setAttribute('aria-invalid', 'true');
            showMessage('کد تأیید ۶ رقمی را وارد کنید.');
            code.focus();
            return;
        }
        code.setAttribute('aria-invalid', 'false');
        showMessage('');
        setBusy(true);
        try {
            var data = await send(config.verifyAction, { request_id: requestId, code: value });
            if (!data.verified) throw new Error('تأیید شماره انجام نشد. دوباره تلاش کنید.');
            config.nonce = data.nonce;
            showMessage('شماره تأیید شد. سفارش‌های شما در حال نمایش هستند…', true);
            showOrders();
        } catch (error) {
            showMessage(error.message || 'ارتباط برقرار نشد. دوباره تلاش کنید.');
            code.setAttribute('aria-invalid', 'true');
            setBusy(false);
            code.focus();
        }
    });
    change.addEventListener('click', function () {
        if (busy) return;
        stage = 'phone'; requestId = ''; code.value = '';
        box.querySelector('[data-access-phone-step]').hidden = false;
        box.querySelector('[data-access-code-step]').hidden = true;
        box.querySelector('[data-access-actions]').hidden = true;
        showMessage(''); setBusy(false); phone.focus();
    });
    resend.addEventListener('click', function () { if (!busy && Date.now() >= expiresAt) requestCode(); });
    if (clear) clear.addEventListener('click', async function () {
        if (busy) return;
        showMessage(''); setBusy(true);
        try { await send(config.clearAction, {}); showOrders(); }
        catch (error) { showMessage(error.message || 'دوباره تلاش کنید.'); setBusy(false); }
    });
}());
