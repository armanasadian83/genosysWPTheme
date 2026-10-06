/* Visual-only three-step customer-club questionnaire. Nothing is submitted. */
(function () {
    'use strict';

    var form = document.getElementById('fans-club-form');
    if (!form) return;

    var card = document.getElementById('fans-club-form-card');
    var steps = Array.prototype.slice.call(form.querySelectorAll('.fans-club-step'));
    var progress = Array.prototype.slice.call(document.querySelectorAll('.fans-club-progress li'));
    var name = document.getElementById('fans-name');
    var phone = document.getElementById('fans-phone');
    var skinGroup = form.querySelector('[data-choice-group="skin"]');
    var currentStep = 0;
    var persianNumber = new Intl.NumberFormat('fa-IR', { useGrouping: false });

    function showError(input, errorId, invalid) {
        var error = document.getElementById(errorId);
        input.classList.toggle('is-invalid', invalid);
        if (invalid) input.setAttribute('aria-invalid', 'true');
        else input.removeAttribute('aria-invalid');
        error.hidden = !invalid;
    }

    function normaliseDigits(value) {
        return value.replace(/[۰-۹]/g, function (digit) { return String(digit.charCodeAt(0) - 1776); })
            .replace(/[٠-٩]/g, function (digit) { return String(digit.charCodeAt(0) - 1632); });
    }

    function validPhone() {
        var value = normaliseDigits(phone.value).replace(/[\s\-]/g, '').replace(/^\+98/, '0').replace(/^0098/, '0');
        return /^09\d{9}$/.test(value);
    }

    function validateDetails() {
        var nameInvalid = name.value.trim() === '';
        var phoneInvalid = !validPhone();
        showError(name, 'fans-name-error', nameInvalid);
        showError(phone, 'fans-phone-error', phoneInvalid);
        if (nameInvalid) name.focus();
        else if (phoneInvalid) phone.focus();
        return !nameInvalid && !phoneInvalid;
    }

    function validateSkin() {
        var chosen = skinGroup.querySelector('[aria-pressed="true"]');
        document.getElementById('fans-skin-error').hidden = !!chosen;
        if (!chosen) skinGroup.querySelector('button').focus();
        return !!chosen;
    }

    function showStep(index) {
        currentStep = index;
        steps.forEach(function (step, stepIndex) { step.hidden = stepIndex !== index; });
        progress.forEach(function (item, stepIndex) {
            item.classList.toggle('is-complete', stepIndex < index);
            item.classList.toggle('is-current', stepIndex === index);
            if (stepIndex === index) item.setAttribute('aria-current', 'step');
            else item.removeAttribute('aria-current');
        });
        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        card.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    }

    form.addEventListener('submit', function (event) { event.preventDefault(); });
    name.addEventListener('input', function () {
        if (name.classList.contains('is-invalid')) showError(name, 'fans-name-error', name.value.trim() === '');
    });
    phone.addEventListener('input', function () {
        if (phone.classList.contains('is-invalid')) showError(phone, 'fans-phone-error', !validPhone());
    });

    form.querySelectorAll('[data-next]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (currentStep === 0 && !validateDetails()) return;
            if (currentStep === 1 && !validateSkin()) return;
            showStep(Math.min(currentStep + 1, steps.length - 1));
        });
    });
    form.querySelectorAll('[data-prev]').forEach(function (button) {
        button.addEventListener('click', function () { showStep(Math.max(currentStep - 1, 0)); });
    });

    form.querySelectorAll('[data-choice-group]').forEach(function (group) {
        group.querySelectorAll('button').forEach(function (button) {
            button.addEventListener('click', function () {
                if (group.getAttribute('data-single') === 'true') {
                    group.querySelectorAll('button').forEach(function (choice) { choice.setAttribute('aria-pressed', 'false'); });
                    button.setAttribute('aria-pressed', 'true');
                    if (group === skinGroup) document.getElementById('fans-skin-error').hidden = true;
                } else {
                    button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
                }
            });
        });
    });

    var stars = Array.prototype.slice.call(form.querySelectorAll('[data-rating]'));
    stars.forEach(function (star) {
        star.addEventListener('click', function () {
            var selected = Number(star.getAttribute('data-rating'));
            stars.forEach(function (choice) {
                var rating = Number(choice.getAttribute('data-rating'));
                choice.classList.toggle('is-selected', rating <= selected);
                choice.setAttribute('aria-checked', rating === selected ? 'true' : 'false');
            });
        });
    });

    var range = document.getElementById('fans-recommend');
    var output = document.getElementById('fans-recommend-value');
    range.addEventListener('input', function () {
        output.textContent = persianNumber.format(Number(range.value));
        range.style.setProperty('--range-fill', (Number(range.value) * 10) + '%');
        range.setAttribute('aria-valuetext', output.textContent);
    });
    range.setAttribute('aria-valuetext', output.textContent);
}());
