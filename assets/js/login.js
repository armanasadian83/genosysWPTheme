(function () {
    'use strict';

    function init() {
        var phoneForm = document.getElementById('genosys-phone-form');
        var codeForm = document.getElementById('genosys-code-form');
        var profileForm = document.getElementById('genosys-profile-form');
        if (!phoneForm || !codeForm || !profileForm) return;

        var settings = window.genosysLogin || {};
        var phoneInput = document.getElementById('genosys-login-phone');
        var phoneError = document.getElementById('genosys-phone-error');
        var codeError = document.getElementById('genosys-code-error');
        var phoneSubmit = phoneForm.querySelector('button[type="submit"]');
        var codeSubmit = codeForm.querySelector('button[type="submit"]');
        var codeFields = Array.prototype.slice.call(codeForm.querySelectorAll('.genosys-login__code-fields input'));
        var codeGroup = codeForm.querySelector('.genosys-login__code-fields');
        var codePhone = document.getElementById('genosys-code-phone');
        var title = document.querySelector('[data-login-title]');
        var subtitle = document.querySelector('[data-login-subtitle]');
        var editPhone = document.getElementById('genosys-edit-phone');
        var resend = document.getElementById('genosys-resend-code');
        var timer = document.getElementById('genosys-code-timer');
        var profileSubmit = profileForm.querySelector('button[type="submit"]');
        var profileError = document.getElementById('genosys-profile-error');
        var profileFields = {
            first_name: document.getElementById('genosys-profile-first-name'),
            last_name: document.getElementById('genosys-profile-last-name'),
            email: document.getElementById('genosys-profile-email')
        };
        var profileFieldErrors = {
            first_name: document.getElementById('genosys-profile-first-name-error'),
            last_name: document.getElementById('genosys-profile-last-name-error'),
            email: document.getElementById('genosys-profile-email-error')
        };
        var intervalId = null;
        var endsAt = 0;
        var activePhone = '';
        var requestId = '';
        var registrationToken = '';
        var requesting = false;
        var verifying = false;
        var completing = false;

        function asciiDigits(value) {
            return String(value).replace(/[۰-۹٠-٩]/g, function (digit) {
                var code = digit.charCodeAt(0);
                return String(code >= 0x6f0 ? code - 0x6f0 : code - 0x660);
            });
        }

        function persianDigits(value) {
            return String(value).replace(/[0-9]/g, function (digit) {
                return '۰۱۲۳۴۵۶۷۸۹'[Number(digit)];
            });
        }

        function normalizePhone(value) {
            var phone = asciiDigits(value).replace(/[\s\-()]/g, '');
            if (/^\+98\d{10}$/.test(phone)) return '0' + phone.slice(3);
            if (/^0098\d{10}$/.test(phone)) return '0' + phone.slice(4);
            if (/^98\d{10}$/.test(phone)) return '0' + phone.slice(2);
            if (/^9\d{9}$/.test(phone)) return '0' + phone;
            return phone;
        }

        function showPhoneError(message, invalid) {
            phoneError.textContent = message;
            phoneError.hidden = false;
            phoneInput.setAttribute('aria-invalid', invalid ? 'true' : 'false');
        }

        function hidePhoneError() {
            phoneError.hidden = true;
            phoneInput.setAttribute('aria-invalid', 'false');
        }

        function showCodeError(message) {
            codeError.textContent = message;
            codeError.hidden = false;
            codeGroup.classList.add('has-error');
            codeFields.forEach(function (field) { field.setAttribute('aria-invalid', 'true'); });
        }

        function hideCodeError() {
            codeError.hidden = true;
            codeGroup.classList.remove('has-error');
            codeFields.forEach(function (field) { field.setAttribute('aria-invalid', 'false'); });
        }

        function showProfileFieldError(name, message) {
            profileFieldErrors[name].textContent = message;
            profileFieldErrors[name].hidden = false;
            profileFields[name].setAttribute('aria-invalid', 'true');
        }

        function hideProfileFieldError(name) {
            profileFieldErrors[name].hidden = true;
            profileFields[name].setAttribute('aria-invalid', 'false');
        }

        function showProfileError(message) {
            profileError.textContent = message;
            profileError.hidden = false;
        }

        function hideProfileError() {
            profileError.hidden = true;
            profileError.textContent = '';
        }

        function redirectHome(target) {
            var destination = new URL(target || settings.homeUrl || '/', window.location.href);
            var home = new URL(settings.homeUrl || '/', window.location.href);
            window.location.assign(destination.origin === window.location.origin ? destination.href : home.href);
        }

        function updateTimer() {
            var seconds = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
            var minutesText = String(Math.floor(seconds / 60)).padStart(2, '0');
            var secondsText = String(seconds % 60).padStart(2, '0');
            timer.textContent = persianDigits(minutesText + ':' + secondsText);
            resend.disabled = requesting || verifying || seconds > 0;
            if (seconds === 0 && intervalId !== null) {
                clearInterval(intervalId);
                intervalId = null;
            }
        }

        function stopTimer() {
            if (intervalId !== null) clearInterval(intervalId);
            intervalId = null;
            endsAt = 0;
            timer.textContent = '۰۰:۰۰';
        }

        function startTimer(duration) {
            if (intervalId !== null) clearInterval(intervalId);
            var seconds = Number(duration);
            endsAt = Date.now() + (Number.isFinite(seconds) && seconds > 0 ? seconds : 120) * 1000;
            updateTimer();
            intervalId = setInterval(updateTimer, 250);
        }

        function setRequestBusy(busy) {
            requesting = busy;
            phoneSubmit.disabled = busy;
            codeSubmit.disabled = busy || verifying;
            editPhone.disabled = busy || verifying;
            resend.disabled = busy || verifying || Date.now() < endsAt;
            phoneForm.setAttribute('aria-busy', String(busy));
            codeForm.setAttribute('aria-busy', String(busy));
        }

        function setVerifyBusy(busy) {
            verifying = busy;
            codeSubmit.disabled = busy || requesting;
            editPhone.disabled = busy || requesting;
            resend.disabled = busy || requesting || Date.now() < endsAt;
            codeForm.setAttribute('aria-busy', String(busy));
        }

        function post(action, fields) {
            if (!settings.ajaxUrl || !settings.nonce) {
                return Promise.reject(new Error('در حال حاضر امکان اتصال به سرویس ورود وجود ندارد. لطفاً دوباره تلاش کنید.'));
            }

            var data = new FormData();
            data.append('action', action);
            data.append('nonce', settings.nonce);
            data.append('redirect_to', settings.returnUrl || settings.homeUrl || '/');
            Object.keys(fields).forEach(function (key) { data.append(key, fields[key]); });

            return fetch(settings.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                body: data
            }).then(function (response) {
                return response.json().then(function (result) {
                    if (!response.ok || !result || result.success !== true) {
                        var message = result && result.data && result.data.message;
                        var error = new Error(message || 'درخواست انجام نشد. لطفاً دوباره تلاش کنید.');
                        error.code = result && result.data && result.data.code;
                        error.field = result && result.data && result.data.field;
                        throw error;
                    }
                    return result.data || {};
                });
            }).catch(function (error) {
                if (error instanceof SyntaxError || error instanceof TypeError) {
                    throw new Error('ارتباط با سرویس ورود برقرار نشد. لطفاً دوباره تلاش کنید.');
                }
                throw error;
            });
        }

        function showCodeScreen(phone, duration) {
            hidePhoneError();
            hideCodeError();
            codeFields.forEach(function (field) { field.value = ''; });
            codePhone.textContent = persianDigits(phone);
            title.textContent = 'کد تأیید';
            phoneForm.hidden = true;
            codeForm.hidden = false;
            startTimer(duration);
            codeFields[0].focus();
        }

        function showProfileScreen(token) {
            registrationToken = token;
            requestId = '';
            stopTimer();
            title.textContent = 'تکمیل اطلاعات';
            if (subtitle) subtitle.textContent = 'شماره‌ی شما تأیید شد؛ یک قدم تا ورود';
            codeForm.hidden = true;
            phoneForm.hidden = true;
            profileForm.hidden = false;
            profileFields.first_name.focus();
        }

        function requestCode(phone, isResend) {
            if (requesting) return;
            setRequestBusy(true);
            post('genosys_sms_request', { phone: phone }).then(function (data) {
                if (!data.request_id) {
                    throw new Error('کد تأیید دریافت نشد. لطفاً دوباره تلاش کنید.');
                }
                activePhone = phone;
                requestId = String(data.request_id);
                if (isResend) {
                    hideCodeError();
                    codeFields.forEach(function (field) { field.value = ''; });
                    startTimer(data.expires_in);
                    codeFields[0].focus();
                } else {
                    showCodeScreen(phone, data.expires_in);
                }
            }).catch(function (error) {
                if (isResend) {
                    showCodeError(error.message);
                } else {
                    showPhoneError(error.message, false);
                }
            }).finally(function () {
                setRequestBusy(false);
            });
        }

        phoneForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (requesting) return;
            var phone = normalizePhone(phoneInput.value);
            if (!/^09\d{9}$/.test(phone)) {
                showPhoneError('شماره تماس معتبر نیست (مثال: 09123456789).', true);
                phoneInput.focus();
                return;
            }
            phoneInput.value = phone;
            hidePhoneError();
            requestCode(phone, false);
        });

        phoneInput.addEventListener('input', hidePhoneError);

        function fillCode(startIndex, value) {
            var digits = asciiDigits(value).replace(/\D/g, '').slice(0, 6 - startIndex);
            if (!digits) return;
            digits.split('').forEach(function (digit, offset) {
                codeFields[startIndex + offset].value = digit;
            });
            hideCodeError();
            codeFields[Math.min(startIndex + digits.length, 5)].focus();
        }

        codeFields.forEach(function (field, index) {
            field.addEventListener('input', function () {
                var digits = asciiDigits(field.value).replace(/\D/g, '');
                field.value = '';
                hideCodeError();
                fillCode(index, digits);
            });
            field.addEventListener('paste', function (event) {
                var pasted = event.clipboardData && event.clipboardData.getData('text');
                if (!pasted) return;
                event.preventDefault();
                fillCode(index, pasted);
            });
            field.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !field.value && index > 0) {
                    codeFields[index - 1].value = '';
                    codeFields[index - 1].focus();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    codeFields[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < 5) {
                    event.preventDefault();
                    codeFields[index + 1].focus();
                }
            });
        });

        codeForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (verifying) return;
            var complete = codeFields.every(function (field) { return /^\d$/.test(field.value); });
            if (!complete) {
                showCodeError('کد ۶ رقمی را کامل وارد کنید.');
                var firstEmpty = codeFields.find(function (field) { return !field.value; });
                if (firstEmpty) firstEmpty.focus();
                return;
            }
            if (!requestId) {
                showCodeError('ابتدا یک کد تأیید جدید دریافت کنید.');
                return;
            }
            hideCodeError();
            setVerifyBusy(true);
            post('genosys_sms_verify', {
                request_id: requestId,
                code: codeFields.map(function (field) { return field.value; }).join('')
            }).then(function (data) {
                if (data.needs_profile) {
                    if (!data.registration_token) {
                        throw new Error('تأیید شماره کامل نشد. لطفاً کد جدیدی دریافت کنید.');
                    }
                    showProfileScreen(String(data.registration_token));
                    return;
                }
                redirectHome(data.redirect);
            }).catch(function (error) {
                showCodeError(error.message);
                codeFields[0].focus();
            }).finally(function () {
                setVerifyBusy(false);
            });
        });

        editPhone.addEventListener('click', function () {
            if (verifying || requesting) return;
            stopTimer();
            activePhone = '';
            requestId = '';
            codeForm.hidden = true;
            phoneForm.hidden = false;
            title.textContent = 'ورود به حساب کاربری';
            hideCodeError();
            phoneInput.focus();
        });

        resend.addEventListener('click', function () {
            if (resend.disabled || requesting || verifying || !activePhone) return;
            hideCodeError();
            requestCode(activePhone, true);
        });

        Object.keys(profileFields).forEach(function (name) {
            profileFields[name].addEventListener('input', function () {
                hideProfileFieldError(name);
                hideProfileError();
            });
        });

        profileForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (completing) return;

            var firstName = profileFields.first_name.value.trim();
            var lastName = profileFields.last_name.value.trim();
            var email = profileFields.email.value.trim();
            var firstInvalid = null;
            Object.keys(profileFields).forEach(hideProfileFieldError);
            hideProfileError();

            if (!firstName) {
                showProfileFieldError('first_name', 'نام را وارد کنید.');
                firstInvalid = profileFields.first_name;
            }
            if (!lastName) {
                showProfileFieldError('last_name', 'نام خانوادگی را وارد کنید.');
                if (!firstInvalid) firstInvalid = profileFields.last_name;
            }
            if (email && (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || !profileFields.email.validity.valid)) {
                showProfileFieldError('email', 'ایمیل معتبر وارد کنید.');
                if (!firstInvalid) firstInvalid = profileFields.email;
            }
            if (firstInvalid) {
                firstInvalid.focus();
                return;
            }
            if (!registrationToken) {
                showProfileError('مهلت تکمیل اطلاعات به پایان رسید. لطفاً ورود را دوباره آغاز کنید.');
                return;
            }

            completing = true;
            profileSubmit.disabled = true;
            profileForm.setAttribute('aria-busy', 'true');
            post('genosys_sms_complete', {
                registration_token: registrationToken,
                first_name: firstName,
                last_name: lastName,
                email: email
            }).then(function (data) {
                registrationToken = '';
                redirectHome(data.redirect);
            }).catch(function (error) {
                if (error.field && Object.prototype.hasOwnProperty.call(profileFields, error.field)) {
                    showProfileFieldError(error.field, error.message);
                    profileFields[error.field].focus();
                } else {
                    showProfileError(error.message);
                }
            }).finally(function () {
                completing = false;
                profileSubmit.disabled = false;
                profileForm.setAttribute('aria-busy', 'false');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
}());
