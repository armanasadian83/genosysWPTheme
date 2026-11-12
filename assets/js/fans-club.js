/* Three-step club questionnaires with explicit, server-confirmed enrollment. */
(function () {
    'use strict';

    var page = document.querySelector('[data-fans-club]');
    if (!page) return;

    var card = document.getElementById('fans-club-form-card');
    var tabs = Array.prototype.slice.call(page.querySelectorAll('[data-fans-audience]'));
    var panels = Array.prototype.slice.call(page.querySelectorAll('[data-fans-panel]'));
    var persianNumber = new Intl.NumberFormat('fa-IR', { useGrouping: false });
    var registration = window.genosysClubRegistration || {};
    var enrollment = {};
    try { enrollment = JSON.parse(page.querySelector('[data-fans-context]').textContent); } catch (_) { /* Server controls remain authoritative. */ }
    var sending = false;
    var accountPhoneInput = page.querySelector('[data-fans-account-phone]');
    var accountPhone = normalisePhone(enrollment.phone || (accountPhoneInput ? accountPhoneInput.value : ''));
    var accountIdentityKey = enrollment.identityKey || '';

    function registrationError(details) {
        var error = new Error(details && details.message || 'ثبت اطلاعات انجام نشد. دوباره تلاش کنید.');
        error.details = details || {};
        return error;
    }

    function postRegistration(action, values) {
        var endpoint;
        try { endpoint = new URL(registration.ajaxUrl, window.location.href); } catch (error) { return Promise.reject(new Error('ارتباط با سامانه برقرار نشد.')); }
        if (endpoint.origin !== window.location.origin) return Promise.reject(new Error('نشانی سامانه معتبر نیست.'));
        var body = new URLSearchParams();
        var controller = new AbortController();
        var timeout = setTimeout(function () { controller.abort(); }, 20000);
        body.set('action', action);
        Object.keys(values || {}).forEach(function (key) { body.set(key, values[key]); });
        return fetch(endpoint.href, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: body, signal: controller.signal })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (!result || !result.success) throw registrationError(result && result.data);
                return result.data;
            }).catch(function (error) {
                if (error.name === 'AbortError') throw new Error('پاسخ سامانه دریافت نشد. برای بررسی ثبت همین درخواست، دوباره تلاش کنید.');
                throw error;
            }).finally(function () { clearTimeout(timeout); });
    }

    function requestId() {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
    }

    function normaliseDigits(value) {
        return String(value).replace(/[۰-۹]/g, function (digit) { return String(digit.charCodeAt(0) - 1776); })
            .replace(/[٠-٩]/g, function (digit) { return String(digit.charCodeAt(0) - 1632); });
    }

    function normalisePhone(value) {
        return normaliseDigits(value).replace(/[\s\-()]/g, '').replace(/^\+98/, '0').replace(/^0098/, '0');
    }

    function validPhone(input, landline) {
        var value = normalisePhone(input.value);
        return landline ? /^0[1-8]\d{9}$/.test(value) : /^09\d{9}$/.test(value);
    }

    function updateAccountPhone(phone) {
        accountPhone = normalisePhone(phone || '');
        page.querySelectorAll('[data-fans-account-phone]').forEach(function (input) {
            input.value = accountPhone;
            input.readOnly = true;
        });
    }

    function audienceContext(audience) {
        return enrollment.audiences && enrollment.audiences[audience] || { canSubmit: false, alreadySubmitted: false, message: enrollment.message || 'برای بررسی وضعیت عضویت، صفحه را دوباره بارگذاری کنید.' };
    }

    function applyEnrollmentContext(context, selectedAudience) {
        if (!context || !context.audiences) return;
        enrollment = context;
        page.setAttribute('data-enrollment-state', context.canSubmit ? 'available' : 'unavailable');
        panels.forEach(function (panel) {
            var audience = panel.getAttribute('data-fans-panel');
            var current = audienceContext(audience);
            var state = panel.querySelector('[data-fans-membership]');
            if (!current.canSubmit) {
                Array.prototype.slice.call(panel.children).forEach(function (child) { if (child !== state) child.remove(); });
            }
            var needsReload = !!current.canSubmit && !panel.querySelector('[data-fans-wizard]');
            state.hidden = !!current.canSubmit && !needsReload;
            state.querySelector('[data-fans-reload]').hidden = !needsReload;
            state.querySelector('[data-fans-membership-message]').textContent = needsReload ? 'ثبت‌نام در این نوع عضویت دوباره امکان‌پذیر است. برای دریافت پرسشنامه‌ی تازه، دکمه‌ی زیر را بزنید.' : (current.message || context.message || 'برای بررسی وضعیت عضویت، صفحه را دوباره بارگذاری کنید.');
        });
        tabs.forEach(function (tab) {
            var current = audienceContext(tab.getAttribute('data-fans-audience'));
            tab.setAttribute('data-registered', current.alreadySubmitted ? 'true' : 'false');
            tab.querySelector('[data-fans-registration-badge]').hidden = !current.alreadySubmitted;
        });
        page.querySelectorAll('[data-fans-switch-to]').forEach(function (control) { control.hidden = !audienceContext(control.getAttribute('data-fans-switch-to')).canSubmit; });
        setAudience(selectedAudience || page.getAttribute('data-audience'), false, true);
    }

    function checkedContext(prepared, audience) {
        var state = prepared && prepared.audiences && prepared.audiences[audience];
        if (!prepared || !state || !state.canSubmit) {
            var error = registrationError(prepared);
            if (state && state.message) error.message = state.message;
            error.blockedAudience = audience;
            throw error;
        }
        return prepared;
    }

    function setAudience(audience, focusTab, force) {
        if (sending && !force) return;
        if (audience !== 'consumer' && audience !== 'clinic') return;
        page.setAttribute('data-audience', audience);
        tabs.forEach(function (tab) {
            var selected = tab.getAttribute('data-fans-audience') === audience;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.tabIndex = selected ? 0 : -1;
            if (selected && focusTab) tab.focus();
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-fans-panel') !== audience;
        });
        page.querySelectorAll('[data-fans-copy]').forEach(function (node) {
            var copy = node.getAttribute('data-' + audience);
            if (copy !== null) node.textContent = copy;
        });
        var available = !!audienceContext(audience).canSubmit;
        page.classList.toggle('fans-club-page--membership', !available);
        page.querySelector('[data-fans-hero-join]').hidden = !available;
        page.querySelector('[data-fans-hero-member]').hidden = available;
        page.querySelector('.fans-club-hero').setAttribute('aria-labelledby', available ? 'fans-club-title' : 'fans-club-member-title');
    }

    page.querySelectorAll('[data-fans-switch-to]').forEach(function (control) {
        control.addEventListener('click', function () { setAudience(control.getAttribute('data-fans-switch-to'), true); });
    });

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function () { setAudience(tab.getAttribute('data-fans-audience'), false); });
        tab.addEventListener('keydown', function (event) {
            var nextIndex;
            // The selector is RTL: the left arrow moves to the next visual tab.
            if (event.key === 'ArrowLeft') nextIndex = (index + 1) % tabs.length;
            else if (event.key === 'ArrowRight') nextIndex = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = tabs.length - 1;
            else return;
            event.preventDefault();
            setAudience(tabs[nextIndex].getAttribute('data-fans-audience'), true);
        });
    });

    page.querySelectorAll('[data-fans-wizard]').forEach(function (form) {
        var audience = form.getAttribute('data-fans-wizard');
        var panel = form.closest('[data-fans-panel]');
        var steps = Array.prototype.slice.call(form.querySelectorAll('.fans-club-step'));
        var progress = Array.prototype.slice.call(panel.querySelectorAll('.fans-club-progress li'));
        var currentStep = 0;
        var feedback = panel.querySelector('[data-fans-feedback]');
        var submitted = false;
        var retryId = '';
        var retryPayload = '';
        var announcement = document.createElement('p');
        announcement.className = 'fans-club-sr-only';
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        panel.appendChild(announcement);

        if (!feedback) {
            feedback = document.createElement('p');
            feedback.className = 'fans-club-feedback';
            feedback.setAttribute('data-fans-feedback', '');
            feedback.setAttribute('role', 'status');
            feedback.hidden = true;
            form.appendChild(feedback);
        }

        function ensureError(input, id, message) {
            var error = document.getElementById(id);
            if (!error) {
                error = document.createElement('p');
                error.id = id;
                error.className = 'fans-club-error';
                error.textContent = message;
                error.hidden = true;
                var consent = input.closest('.fans-club-consent');
                if (consent) consent.insertAdjacentElement('afterend', error);
                else (input.closest('fieldset') || input.parentElement).appendChild(error);
            }
            var describedBy = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
            if (describedBy.indexOf(id) < 0) describedBy.push(id);
            input.setAttribute('aria-describedby', describedBy.join(' '));
            return error;
        }

        function showError(input, id, invalid, message) {
            if (!input) return false;
            var error = ensureError(input, id, message || 'این مورد را تکمیل کنید.');
            input.classList.toggle('is-invalid', invalid);
            if (invalid) input.setAttribute('aria-invalid', 'true');
            else input.removeAttribute('aria-invalid');
            error.hidden = !invalid;
            return invalid;
        }

        function validateStep(index) {
            var invalid = [];
            function field(id, fails, message) {
                var input = form.querySelector('#' + id);
                if (input && showError(input, id + '-error', fails(input), message)) invalid.push(input);
            }
            function group(key, errorId, message) {
                var input = form.querySelector('[data-choice-group="' + key + '"]');
                if (input && showError(input, errorId, !input.querySelector('[aria-pressed="true"]'), message)) {
                    invalid.push(input.querySelector('button') || input);
                }
            }
            if (audience === 'clinic') {
                if (index === 0) {
                    field('clinic-name', function (input) { return !input.value.trim(); });
                    field('clinic-contact', function (input) { return !input.value.trim(); });
                    field('clinic-phone', function (input) { return !validPhone(input, false); });
                    field('clinic-landline', function (input) { return !!input.value.trim() && !validPhone(input, true); });
                } else if (index === 1) {
                    field('clinic-experience', function (input) { return !input.value; });
                    group('clinic-services', 'clinic-services-error', 'حداقل یک نوع خدمت را انتخاب کنید.');
                } else if (index === 2) {
                    group('clinic-clients', 'clinic-clients-error', 'مشتریان خود را مشخص کنید.');
                    field('clinic-consumption', function (input) { return !input.value; });
                    field('clinic-consent', function (input) { return !input.checked; }, 'برای ادامه، موافقت خود را مشخص کنید.');
                }
            } else {
                if (index === 0) {
                    field('fans-name', function (input) { return !input.value.trim(); });
                    field('fans-phone', function (input) { return !validPhone(input, false); });
                } else if (index === 1) {
                    group('skin', 'fans-skin-error', 'نوع پوست خود را انتخاب کنید.');
                } else if (index === 2) {
                    var rating = form.querySelector('.fans-club-stars');
                    if (rating && showError(rating, 'fans-rating-error', !rating.querySelector('[aria-checked="true"]'), 'میزان رضایت خود را انتخاب کنید.')) {
                        invalid.push(rating.querySelector('button'));
                    }
                    field('fans-consent', function (input) { return !input.checked; }, 'برای ادامه، موافقت خود را مشخص کنید.');
                }
            }
            return invalid;
        }

        function showStep(index, shouldScroll) {
            currentStep = index;
            steps.forEach(function (step, stepIndex) { step.hidden = stepIndex !== index; });
            progress.forEach(function (item, stepIndex) {
                item.classList.toggle('is-complete', stepIndex < index);
                item.classList.toggle('is-current', stepIndex === index);
                if (stepIndex === index) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            var label = progress[index] ? progress[index].textContent.trim() : '';
            announcement.textContent = 'مرحله‌ی ' + persianNumber.format(index + 1) + ' از ' + persianNumber.format(steps.length) + ': ' + label;
            if (shouldScroll !== false && card) {
                var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                card.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
                var heading = steps[index].querySelector('h3, h2') || panel.querySelector('.fans-club-form-card__header h2');
                if (heading) {
                    heading.tabIndex = -1;
                    heading.focus({ preventScroll: true });
                }
            }
        }

        function payload() {
            function value(id) { var input = form.querySelector('#' + id); return input ? input.value.trim() : ''; }
            function choices(key) { return Array.prototype.map.call(form.querySelectorAll('[data-choice-group="' + key + '"] [aria-pressed="true"]'), function (button) { return button.textContent.trim(); }); }
            if (audience === 'clinic') return {
                audience: audience, clinic_name: value('clinic-name'), contact_name: value('clinic-contact'),
                contact_phone: accountPhone, clinic_landline: normalisePhone(value('clinic-landline')),
                clinic_city: value('clinic-city'), activity: value('clinic-activity'), experience: value('clinic-experience'),
                services: choices('clinic-services'), client_needs: choices('clinic-clients'), monthly_consumption: value('clinic-consumption'),
                monthly_clients: value('clinic-visits'), clinic_message: value('clinic-message'), consent: !!form.querySelector('#clinic-consent').checked
            };
            var selected = form.querySelector('[data-rating][aria-checked="true"]');
            return {
                audience: audience, full_name: value('fans-name'), phone: accountPhone, email: value('fans-email'),
                city: value('fans-city'), age_range: value('fans-age'), skin: choices('skin')[0] || '', concerns: choices('concerns'),
                categories: choices('categories'), rating: selected ? Number(selected.getAttribute('data-rating')) : 0,
                recommend: Number(value('fans-recommend')), source: value('fans-source'), message: value('fans-message'),
                consent: !!form.querySelector('#fans-consent').checked
            };
        }

        function finish() {
            if (sending || submitted) return;
            for (var index = 0; index < steps.length; index += 1) {
                var invalid = validateStep(index);
                if (invalid.length) {
                    showStep(index, true);
                    invalid[0].focus({ preventScroll: true });
                    return;
                }
            }
            var data = JSON.stringify(payload());
            if (!retryId || data !== retryPayload) { retryId = requestId(); retryPayload = data; }
            var disabled = [];
            sending = true;
            page.setAttribute('aria-busy', 'true');
            page.querySelectorAll('button, input, select, textarea').forEach(function (control) {
                if (!control.disabled) { disabled.push(control); control.disabled = true; }
            });
            feedback.classList.remove('is-error', 'is-success');
            feedback.textContent = 'در حال ثبت اطلاعات…';
            feedback.hidden = false;
            var honeypot = form.querySelector('[name="website"]');
            postRegistration(registration.prepareAction || 'genosys_club_prepare', { audience: audience })
                .then(function (prepared) {
                    if (accountIdentityKey && prepared.identityKey !== accountIdentityKey) {
                        window.location.reload();
                        throw new Error('نشست حساب کاربری تغییر کرده است. صفحه برای دریافت اطلاعات حساب فعلی تازه می‌شود.');
                    }
                    applyEnrollmentContext(prepared, audience);
                    checkedContext(prepared, audience);
                    var previousPhone = accountPhone;
                    updateAccountPhone(prepared.phone);
                    if (previousPhone !== accountPhone) {
                        throw new Error('شماره موبایل حساب شما تغییر کرده است. شماره‌ی جدید در فرم قرار گرفت؛ پس از بررسی، دوباره ثبت عضویت را بزنید.');
                    }
                    return postRegistration(registration.registerAction || 'genosys_club_register', {
                        nonce: prepared.nonce, request_id: retryId, data: data, website: honeypot ? honeypot.value : ''
                    });
                })
                .then(function (result) {
                    submitted = true;
                    applyEnrollmentContext(result, audience);
                })
                .catch(function (error) {
                    if (error.details && error.details.audiences) applyEnrollmentContext(error.details, audience);
                    if (error.blockedAudience || error.details && error.details.audiences && !audienceContext(audience).canSubmit) {
                        submitted = true;
                        return;
                    }
                    if (error.details && error.details.authRequired) {
                        feedback.textContent = 'برای ادامه، دوباره وارد حساب کاربری خود شوید.';
                        window.location.reload();
                        return;
                    }
                    feedback.classList.add('is-error');
                    feedback.textContent = error.message || 'ارتباط برقرار نشد. اطلاعات شما در فرم باقی مانده است؛ دوباره تلاش کنید.';
                    if (error.details && (error.details.phoneChanged || error.details.identityChanged)) {
                        return postRegistration(registration.prepareAction || 'genosys_club_prepare', { audience: audience })
                            .then(function (prepared) {
                                if (accountIdentityKey && prepared.identityKey !== accountIdentityKey) { window.location.reload(); return; }
                                applyEnrollmentContext(prepared, audience); checkedContext(prepared, audience); updateAccountPhone(prepared.phone);
                            })
                            .catch(function (refreshError) {
                                if (refreshError.details && refreshError.details.audiences) applyEnrollmentContext(refreshError.details, audience);
                                if (refreshError.blockedAudience) {
                                    submitted = true;
                                } else if (refreshError.details && refreshError.details.authRequired) window.location.reload();
                            });
                    }
                })
                .finally(function () {
                    sending = false;
                    page.removeAttribute('aria-busy');
                    disabled.forEach(function (control) { control.disabled = false; });
                    if (submitted) {
                        var membershipTitle = document.getElementById('fans-club-membership-' + audience);
                        if (membershipTitle) membershipTitle.focus({ preventScroll: true });
                        if (card) card.scrollIntoView({ behavior: 'auto', block: 'start' });
                    } else feedback.focus({ preventScroll: true });
                });
            feedback.tabIndex = -1;
            feedback.focus({ preventScroll: true });
        }

        function advance() {
            if (sending || submitted) return;
            feedback.hidden = true;
            if (currentStep === steps.length - 1) { finish(); return; }
            var invalid = validateStep(currentStep);
            if (invalid.length) { invalid[0].focus(); return; }
            showStep(currentStep + 1, true);
        }

        form.addEventListener('submit', function (event) { event.preventDefault(); advance(); });
        form.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && event.target.matches('input:not([type="checkbox"]):not([type="range"])')) {
                event.preventDefault();
                advance();
            }
        });
        form.querySelectorAll('[data-next]').forEach(function (button) { button.addEventListener('click', advance); });
        form.querySelectorAll('[data-prev]').forEach(function (button) {
            button.addEventListener('click', function () { feedback.hidden = true; showStep(Math.max(currentStep - 1, 0), true); });
        });
        form.querySelectorAll('[data-register]').forEach(function (button) { button.addEventListener('click', finish); });
        form.querySelectorAll('input, select').forEach(function (input) {
            function revalidate() {
                feedback.hidden = true;
                if (input.classList.contains('is-invalid')) validateStep(currentStep);
            }
            input.addEventListener('input', revalidate);
            input.addEventListener('change', revalidate);
        });
        form.querySelectorAll('[data-choice-group]').forEach(function (group) {
            group.querySelectorAll('button').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (group.getAttribute('data-single') === 'true') {
                        group.querySelectorAll('button').forEach(function (choice) { choice.setAttribute('aria-pressed', 'false'); });
                        button.setAttribute('aria-pressed', 'true');
                    } else {
                        button.setAttribute('aria-pressed', button.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
                    }
                    feedback.hidden = true;
                    if (group.classList.contains('is-invalid')) validateStep(currentStep);
                });
            });
        });

        var stars = Array.prototype.slice.call(form.querySelectorAll('[data-rating]'));
        function selectRating(selected) {
            stars.forEach(function (choice) {
                var rating = Number(choice.getAttribute('data-rating'));
                choice.classList.toggle('is-selected', rating <= selected);
                choice.setAttribute('aria-checked', rating === selected ? 'true' : 'false');
                choice.tabIndex = rating === selected ? 0 : -1;
            });
            feedback.hidden = true;
            var ratingGroup = form.querySelector('.fans-club-stars');
            if (ratingGroup && ratingGroup.classList.contains('is-invalid')) validateStep(currentStep);
        }
        stars.forEach(function (star, index) {
            star.tabIndex = index === 0 ? 0 : -1;
            star.addEventListener('click', function () { selectRating(Number(star.getAttribute('data-rating'))); });
            star.addEventListener('keydown', function (event) {
                var nextIndex;
                if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') nextIndex = (index + 1) % stars.length;
                else if (event.key === 'ArrowRight' || event.key === 'ArrowUp') nextIndex = (index + stars.length - 1) % stars.length;
                else if (event.key === 'Home') nextIndex = 0;
                else if (event.key === 'End') nextIndex = stars.length - 1;
                else return;
                event.preventDefault();
                selectRating(Number(stars[nextIndex].getAttribute('data-rating')));
                stars[nextIndex].focus();
            });
        });

        var range = form.querySelector('#fans-recommend');
        var output = form.querySelector('#fans-recommend-value');
        if (range && output) {
            range.addEventListener('input', function () {
                output.textContent = persianNumber.format(Number(range.value));
                range.style.setProperty('--range-fill', (Number(range.value) * 10) + '%');
                range.setAttribute('aria-valuetext', output.textContent);
            });
            range.setAttribute('aria-valuetext', output.textContent);
        }
        showStep(0, false);
    });

    setAudience(page.getAttribute('data-audience') || 'clinic', false);

    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        // A restored browser page must recheck membership and account identity.
        window.location.reload();
    });
}());
