/* PHP drafts are text only. Only explicit, server-validated saves execute changes. */
(function () {
  'use strict';
  var root = document.querySelector('[data-php-editor]'), config = window.genosysPHPEditor;
  if (!root || !config) return;
  var settings = root.closest('.gs-settings'), textarea = root.querySelector('[data-php-code]');
  var states = new Map(), files = new Map((config.files || []).map(function (file) { return [file.path, file]; }));
  var current = null, editor = null, started = false, busy = false, muted = false;
  var validationTimer, validationSequence = 0, markers = [], width = 1440, height = 900, scale = 1, dragScale = null;
  var frame = root.querySelector('[data-php-preview-frame]'), previewStarted = false, lastPreviewURL = config.previewUrl;
  var numbers = new Intl.NumberFormat('fa-IR', { useGrouping: false });
  function node(key) { return root.querySelector('[data-php-' + key + ']'); }
  function button(key) { return root.querySelector('[data-php-action="' + key + '"]'); }
  function normalize(value) { return String(value).replace(/\r\n?/g, '\n'); }
  function original(state) { return state && state.restoreContent !== null && normalize(state.restoreContent) === state.text; }
  function dirty(state) { return !!state && (state.text !== state.saved || (original(state) && state.restoreHash !== state.hash)); }
  function dirtyCount() { var count = 0; states.forEach(function (state) { if (dirty(state)) count++; }); return count; }
  function message(text, kind) {
    var element = node('message'); element.textContent = text || ''; element.hidden = !text;
    element.classList.toggle('is-error', kind === 'error'); element.classList.toggle('is-success', kind === 'success');
  }
  function api(action, values) {
    var body = new URLSearchParams({ action: 'genosys_php_editor_' + action, nonce: config.nonce });
    Object.keys(values).forEach(function (key) { body.set(key, values[key]); });
    var controller = new AbortController(), timer = setTimeout(function () { controller.abort(); }, action === 'save' ? 240000 : 25000);
    return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: body, signal: controller.signal })
      .then(function (response) { return response.json(); }).then(function (result) {
        if (!result || !result.success) {
          var data = result && result.data || {}, error = new Error(data.message || 'درخواست انجام نشد.');
          error.code = data.code || 'request'; error.validation = data.validation; throw error;
        }
        return result.data;
      }).finally(function () { clearTimeout(timer); });
  }
  function makeState(data) {
    return { file: data.file, saved: normalize(data.content), text: normalize(data.content), hash: data.hash,
      history: data.history || [], readonly: !!data.readonly || !!files.get(data.file).readonly,
      readonlyReason: data.readonlyReason || files.get(data.file).readonlyReason || '', doc: null,
      validation: null, validatedText: null, validating: false, uncertain: false, restoreContent: null, restoreHash: null };
  }
  function updateCursor() {
    var state = states.get(current); if (!state) return;
    var cursor = editor ? editor.getCursor() : { line: textarea.value.slice(0, textarea.selectionStart || 0).split('\n').length - 1, ch: 0 };
    node('cursor').textContent = 'سطر ' + numbers.format(cursor.line + 1) + '، ستون ' + numbers.format(cursor.ch + 1) + ' · ' + numbers.format(state.text.split('\n').length) + ' خط · ' + numbers.format(state.text.length) + ' نویسه';
  }
  function clearMarkers() { if (editor) markers.forEach(function (line) { editor.removeLineClass(line, 'background', 'gspe__line-error'); }); markers = []; }
  function renderValidation(state) {
    clearMarkers();
    var validation = state && state.validatedText === state.text ? state.validation : null;
    var errors = validation && validation.errors || [], warnings = validation && validation.warnings || [];
    var checking = state && state.validating;
    node('error-count').textContent = numbers.format(errors.length);
    node('warning-count').textContent = numbers.format(warnings.length);
    var label = checking ? 'در حال بررسی روی سرور…' : !validation ? 'برای ذخیره، بررسی سرور لازم است' : validation.valid ? 'بررسی ایستا انجام شد؛ کنترل سرور هنگام ذخیره' : 'ذخیره مسدود است؛ مشکلات را رفع کنید';
    node('validation-label').textContent = label;
    node('validation-status').dataset.status = errors.length ? 'error' : validation && validation.valid ? 'ok' : 'pending';
    node('validation-icon').textContent = errors.length ? '!' : validation && validation.valid ? '✓' : '…';
    node('validation-status').classList.toggle('is-error', !!errors.length);
    node('validation-status').classList.toggle('is-valid', !!validation && validation.valid);
    node('validation').textContent = checking ? 'در حال بررسی…' : errors.length ? numbers.format(errors.length) + ' مشکل' : validation ? 'بررسی ایستا انجام شد' : 'بررسی نشده';
    node('validation').classList.toggle('is-error', !!errors.length);
    node('validation').classList.toggle('is-valid', !!validation && validation.valid);
    root.querySelectorAll('[data-php-check]').forEach(function (card) {
      var checks = validation && validation.checks;
      var check = Array.isArray(checks) ? checks.find(function (item) { return item.id === card.dataset.phpCheck; }) : checks && checks[card.dataset.phpCheck];
      var status = check && (check.status || (check.passed ? 'ok' : 'pending')) || 'pending';
      card.dataset.status = status;
      var detail = card.querySelector('[data-check-status]');
      if (detail) detail.textContent = check && (check.message || check.label) || (checking ? 'در حال بررسی' : 'بررسی نشده');
      var icon = card.querySelector('[data-check-icon]');
      if (icon) icon.textContent = status === 'ok' ? '✓' : status === 'error' || status === 'unsupported' ? '!' : '…';
    });
    var list = node('issues'); list.replaceChildren();
    errors.concat(warnings).forEach(function (issue, index) {
      var row = document.createElement('button'); row.type = 'button'; row.className = 'gspe__issue';
      row.dataset.severity = index >= errors.length ? 'warning' : 'error';
      var icon = document.createElement('span'); icon.className = 'gspe__issue-icon'; icon.textContent = index >= errors.length ? '!' : '×';
      var text = document.createElement('span'); text.className = 'gspe__issue-content'; text.textContent = issue.message || String(issue);
      var detail = document.createElement('small'); detail.className = 'gspe__issue-location'; detail.textContent = (issue.code || 'PHP') + (issue.line ? ' · سطر ' + numbers.format(issue.line) : '');
      row.append(icon, text, detail);
      if (issue.line) {
        row.dataset.line = String(issue.line);
        if (editor && index < errors.length) {
          var line = Math.max(0, Math.min(editor.lineCount() - 1, issue.line - 1));
          editor.addLineClass(line, 'background', 'gspe__line-error'); markers.push(line);
        }
      } else row.disabled = true;
      list.appendChild(row);
    });
    list.hidden = !errors.length && !warnings.length;
    var summary = node('summary'); summary.hidden = !!errors.length;
    summary.dataset.status = validation && validation.runtimeVerified ? 'ok' : 'pending';
    summary.textContent = checking ? 'کد بدون اجرا شدن در حال بررسی است…' : !validation ? 'کد هنوز تأیید نشده است. تا پایان بررسی، ذخیره‌سازی غیرفعال می‌ماند.' : validation.runtimeVerified ? 'ذخیره و بررسی اجرایی مسیرهای مدیریت، صفحهٔ اصلی و تنظیمات قالب انجام شد. بررسی این مسیرها تضمین همهٔ وضعیت‌های آینده نیست.' : 'بررسی ایستای کد انجام شده است. پیش از تأیید ذخیره، وردپرس اجرای مسیرهای مدیریت، صفحهٔ اصلی و تنظیمات قالب را بررسی می‌کند. همهٔ شاخه‌های شرطی و داده‌های آینده قابل تضمین نیستند.';
  }
  function updateStatus() {
    var state = states.get(current), count = dirtyCount();
    root.classList.toggle('is-dirty', count > 0); root.classList.toggle('is-saved', count === 0 && !!state);
    node('save-status').textContent = busy ? 'در حال دریافت یا ذخیره…' : count ? numbers.format(count) + ' فایل با تغییر ذخیره‌نشده' : 'همهٔ تغییرات ذخیره شده است';
    root.querySelectorAll('[data-php-file]').forEach(function (control) {
      var selected = control.dataset.phpFile === current, item = states.get(control.dataset.phpFile);
      control.setAttribute('aria-selected', selected ? 'true' : 'false'); control.tabIndex = selected ? 0 : -1; control.disabled = busy;
      var dot = control.querySelector('[data-file-dirty]'); if (dot) dot.hidden = !dirty(item);
      var error = control.querySelector('[data-file-error]'); if (error) { error.hidden = !item || item.validatedText !== item.text || !item.validation || !(item.validation.errors || []).length; error.textContent = '!'; error.dataset.status = 'error'; }
    });
    var valid = state && state.validatedText === state.text && state.validation && state.validation.valid && !(state.validation.errors || []).length;
    button('save').disabled = busy || !state || state.readonly || !dirty(state) || !valid || state.validating || state.uncertain;
    button('save').setAttribute('aria-busy', busy ? 'true' : 'false');
    button('reset').disabled = busy || !state || state.readonly;
    var readonlyNote = node('readonly-note'); readonlyNote.hidden = !state || !state.readonly;
    if (state && state.readonly) readonlyNote.textContent = state.readonlyReason || 'این فایل فقط خواندنی است؛ ویرایش و ذخیره غیرفعال است.';
    button('validate').disabled = busy || !state || state.validating;
    node('history').disabled = busy || !state || !state.history.length;
    if (editor) {
      editor.setOption('readOnly', busy || !state || state.readonly);
      var history = editor.historySize(); button('undo').disabled = busy || !state || state.readonly || !history.undo; button('redo').disabled = busy || !state || state.readonly || !history.redo;
    } else { textarea.readOnly = busy || !state || state.readonly; button('undo').disabled = true; button('redo').disabled = true; }
    renderValidation(state); updateCursor();
  }
  function historyOptions(state) {
    var select = node('history'); select.replaceChildren(new Option('تاریخچهٔ نسخه‌ها', ''));
    state.history.forEach(function (item) { select.add(new Option(new Date(item.date).toLocaleString('fa-IR') + (item.current ? ' · نسخهٔ فعلی' : ''), item.id)); });
  }
  function installState(state) {
    clearMarkers(); current = state.file; muted = true;
    if (editor) { if (!state.doc) state.doc = new window.wp.CodeMirror.Doc(state.text, 'application/x-httpd-php'); editor.swapDoc(state.doc); }
    else textarea.value = state.text;
    muted = false; node('current-file').textContent = current; historyOptions(state); updateStatus();
    if (editor) requestAnimationFrame(function () { editor.refresh(); });
    message(state.readonly ? state.readonlyReason || 'این فایل فقط خواندنی است.' : '');
  }
  async function validate(state) {
    if (!state || busy) return;
    clearTimeout(validationTimer);
    var text = state.text, sequence = ++validationSequence; state.validating = true; updateStatus();
    try {
      var data = await api('validate', { file: state.file, content: original(state) ? state.restoreContent : text, hash: state.hash });
      if (state.text !== text) return;
      state.validation = data.validation; state.validatedText = text;
    } catch (error) {
      if (state.text !== text) return;
      state.validation = error.validation || { valid: false, errors: [{ code: error.code || 'validation_unavailable', message: error.message || 'بررسی سرور انجام نشد؛ ذخیره مسدود است.' }], warnings: [] };
      state.validatedText = text;
    } finally { if (sequence === validationSequence || state.text === text) state.validating = false; updateStatus(); }
  }
  function scheduleValidation(state, delay) {
    clearTimeout(validationTimer); state.validating = false;
    validationTimer = setTimeout(function () { validate(state); }, delay === undefined ? 650 : delay);
  }
  async function loadFile(path, force) {
    if (busy || !files.has(path)) return;
    if (!force && states.has(path)) { installState(states.get(path)); if (states.get(path).validatedText !== states.get(path).text) scheduleValidation(states.get(path), 0); return; }
    busy = true; updateStatus();
    try { var data = await api('read', { file: path }); var state = makeState(data); states.set(path, state); installState(state); scheduleValidation(state, 0); }
    catch (error) { message(error.message, 'error'); }
    finally { busy = false; updateStatus(); }
  }
  function onEdit() {
    if (muted || busy || !current) return;
    var state = states.get(current); if (!state || state.readonly) return;
    state.text = editor ? editor.getValue() : textarea.value; state.validation = null; state.validatedText = null;
    message(''); scheduleValidation(state); updateStatus();
  }
  async function save() {
    var state = states.get(current);
    if (!state || button('save').disabled || busy) return;
    if (new Blob([state.text]).size > config.maxBytes) { message('حجم فایل از حد مجاز بیشتر است.', 'error'); return; }
    busy = true; updateStatus(); message('در حال بررسی نهایی و کنترل اجرایی سرور؛ تا اعلام نتیجه صفحه را باز نگه دارید.');
    try {
      var data = await api('save', { file: state.file, content: original(state) ? state.restoreContent : state.text, hash: state.hash });
      state.saved = normalize(data.content); state.text = state.saved; state.hash = data.hash; state.history = data.history || [];
      state.restoreContent = null; state.restoreHash = null; state.uncertain = false; state.validation = data.validation; state.validatedText = state.text;
      historyOptions(state); message(data.historyWarning || 'فایل انتخاب‌شده پس از بررسی سرور ذخیره شد.', data.historyWarning ? '' : 'success');
      if (previewStarted) navigatePreview(lastPreviewURL);
    } catch (error) {
      if (!error.code) { state.uncertain = true; message('نتیجهٔ ذخیره مشخص نشد؛ برای دریافت وضعیت قطعی صفحه را دوباره باز کنید. درخواست خودکار تکرار نمی‌شود.', 'error'); }
      else { if (error.validation) { state.validation = error.validation; state.validatedText = state.text; } message(error.message, 'error'); }
    } finally { busy = false; updateStatus(); }
  }
  async function reset() {
    var state = states.get(current); if (!state || busy || state.readonly) return;
    if (!window.confirm('کد این فایل به نسخهٔ اصلی ZIP قالب نصب‌شده بازگردد؟ برای اعمال دائمی باید ذخیره‌سازی و بررسی سرور انجام شود.')) return;
    busy = true; updateStatus();
    try {
      var data = await api('original', { file: state.file }), restored = makeState(data.current);
      restored.text = normalize(data.content); restored.restoreContent = data.content; restored.restoreHash = data.originalHash;
      states.set(restored.file, restored); installState(restored); scheduleValidation(restored, 0);
      message('نسخهٔ اصلی ZIP قالب (' + data.installedVersion + ') در پیش‌نویس بازیابی شد؛ هنوز ذخیره نشده است.', 'success');
    } catch (error) { message(error.message, 'error'); }
    finally { busy = false; updateStatus(); }
  }
  async function revision(id) {
    var state = states.get(current); node('history').value = ''; if (!state || busy || !id || state.readonly) return;
    if (dirty(state) && !window.confirm('نسخهٔ انتخاب‌شده جای پیش‌نویس این فایل قرار بگیرد؟ ذخیره همچنان به بررسی سرور نیاز دارد.')) return;
    busy = true; updateStatus();
    try {
      var data = await api('revision', { file: current, revision: id }); muted = true; state.text = normalize(data.content);
      state.restoreContent = null; state.restoreHash = null; state.validation = null; state.validatedText = null;
      if (editor) editor.setValue(state.text); else textarea.value = state.text; muted = false;
      scheduleValidation(state, 0); message('نسخهٔ انتخاب‌شده فقط در پیش‌نویس قرار گرفت.');
    } catch (error) { message(error.message, 'error'); }
    finally { muted = false; busy = false; updateStatus(); }
  }
  function safePreview(value) {
    var home = new URL(config.previewUrl, location.href), url = new URL(value, home);
    var blocked = ['action','wc-ajax','wc-api','add-to-cart','remove_item','undo_item','remove_coupon','apply_coupon','update_cart','proceed','order_again','cancel_order','download_file','rest_route'];
    if (url.origin !== home.origin || !/^https?:$/.test(url.protocol) || /\/(?:wp-admin|wp-json|wc-api|customer-logout|delete-payment-method|set-default-payment-method)(?:\/|$)|\/wp-login\.php/i.test(decodeURIComponent(url.pathname)) || blocked.some(function (key) { return url.searchParams.has(key); })) throw new Error('فقط صفحه‌های همین سایت و بدون عملیات سفارش برای پیش‌نمایش در دسترس‌اند.');
    url.searchParams.set('genosys_css_preview', '1'); url.searchParams.set('_genosys_css_nonce', home.searchParams.get('_genosys_css_nonce'));
    return url;
  }
  function previewNotice(text) { node('preview-note').textContent = text || ''; node('preview-note').hidden = !text; }
  function navigatePreview(value) {
    try { var url = safePreview(value); lastPreviewURL = url.href; previewStarted = true; previewNotice('در حال دریافت نسخهٔ ذخیره‌شدهٔ سایت…'); frame.src = url.href; }
    catch (error) { previewNotice(error.message); }
  }
  function fitPreview() {
    var stage = node('preview-stage'); if (!stage.clientWidth) return;
    scale = Math.min(dragScale === null ? 1 : dragScale, Math.max(180, stage.clientWidth - 34) / width);
    node('preview-scale').style.width = width + 'px'; node('preview-scale').style.height = height + 'px'; node('preview-scale').style.transform = 'scale(' + scale + ')';
    frame.style.width = width + 'px'; frame.style.height = height + 'px';
    node('preview-viewport').style.width = (width * scale + 2) + 'px'; node('preview-viewport').style.height = (height * scale + 2) + 'px';
  }
  function dimensions(nextWidth, nextHeight, keepScale) {
    if (!keepScale) dragScale = null;
    width = Math.max(240, Math.min(2560, Math.round(Number(nextWidth) || 1440)));
    height = Math.max(240, Math.min(1600, Math.round(Number(nextHeight) || 900)));
    node('preview-width').value = width; node('preview-height').value = height;
    button('preview-desktop').setAttribute('aria-pressed', width >= 821 ? 'true' : 'false'); button('preview-mobile').setAttribute('aria-pressed', width <= 480 ? 'true' : 'false'); fitPreview();
  }
  var previousPreviewSize = null;
  function expandPreview(expand) {
    if (expand === root.classList.contains('is-preview-expanded')) return;
    if (expand) previousPreviewSize = { width: width, height: height, scale: dragScale };
    root.classList.toggle('is-preview-expanded', expand); button('preview-fullscreen').setAttribute('aria-pressed', expand ? 'true' : 'false');
    button('preview-fullscreen').title = expand ? 'بازگشت از نمایش تمام‌عرض' : 'نمایش تمام‌عرض';
    requestAnimationFrame(function () {
      if (expand) { var stage = node('preview-stage'); dimensions(stage.clientWidth - 36, stage.clientHeight - 44); }
      else { if (previousPreviewSize) { dimensions(previousPreviewSize.width, previousPreviewSize.height); dragScale = previousPreviewSize.scale; } previousPreviewSize = null; button('preview-fullscreen').focus({ preventScroll: true }); }
      fitPreview();
    });
  }
  function activateView(key, focus) {
    root.querySelectorAll('[data-php-tab]').forEach(function (tab) { var active = tab.dataset.phpTab === key; tab.setAttribute('aria-selected', active ? 'true' : 'false'); tab.tabIndex = active ? 0 : -1; if (active && focus) tab.focus(); });
    root.querySelectorAll('[data-php-tab-panel]').forEach(function (panel) { panel.hidden = panel.dataset.phpTabPanel !== key; });
    if (key === 'preview') { if (!previewStarted) navigatePreview(config.previewUrl); requestAnimationFrame(fitPreview); }
    else if (editor) requestAnimationFrame(function () { editor.refresh(); });
  }
  function initialize() {
    if (started) { if (editor) editor.refresh(); fitPreview(); return; }
    started = true; root.querySelectorAll('input,select,textarea').forEach(function (input) { input.setAttribute('form', 'genosys-php-editor-isolated'); });
    if (!config.allowed) { message(config.reason || 'ویرایش PHP برای این حساب یا این میزبان غیرفعال است.', 'error'); root.querySelectorAll('input,button,select,textarea').forEach(function (control) { control.disabled = true; }); return; }
    if (config.codeEditor && window.wp && window.wp.codeEditor) {
      var options = Object.assign({}, config.codeEditor); options.codemirror = Object.assign({}, options.codemirror, { mode: 'application/x-httpd-php', lineNumbers: true, direction: 'ltr', lint: false, indentUnit: 2, tabSize: 2 });
      options.codemirror.extraKeys = Object.assign({}, options.codemirror.extraKeys, { 'Ctrl-S': save, 'Cmd-S': save });
      editor = window.wp.codeEditor.initialize(textarea, options).codemirror; root.genosysCodeMirror = editor;
      editor.on('change', onEdit); editor.on('cursorActivity', updateCursor);
    } else {
      textarea.addEventListener('input', onEdit); textarea.addEventListener('keyup', updateCursor); textarea.addEventListener('click', updateCursor);
      textarea.addEventListener('keydown', function (event) { if (event.key === 'Tab') { event.preventDefault(); textarea.setRangeText('  ', textarea.selectionStart, textarea.selectionEnd, 'end'); onEdit(); } });
    }
    node('preview-page').replaceChildren(); (config.previewPages || []).forEach(function (page) { node('preview-page').add(new Option(page.label, page.url)); });
    dimensions(width, height); if (config.files.length) loadFile(config.files[0].path); else message('فایل PHP قابل خواندن پیدا نشد.', 'error');
  }
  frame.addEventListener('load', function () {
    try {
      if (!frame.contentWindow.genosysCSSReadOnlyPreview) { previewNotice('نشست پیش‌نمایش معتبر نیست؛ صفحه را دوباره باز کنید.'); return; }
      var url = safePreview(frame.contentWindow.location.href); lastPreviewURL = url.href;
      url.searchParams.delete('genosys_css_preview'); url.searchParams.delete('_genosys_css_nonce'); node('preview-path').value = url.pathname + url.search + url.hash;
      frame.contentDocument.addEventListener('keydown', function (event) { if (event.key === 'Escape' && root.classList.contains('is-preview-expanded')) { event.preventDefault(); expandPreview(false); } });
      previewNotice(''); fitPreview();
    } catch (error) { previewNotice('صفحه‌ای از همین وب‌سایت را انتخاب کنید.'); }
  });
  var fontSize = 14;
  root.addEventListener('click', async function (event) {
    var file = event.target.closest('[data-php-file]'); if (file) { event.preventDefault(); loadFile(file.dataset.phpFile); return; }
    var tab = event.target.closest('[data-php-tab]'); if (tab) { event.preventDefault(); activateView(tab.dataset.phpTab); return; }
    var issue = event.target.closest('[data-line]');
    if (issue && editor) { event.preventDefault(); activateView('code'); editor.setCursor({ line: Math.max(0, Math.min(editor.lineCount() - 1, Number(issue.dataset.line) - 1)), ch: 0 }); editor.focus(); editor.scrollIntoView(editor.getCursor(), 100); return; }
    var control = event.target.closest('[data-php-action]'); if (!control || control.disabled) return; event.preventDefault(); var action = control.dataset.phpAction;
    if (action === 'save') return save(); if (action === 'reset') return reset(); if (action === 'validate') return validate(states.get(current));
    if (action === 'undo' && editor) return editor.undo(); if (action === 'redo' && editor) return editor.redo();
    if (action === 'copy') {
      var state = states.get(current); if (!state) return;
      try { if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(state.text); else { var copy = document.createElement('textarea'); copy.value = state.text; copy.style.position = 'fixed'; copy.style.opacity = '0'; root.appendChild(copy); copy.select(); var copied = document.execCommand('copy'); copy.remove(); if (!copied) throw new Error('copy'); } message('کد فایل انتخاب‌شده کپی شد.', 'success'); }
      catch (error) { message('کد را انتخاب و به‌صورت دستی کپی کنید.', 'error'); } return;
    }
    if (action === 'download') { var item = states.get(current); if (!item) return; var blob = URL.createObjectURL(new Blob([original(item) ? item.restoreContent : item.text], { type: 'text/plain;charset=utf-8' })); var link = document.createElement('a'); link.href = blob; link.download = current.split('/').pop(); document.body.appendChild(link); link.click(); link.remove(); setTimeout(function () { URL.revokeObjectURL(blob); }, 1000); return; }
    if (action === 'theme') { root.classList.toggle('is-light'); control.setAttribute('aria-pressed', root.classList.contains('is-light') ? 'true' : 'false'); return; }
    if (action === 'wrap') { var wrap = control.getAttribute('aria-pressed') !== 'true'; control.setAttribute('aria-pressed', wrap ? 'true' : 'false'); if (editor) editor.setOption('lineWrapping', wrap); else textarea.wrap = wrap ? 'soft' : 'off'; return; }
    if (action === 'font-increase' || action === 'font-decrease') { fontSize = Math.max(11, Math.min(24, fontSize + (action === 'font-increase' ? 1 : -1))); root.querySelector('.gspe__editor').style.setProperty('--gsce-editor-font', fontSize + 'px'); if (editor) editor.refresh(); return; }
    if (action === 'fullscreen') { root.classList.toggle('is-fullscreen'); control.setAttribute('aria-pressed', root.classList.contains('is-fullscreen') ? 'true' : 'false'); if (editor) requestAnimationFrame(function () { editor.refresh(); }); return; }
    if (action === 'preview-fullscreen') return expandPreview(!root.classList.contains('is-preview-expanded'));
    if (action === 'preview-desktop') return dimensions(1440, 900); if (action === 'preview-mobile') return dimensions(390, 844);
    if (action === 'preview-open') return navigatePreview(node('preview-path').value); if (action === 'preview-reload') return navigatePreview(lastPreviewURL);
  });
  root.addEventListener('change', function (event) {
    if (event.target.matches('[data-php-history]')) revision(event.target.value);
    else if (event.target.matches('[data-php-preview-page]')) navigatePreview(event.target.value);
    else if (event.target.matches('[data-php-preview-width],[data-php-preview-height]')) dimensions(node('preview-width').value, node('preview-height').value);
  });
  root.addEventListener('input', function (event) {
    if (event.target.matches('[data-php-preview-width],[data-php-preview-height]') && event.target.validity.valid) dimensions(node('preview-width').value, node('preview-height').value);
    if (event.target.matches('[data-php-file-search]')) {
      var query = event.target.value.toLocaleLowerCase().trim(), count = 0;
      root.querySelectorAll('[data-php-file]').forEach(function (file) { file.hidden = query && !file.dataset.phpFile.toLocaleLowerCase().includes(query) && !file.textContent.toLocaleLowerCase().includes(query); if (!file.hidden) count++; });
      root.querySelectorAll('[data-php-group]').forEach(function (group) { group.hidden = !group.querySelector('[data-php-file]:not([hidden])'); }); node('file-empty').hidden = count > 0;
    }
  });
  root.addEventListener('keydown', function (event) {
    var file = event.target.closest('[data-php-file]');
    if (file && ['ArrowDown','ArrowUp','Home','End'].includes(event.key)) {
      event.preventDefault(); var available = Array.prototype.slice.call(root.querySelectorAll('[data-php-file]:not([hidden])')), index = available.indexOf(file);
      var next = event.key === 'Home' ? 0 : event.key === 'End' ? available.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + available.length) % available.length;
      available[next].focus(); loadFile(available[next].dataset.phpFile); return;
    }
    if (event.target.matches('[data-php-tab]') && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) { event.preventDefault(); activateView(event.target.dataset.phpTab === 'code' ? 'preview' : 'code', true); }
    if (event.key === 'Enter' && event.target.matches('input')) { event.preventDefault(); if (event.target.matches('[data-php-preview-path]')) navigatePreview(event.target.value); }
  });
  document.addEventListener('keydown', function (event) {
    if (!settings.classList.contains('is-php-editor')) return;
    if (event.key === 'Escape') { if (root.classList.contains('is-preview-expanded')) expandPreview(false); else if (root.classList.contains('is-fullscreen')) { root.classList.remove('is-fullscreen'); button('fullscreen').setAttribute('aria-pressed', 'false'); if (editor) editor.refresh(); } }
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); save(); }
  });
  window.addEventListener('beforeunload', function (event) { if (dirtyCount()) { event.preventDefault(); event.returnValue = ''; } });
  function drag(control, event, update, corner) {
    if (event.button !== 0 || !event.isPrimary) return; event.preventDefault(); control.setPointerCapture(event.pointerId);
    root.classList.add('is-preview-resizing'); root.style.setProperty('--gspe-resize-cursor', corner ? 'nwse-resize' : 'col-resize');
    var oldSelection = document.body.style.userSelect; document.body.style.userSelect = 'none';
    function move(next) { update(next.clientX, next.clientY); }
    function end() { control.removeEventListener('pointermove', move); control.removeEventListener('pointerup', end); control.removeEventListener('pointercancel', end); control.removeEventListener('lostpointercapture', end); root.classList.remove('is-preview-resizing'); document.body.style.userSelect = oldSelection; if (control.hasPointerCapture(event.pointerId)) control.releasePointerCapture(event.pointerId); }
    control.addEventListener('pointermove', move); control.addEventListener('pointerup', end); control.addEventListener('pointercancel', end); control.addEventListener('lostpointercapture', end);
  }
  var rail = root.querySelector('.gspe__files'), workspace = root.querySelector('.gspe__workspace');
  function railWidth(value) { if (window.innerWidth <= 1150 || !workspace.clientWidth) return; var gap = parseFloat(getComputedStyle(workspace).columnGap) || 20; root.style.setProperty('--gspe-files-panel-width', Math.round(Math.max(230, Math.min(workspace.clientWidth - gap - 450, value))) + 'px'); if (editor) editor.refresh(); fitPreview(); }
  node('files-resize').addEventListener('pointerdown', function (event) { var x = event.clientX, start = rail.getBoundingClientRect().width; drag(node('files-resize'), event, function (next) { railWidth(start + x - next); }); });
  node('files-resize').addEventListener('keydown', function (event) { if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { event.preventDefault(); railWidth(rail.getBoundingClientRect().width + (event.key === 'ArrowLeft' ? 10 : -10) * (event.shiftKey ? 5 : 1)); } });
  node('preview-resize').addEventListener('pointerdown', function (event) { var x = event.clientX, y = event.clientY, startWidth = width, startHeight = height, startScale = scale; dragScale = scale; drag(node('preview-resize'), event, function (nextX, nextY) { var maximum = Math.max(180, node('preview-stage').clientWidth - 34) / startScale; dimensions(Math.max(180 / startScale, Math.min(maximum, startWidth + (nextX - x) / startScale)), startHeight + (nextY - y) / startScale, true); }, true); });
  node('preview-resize').addEventListener('keydown', function (event) { if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) { event.preventDefault(); var step = event.shiftKey ? 50 : 10; dimensions(width + (event.key === 'ArrowRight' ? step : event.key === 'ArrowLeft' ? -step : 0), height + (event.key === 'ArrowDown' ? step : event.key === 'ArrowUp' ? -step : 0)); } });
  if (window.ResizeObserver) new ResizeObserver(fitPreview).observe(node('preview-stage'));
  window.addEventListener('resize', function () { if (root.style.getPropertyValue('--gspe-files-panel-width')) railWidth(rail.getBoundingClientRect().width); fitPreview(); });
  document.addEventListener('genosys:settings-tab', function (event) { if (event.detail.tab === 'php-editor') initialize(); });
  if (settings.classList.contains('is-php-editor')) initialize();
}());
