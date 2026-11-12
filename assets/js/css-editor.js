/* The source editor keeps drafts in memory. Only the explicit save action writes. */
(function () {
  'use strict';
  var root = document.querySelector('[data-css-editor]');
  var config = window.genosysCSSEditor;
  if (!root || !config) return;
  var settings = root.closest('.gs-settings');
  var textarea = root.querySelector('[data-css-code]');
  var frame = root.querySelector('[data-css-preview-frame]');
  var states = new Map();
  var manifest = new Map((config.files || []).map(function (file) { return [file.path, file]; }));
  var current = null;
  var editor = null;
  var started = false;
  var busy = false;
  var muted = false;
  var readSequence = 0;
  var previewTimer;
  var fontSize = 14;
  var previewWidth = 390;
  var previewHeight = 844;
  var previewScale = 1;
  var previewDragScale = null;
  var previewBeforeExpansion = null;
  var previewDocument = null;
  var previewObserver = null;
  var applying = false;
  var lastPreviewURL = config.previewUrl;
  var numbers = new Intl.NumberFormat('fa-IR', { useGrouping: false });

  function node(key) { return root.querySelector('[data-css-' + key + ']'); }
  function button(key) { return root.querySelector('[data-css-action="' + key + '"]'); }
  function normalize(text) { return String(text).replace(/\r\n?/g, '\n'); }
  function restoredContent(state) { return state && state.restoreContent !== null && normalize(state.restoreContent) === state.text; }
  function dirty(state) { return !!state && (state.text !== state.saved || (restoredContent(state) && state.restoreHash !== state.hash)); }
  function dirtyCount() { var count = 0; states.forEach(function (state) { if (dirty(state)) count++; }); return count; }
  function message(text, kind) {
    var element = node('message');
    element.textContent = text || '';
    element.hidden = !text;
    element.classList.toggle('is-error', kind === 'error');
    element.classList.toggle('is-success', kind === 'success');
  }
  function api(action, values) {
    var data = new URLSearchParams();
    data.set('action', 'genosys_css_editor_' + action);
    data.set('nonce', config.nonce);
    Object.keys(values).forEach(function (key) { data.set(key, values[key]); });
    var controller = new AbortController();
    var timer = setTimeout(function () { controller.abort(); }, 25000);
    return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', cache: 'no-store', body: data, signal: controller.signal })
      .then(function (response) { return response.json(); })
      .then(function (result) {
        if (!result || !result.success) {
          var error = new Error(result && result.data && result.data.message || 'درخواست انجام نشد. دوباره تلاش کنید.');
          error.code = result && result.data && result.data.code || 'request';
          throw error;
        }
        return result.data;
      }).finally(function () { clearTimeout(timer); });
  }

  // A structural check is intentionally described as such, rather than claiming
  // that an older CSS linter understands every modern browser feature.
  function structure(text) {
    var stack = [], quote = '', comment = false, escaped = false, line = 1;
    var close = { '}': '{', ')': '(', ']': '[' };
    for (var index = 0; index < text.length; index++) {
      var char = text[index], next = text[index + 1];
      if (char === '\n') line++;
      if (comment) { if (char === '*' && next === '/') { comment = false; index++; } continue; }
      if (quote) {
        if (escaped) { escaped = false; continue; }
        if (char === '\\') { escaped = true; continue; }
        if (char === quote) quote = '';
        continue;
      }
      if (char === '/' && next === '*') { comment = true; index++; continue; }
      if (char === '"' || char === "'") { quote = char; continue; }
      if (char === '\\') { index++; continue; }
      if (char === '{' || char === '(' || char === '[') stack.push({ char: char, line: line });
      else if (close[char] && (!stack.length || stack.pop().char !== close[char])) return 'پرانتز یا آکولاد نامتوازن در سطر ' + numbers.format(line);
    }
    if (comment) return 'توضیح CSS بسته نشده است.';
    if (quote) return 'رشتهٔ متنی بسته نشده است.';
    if (stack.length) return 'پرانتز یا آکولاد باز در سطر ' + numbers.format(stack[stack.length - 1].line);
    return '';
  }
  function updateStatus() {
    var state = states.get(current);
    var count = dirtyCount();
    root.classList.toggle('is-dirty', count > 0);
    root.classList.toggle('is-saved', count === 0 && !!state);
    var status = node('save-status');
    status.textContent = busy ? 'در حال دریافت یا ذخیره…' : count ? numbers.format(count) + ' فایل با تغییر ذخیره‌نشده' : 'همهٔ تغییرات ذخیره شده است';
    root.querySelectorAll('[data-css-file]').forEach(function (file) {
      var selected = file.dataset.cssFile === current;
      file.setAttribute('aria-selected', selected ? 'true' : 'false');
      file.tabIndex = selected ? 0 : -1;
      file.disabled = busy;
      file.querySelector('[data-file-dirty]').hidden = !dirty(states.get(file.dataset.cssFile));
    });
    var problem = state ? structure(state.text) : '';
    node('validation').textContent = !state ? 'در انتظار بارگذاری کد' : problem || 'بدون خطای ساختاری';
    node('validation').classList.toggle('is-valid', !!state && !problem);
    node('validation').classList.toggle('is-error', !!problem);
    button('save').disabled = busy || !state || state.readonly || !dirty(state) || !!problem || state.uncertain;
    button('save').setAttribute('aria-busy', busy ? 'true' : 'false');
    button('reset').disabled = busy || !state || state.readonly;
    node('history').disabled = busy || !state || !state.history.length;
    if (!editor) {
      textarea.readOnly = busy || !state || state.readonly;
      button('undo').disabled = true;
      button('redo').disabled = true;
    }
    if (editor) {
      var history = editor.historySize();
      button('undo').disabled = busy || !state || state.readonly || !history.undo;
      button('redo').disabled = busy || !state || state.readonly || !history.redo;
      editor.setOption('readOnly', busy || !state || state.readonly);
    }
    updateCursor();
  }
  function updateCursor() {
    var state = states.get(current);
    if (!state) return;
    var cursor;
    if (editor) cursor = editor.getCursor();
    else {
      var before = textarea.value.slice(0, textarea.selectionStart || 0).split('\n');
      cursor = { line: before.length - 1, ch: before[before.length - 1].length };
    }
    node('cursor').textContent = 'سطر ' + numbers.format(cursor.line + 1) + '، ستون ' + numbers.format(cursor.ch + 1) + ' · ' + numbers.format(state.text.split('\n').length) + ' خط · ' + numbers.format(state.text.length) + ' نویسه';
  }
  function historyOptions(state) {
    var select = node('history');
    select.replaceChildren(new Option('تاریخچهٔ نسخه‌ها', ''));
    state.history.forEach(function (item) {
      var date = new Date(item.date);
      var label = Number.isNaN(date.getTime()) ? item.date : date.toLocaleString('fa-IR');
      select.add(new Option(label + (item.current ? ' · نسخهٔ فعلی' : '') + (item.user ? ' · ' + item.user : ''), item.id));
    });
  }
  function makeState(data) {
    return { file: data.file, saved: normalize(data.content), text: normalize(data.content), hash: data.hash, history: data.history || [], readonly: !!data.readonly, doc: null, uncertain: false, restoreContent: null, restoreHash: null };
  }
  function installState(state) {
    current = state.file;
    muted = true;
    if (editor) {
      if (!state.doc) state.doc = new window.wp.CodeMirror.Doc(state.text, 'css');
      editor.swapDoc(state.doc);
    } else textarea.value = state.text;
    muted = false;
    node('current-file').textContent = current;
    historyOptions(state);
    updateStatus();
    if (editor) requestAnimationFrame(function () { editor.refresh(); });
    updatePreviewNote();
  }
  async function loadFile(path, force) {
    if (busy || !manifest.has(path)) return;
    if (!force && states.has(path)) { installState(states.get(path)); message(''); return; }
    var sequence = ++readSequence;
    busy = true; updateStatus();
    try {
      var data = await api('read', { file: path });
      if (sequence !== readSequence) return;
      var state = makeState(data);
      states.set(path, state);
      installState(state);
      message(state.readonly ? 'این فایل در میزبان فقط خواندنی است؛ ویرایش و ذخیرهٔ آن در دسترس نیست.' : '');
      applyDrafts();
    } catch (error) { message(error.message || 'دریافت فایل ممکن نشد.', 'error'); }
    finally { busy = false; updateStatus(); }
  }
  function onEdit() {
    if (muted || !current) return;
    var state = states.get(current);
    if (!state || state.readonly || busy) return;
    state.text = editor ? editor.getValue() : textarea.value;
    message(''); updateStatus();
    clearTimeout(previewTimer);
    previewTimer = setTimeout(applyDrafts, 120);
  }
  async function save() {
    var state = states.get(current);
    if (busy || !state || state.readonly || !dirty(state) || state.uncertain) return;
    var problem = structure(state.text);
    if (problem) { message(problem, 'error'); return; }
    if (new Blob([state.text]).size > config.maxBytes) { message('حجم فایل از یک مگابایت بیشتر است.', 'error'); return; }
    busy = true; updateStatus();
    try {
      var data = await api('save', { file: state.file, content: restoredContent(state) ? state.restoreContent : state.text, hash: state.hash });
      state.saved = normalize(data.content);
      state.text = state.saved;
      state.hash = data.hash;
      state.history = data.history || [];
      state.readonly = !!data.readonly;
      state.uncertain = false;
      state.restoreContent = null;
      state.restoreHash = null;
      historyOptions(state);
      // Keep the saved stylesheet current in this preview too; other in-memory
      // drafts are not discarded or published by saving this selected file.
      updatePreviewSavedFile(state);
      message(data.historyWarning || ('فایل انتخاب‌شده ذخیره شد.' + (dirtyCount() ? ' پیش‌نویس فایل‌های دیگر هنوز ذخیره نشده است.' : '')), data.historyWarning ? '' : 'success');
    } catch (error) {
      if (!error.code) {
        state.uncertain = true;
        message('نتیجهٔ ذخیره مشخص نشد. صفحه را دوباره باز کنید تا نسخهٔ قطعی سرور را ببینید؛ بازنشانی، کد اصلی قالب نصب‌شده را بازیابی می‌کند. درخواست خودکار تکرار نمی‌شود.', 'error');
      } else message(error.message, 'error');
    } finally { busy = false; updateStatus(); }
  }
  async function reset() {
    var state = states.get(current);
    if (!state || busy || state.readonly) return;
    if (!window.confirm('کد این فایل به نسخهٔ اصلی ZIP قالب نصب‌شده بازگردد؟ تغییرات این فایل کنار گذاشته می‌شود. برای اعمال روی سایت باید ذخیره‌سازی را بزنید.')) return;
    busy = true; updateStatus();
    try {
      var data = await api('original', { file: state.file });
      var restored = makeState(data.current);
      restored.text = normalize(data.content);
      restored.restoreContent = data.content;
      restored.restoreHash = data.originalHash;
      states.set(restored.file, restored);
      installState(restored);
      applyDrafts();
      message('نسخهٔ اصلی فایل از ZIP قالب نصب‌شده (' + data.installedVersion + ') بازیابی شد؛ برای اعمال دائمی ذخیره‌سازی را بزنید.', 'success');
    } catch (error) { message(error.message || 'بازیابی نسخهٔ اصلی ممکن نشد؛ پیش‌نویس فعلی حفظ شد.', 'error'); }
    finally { busy = false; updateStatus(); }
  }
  async function revision(id) {
    var state = states.get(current);
    node('history').value = '';
    if (!state || !id || busy || state.readonly) return;
    if (dirty(state) && !window.confirm('نسخهٔ انتخاب‌شده جای پیش‌نویس این فایل قرار بگیرد؟ برای انتشار باید ذخیره‌سازی را بزنید.')) return;
    busy = true; updateStatus();
    try {
      var data = await api('revision', { file: current, revision: id });
      muted = true;
      state.text = normalize(data.content);
      if (editor) editor.setValue(state.text); else textarea.value = state.text;
      muted = false;
      message('نسخهٔ انتخاب‌شده در پیش‌نویس قرار گرفت؛ هنوز ذخیره نشده است.');
      applyDrafts();
    } catch (error) { message(error.message, 'error'); }
    finally { busy = false; muted = false; updateStatus(); }
  }

  function fileFromURL(value) {
    try {
      var url = new URL(value, frame.contentWindow.location.href);
      var result = null;
      manifest.forEach(function (file, path) {
        var source = new URL(file.url, location.href);
        if (url.origin === source.origin && decodeURI(url.pathname) === decodeURI(source.pathname)) result = path;
      });
      return result;
    } catch (error) { return null; }
  }
  function cssURL(value, base) {
    var text = value.trim();
    if (!text || /^(?:data:|blob:|#|var\()/i.test(text)) return value;
    try {
      var target = new URL(text, base);
      if (!/^https?:$/.test(target.protocol) || unsafePreviewURL(target)) return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg"/%3E';
      return target.href;
    } catch (error) { return value; }
  }
  // Resolve relative asset URLs against the real CSS file, not the iframe URL.
  // Quoted strings and comments outside url()/@import remain byte-for-byte text.
  function previewCSS(text, base) {
    var result = '', index = 0;
    function quoted(start) {
      var quote = text[start], end = start + 1;
      while (end < text.length) { if (text[end] === '\\') { end += 2; continue; } if (text[end] === quote) return end + 1; end++; }
      return text.length;
    }
    while (index < text.length) {
      if (text.slice(index, index + 2) === '/*') {
        var commentEnd = text.indexOf('*/', index + 2);
        commentEnd = commentEnd < 0 ? text.length : commentEnd + 2;
        result += text.slice(index, commentEnd); index = commentEnd; continue;
      }
      if (text[index] === '"' || text[index] === "'") { var stringEnd = quoted(index); result += text.slice(index, stringEnd); index = stringEnd; continue; }
      var importMatch = text.slice(index).match(/^@import\s+(["'])/i);
      if (importMatch) {
        var quoteStart = index + importMatch[0].length - 1;
        var quoteEnd = quoted(quoteStart);
        result += text.slice(index, quoteStart) + JSON.stringify(cssURL(text.slice(quoteStart + 1, quoteEnd - 1), base));
        index = quoteEnd; continue;
      }
      var match = text.slice(index).match(/^url\s*\(/i);
      if (match && (index === 0 || !/[\w-]/.test(text[index - 1]))) {
        var end = index + match[0].length, begin = end;
        while (end < text.length) {
          if (text[end] === '"' || text[end] === "'") { end = quoted(end); continue; }
          if (text[end] === '\\') { end += 2; continue; }
          if (text[end] === ')') break;
          end++;
        }
        if (end < text.length) {
          var value = text.slice(begin, end).trim();
          if ((value[0] === '"' || value[0] === "'") && value[value.length - 1] === value[0]) value = value.slice(1, -1);
          result += 'url(' + JSON.stringify(cssURL(value, base)) + ')'; index = end + 1; continue;
        }
      }
      result += text[index++];
    }
    return result;
  }
  function previewLinks() {
    if (!previewDocument) return [];
    return Array.prototype.slice.call(previewDocument.querySelectorAll('link[rel~="stylesheet"][href]'));
  }
  function applyDrafts() {
    if (!previewDocument || applying) return;
    applying = true;
    try {
      previewLinks().forEach(function (link) {
        var path = fileFromURL(link.href);
        if (!path) return;
        var state = states.get(path);
        var style = link.__genosysDraft;
        if (!state || !dirty(state)) {
          if (style && !style.__genosysSaved) { style.remove(); link.__genosysDraft = null; link.disabled = !!link.__genosysWasDisabled; }
          return;
        }
        if (!style) {
          style = previewDocument.createElement('style');
          style.dataset.genosysCSSDraft = path;
          if (link.media) style.media = link.media;
          link.__genosysWasDisabled = link.disabled;
          link.insertAdjacentElement('afterend', style);
          link.__genosysDraft = style;
          link.disabled = true;
        }
        if (style.__genosysText !== state.text) {
          style.textContent = previewCSS(state.text, manifest.get(path).url);
          style.__genosysText = state.text;
        }
      });
      updatePreviewNote();
    } catch (error) { previewNotice('این صفحه اجازهٔ پیش‌نمایش زنده نمی‌دهد. یک مسیر از همین وب‌سایت انتخاب کنید.'); }
    finally { if (previewObserver) previewObserver.takeRecords(); applying = false; }
  }
  function updatePreviewSavedFile(state) {
    if (!previewDocument) return;
    previewLinks().forEach(function (link) {
      if (fileFromURL(link.href) !== state.file) return;
      var replacement = link.__genosysDraft;
      if (replacement) {
        replacement.textContent = previewCSS(state.saved, manifest.get(state.file).url);
        replacement.__genosysText = state.saved;
        replacement.__genosysSaved = true;
      }
      var target = new URL(link.href);
      target.searchParams.set('genosys_css', state.hash.slice(0, 16));
      function finish() {
        link.removeEventListener('load', finish);
        if (link.__genosysDraft === replacement && replacement) { replacement.remove(); link.__genosysDraft = null; }
        link.disabled = !!link.__genosysWasDisabled;
        applyDrafts();
      }
      link.addEventListener('load', finish, { once: true });
      link.href = target.href;
      // The saved snapshot keeps this frame accurate even if the browser defers
      // loading a disabled link. It is never an extra rule after preferences.
      if (replacement) link.disabled = false;
    });
    if (previewObserver) previewObserver.takeRecords();
    updatePreviewNote();
  }
  function previewNotice(text) {
    node('preview-note').textContent = text || '';
    node('preview-note').hidden = !text;
  }
  function updatePreviewNote() {
    if (!current || !previewDocument) return;
    var loaded = previewLinks().some(function (link) { return fileFromURL(link.href) === current; });
    previewNotice(loaded ? '' : 'این فایل در صفحهٔ فعلی بارگذاری نشده است. برای دیدن نتیجه، صفحهٔ مرتبط را انتخاب کنید.' + (manifest.get(current).scope === 'admin' ? ' این فایل مربوط به پنل مدیریت است.' : ''));
  }
  function safePreview(value) {
    var home = new URL(config.previewUrl, location.href);
    var url;
    try { url = new URL(value, home); } catch (error) { throw new Error('مسیر پیش‌نمایش معتبر نیست.'); }
    if (url.origin !== home.origin || !/^https?:$/.test(url.protocol)) throw new Error('فقط صفحه‌های همین وب‌سایت برای پیش‌نمایش در دسترس‌اند.');
    if (unsafePreviewURL(url)) throw new Error('عملیات سفارش و ارسال اطلاعات در پیش‌نمایش فعال نیست.');
    url.searchParams.set('genosys_css_preview', '1');
    url.searchParams.set('_genosys_css_nonce', home.searchParams.get('_genosys_css_nonce'));
    return url;
  }
  function unsafePreviewURL(url) {
    var denied = ['action', 'wc-ajax', 'wc-api', 'add-to-cart', 'remove_item', 'undo_item', 'order_again', 'cancel_order', 'download_file', 'rest_route', 'remove_coupon', 'apply_coupon', 'update_cart', 'proceed'];
    return /\/(?:wp-admin|wp-json|wc-api)(?:\/|$)|\/wp-login\.php|\/(?:customer-logout|delete-payment-method|set-default-payment-method)(?:\/|$)/i.test(url.pathname) || denied.some(function (key) { return url.searchParams.has(key); });
  }
  function navigatePreview(value) {
    try {
      var url = safePreview(value);
      lastPreviewURL = url.href;
      previewDocument = null;
      if (previewObserver) previewObserver.disconnect();
      previewNotice('در حال بارگذاری وب‌سایت…');
      frame.src = url.href;
    } catch (error) { previewNotice(error.message); }
  }
  function onPreviewLoad() {
    try {
      var documentInside = frame.contentDocument;
      if (!documentInside || !frame.contentWindow.genosysCSSReadOnlyPreview) {
        previewDocument = null;
        previewNotice('بارگذاری پیش‌نمایش انجام نشد یا نشست منقضی شده است. صفحهٔ ویرایشگر را دوباره باز کنید.');
        return;
      }
      var locationInside = new URL(frame.contentWindow.location.href);
      safePreview(locationInside.href);
      previewDocument = documentInside;
      documentInside.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && root.classList.contains('is-preview-expanded')) {
          event.preventDefault(); setPreviewExpanded(false);
        }
      });
      lastPreviewURL = locationInside.href;
      locationInside.searchParams.delete('genosys_css_preview');
      locationInside.searchParams.delete('_genosys_css_nonce');
      node('preview-path').value = locationInside.pathname + locationInside.search + locationInside.hash;
      if (previewObserver) previewObserver.disconnect();
      previewObserver = new MutationObserver(function (records) {
        if (records.some(function (record) {
          return record.type === 'attributes' || Array.prototype.some.call(record.addedNodes, function (added) { return added.nodeType === 1 && (added.matches('link[rel~="stylesheet"]') || added.querySelector('link[rel~="stylesheet"]')); });
        })) applyDrafts();
      });
      previewObserver.observe(documentInside.head, { childList: true, subtree: true, attributes: true, attributeFilter: ['href', 'media'] });
      applyDrafts();
      fitPreview();
    } catch (error) { previewDocument = null; previewNotice('برای پیش‌نمایش زنده، صفحه‌ای از همین وب‌سایت انتخاب کنید.'); }
  }
  function fitPreview() {
    var stage = node('preview-stage');
    var viewport = node('preview-viewport');
    var scale = node('preview-scale');
    if (!stage || !stage.clientWidth) return;
    var available = Math.max(180, stage.clientWidth - 34);
    previewScale = Math.min(previewDragScale === null ? 1 : previewDragScale, available / previewWidth);
    scale.style.width = previewWidth + 'px'; scale.style.height = previewHeight + 'px';
    scale.style.transform = 'scale(' + previewScale + ')';
    frame.style.width = previewWidth + 'px'; frame.style.height = previewHeight + 'px';
    viewport.style.width = (previewWidth * previewScale + 2) + 'px';
    viewport.style.height = (previewHeight * previewScale + 2) + 'px';
  }
  function dimensions(width, height, keepScale) {
    if (!keepScale) previewDragScale = null;
    previewWidth = Math.max(240, Math.min(2560, Math.round(Number(width) || 390)));
    previewHeight = Math.max(240, Math.min(1600, Math.round(Number(height) || 844)));
    node('preview-width').value = previewWidth;
    node('preview-height').value = previewHeight;
    button('preview-mobile').setAttribute('aria-pressed', previewWidth <= 480 ? 'true' : 'false');
    button('preview-desktop').setAttribute('aria-pressed', previewWidth >= 821 ? 'true' : 'false');
    fitPreview();
  }
  function fitExpandedPreview() {
    if (!root.classList.contains('is-preview-expanded')) return;
    var stage = node('preview-stage');
    dimensions(stage.clientWidth - 36, stage.clientHeight - 44);
  }
  function setPreviewExpanded(expanded) {
    if (expanded === root.classList.contains('is-preview-expanded')) return;
    var control = button('preview-fullscreen');
    if (expanded) {
      previewBeforeExpansion = { width: previewWidth, height: previewHeight, scale: previewDragScale };
      root.classList.remove('is-fullscreen');
      button('fullscreen').setAttribute('aria-pressed', 'false');
    }
    root.classList.toggle('is-preview-expanded', expanded);
    control.setAttribute('aria-pressed', expanded ? 'true' : 'false');
    control.setAttribute('aria-label', expanded ? 'بازگشت از نمایش تمام‌عرض' : 'نمایش تمام‌عرض');
    control.title = expanded ? 'بازگشت از نمایش تمام‌عرض' : 'نمایش تمام‌عرض';
    if (expanded) requestAnimationFrame(fitExpandedPreview);
    else {
      if (previewBeforeExpansion) {
        dimensions(previewBeforeExpansion.width, previewBeforeExpansion.height);
        previewDragScale = previewBeforeExpansion.scale;
        previewBeforeExpansion = null;
      }
      clampWorkspacePanels();
      fitPreview();
      if (editor) requestAnimationFrame(function () { editor.refresh(); });
      control.focus({ preventScroll: true });
    }
  }
  function initialize() {
    if (started) { if (editor) requestAnimationFrame(function () { editor.refresh(); }); updateStatus(); fitPreview(); return; }
    started = true;
    // These controls are an independent source-editing workflow, even though the
    // settings tabs share an outer form. They cannot validate/submit that form.
    root.querySelectorAll('input,select,textarea').forEach(function (element) { element.setAttribute('form', 'genosys-css-editor-isolated'); });
    if (!config.allowed) {
      message(config.reason || 'ویرایش فایل قالب در این وردپرس مجاز نیست.', 'error');
      textarea.readOnly = true;
      root.querySelectorAll('button,select,input').forEach(function (element) { element.disabled = true; });
      node('save-status').textContent = 'ویرایش در دسترس نیست';
      frame.removeAttribute('src');
      return;
    }
    if (config.codeEditor && window.wp && window.wp.codeEditor) {
      var options = Object.assign({}, config.codeEditor);
      options.codemirror = Object.assign({}, options.codemirror, { lineNumbers: true, direction: 'ltr', lineWrapping: false, lint: false, indentUnit: 2, tabSize: 2 });
      options.codemirror.extraKeys = Object.assign({}, options.codemirror.extraKeys, { 'Ctrl-S': save, 'Cmd-S': save });
      editor = window.wp.codeEditor.initialize(textarea, options).codemirror;
      editor.on('change', onEdit);
      editor.on('cursorActivity', updateCursor);
      // A stable binding also helps integrations inspect the actual native editor.
      root.genosysCodeMirror = editor;
    } else {
      textarea.addEventListener('input', onEdit);
      textarea.addEventListener('keyup', updateCursor);
      textarea.addEventListener('click', updateCursor);
      textarea.addEventListener('keydown', function (event) {
        if (event.key !== 'Tab') return;
        event.preventDefault();
        var start = textarea.selectionStart, end = textarea.selectionEnd;
        textarea.setRangeText('  ', start, end, 'end'); onEdit();
      });
    }
    var pages = node('preview-page'); pages.replaceChildren();
    (config.previewPages || []).forEach(function (page) { pages.add(new Option(page.label, page.url)); });
    dimensions(previewWidth, previewHeight);
    navigatePreview(config.previewUrl);
    if (config.files.length) loadFile(config.files[0].path);
    else message('فایل CSS قابل خواندن در قالب پیدا نشد.', 'error');
  }

  frame.addEventListener('load', onPreviewLoad);
  root.addEventListener('click', async function (event) {
    var file = event.target.closest('[data-css-file]');
    if (file) { event.preventDefault(); loadFile(file.dataset.cssFile); return; }
    var control = event.target.closest('[data-css-action]');
    if (!control || control.disabled) return;
    event.preventDefault();
    var action = control.dataset.cssAction;
    if (action === 'save') return save();
    if (action === 'reset') return reset();
    if (action === 'undo' && editor) { editor.undo(); return; }
    if (action === 'redo' && editor) { editor.redo(); return; }
    if (action === 'copy') {
      var state = states.get(current); if (!state) return;
      try {
        if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(state.text);
        else { var copy = document.createElement('textarea'); copy.value = state.text; copy.style.position = 'fixed'; copy.style.opacity = '0'; root.appendChild(copy); copy.select(); var copied = document.execCommand('copy'); copy.remove(); if (!copied) throw new Error('copy'); }
        message('کد فایل انتخاب‌شده کپی شد.', 'success');
      } catch (error) { message('کپی خودکار در این مرورگر مجاز نیست. کد را انتخاب و کپی کنید.', 'error'); }
      return;
    }
    if (action === 'download') {
      var downloadState = states.get(current); if (!downloadState) return;
      var blob = URL.createObjectURL(new Blob([downloadState.text], { type: 'text/css;charset=utf-8' }));
      var link = document.createElement('a'); link.href = blob; link.download = current.split('/').pop(); document.body.appendChild(link); link.click(); link.remove(); setTimeout(function () { URL.revokeObjectURL(blob); }, 1000); return;
    }
    if (action === 'theme') { root.classList.toggle('is-light'); control.setAttribute('aria-pressed', root.classList.contains('is-light') ? 'true' : 'false'); return; }
    if (action === 'wrap') { var wrap = control.getAttribute('aria-pressed') !== 'true'; control.setAttribute('aria-pressed', wrap ? 'true' : 'false'); if (editor) editor.setOption('lineWrapping', wrap); else textarea.wrap = wrap ? 'soft' : 'off'; return; }
    if (action === 'font-increase' || action === 'font-decrease') { fontSize = Math.max(11, Math.min(24, fontSize + (action === 'font-increase' ? 1 : -1))); root.querySelector('.gsce__editor').style.setProperty('--gsce-editor-font', fontSize + 'px'); if (editor) editor.refresh(); return; }
    if (action === 'fullscreen') { if (root.classList.contains('is-preview-expanded')) setPreviewExpanded(false); root.classList.toggle('is-fullscreen'); control.setAttribute('aria-pressed', root.classList.contains('is-fullscreen') ? 'true' : 'false'); if (editor) requestAnimationFrame(function () { editor.refresh(); }); return; }
    if (action === 'preview-fullscreen') return setPreviewExpanded(!root.classList.contains('is-preview-expanded'));
    if (action === 'preview-desktop') return dimensions(1440, 900);
    if (action === 'preview-mobile') return dimensions(390, 844);
    if (action === 'preview-open') return navigatePreview(node('preview-path').value);
    if (action === 'preview-reload') return navigatePreview(lastPreviewURL);
  });
  root.addEventListener('change', function (event) {
    if (event.target.matches('[data-css-history]')) revision(event.target.value);
    else if (event.target.matches('[data-css-preview-page]')) navigatePreview(event.target.value);
    else if (event.target.matches('[data-css-preview-width],[data-css-preview-height]')) dimensions(node('preview-width').value, node('preview-height').value);
  });
  root.addEventListener('input', function (event) {
    if (event.target.matches('[data-css-preview-width],[data-css-preview-height]') && event.target.validity.valid) dimensions(node('preview-width').value, node('preview-height').value);
  });
  root.addEventListener('keydown', function (event) {
    var file = event.target.closest('[data-css-file]');
    if (file && ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
      event.preventDefault(); var files = Array.prototype.slice.call(root.querySelectorAll('[data-css-file]')); var index = files.indexOf(file);
      var next = event.key === 'Home' ? 0 : event.key === 'End' ? files.length - 1 : (index + (event.key === 'ArrowDown' ? 1 : -1) + files.length) % files.length;
      files[next].focus(); loadFile(files[next].dataset.cssFile); return;
    }
    if (event.key === 'Enter' && event.target.matches('input')) { event.preventDefault(); if (event.target.matches('[data-css-preview-path]')) navigatePreview(event.target.value); }
    if (event.key === 'Escape' && root.classList.contains('is-fullscreen')) { root.classList.remove('is-fullscreen'); button('fullscreen').setAttribute('aria-pressed', 'false'); if (editor) editor.refresh(); }
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && root.classList.contains('is-preview-expanded')) { event.preventDefault(); setPreviewExpanded(false); return; }
    if (!settings.classList.contains('is-css-editor') || !(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
    event.preventDefault(); save();
  });
  window.addEventListener('beforeunload', function (event) { if (dirtyCount()) { event.preventDefault(); event.returnValue = ''; } });
  document.addEventListener('genosys:settings-tab', function (event) { if (event.detail.tab === 'css-editor') initialize(); });
  var panel = root.querySelector('.gsce__preview');
  var workspace = root.querySelector('.gsce__workspace');
  var panelResize = node('panel-resize');
  var filesPanel = root.querySelector('.gsce__files');
  var filesResize = node('files-resize');
  function desktopPanel() { return window.matchMedia('(min-width: 1151px)').matches && !root.classList.contains('is-preview-expanded') && workspace.clientWidth > 0; }
  function setPanelWidth(width) {
    if (!desktopPanel()) return panel.getBoundingClientRect().width;
    var filesWidth = root.querySelector('.gsce__files').getBoundingClientRect().width;
    var gap = parseFloat(window.getComputedStyle(workspace).columnGap) || 20;
    var minimum = window.innerWidth <= 1400 ? 245 : window.innerWidth >= 1750 ? 380 : 280;
    var editorMinimum = window.innerWidth <= 1400 ? 340 : window.innerWidth >= 1750 ? 550 : 400;
    var maximum = Math.max(minimum, workspace.clientWidth - filesWidth - gap * 2 - editorMinimum);
    var applied = Math.round(Math.max(minimum, Math.min(maximum, width)));
    root.style.setProperty('--gsce-preview-panel-width', applied + 'px');
    return applied;
  }
  function setFilesWidth(width) {
    if (!desktopPanel()) return filesPanel.getBoundingClientRect().width;
    var previewPanelWidth = panel.getBoundingClientRect().width;
    var gap = parseFloat(window.getComputedStyle(workspace).columnGap) || 20;
    var minimum = window.innerWidth <= 1400 ? 185 : window.innerWidth >= 1750 ? 235 : 190;
    var editorMinimum = window.innerWidth <= 1400 ? 340 : window.innerWidth >= 1750 ? 550 : 400;
    var maximum = Math.max(minimum, workspace.clientWidth - previewPanelWidth - gap * 2 - editorMinimum);
    var applied = Math.round(Math.max(minimum, Math.min(maximum, width)));
    root.style.setProperty('--gsce-files-panel-width', applied + 'px');
    return applied;
  }
  function clampWorkspacePanels() {
    if (!desktopPanel()) return;
    if (root.style.getPropertyValue('--gsce-files-panel-width')) setFilesWidth(filesPanel.getBoundingClientRect().width);
    if (root.style.getPropertyValue('--gsce-preview-panel-width')) setPanelWidth(panel.getBoundingClientRect().width);
  }
  function startResize(control, event, update, corner) {
    if (event.button !== 0 || !event.isPrimary) return;
    event.preventDefault();
    control.setPointerCapture(event.pointerId);
    root.classList.add('is-preview-resizing');
    root.style.setProperty('--gsce-resize-cursor', corner ? 'nwse-resize' : 'col-resize');
    var previousSelect = document.body.style.userSelect;
    var previousCursor = document.body.style.cursor;
    document.body.style.userSelect = 'none';
    document.body.style.cursor = corner ? 'nwse-resize' : 'col-resize';
    var pending = null, tick = 0;
    function flush() { tick = 0; if (pending) { update(pending.clientX, pending.clientY); pending = null; } }
    function move(next) { pending = next; if (!tick) tick = requestAnimationFrame(flush); }
    function end(next) {
      if (next.type === 'pointerup') pending = next;
      if (tick) cancelAnimationFrame(tick);
      flush();
      control.removeEventListener('pointermove', move);
      control.removeEventListener('pointerup', end);
      control.removeEventListener('pointercancel', end);
      control.removeEventListener('lostpointercapture', end);
      root.classList.remove('is-preview-resizing');
      document.body.style.userSelect = previousSelect;
      document.body.style.cursor = previousCursor;
      if (control.hasPointerCapture(event.pointerId)) control.releasePointerCapture(event.pointerId);
      fitPreview();
    }
    control.addEventListener('pointermove', move);
    control.addEventListener('pointerup', end);
    control.addEventListener('pointercancel', end);
    control.addEventListener('lostpointercapture', end);
  }
  if (panelResize) {
    panelResize.addEventListener('pointerdown', function (event) {
      var startX = event.clientX, width = panel.getBoundingClientRect().width;
      previewDragScale = null;
      startResize(panelResize, event, function (x) { setPanelWidth(width + x - startX); fitPreview(); }, false);
    });
    panelResize.addEventListener('keydown', function (event) {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault(); previewDragScale = null;
      setPanelWidth(panel.getBoundingClientRect().width + (event.key === 'ArrowRight' ? 1 : -1) * (event.shiftKey ? 50 : 10));
      fitPreview();
    });
  }
  if (filesResize) {
    filesResize.addEventListener('pointerdown', function (event) {
      var startX = event.clientX, width = filesPanel.getBoundingClientRect().width;
      startResize(filesResize, event, function (x) { setFilesWidth(width + startX - x); fitPreview(); if (editor) editor.refresh(); }, false);
    });
    filesResize.addEventListener('keydown', function (event) {
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      setFilesWidth(filesPanel.getBoundingClientRect().width + (event.key === 'ArrowLeft' ? 1 : -1) * (event.shiftKey ? 50 : 10));
      fitPreview(); if (editor) editor.refresh();
    });
  }
  var resize = node('preview-resize');
  resize.addEventListener('pointerdown', function (event) {
    var startX = event.clientX, startY = event.clientY, startWidth = previewWidth, startHeight = previewHeight, scale = previewScale;
    var panelWidth = panel.getBoundingClientRect().width;
    var visibleWidth = previewWidth * scale;
    previewDragScale = scale;
    startResize(resize, event, function (x, y) {
      var delta = x - startX;
      if (desktopPanel()) {
        var appliedDelta = setPanelWidth(panelWidth + delta) - panelWidth;
        if (delta > 0) delta = appliedDelta;
      }
      else delta = Math.min(node('preview-stage').clientWidth - 34 - visibleWidth, delta);
      var width = Math.max(180 / scale, startWidth + delta / scale);
      dimensions(width, startHeight + (y - startY) / scale, true);
    }, true);
  });
  resize.addEventListener('keydown', function (event) {
    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
    event.preventDefault(); var step = event.shiftKey ? 50 : 10;
    dimensions(previewWidth + (event.key === 'ArrowRight' ? step : event.key === 'ArrowLeft' ? -step : 0), previewHeight + (event.key === 'ArrowDown' ? step : event.key === 'ArrowUp' ? -step : 0));
  });
  if (window.ResizeObserver) new ResizeObserver(fitPreview).observe(node('preview-stage'));
  window.addEventListener('resize', function () {
    if (root.classList.contains('is-preview-expanded')) fitExpandedPreview();
    else { clampWorkspacePanels(); fitPreview(); }
  });
  if (settings.classList.contains('is-css-editor')) initialize();
}());
