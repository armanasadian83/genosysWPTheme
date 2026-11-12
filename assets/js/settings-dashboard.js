(function () {
  'use strict';
  var root = document.querySelector('.gs-settings');
  if (!root) return;
  var form = root.querySelector('form') || root.closest('form');
  var tabs = Array.prototype.slice.call(root.querySelectorAll('.gs-tab[data-tab]'));
  var panels = Array.prototype.slice.call(root.querySelectorAll('.gs-panel'));
  var savebar = root.querySelector('.gs-savebar');
  var dirtyLabel = root.querySelector('[data-dirty-label]');
  var dirty = false;
  var pristineText = dirtyLabel ? dirtyLabel.textContent : '';
  var settings = window.genosysSettingsDashboard || {};

  function markDirty() {
    dirty = true;
    if (savebar) savebar.classList.add('is-dirty');
    if (dirtyLabel) dirtyLabel.textContent = settings.unsaved || 'تغییرات ذخیره‌نشده دارید';
  }

  function activateTab(key, focus, updateHash) {
    var chosen = tabs.find(function (tab) { return tab.dataset.tab === key; }) || tabs[0];
    if (!chosen) return;
    tabs.forEach(function (tab) {
      var selected = tab === chosen;
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.tabIndex = selected ? 0 : -1;
    });
    panels.forEach(function (panel) {
      panel.hidden = panel.id !== 'gs-panel-' + chosen.dataset.tab;
    });
    var activeInput = root.querySelector('[data-active-tab]');
    if (activeInput) activeInput.value = chosen.dataset.tab;
    root.classList.toggle('is-css-editor', chosen.dataset.tab === 'css-editor');
    root.classList.toggle('is-php-editor', chosen.dataset.tab === 'php-editor');
    document.dispatchEvent(new CustomEvent('genosys:settings-tab', { detail: { tab: chosen.dataset.tab } }));
    if (focus) chosen.focus();
    if (updateHash && window.history && window.history.replaceState) {
      window.history.replaceState(null, '', window.location.pathname + window.location.search + '#' + chosen.dataset.tab);
    }
    if ((focus || chosen.dataset.tab === 'css-editor' || chosen.dataset.tab === 'php-editor') && chosen.scrollIntoView) chosen.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'auto' });
  }

  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () { activateTab(tab.dataset.tab, false, true); });
    tab.addEventListener('keydown', function (event) {
      var next = index;
      var rtl = window.getComputedStyle(root).direction === 'rtl';
      if (event.key === 'ArrowRight') next = (index + (rtl ? -1 : 1) + tabs.length) % tabs.length;
      else if (event.key === 'ArrowLeft') next = (index + (rtl ? 1 : -1) + tabs.length) % tabs.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabs.length - 1;
      else return;
      event.preventDefault();
      activateTab(tabs[next].dataset.tab, true, true);
    });
  });
  root.classList.add('is-ready');
  activateTab(window.location.hash.replace(/^#(?:gs-panel-)?/, '') || (tabs.find(function (tab) { return tab.getAttribute('aria-selected') === 'true'; }) || {}).dataset?.tab, false, false);
  window.addEventListener('hashchange', function () { activateTab(window.location.hash.replace(/^#(?:gs-panel-)?/, ''), false, false); });

  function mediaFileName(value) {
    try { return decodeURIComponent(value.split('/').pop().split('?')[0]) || ''; }
    catch (error) { return value.split('/').pop().split('?')[0] || ''; }
  }
  function updateMedia(container, value, title, attachmentId) {
    var isVideo = container.dataset.mediaKind === 'video';
    var input = container.querySelector('[data-media-value]');
    var idInput = container.querySelector('[data-media-id]');
    if (idInput) idInput.value = value ? String(attachmentId || 0) : '0';
    var preview = container.querySelector('[data-media-preview]');
    var label = container.querySelector('[data-media-label]');
    var remove = container.querySelector('[data-media-remove]');
    if (input) {
      input.value = value;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (preview && !isVideo) {
      var image = preview.querySelector('img');
      if (!image && value) {
        image = document.createElement('img');
        image.alt = '';
        preview.appendChild(image);
      }
      if (image) {
        image.hidden = !value;
        if (value) image.src = value;
        else image.removeAttribute('src');
      }
      Array.prototype.forEach.call(preview.querySelectorAll('svg,.dashicons'), function (icon) {
        if (value) icon.setAttribute('hidden', '');
        else icon.removeAttribute('hidden');
      });
    }
    if (label) label.textContent = value ? (title || mediaFileName(value)) : (isVideo ? 'ویدیویی انتخاب نشده' : (settings.noImage || 'تصویری انتخاب نشده'));
    if (remove) remove.hidden = !value;
    var error = container.closest('.gs-field')?.querySelector('[data-media-error]');
    if (error) { error.textContent = ''; error.hidden = true; }
    container.classList.toggle('has-image', !!value);
  }

  function chooseMedia(container) {
    if (!window.wp || !window.wp.media) return;
    var isVideo = container.dataset.mediaKind === 'video';
    var videoTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    var frame = window.wp.media({
      title: isVideo ? 'بارگذاری یا انتخاب ویدیوی صفحه برند' : (settings.mediaTitle || 'انتخاب تصویر'),
      button: { text: isVideo ? 'استفاده از این ویدیو' : (settings.mediaButton || 'استفاده از تصویر') },
      library: { type: isVideo ? videoTypes : 'image' },
      multiple: false
    });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first();
      if (!attachment) return;
      var data = attachment.toJSON();
      if (isVideo && (videoTypes.indexOf(data.mime) === -1 || !data.id)) {
        var error = container.closest('.gs-field')?.querySelector('[data-media-error]');
        if (error) { error.textContent = 'یک ویدیوی MP4، WebM یا OGV از کتابخانه رسانه انتخاب کنید.'; error.hidden = false; }
        return;
      }
      if (data.url) updateMedia(container, data.url, data.filename || data.title, data.id);
    });
    frame.open();
  }

  function renumberRows(repeater) {
    var rows = repeater.querySelector('.gs-repeater__rows');
    if (!rows) return;
    Array.prototype.forEach.call(rows.children, function (row, index) {
      row.dataset.rowIndex = index;
      Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (field) {
        field.name = field.name.replace(/\[(?:\d+|__INDEX__)\]/, '[' + index + ']');
      });
      var ids = {};
      Array.prototype.forEach.call(row.querySelectorAll('[id]'), function (element) {
        var oldId = element.id;
        var newId = oldId.replace(/(?:__index__|\d+)$/i, String(index));
        if (oldId !== newId) { ids[oldId] = newId; element.id = newId; }
      });
      Array.prototype.forEach.call(row.querySelectorAll('label[for]'), function (label) {
        if (ids[label.htmlFor]) label.htmlFor = ids[label.htmlFor];
        else label.htmlFor = label.htmlFor.replace(/__index__/gi, String(index));
      });
    });
    var empty = repeater.querySelector('.gs-empty');
    if (empty) empty.hidden = rows.children.length > 0;
  }

  function updateMoveButtons(container) {
    if (!container) return;
    var rows = Array.prototype.slice.call(container.querySelectorAll('.gs-section-row'));
    rows.forEach(function (row, index) {
      var up = row.querySelector('[data-move="up"]');
      var down = row.querySelector('[data-move="down"]');
      if (up) up.disabled = index === 0;
      if (down) down.disabled = index === rows.length - 1;
    });
  }

  function syncToggle(field) {
    var holder = field.closest('.gs-toggle');
    if (holder) holder.classList.toggle('is-enabled', field.checked);
  }

  function updatePreview(field) {
    var targetKey = field.dataset.previewTarget;
    if (!targetKey) return;
    var preview = root.querySelector('.gs-nav-preview');
    if (!preview) return;
    var targets = Array.prototype.filter.call(preview.querySelectorAll('[data-preview]'), function (target) { return target.dataset.preview === targetKey; });
    if (targetKey === 'actions' && !targets.length) {
      var actions = preview.querySelector('.gs-preview-actions');
      if (actions) targets.push(actions);
    }
    var kind = field.dataset.previewKind || (field.type === 'checkbox' ? 'visible' : 'text');
    targets.forEach(function (target) {
      if (kind === 'visible') target.hidden = field.type === 'checkbox' ? !field.checked : field.value === 'no';
      else if (kind === 'color' && /^#[\da-f]{6}$/i.test(field.value)) target.style.color = field.value;
      else if (kind === 'background' && /^#[\da-f]{6}$/i.test(field.value)) target.style.background = field.value;
      else if (kind === 'image') {
        var image = target.matches('img') ? target : target.querySelector('img');
        if (image && field.value) image.src = field.value;
      }
      else if (kind === 'height') {
        var height = Number(field.value);
        if (Number.isFinite(height) && height >= 50 && height <= 180) target.style.minHeight = height + 'px';
      }
      else if (kind === 'text') target.textContent = field.value;
    });
  }

  function hexColor(value) {
    value = String(value || '').trim();
    if (/^#[\da-f]{3}$/i.test(value)) value = '#' + value.slice(1).split('').map(function (digit) { return digit + digit; }).join('');
    return /^#[\da-f]{6}$/i.test(value) ? value : '';
  }

  function syncNavbarPreview() {
    var preview = root.querySelector('.gs-nav-preview');
    if (!preview) return;
    var inner = preview.classList.contains('is-inner');
    var logo = preview.querySelector('[data-preview="logo"]');
    function value(key) {
      var field = root.querySelector('[name="site[' + key + ']"]');
      return field ? field.value : '';
    }
    if (logo) {
      if (!logo.dataset.defaultHomeLogo) logo.dataset.defaultHomeLogo = preview.dataset.homeLogo || logo.src;
      if (!logo.dataset.defaultInnerLogo) logo.dataset.defaultInnerLogo = preview.dataset.innerLogo || logo.dataset.defaultHomeLogo.replace('genosys-logo-light-1.2.4.svg', 'genosys-logo-1.1.1.svg');
      var source = value(inner ? 'header_logo_internal' : 'header_logo_home') || (inner ? logo.dataset.defaultInnerLogo : logo.dataset.defaultHomeLogo);
      if (source && logo.src !== source) logo.src = source;
    }
    var iconColor = hexColor(value(inner ? 'header_icon_color_internal' : 'header_icon_color_home'));
    var icons = preview.querySelector('.gs-preview-actions');
    if (icons && iconColor) icons.style.color = iconColor;
    var hover = hexColor(value('header_icon_hover'));
    if (hover) preview.style.setProperty('--gs-preview-hover', hover);
    var linkColor = hexColor(value(inner ? 'header_link_color_internal' : 'header_link_color_home'));
    var linkHover = hexColor(value(inner ? 'header_link_hover_internal' : 'header_link_hover_home'));
    if (linkColor) preview.style.setProperty('--gs-preview-menu-color', linkColor);
    if (linkHover) preview.style.setProperty('--gs-preview-link-hover', linkHover);
    var background = hexColor(value(inner ? 'header_background_internal' : 'header_background_home'));
    preview.style.background = background || (inner ? '#ffffff' : 'linear-gradient(110deg,#6c665e,#2c2924)');
    var primary = hexColor(value('primary_color'));
    var login = preview.querySelector('.gs-preview-login');
    if (login && primary) login.style.background = primary;
    var height = Number(value('header_height'));
    if (Number.isFinite(height) && height >= 76 && height <= 180) preview.style.minHeight = height + 'px';
  }

  function syncColor(field) {
    var holder = field.closest('.gs-color');
    if (!holder) return;
    var color = holder.querySelector('input[type="color"]');
    var text = holder.querySelector('[data-color-text]');
    if (!color || !text) return;
    if (field === color) {
      text.value = color.value;
      text.setCustomValidity('');
      updatePreview(text);
    } else if (field === text) {
      var normalized = hexColor(text.value);
      var valid = !!normalized;
      text.setCustomValidity(valid || !text.value.trim() ? '' : (settings.invalidColor || 'رنگ را به صورت #c8102e وارد کنید.'));
      if (valid) color.value = normalized;
    }
  }

  root.addEventListener('click', function (event) {
    var choose = event.target.closest('[data-media-choose]');
    if (choose && root.contains(choose)) {
      event.preventDefault();
      var media = choose.closest('[data-media]');
      if (media) chooseMedia(media);
      return;
    }
    var clear = event.target.closest('[data-media-remove]');
    if (clear && root.contains(clear)) {
      event.preventDefault();
      var mediaContainer = clear.closest('[data-media]');
      if (mediaContainer) updateMedia(mediaContainer, '', '');
      return;
    }
    var add = event.target.closest('[data-add-row]');
    if (add && root.contains(add)) {
      event.preventDefault();
      var repeater = add.closest('.gs-repeater');
      if (!repeater) return;
      var template = repeater.querySelector('template[data-row-template]');
      var rows = repeater.querySelector('.gs-repeater__rows');
      var max = Number(repeater.dataset.maxRows || 30);
      if (!template || !rows || rows.children.length >= max) return;
      var fragment = template.content.cloneNode(true);
      rows.appendChild(fragment);
      renumberRows(repeater);
      markDirty();
      var lastRow = rows.lastElementChild;
      var firstField = lastRow && lastRow.querySelector('input:not([type="hidden"]),select,textarea,[data-media-choose]');
      if (firstField) firstField.focus();
      return;
    }
    var remove = event.target.closest('[data-remove-row]');
    if (remove && root.contains(remove)) {
      event.preventDefault();
      var row = remove.closest('.gs-repeater__row');
      var rowRepeater = remove.closest('.gs-repeater');
      if (!row || !rowRepeater) return;
      var nextFocus = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      renumberRows(rowRepeater);
      markDirty();
      var focusTarget = nextFocus ? nextFocus.querySelector('[data-remove-row]') : rowRepeater.querySelector('[data-add-row]');
      if (focusTarget) focusTarget.focus();
      return;
    }
    var move = event.target.closest('[data-move]');
    if (move && root.contains(move)) {
      event.preventDefault();
      var section = move.closest('.gs-section-row');
      var sectionList = move.closest('.gs-sections');
      if (!section || !sectionList || move.disabled) return;
      if (move.dataset.move === 'up' && section.previousElementSibling) sectionList.insertBefore(section, section.previousElementSibling);
      else if (move.dataset.move === 'down' && section.nextElementSibling) sectionList.insertBefore(section.nextElementSibling, section);
      updateMoveButtons(sectionList);
      move.focus();
      markDirty();
      return;
    }
    var passwordToggle = event.target.closest('[data-password-toggle]');
    if (passwordToggle && root.contains(passwordToggle)) {
      event.preventDefault();
      var passwordHolder = passwordToggle.closest('.gs-password') || passwordToggle.parentElement;
      var password = passwordHolder.querySelector('input');
      if (!password) return;
      var show = password.type === 'password';
      password.type = show ? 'text' : 'password';
      passwordToggle.setAttribute('aria-pressed', show ? 'true' : 'false');
      passwordToggle.setAttribute('aria-label', show ? 'پنهان کردن کلید API' : 'نمایش کلید API');
      return;
    }
    var mode = event.target.closest('[data-preview-mode]');
    if (mode && root.contains(mode)) {
      event.preventDefault();
      var navPreview = root.querySelector('.gs-nav-preview');
      if (navPreview) navPreview.classList.toggle('is-inner', mode.dataset.previewMode === 'inner');
      syncNavbarPreview();
      Array.prototype.forEach.call(root.querySelectorAll('[data-preview-mode]'), function (button) { button.setAttribute('aria-pressed', button === mode ? 'true' : 'false'); });
    }
  });

  function onFieldChange(event) {
    var field = event.target;
    if (field.closest('[data-css-editor],[data-php-editor]')) return;
    if (!field.matches('input,textarea,select') || field.type === 'submit' || field.type === 'button') return;
    if (field.type === 'checkbox') syncToggle(field);
    syncColor(field);
    updatePreview(field);
    syncNavbarPreview();
    markDirty();
  }
  root.addEventListener('input', onFieldChange);
  root.addEventListener('change', onFieldChange);
  Array.prototype.forEach.call(root.querySelectorAll('.gs-toggle input[type="checkbox"]'), syncToggle);
  Array.prototype.forEach.call(root.querySelectorAll('.gs-repeater'), renumberRows);
  Array.prototype.forEach.call(root.querySelectorAll('.gs-sections'), updateMoveButtons);
  Array.prototype.forEach.call(root.querySelectorAll('[data-preview-target]'), updatePreview);
  syncNavbarPreview();
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
  if (form) {
    form.addEventListener('submit', function (event) {
      if (root.classList.contains('is-css-editor') || root.classList.contains('is-php-editor')) { event.preventDefault(); return; }
      dirty = false;
      if (savebar) {
        savebar.classList.remove('is-dirty');
        savebar.setAttribute('aria-busy', 'true');
      }
      if (dirtyLabel) dirtyLabel.textContent = settings.saving || 'در حال ذخیره تغییرات';
    });
    form.addEventListener('invalid', function (event) {
      var panel = event.target.closest('.gs-panel');
      if (panel) activateTab(panel.id.replace('gs-panel-', ''), false, true);
    }, true);
  }
  if (dirtyLabel && !pristineText) dirtyLabel.textContent = settings.saved || 'همه تغییرات ذخیره شده است';
}());
