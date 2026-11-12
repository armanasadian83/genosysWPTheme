/* Contact page interactions: local drafts, native validation and server-confirmed delivery. */
(function () {
    'use strict';
    var page = document.querySelector('[data-contact-page]');
    if (!page) return;
    var form = page.querySelector('[data-contact-form]');
    var config = window.genosysContact || {};
    var count = page.querySelector('[data-contact-count]');
    var attachment = form.querySelector('[name="attachment"]');
    var attachmentLabel = page.querySelector('[data-contact-file-label]');
    var attachmentHint = attachmentLabel.textContent;
    var attachmentRemove = page.querySelector('[data-contact-file-remove]');
    var submit = form.querySelector('[type="submit"]');
    var submitLabel = page.querySelector('[data-contact-submit-label]');
    var originalSubmitLabel = submitLabel.textContent;
    var status = page.querySelector('[data-contact-form-status]');
    var sending = false;
    var persianDigits = function (value) { return String(value).replace(/\d/g, function (digit) { return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]; }); };
    var normalizeDigits = function (value) { return String(value).replace(/[۰-۹]/g, function (digit) { return String(digit.charCodeAt(0) - 1776); }).replace(/[٠-٩]/g, function (digit) { return String(digit.charCodeAt(0) - 1632); }); };
    var normalizePhone = function (value) { return normalizeDigits(value).replace(/[\s()\-]/g, '').replace(/^(?:\+98|0098)/, '0'); };

    function fieldError(name, message) {
        var node = form.querySelector('[data-contact-error="' + name + '"]');
        var input = form.elements.namedItem(name);
        if (node) { node.textContent = message || ''; node.hidden = !message; }
        if (input && input.setAttribute) { if (message) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid'); }
    }
    function clearErrors() {
        form.querySelectorAll('[data-contact-error]').forEach(function (node) { fieldError(node.getAttribute('data-contact-error'), ''); });
    }
    function showStatus(message, success) {
        status.textContent = message;
        status.classList.toggle('is-success', Boolean(success));
        status.hidden = false;
        status.focus({ preventScroll: true });
    }
    function updateCounter() { count.textContent = persianDigits(Array.from(form.elements.message.value).length); }
    function updateAttachment() {
        var file = attachment.files && attachment.files[0];
        attachmentLabel.textContent = file ? file.name : attachmentHint;
        attachmentRemove.hidden = !file;
        attachment.closest('.gs-contact-attachment-wrap').classList.toggle('has-file', Boolean(file));
    }
    function validAttachment() {
        var file = attachment.files && attachment.files[0];
        if (!file) return true;
        if (file.size > (Number(config.maxFileBytes) || 5242880)) { fieldError('attachment', 'حجم فایل نباید بیشتر از ۵ مگابایت باشد.'); return false; }
        if (!/\.(jpe?g|png|webp|pdf)$/i.test(file.name) || (file.type && !/^(image\/(jpeg|png|webp)|application\/pdf)$/i.test(file.type))) { fieldError('attachment', 'فقط تصویر JPG، PNG، WebP یا فایل PDF انتخاب کنید.'); return false; }
        return true;
    }
    function validForm() {
        clearErrors();
        var valid = true;
        function fail(name, message) { fieldError(name, message); valid = false; }
        if (form.elements.fullname.value.trim().length < 2) fail('fullname', 'نام و نام خانوادگی خود را وارد کنید.');
        if (!/^09\d{9}$/.test(normalizePhone(form.elements.mobile.value))) fail('mobile', 'شماره موبایل معتبر وارد کنید (مانند ۰۹۱۲۳۴۵۶۷۸۹).');
        if (form.elements.email.value.trim() && !form.elements.email.validity.valid) fail('email', 'نشانی ایمیل معتبر وارد کنید.');
        if (!form.elements.topic.value) fail('topic', 'موضوع پیام را انتخاب کنید.');
        var length = Array.from(form.elements.message.value.trim()).length;
        if (length < 1) fail('message', 'پیام خود را بنویسید.');
        else if (length > 500) fail('message', 'متن پیام باید حداکثر ۵۰۰ نویسه باشد.');
        if (!form.elements.consent.checked) fail('consent', 'برای ارسال پیام، قوانین حریم خصوصی را بپذیرید.');
        if (!validAttachment()) valid = false;
        if (!valid) {
            var first = form.querySelector('[aria-invalid="true"]');
            if (first) first.focus();
        }
        return valid;
    }

    page.querySelectorAll('[data-contact-copy]').forEach(function (button) {
        var resetTimer;
        var original = button.textContent;
        button.addEventListener('click', function () {
            var value = button.getAttribute('data-contact-copy');
            if (!value || button.disabled) return;
            function fallbackCopy() {
                var previous = document.activeElement;
                var textarea = document.createElement('textarea');
                textarea.value = value;
                textarea.style.cssText = 'position:fixed;top:0;left:-9999px;opacity:0';
                textarea.setAttribute('readonly', '');
                document.body.appendChild(textarea);
                textarea.select();
                textarea.setSelectionRange(0, textarea.value.length);
                var copied = false;
                try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
                textarea.remove();
                if (previous && previous.focus) previous.focus({ preventScroll: true });
                return copied ? Promise.resolve() : Promise.reject(new Error('copy'));
            }
            var copy = navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(value).catch(fallbackCopy) : fallbackCopy();
            copy.then(function () {
                clearTimeout(resetTimer);
                button.textContent = button.getAttribute('data-copied-label');
                page.querySelector('[data-contact-copy-status]').textContent = 'اطلاعات تماس کپی شد.';
                resetTimer = setTimeout(function () { button.textContent = original; }, 2200);
            }).catch(function () { page.querySelector('[data-contact-copy-status]').textContent = 'کپی خودکار انجام نشد. اطلاعات تماس را انتخاب و کپی کنید.'; });
        });
    });
    page.querySelectorAll('[data-contact-faq]').forEach(function (item) {
        item.addEventListener('toggle', function () {
            if (!item.open) return;
            page.querySelectorAll('[data-contact-faq]').forEach(function (other) { if (other !== item) other.open = false; });
        });
    });
    form.elements.message.addEventListener('input', updateCounter);
    form.addEventListener('input', function (event) { if (event.target.name) fieldError(event.target.name, ''); });
    form.addEventListener('change', function (event) { if (event.target.name) fieldError(event.target.name, ''); });
    attachment.addEventListener('change', function () { updateAttachment(); validAttachment(); });
    attachmentRemove.addEventListener('click', function () { attachment.value = ''; updateAttachment(); fieldError('attachment', ''); attachment.focus(); });
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (sending || !validForm()) return;
        var endpoint;
        try { endpoint = new URL(config.ajaxUrl, window.location.href); } catch (error) { showStatus('ارتباط با سامانه برقرار نشد. صفحه را دوباره بارگذاری کنید.', false); return; }
        if (!config.nonce || endpoint.origin !== window.location.origin) { showStatus('ارتباط با سامانه برقرار نشد. صفحه را دوباره بارگذاری کنید.', false); return; }
        var body = new FormData(form);
        body.set('action', 'genosys_contact_submit');
        body.set('nonce', config.nonce);
        body.set('mobile', normalizePhone(form.elements.mobile.value));
        body.set('fullname', form.elements.fullname.value.trim());
        body.set('email', form.elements.email.value.trim());
        body.set('message', form.elements.message.value.trim());
        sending = true;
        status.hidden = true;
        submit.disabled = true;
        form.setAttribute('aria-busy', 'true');
        submitLabel.textContent = 'در حال ارسال…';
        var controller = new AbortController();
        var timeout = setTimeout(function () { controller.abort(); }, 35000);
        fetch(endpoint.href, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: body, signal: controller.signal })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result || result.success !== true) {
                    var data = result && result.data || {};
                    Object.keys(data.fields || {}).forEach(function (name) { if (/^(fullname|mobile|email|topic|message|consent|attachment)$/.test(name)) fieldError(name, String(data.fields[name])); });
                    var failure = new Error('contact_submit');
                    failure.contactMessage = typeof data.message === 'string' && data.message ? data.message : 'پیام ثبت نشد. لطفاً دوباره تلاش کنید.';
                    throw failure;
                }
                form.reset();
                clearErrors();
                updateCounter();
                updateAttachment();
                showStatus(result.data && result.data.message || 'پیام شما با موفقیت ثبت شد.', true);
            }).catch(function (error) { showStatus(error.name === 'AbortError' ? 'پاسخ سامانه دریافت نشد. پیش از ارسال دوباره، اتصال خود را بررسی کنید.' : error.contactMessage || 'ارسال پیام انجام نشد. اتصال خود را بررسی و دوباره تلاش کنید.', false); })
            .finally(function () { clearTimeout(timeout); sending = false; submit.disabled = false; form.removeAttribute('aria-busy'); submitLabel.textContent = originalSubmitLabel; });
    });
    updateCounter();
    updateAttachment();
})();
