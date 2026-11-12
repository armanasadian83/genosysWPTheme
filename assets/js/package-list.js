(function () {
    'use strict';

    function persianNumber(value) {
        return String(value).replace(/\d/g, function (digit) {
            return '۰۱۲۳۴۵۶۷۸۹'.charAt(Number(digit));
        });
    }

    function initList(root) {
        if (root.dataset.packageListReady) return;
        root.dataset.packageListReady = 'true';

        var selectAll = root.querySelector('[data-package-select-all]');
        var bulkForm = root.querySelector('[data-package-bulk]');
        var selectedCount = root.querySelector('[data-package-selected-count]');
        var selectedInputs = root.querySelector('[data-package-selected-inputs]');

        function selectable() {
            return Array.prototype.filter.call(root.querySelectorAll('[data-package-select]'), function (input) {
                return !input.disabled;
            });
        }

        function updateSelection() {
            var available = selectable();
            var selected = available.filter(function (input) { return input.checked; });

            if (selectAll) {
                selectAll.checked = available.length > 0 && selected.length === available.length;
                selectAll.indeterminate = selected.length > 0 && selected.length < available.length;
                selectAll.disabled = available.length === 0;
            }

            root.querySelectorAll('[data-package-select]').forEach(function (input) {
                var row = input.closest('[data-package-row]');
                if (row) row.classList.toggle('is-selected', input.checked && !input.disabled);
            });

            if (selectedCount) selectedCount.textContent = persianNumber(selected.length);
            if (selectedInputs) {
                selectedInputs.replaceChildren();
                var ids = Object.create(null);
                selected.forEach(function (input) {
                    var id = String(input.value).trim();
                    if (!/^[1-9]\d*$/.test(id) || ids[id]) return;
                    ids[id] = true;
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'ids[]';
                    hidden.value = id;
                    selectedInputs.appendChild(hidden);
                });
            }
            if (bulkForm) bulkForm.hidden = selected.length === 0;
            return selected.length;
        }

        root.addEventListener('change', function (event) {
            var target = event.target;
            if (target === selectAll) {
                selectable().forEach(function (input) { input.checked = selectAll.checked; });
                updateSelection();
            } else if (target.matches('[data-package-select]')) {
                updateSelection();
            } else if (target.matches('select[name="gpl_order"]')) {
                var form = target.closest('form');
                if (form) {
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else HTMLFormElement.prototype.submit.call(form);
                }
            }
        });

        root.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-package-toggle]');
            if (!toggle || !root.contains(toggle) || toggle.disabled) return;
            var detail = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!detail || !root.contains(detail)) return;
            event.preventDefault();
            var expanded = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', String(expanded));
            detail.hidden = !expanded;
            var row = toggle.closest('[data-package-row]');
            if (row) row.classList.toggle('is-open', expanded);
            var label = toggle.querySelector('[data-package-toggle-label]');
            if (label) label.textContent = expanded ? 'بستن محتوا' : 'مشاهده‌ی محتوا';
        });

        root.addEventListener('submit', function (event) {
            var form = event.target;
            if (form === bulkForm && updateSelection() === 0) {
                event.preventDefault();
                return;
            }
            if (!form.matches('[data-package-confirm]')) return;
            var message = form.getAttribute('data-package-confirm');
            if (message && !window.confirm(message)) event.preventDefault();
        });

        // Browsers may restore checked controls when returning to this list.
        window.addEventListener('pageshow', updateSelection);
        updateSelection();
    }

    function init() {
        document.querySelectorAll('[data-package-list]').forEach(initList);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
