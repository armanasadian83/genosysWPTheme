(function () {
    'use strict';

    function init() {
        var root = document.querySelector('.gpk[data-package-builder]');
        if (!root || root.dataset.packageBuilderReady) return;
        root.dataset.packageBuilderReady = 'true';

        var config = window.genosysPackageBuilder || {};
        var precision = Number(config.priceDecimals);
        precision = Number.isInteger(precision) ? Math.max(0, Math.min(6, precision)) : 0;
        var factor = Math.pow(10, precision);
        var decimalSeparator = String(config.decimalSeparator || '.');
        var thousandSeparator = typeof config.thousandSeparator === 'string' ? config.thousandSeparator : ',';
        var currency = String(config.currencySymbol || 'تومان');
        var maxItems = Math.max(2, Math.min(24, Number(config.maxItems) || 24));
        var items = root.querySelector('[data-package-items]');
        var rowTemplate = root.querySelector('[data-package-row-template]');
        var iconTemplate = root.querySelector('[data-product-icon]');
        var addButton = root.querySelector('[data-add-product]');
        var nameInput = root.querySelector('[data-package-name]');
        var priceInput = root.querySelector('[data-package-price]');
        var skuInput = root.querySelector('[data-package-sku]');
        var statusInput = root.querySelector('[data-package-status]');
        var visibilityInput = root.querySelector('[data-package-visibility]');
        var statusLabel = root.querySelector('[data-package-status-label]') || root.querySelector('.gpk__draft');
        var saveButtons = Array.prototype.slice.call(root.querySelectorAll('[data-package-action], [data-prototype-action]'));
        var slug = root.querySelector('[data-package-slug]');
        var editor = root.querySelector('[data-package-editor]');
        var toast = root.querySelector('[data-package-message]');
        var upload = root.querySelector('[data-package-upload]');
        var imageIdInput = root.querySelector('[data-package-image]');
        var imageError = root.querySelector('[data-image-error]');
        var uploadPreview = root.querySelector('[data-upload-preview]');
        var uploadCopy = root.querySelector('[data-upload-copy]');
        var imageChange = root.querySelector('[data-image-change]');
        var imageRemove = root.querySelector('[data-image-remove]');
        var previewImage = root.querySelector('[data-preview-image]');
        var previewIcon = root.querySelector('[data-preview-icon]');
        var previewName = root.querySelector('[data-preview-name]');
        var emptyPreviewName = previewName ? previewName.textContent : 'نام پکیج شما';
        var rows = [];
        var rowSequence = 0;
        var openRow = null;
        var totalMinor = null;
        var selectedDiscount = null;
        var selectedImage = null;
        var mediaFrame = null;
        var mediaReturnFocus = null;
        var saving = false;
        var packageId = Number(config.packageId) || 0;
        packageId = Number.isSafeInteger(packageId) && packageId > 0 ? packageId : 0;
        var savedPermalink = '';
        var creationToken = makeRequestToken();
        var retryFingerprint = '';
        var retryToken = '';
        var toastTimer = null;
        var editorSelection = null;

        function normalizeDigits(value) {
            return String(value).replace(/[۰-۹]/g, function (digit) {
                return String(digit.charCodeAt(0) - 1776);
            }).replace(/[٠-٩]/g, function (digit) {
                return String(digit.charCodeAt(0) - 1632);
            });
        }

        function persianDigits(value) {
            return String(value).replace(/[0-9]/g, function (digit) {
                return String.fromCharCode(1776 + Number(digit));
            });
        }

        function replaceAllText(value, find, replacement) {
            return find ? value.split(find).join(replacement) : value;
        }

        function parsePrice(value) {
            var normalized = normalizeDigits(value).trim().replace(/\s/g, '');
            normalized = replaceAllText(normalized, thousandSeparator, '');
            normalized = normalized.replace(/٬/g, '');
            normalized = replaceAllText(normalized, decimalSeparator, '.').replace(/٫/g, '.');
            if (decimalSeparator !== ',') normalized = normalized.replace(/,/g, '');
            return decimalToMinor(normalized);
        }

        function decimalToMinor(value) {
            var normalized = normalizeDigits(value).trim();
            if (!/^\d+(?:\.\d*)?$/.test(normalized)) return null;
            var parts = normalized.split('.');
            var fractional = (parts[1] || '').padEnd(precision + 1, '0');
            var minor = Number(parts[0]) * factor;
            if (precision) minor += Number(fractional.slice(0, precision));
            if (Number(fractional.charAt(precision)) >= 5) minor += 1;
            return Number.isSafeInteger(minor) ? minor : null;
        }

        function formatMoney(minor) {
            if (minor === null || !Number.isSafeInteger(minor)) return '—';
            var absolute = Math.abs(minor);
            var whole = String(Math.floor(absolute / factor));
            whole = whole.replace(/\B(?=(\d{3})+(?!\d))/g, function () { return thousandSeparator; });
            var fraction = precision ? decimalSeparator + String(absolute % factor).padStart(precision, '0') : '';
            return persianDigits((minor < 0 ? '-' : '') + whole + fraction) + ' ' + currency;
        }

        function priceForInput(minor) {
            var whole = Math.floor(minor / factor);
            return String(whole) + (precision ? decimalSeparator + String(minor % factor).padStart(precision, '0') : '');
        }

        function canonicalPrice(minor) {
            if (minor === null) return '';
            return String(Math.floor(minor / factor)) + (precision ? '.' + String(minor % factor).padStart(precision, '0') : '');
        }

        function makeRequestToken() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
            if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
                var bytes = new Uint8Array(16);
                window.crypto.getRandomValues(bytes);
                return Array.prototype.map.call(bytes, function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
            }
            return 'gpk-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
        }

        function quantity(input) {
            var value = normalizeDigits(input ? input.value : '1').trim();
            if (!/^\d+$/.test(value)) return 1;
            return Math.max(1, Math.min(9999, Number(value) || 1));
        }

        function setText(selector, text) {
            var node = root.querySelector(selector);
            if (node) node.textContent = text;
        }

        function showToast(message, isError) {
            if (!toast) return;
            window.clearTimeout(toastTimer);
            toast.textContent = message;
            toast.classList.toggle('is-error', !!isError);
            toast.hidden = false;
            toastTimer = window.setTimeout(function () { toast.hidden = true; }, 7000);
        }

        function productIcon() {
            if (iconTemplate && iconTemplate.content) return iconTemplate.content.cloneNode(true);
            var fallback = document.createElement('span');
            fallback.textContent = '◈';
            fallback.setAttribute('aria-hidden', 'true');
            return fallback;
        }

        function safeImageURL(value) {
            try {
                var url = new URL(String(value || ''));
                return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null;
            } catch (ignored) {
                return null;
            }
        }

        function renderThumb(container, imageURL) {
            if (!container) return;
            container.replaceChildren();
            var safeURL = safeImageURL(imageURL);
            if (!safeURL) {
                container.appendChild(productIcon());
                return;
            }
            var image = document.createElement('img');
            image.src = safeURL;
            image.alt = '';
            image.loading = 'lazy';
            image.decoding = 'async';
            image.addEventListener('error', function () {
                if (image.parentNode === container) container.replaceChildren(productIcon());
            }, { once: true });
            container.appendChild(image);
        }

        function normalizeProduct(raw) {
            if (!raw || typeof raw !== 'object') return null;
            var id = Number(raw.id);
            if (!Number.isSafeInteger(id) || id <= 0 || typeof raw.name !== 'string') return null;
            var price = typeof raw.price === 'number' && Number.isFinite(raw.price) && raw.price >= 0 ? raw.price : null;
            var minor = Number.isSafeInteger(raw.price_minor) && raw.price_minor >= 0 ? raw.price_minor : null;
            if (minor === null && price !== null) {
                var derived = Math.round(price * factor);
                if (Number.isSafeInteger(derived)) minor = derived;
            }
            var stock = typeof raw.stock_quantity === 'number' && Number.isFinite(raw.stock_quantity) ? raw.stock_quantity : null;
            var pool = Number(raw.stock_pool_id);
            var state = ['available', 'low', 'outofstock', 'unmanaged', 'backorder'].indexOf(raw.stock_state) >= 0 ? raw.stock_state : 'unmanaged';
            return {
                id: id,
                name: raw.name,
                sku: typeof raw.sku === 'string' ? raw.sku : '',
                priceMinor: minor,
                image: safeImageURL(raw.image),
                stockQuantity: stock,
                stockPool: Number.isSafeInteger(pool) && pool > 0 ? pool : id,
                stockStatus: raw.stock_status,
                stockState: state,
                stockLabel: typeof raw.stock_label === 'string' ? raw.stock_label : 'نامشخص'
            };
        }

        function catalogError(message) {
            var error = new Error(message);
            error.catalogMessage = message;
            return error;
        }

        function isDuplicate(productId, exceptRow) {
            return rows.some(function (row) {
                return row !== exceptRow && row.product && row.product.id === productId;
            });
        }

        function packageStock() {
            var selected = rows.filter(function (row) { return !!row.product; });
            if (!selected.length) return '—';
            var pools = new Map();
            var forcedZero = false;
            selected.forEach(function (row) {
                var product = row.product;
                if (product.stockStatus === 'outofstock' || (product.stockQuantity !== null && product.stockQuantity <= 0)) forcedZero = true;
                var pool = pools.get(product.stockPool);
                if (!pool) {
                    pool = { stock: null, demand: 0 };
                    pools.set(product.stockPool, pool);
                }
                pool.demand += quantity(row.qty);
                if (product.stockQuantity !== null) {
                    pool.stock = pool.stock === null ? product.stockQuantity : Math.min(pool.stock, product.stockQuantity);
                }
            });
            if (forcedZero) return '۰ عدد';
            var bound = null;
            pools.forEach(function (pool) {
                if (pool.stock === null) return;
                var available = Math.max(0, Math.floor(pool.stock / pool.demand));
                bound = bound === null ? available : Math.min(bound, available);
            });
            return bound === null ? 'نامشخص' : persianDigits(bound) + ' عدد';
        }

        function update() {
            var selected = rows.filter(function (row) { return !!row.product; });
            var distinct = new Set(selected.map(function (row) { return row.product.id; })).size;
            totalMinor = selected.length ? 0 : null;
            selected.forEach(function (row) {
                if (totalMinor === null || row.product.priceMinor === null) {
                    totalMinor = null;
                    return;
                }
                var nextTotal = totalMinor + row.product.priceMinor * quantity(row.qty);
                totalMinor = Number.isSafeInteger(nextTotal) ? nextTotal : null;
            });
            var name = nameInput ? nameInput.value.trim() : '';
            var packagePrice = priceInput ? parsePrice(priceInput.value) : null;
            rows.forEach(function (row) {
                var count = quantity(row.qty);
                row.node.querySelectorAll('[data-qty-step]').forEach(function (button) {
                    var step = Number(button.dataset.qtyStep);
                    button.disabled = step < 0 ? count <= 1 : count >= 9999;
                });
            });
            setText('[data-total-price]', formatMoney(totalMinor));
            setText('[data-package-stock]', packageStock());
            setText('[data-product-count]', distinct ? persianDigits(distinct) + ' محصول' : '—');
            setText('[data-preview-count]', persianDigits(distinct));
            setText('[data-preview-name]', name || emptyPreviewName);
            setText('[data-preview-price]', formatMoney(packagePrice));

            if (slug) {
                var generatedSlug = name.normalize('NFKC').toLowerCase().replace(/[\s\u200c]+/g, '-').replace(/[^\p{L}\p{N}-]/gu, '').replace(/-+/g, '-').replace(/^-|-$/g, '');
                var visiblePermalink = savedPermalink;
                try { visiblePermalink = decodeURI(savedPermalink); } catch (ignored) {}
                slug.textContent = visiblePermalink || String(config.slugBase || '') + (generatedSlug || '…');
            }
            var hasDiscount = packagePrice !== null && packagePrice > 0 && totalMinor !== null && totalMinor > packagePrice;
            var original = root.querySelector('[data-preview-original]');
            var discount = root.querySelector('[data-preview-discount]');
            if (original) {
                original.textContent = formatMoney(totalMinor);
                original.hidden = !hasDiscount;
            }
            if (discount) {
                discount.textContent = hasDiscount ? persianDigits(Math.round((totalMinor - packagePrice) * 100 / totalMinor)) + '٪' : '';
                discount.hidden = !hasDiscount;
            }
            root.querySelectorAll('[data-discount]').forEach(function (button) {
                var percent = Number(button.dataset.discount);
                var active = selectedDiscount === percent && totalMinor !== null && totalMinor > 0 && packagePrice !== null && packagePrice === Math.round(totalMinor * (100 - percent) / 100);
                button.classList.toggle('is-active', active);
                button.classList.toggle('is-selected', active);
                button.setAttribute('aria-pressed', String(active));
            });
            var checks = { name: !!name, products: distinct >= 2, image: !!selectedImage, price: packagePrice !== null && packagePrice > 0 };
            root.querySelectorAll('[data-check]').forEach(function (item) {
                item.classList.toggle('is-complete', !!checks[item.dataset.check]);
            });
            if (previewImage) previewImage.alt = name ? 'تصویر ' + name : 'تصویر پکیج';
            return checks;
        }

        function setActiveOption(row, index) {
            var enabled = row.optionNodes.filter(function (option) { return !option.disabled; });
            if (!enabled.length) index = -1;
            else index = Math.max(0, Math.min(enabled.length - 1, index));
            row.activeOption = index;
            row.optionNodes.forEach(function (option) { option.classList.remove('is-active'); });
            var active = index >= 0 ? enabled[index] : null;
            if (active) {
                active.classList.add('is-active');
                row.search.setAttribute('aria-activedescendant', active.id);
                active.scrollIntoView({ block: 'nearest' });
            } else row.search.removeAttribute('aria-activedescendant');
        }

        function renderOptions(row) {
            if (!row.options) return;
            var scrollTop = row.options.scrollTop;
            row.options.replaceChildren();
            row.optionNodes = [];
            row.products.forEach(function (product) {
                var duplicate = isDuplicate(product.id, row);
                var option = document.createElement('button');
                option.type = 'button';
                option.className = 'gpk__option';
                option.id = 'gpk-option-' + row.id + '-' + product.id;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', String(!!row.product && row.product.id === product.id));
                option.tabIndex = -1;
                option.disabled = duplicate;
                option.classList.toggle('is-disabled', duplicate);
                if (duplicate) option.title = 'این محصول قبلاً انتخاب شده است.';
                var thumb = document.createElement('span');
                thumb.className = 'gpk__option-thumb';
                renderThumb(thumb, product.image);
                var copy = document.createElement('span');
                copy.className = 'gpk__option-copy';
                var title = document.createElement('span');
                title.className = 'gpk__option-name';
                title.textContent = product.name;
                var price = document.createElement('span');
                price.className = 'gpk__option-price';
                price.textContent = formatMoney(product.priceMinor);
                copy.appendChild(title);
                copy.appendChild(price);
                var stock = document.createElement('span');
                stock.className = 'gpk__option-stock';
                stock.dataset.state = product.stockState;
                stock.textContent = duplicate ? 'قبلاً انتخاب شده' : product.stockLabel;
                option.appendChild(thumb);
                option.appendChild(copy);
                option.appendChild(stock);
                option.addEventListener('click', function () { selectProduct(row, product); });
                row.options.appendChild(option);
                row.optionNodes.push(option);
            });
            row.options.scrollTop = scrollTop;
            row.activeOption = -1;
            if (row.search) row.search.removeAttribute('aria-activedescendant');
            if (row.status) {
                row.status.textContent = row.loading ? 'در حال دریافت محصولات…' : (row.error || (row.products.length ? '' : 'محصولی پیدا نشد.'));
                row.status.hidden = !row.status.textContent;
            }
            if (row.more) {
                // Keep keyboard focus inside the picker while appending results.
                row.more.disabled = false;
                row.more.setAttribute('aria-disabled', String(row.loading));
                row.more.setAttribute('aria-busy', String(row.loading));
                if (!row.hasMore && document.activeElement === row.more && !row.popover.hidden) row.search.focus();
                row.more.hidden = !row.hasMore;
            }
        }

        function closePicker(row, restoreFocus) {
            if (!row) return;
            row.popover.hidden = true;
            row.picker.classList.remove('is-open');
            row.trigger.setAttribute('aria-expanded', 'false');
            row.search.setAttribute('aria-expanded', 'false');
            row.search.removeAttribute('aria-activedescendant');
            if (openRow === row) openRow = null;
            if (restoreFocus) row.trigger.focus();
        }

        function openPicker(row) {
            if (openRow && openRow !== row) closePicker(openRow, false);
            openRow = row;
            row.popover.hidden = false;
            row.picker.classList.add('is-open');
            row.trigger.setAttribute('aria-expanded', 'true');
            row.search.setAttribute('aria-expanded', 'true');
            row.search.focus();
            if (!row.loaded && !row.loading && !row.timer) requestProducts(row, false);
            else renderOptions(row);
        }

        async function requestProducts(row, append) {
            if (row.abort) row.abort.abort();
            var generation = ++row.generation;
            var controller = new AbortController();
            row.abort = controller;
            row.loading = true;
            row.error = '';
            if (!append) {
                row.products = [];
                row.hasMore = false;
            }
            renderOptions(row);
            var requestedPage = append ? row.page + 1 : 1;
            try {
                if (!config.endpoint) throw catalogError('دریافت فهرست محصولات در دسترس نیست.');
                var url = new URL(String(config.endpoint), window.location.href);
                if (url.origin !== window.location.origin) throw catalogError('دریافت فهرست محصولات در دسترس نیست.');
                url.searchParams.set('action', String(config.action || 'genosys_package_products'));
                url.searchParams.set('nonce', String(config.nonce || ''));
                url.searchParams.set('q', row.query);
                url.searchParams.set('page', String(requestedPage));
                var response = await window.fetch(url.href, {
                    method: 'GET', credentials: 'same-origin', signal: controller.signal,
                    headers: { Accept: 'application/json' }
                });
                var payload = await response.json();
                if (generation !== row.generation || !rows.includes(row)) return;
                if (!response.ok || !payload || payload.success !== true || !payload.data || !Array.isArray(payload.data.products)) {
                    var message = payload && payload.data && typeof payload.data.message === 'string' ? payload.data.message : 'دریافت محصولات انجام نشد. دوباره تلاش کنید.';
                    throw catalogError(message);
                }
                var found = payload.data.products.map(normalizeProduct).filter(Boolean);
                var combined = append ? row.products.concat(found) : found;
                var seen = new Set();
                row.products = combined.filter(function (product) {
                    if (seen.has(product.id)) return false;
                    seen.add(product.id);
                    return true;
                });
                row.page = requestedPage;
                row.hasMore = payload.data.has_more === true;
                row.loaded = true;
            } catch (error) {
                if (generation !== row.generation || !rows.includes(row) || error.name === 'AbortError') return;
                row.error = error.catalogMessage || 'دریافت محصولات انجام نشد. دوباره تلاش کنید.';
                row.loaded = false;
                if (!append) row.hasMore = false;
            } finally {
                if (generation === row.generation && rows.includes(row)) {
                    row.loading = false;
                    row.abort = null;
                    renderOptions(row);
                }
            }
        }

        function setRowProduct(row, product) {
            row.product = product;
            row.node.classList.add('has-product');
            row.node.classList.add('is-selected');
            row.picker.classList.add('is-selected');
            if (row.name) row.name.textContent = product.name;
            if (row.price) {
                row.price.textContent = formatMoney(product.priceMinor);
                row.price.hidden = false;
            }
            if (row.thumb) row.thumb.hidden = false;
            renderThumb(row.thumb, product.image);
            if (row.stock) row.stock.dataset.state = product.stockState;
            if (row.stockValue) row.stockValue.textContent = product.stockLabel;
            row.trigger.setAttribute('aria-label', 'تغییر محصول: ' + product.name);
        }

        function selectProduct(row, product) {
            if (isDuplicate(product.id, row)) {
                showToast('این محصول قبلاً به پکیج اضافه شده است.', true);
                renderOptions(row);
                return;
            }
            setRowProduct(row, product);
            closePicker(row, true);
            rows.forEach(function (other) {
                if (other !== row && !other.popover.hidden) renderOptions(other);
            });
            update();
        }

        function syncRows() {
            rows.forEach(function (row, index) {
                var number = row.node.querySelector('[data-row-number]');
                if (number) number.textContent = persianDigits(index + 1);
                if (row.remove) {
                    row.remove.hidden = rows.length <= 2;
                    row.remove.setAttribute('aria-label', 'حذف محصول ' + persianDigits(index + 1));
                }
                if (row.qty) row.qty.setAttribute('aria-label', 'تعداد محصول ' + persianDigits(index + 1));
            });
            if (addButton) addButton.disabled = rows.length >= maxItems;
            update();
        }

        function registerRow(node) {
            var row = {
                id: ++rowSequence, node: node, product: null, products: [], optionNodes: [],
                query: '', page: 0, hasMore: false, loaded: false, loading: false,
                generation: 0, abort: null, timer: null, activeOption: -1, error: '',
                picker: node.querySelector('[data-picker]'), trigger: node.querySelector('[data-picker-trigger]'),
                popover: node.querySelector('[data-picker-popover]'), search: node.querySelector('[data-product-search]'),
                options: node.querySelector('[data-product-options]'), status: node.querySelector('[data-picker-status]'),
                more: node.querySelector('[data-load-more]'), qty: node.querySelector('[data-item-qty]'),
                remove: node.querySelector('[data-remove-product]'), name: node.querySelector('[data-picked-name]'),
                price: node.querySelector('[data-picked-price]'), thumb: node.querySelector('[data-picked-thumb]'),
                stock: node.querySelector('[data-row-stock]'), stockValue: node.querySelector('[data-stock-value]')
            };
            if (!row.picker || !row.trigger || !row.popover || !row.search || !row.options || !row.qty) return null;
            rows.push(row);
            row.options.id = 'gpk-options-' + row.id;
            row.options.setAttribute('role', 'listbox');
            row.options.setAttribute('aria-label', 'محصولات قابل انتخاب');
            row.qty.setAttribute('min', '1');
            row.qty.setAttribute('max', '9999');
            row.trigger.setAttribute('aria-controls', row.options.id);
            row.trigger.setAttribute('aria-haspopup', 'listbox');
            row.search.setAttribute('role', 'combobox');
            row.search.setAttribute('aria-controls', row.options.id);
            row.search.setAttribute('aria-autocomplete', 'list');
            row.search.setAttribute('aria-expanded', 'false');
            row.search.maxLength = 160;
            row.trigger.addEventListener('click', function () {
                if (row.popover.hidden) openPicker(row);
                else closePicker(row, false);
            });
            row.trigger.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    openPicker(row);
                    setActiveOption(row, event.key === 'ArrowDown' ? 0 : row.optionNodes.length - 1);
                }
            });
            row.search.addEventListener('input', function () {
                window.clearTimeout(row.timer);
                row.generation += 1;
                if (row.abort) row.abort.abort();
                row.abort = null;
                row.query = row.search.value.trim();
                row.products = [];
                row.page = 0;
                row.hasMore = false;
                row.loaded = false;
                row.loading = true;
                row.error = '';
                renderOptions(row);
                row.timer = window.setTimeout(function () {
                    row.timer = null;
                    requestProducts(row, false);
                }, 200);
            });
            row.search.addEventListener('keydown', function (event) {
                var enabled = row.optionNodes.filter(function (option) { return !option.disabled; });
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
                    event.preventDefault();
                    var next = row.activeOption;
                    if (event.key === 'ArrowDown') next += 1;
                    if (event.key === 'ArrowUp') next = next < 0 ? enabled.length - 1 : next - 1;
                    if (event.key === 'Home') next = 0;
                    if (event.key === 'End') next = enabled.length - 1;
                    setActiveOption(row, next);
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    if (enabled[row.activeOption]) enabled[row.activeOption].click();
                }
            });
            if (row.more) row.more.addEventListener('click', function () {
                if (row.hasMore && !row.loading) requestProducts(row, true);
            });
            row.qty.addEventListener('input', update);
            row.qty.addEventListener('change', function () {
                row.qty.value = String(quantity(row.qty));
                update();
            });
            node.querySelectorAll('[data-qty-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    row.qty.value = String(Math.max(1, Math.min(9999, quantity(row.qty) + Number(button.dataset.qtyStep))));
                    update();
                });
            });
            if (row.remove) row.remove.addEventListener('click', function () {
                if (rows.length <= 2) return;
                var index = rows.indexOf(row);
                closePicker(row, false);
                window.clearTimeout(row.timer);
                row.generation += 1;
                if (row.abort) row.abort.abort();
                rows.splice(index, 1);
                node.remove();
                syncRows();
                rows.forEach(function (other) { if (!other.popover.hidden) renderOptions(other); });
                (rows[Math.min(index, rows.length - 1)] || rows[0]).trigger.focus();
            });
            return row;
        }

        function addRow() {
            if (!items || !rowTemplate || !rowTemplate.content || rows.length >= maxItems) return null;
            var fragment = rowTemplate.content.cloneNode(true);
            var node = fragment.querySelector('[data-package-row]');
            if (!node) return null;
            items.appendChild(fragment);
            var row = registerRow(node);
            syncRows();
            return row;
        }

        if (items) items.querySelectorAll('[data-package-row]').forEach(registerRow);
        while (rows.length < 2) {
            if (!addRow()) break;
        }
        syncRows();
        if (addButton) addButton.addEventListener('click', function () {
            var row = addRow();
            if (row) openPicker(row);
        });
        if (nameInput) nameInput.addEventListener('input', update);
        if (priceInput) priceInput.addEventListener('input', function () {
            selectedDiscount = null;
            update();
        });
        root.querySelectorAll('[data-discount]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (totalMinor === null || totalMinor <= 0) {
                    showToast('ابتدا محصولاتی با قیمت مشخص انتخاب کنید.', true);
                    return;
                }
                var percent = Number(button.dataset.discount);
                if ([10, 15, 20].indexOf(percent) < 0 || !priceInput) return;
                selectedDiscount = percent;
                priceInput.value = priceForInput(Math.round(totalMinor * (100 - percent) / 100));
                update();
            });
        });

        document.addEventListener('click', function (event) {
            if (openRow && !openRow.picker.contains(event.target)) closePicker(openRow, false);
        });
        root.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && openRow) {
                event.preventDefault();
                closePicker(openRow, true);
            }
        });
        root.addEventListener('focusout', function () {
            window.setTimeout(function () {
                if (openRow && !openRow.picker.contains(document.activeElement)) closePicker(openRow, false);
            }, 0);
        });

        function insertPlainText(text) {
            if (!editor) return;
            editor.focus();
            var selection = window.getSelection();
            if (!selection || !selection.rangeCount) return;
            var range = selection.getRangeAt(0);
            if (!editor.contains(range.commonAncestorContainer)) return;
            range.deleteContents();
            var node = document.createTextNode(text);
            range.insertNode(node);
            range.setStartAfter(node);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            editorSelection = range.cloneRange();
        }

        if (editor) {
            document.addEventListener('selectionchange', function () {
                var selection = window.getSelection();
                if (selection && selection.rangeCount && editor.contains(selection.getRangeAt(0).commonAncestorContainer)) {
                    editorSelection = selection.getRangeAt(0).cloneRange();
                    root.querySelectorAll('[data-format]').forEach(function (button) {
                        try { button.setAttribute('aria-pressed', String(document.queryCommandState(button.dataset.format))); }
                        catch (ignored) { button.setAttribute('aria-pressed', 'false'); }
                    });
                }
            });
            root.querySelectorAll('[data-format]').forEach(function (button) {
                button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                button.addEventListener('click', function () {
                    var format = button.dataset.format;
                    if (['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'].indexOf(format) < 0) return;
                    editor.focus();
                    if (editorSelection && editor.contains(editorSelection.commonAncestorContainer)) {
                        var selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(editorSelection);
                    }
                    if (typeof document.execCommand === 'function') document.execCommand(format, false, null);
                });
            });
            editor.addEventListener('paste', function (event) {
                event.preventDefault();
                insertPlainText(event.clipboardData ? event.clipboardData.getData('text/plain') : '');
            });
            editor.addEventListener('dragover', function (event) { event.preventDefault(); });
            editor.addEventListener('drop', function (event) {
                event.preventDefault();
                insertPlainText(event.dataTransfer ? event.dataTransfer.getData('text/plain') : '');
            });
        }

        function setImageError(message) {
            if (imageError) {
                imageError.textContent = message;
                imageError.hidden = !message;
            }
            if (upload) upload.setAttribute('aria-invalid', message ? 'true' : 'false');
            if (message) showToast(message, true);
        }

        function displayImage(source) {
            if (uploadPreview) {
                uploadPreview.src = source;
                uploadPreview.hidden = false;
            }
            if (previewImage) {
                previewImage.src = source;
                previewImage.hidden = false;
            }
            if (uploadCopy) uploadCopy.hidden = true;
            if (previewIcon) previewIcon.hidden = true;
            if (imageChange) imageChange.hidden = false;
            if (imageRemove) imageRemove.hidden = false;
            if (upload) upload.classList.add('has-image');
        }

        function selectMediaAttachment(attachment, initializing) {
            if (!attachment || typeof attachment !== 'object') return false;
            var id = Number(attachment.id);
            var sizes = attachment.sizes || {};
            var imageURL = safeImageURL((sizes.large || sizes.medium_large || sizes.medium || {}).url) || safeImageURL(attachment.url);
            var mime = String(attachment.mime || '').toLowerCase();
            if (!Number.isSafeInteger(id) || id <= 0 || !imageURL) {
                setImageError('تصویر انتخاب‌شده معتبر نیست. دوباره از کتابخانه رسانه انتخاب کنید.');
                return false;
            }
            if (!initializing && ['image/jpeg', 'image/png', 'image/webp'].indexOf(mime) < 0) {
                setImageError('فقط تصویر JPG، PNG یا WebP قابل انتخاب است.');
                return false;
            }
            var fileSize = Number(attachment.filesizeInBytes);
            if (!initializing && Number.isFinite(fileSize) && fileSize > 5 * 1024 * 1024) {
                setImageError('حجم تصویر باید حداکثر ۵ مگابایت باشد.');
                return false;
            }
            selectedImage = { id: id, url: imageURL };
            if (imageIdInput) imageIdInput.value = String(id);
            setImageError('');
            displayImage(imageURL);
            update();
            return true;
        }

        function openMediaPicker(uploadMode) {
            if (config.readOnly) return;
            if (!window.wp || typeof window.wp.media !== 'function') {
                setImageError('کتابخانه رسانه در دسترس نیست. صفحه را تازه کنید و دوباره تلاش کنید.');
                return;
            }
            if (openRow) closePicker(openRow, false);
            mediaReturnFocus = document.activeElement;
            if (!mediaFrame) {
                mediaFrame = window.wp.media({
                    title: 'انتخاب تصویر پکیج',
                    button: { text: 'استفاده از این تصویر' },
                    library: { type: ['image/jpeg', 'image/png', 'image/webp'] },
                    multiple: false
                });
                if (!mediaFrame) {
                    setImageError('کتابخانه رسانه در دسترس نیست. صفحه را تازه کنید و دوباره تلاش کنید.');
                    return;
                }
                mediaFrame.on('open', function () {
                    var selection = mediaFrame.state().get('selection');
                    selection.reset();
                    if (selectedImage) {
                        var attachment = window.wp.media.attachment(selectedImage.id);
                        selection.add(attachment);
                        if (typeof attachment.fetch === 'function') attachment.fetch();
                    }
                });
                mediaFrame.on('select', function () {
                    var attachment = mediaFrame.state().get('selection').first();
                    if (attachment) selectMediaAttachment(attachment.toJSON(), false);
                });
                mediaFrame.on('close', function () {
                    var control = mediaReturnFocus && root.contains(mediaReturnFocus) && !mediaReturnFocus.hidden ? mediaReturnFocus : upload;
                    if (control) control.focus();
                });
            }
            mediaFrame.open();
            if (uploadMode && mediaFrame.content && typeof mediaFrame.content.mode === 'function') mediaFrame.content.mode('upload');
        }

        function removeImage(restoreFocus) {
            selectedImage = null;
            if (imageIdInput) imageIdInput.value = '0';
            [uploadPreview, previewImage].forEach(function (image) {
                if (!image) return;
                image.hidden = true;
                image.removeAttribute('src');
            });
            if (uploadCopy) uploadCopy.hidden = false;
            if (previewIcon) previewIcon.hidden = false;
            if (imageChange) imageChange.hidden = true;
            if (imageRemove) imageRemove.hidden = true;
            if (upload) upload.classList.remove('has-image');
            setImageError('');
            update();
            if (restoreFocus !== false && upload) upload.focus();
        }

        if (imageError && upload) {
            if (!imageError.id) imageError.id = 'gpk-image-error';
            upload.setAttribute('aria-describedby', imageError.id);
        }
        if (upload) {
            upload.addEventListener('click', function (event) {
                if (event.target.closest('button')) return;
                openMediaPicker(false);
            });
            upload.addEventListener('keydown', function (event) {
                if (event.target === upload && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    openMediaPicker(false);
                }
            });
            upload.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
                upload.classList.add('is-dragging');
            });
            upload.addEventListener('dragleave', function (event) {
                if (!upload.contains(event.relatedTarget)) upload.classList.remove('is-dragging');
            });
            upload.addEventListener('drop', function (event) {
                event.preventDefault();
                upload.classList.remove('is-dragging');
                openMediaPicker(true);
                showToast('برای بارگذاری تصویر، فایل را در پنجره رسانه انتخاب کنید.', false);
            });
        }
        if (imageChange) imageChange.addEventListener('click', function () { openMediaPicker(false); });
        if (imageRemove) imageRemove.addEventListener('click', function () { if (!config.readOnly) removeImage(true); });
        var favorite = root.querySelector('[data-preview-favorite]');
        if (favorite) favorite.addEventListener('click', function () {
            var pressed = favorite.getAttribute('aria-pressed') !== 'true';
            favorite.setAttribute('aria-pressed', String(pressed));
            favorite.classList.toggle('is-active', pressed);
        });
        function appendDescriptionNodes(source, target) {
            var allowed = { P: 'p', DIV: 'div', BR: 'br', STRONG: 'strong', B: 'strong', EM: 'em', I: 'em', UL: 'ul', OL: 'ol', LI: 'li', H1: 'h1', H2: 'h2', H3: 'h3', H4: 'h4', H5: 'h5', H6: 'h6', BLOCKQUOTE: 'blockquote', PRE: 'pre', CODE: 'code', SPAN: 'span', S: 's', STRIKE: 's', DEL: 'del', U: 'u', SUB: 'sub', SUP: 'sup', HR: 'hr', A: 'a' };
            var dropped = ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'IMG', 'SVG', 'MATH', 'FORM', 'INPUT', 'BUTTON', 'TEMPLATE', 'LINK', 'META'];
            Array.prototype.forEach.call(source.childNodes, function (node) {
                if (node.nodeType === 3) {
                    target.appendChild(document.createTextNode(node.textContent));
                    return;
                }
                if (node.nodeType !== 1 || dropped.indexOf(node.tagName) >= 0) return;
                var tag = allowed[node.tagName];
                if (!tag) {
                    appendDescriptionNodes(node, target);
                    return;
                }
                var clean = document.createElement(tag);
                if (tag === 'a') {
                    var href = String(node.getAttribute('href') || '').trim();
                    try {
                        var link = new URL(href, window.location.href);
                        if (href && ['http:', 'https:', 'mailto:', 'tel:'].indexOf(link.protocol) >= 0) clean.setAttribute('href', link.href);
                    } catch (ignored) {}
                }
                appendDescriptionNodes(node, clean);
                target.appendChild(clean);
            });
        }

        function editorDescription() {
            if (!editor) return '';
            var clean = document.createElement('div');
            appendDescriptionNodes(editor, clean);
            return clean.innerHTML;
        }

        function renderDescription(html) {
            if (!editor) return;
            var template = document.createElement('template');
            // This description comes from the authenticated, server-sanitized package.
            template.innerHTML = String(html || '');
            var clean = document.createElement('div');
            appendDescriptionNodes(template.content, clean);
            editor.replaceChildren();
            while (clean.firstChild) editor.appendChild(clean.firstChild);
            editorSelection = null;
        }

        function updateSavedStatus(status) {
            var labels = { publish: 'منتشر شده', draft: 'پیش‌نویس', pending: 'در انتظار بررسی', private: 'خصوصی' };
            if (statusLabel) statusLabel.textContent = labels[status] || 'پیش‌نویس';
        }

        function hydratePackage(data) {
            if (!data || typeof data !== 'object') return;
            if (nameInput) nameInput.value = typeof data.name === 'string' ? data.name : '';
            if (skuInput) skuInput.value = typeof data.sku === 'string' ? data.sku : '';
            if (priceInput) {
                var initialPrice = data.price === '' || data.price === null || data.price === undefined ? null : decimalToMinor(data.price);
                priceInput.value = initialPrice === null ? '' : priceForInput(initialPrice);
            }
            renderDescription(data.description);
            var savedStatus = String(data.status || 'draft');
            if (statusInput) statusInput.value = savedStatus === 'private' ? 'publish' : savedStatus;
            if (visibilityInput) visibilityInput.value = savedStatus === 'private' ? 'private' : String(data.visibility || 'public');
            savedPermalink = safeImageURL(data.permalink) || '';
            var seen = new Set();
            var existingItems = Array.isArray(data.items) ? data.items.slice(0, maxItems).map(function (item) {
                var product = normalizeProduct(item && item.product);
                if (!product || seen.has(product.id)) return null;
                seen.add(product.id);
                return { product: product, quantity: quantity({ value: item.quantity }) };
            }).filter(Boolean) : [];
            while (rows.length < Math.max(2, existingItems.length)) {
                if (!addRow()) break;
            }
            existingItems.forEach(function (item, index) {
                if (!rows[index]) return;
                rows[index].qty.value = String(item.quantity);
                setRowProduct(rows[index], item.product);
            });
            if (data.image && Number(data.image.id) > 0) selectMediaAttachment(data.image, true);
            updateSavedStatus(savedStatus);
            syncRows();
            if (Array.isArray(data.items) && existingItems.length !== data.items.length) showToast('بعضی محصولات این پکیج در دسترس نیستند؛ محتوای پکیج را بررسی کنید.', true);
        }

        function captureFormState() {
            return JSON.stringify({
                name: nameInput ? nameInput.value : '', description: editorDescription(),
                items: rows.map(function (row) { return { id: row.product ? row.product.id : 0, quantity: row.qty.value }; }),
                image: selectedImage ? selectedImage.id : 0, price: priceInput ? priceInput.value : '',
                sku: skuInput ? skuInput.value : '', status: statusInput ? statusInput.value : '',
                visibility: visibilityInput ? visibilityInput.value : ''
            });
        }

        function fieldError(message, control) {
            var error = catalogError(message);
            error.control = control;
            return error;
        }

        function collectPayload(status) {
            var checks = update();
            var complete = status === 'publish' || status === 'pending';
            var required = complete ? ['name', 'products', 'image', 'price'] : ['name'];
            var missing = required.filter(function (key) { return !checks[key]; });
            if (missing.length) {
                var labels = { name: 'نام پکیج', products: 'حداقل دو محصول متفاوت', image: 'تصویر پکیج', price: 'قیمت معتبر پکیج' };
                var first = missing[0];
                var control = first === 'name' ? nameInput : first === 'image' ? upload : first === 'price' ? priceInput : (rows.find(function (row) { return !row.product; }) || rows[0] || {}).trigger;
                throw fieldError('برای ذخیره پکیج، ' + missing.map(function (key) { return labels[key]; }).join('، ') + ' را وارد کنید.', control);
            }
            var picked = [];
            var seen = new Set();
            rows.forEach(function (row) {
                if (!row.product) return;
                var id = Number(row.product.id);
                var rawQuantity = normalizeDigits(row.qty.value).trim();
                var count = Number(rawQuantity);
                if (!Number.isSafeInteger(id) || id <= 0 || seen.has(id)) throw fieldError('محصولات پکیج باید معتبر و متفاوت باشند.', row.trigger);
                if (!/^\d+$/.test(rawQuantity) || !Number.isInteger(count) || count < 1 || count > 9999) throw fieldError('تعداد هر محصول باید بین ۱ و ۹۹۹۹ باشد.', row.qty);
                seen.add(id);
                picked.push({ product_id: id, quantity: count });
            });
            var rawPrice = priceInput ? priceInput.value.trim() : '';
            var price = rawPrice ? parsePrice(rawPrice) : null;
            if (rawPrice && price === null) throw fieldError('قیمت پکیج را به‌صورت عدد معتبر وارد کنید.', priceInput);
            var imageId = selectedImage ? Number(selectedImage.id) : 0;
            if (!Number.isSafeInteger(imageId) || imageId < 0) throw fieldError('تصویر پکیج را دوباره از کتابخانه رسانه انتخاب کنید.', upload);
            var visibility = visibilityInput ? visibilityInput.value : 'public';
            if (['publish', 'draft', 'pending'].indexOf(status) < 0) throw fieldError('وضعیت انتخاب‌شده معتبر نیست.', statusInput);
            if (['public', 'private', 'hidden'].indexOf(visibility) < 0) throw fieldError('نمایان بودن انتخاب‌شده معتبر نیست.', visibilityInput);
            return {
                package_id: packageId, name: nameInput.value.trim(), description: editorDescription(),
                items: picked, image_id: imageId, price: canonicalPrice(price),
                sku: skuInput ? skuInput.value.trim() : '', status: status, visibility: visibility
            };
        }

        function setSaving(value) {
            saving = value;
            root.classList.toggle('is-saving', value);
            saveButtons.forEach(function (button) {
                button.disabled = value || !!config.readOnly;
                button.setAttribute('aria-busy', String(value));
            });
        }

        async function savePackage(action) {
            if (saving || config.readOnly) return;
            var payload;
            var beforeForm;
            try {
                var status = action === 'draft' ? 'draft' : (statusInput ? statusInput.value : 'publish');
                payload = collectPayload(status);
                beforeForm = captureFormState();
                if (!config.saveAction || !config.saveNonce || !config.endpoint) throw catalogError('ذخیره پکیج در دسترس نیست. صفحه را تازه کنید و دوباره تلاش کنید.');
            } catch (error) {
                showToast(error.catalogMessage || 'اطلاعات پکیج را بررسی کنید.', true);
                if (error.control) error.control.focus();
                return;
            }
            var fingerprint = JSON.stringify(payload);
            if (!packageId) retryToken = creationToken;
            else if (retryFingerprint !== fingerprint || !retryToken) retryToken = makeRequestToken();
            retryFingerprint = fingerprint;
            var body = new URLSearchParams();
            body.set('action', String(config.saveAction));
            body.set('nonce', String(config.saveNonce));
            body.set('request_token', retryToken);
            Object.keys(payload).forEach(function (key) { body.set(key, key === 'items' ? JSON.stringify(payload[key]) : String(payload[key])); });
            setSaving(true);
            showToast('در حال ذخیره پکیج…', false);
            try {
                var endpoint = new URL(String(config.endpoint), window.location.href);
                if (endpoint.origin !== window.location.origin) throw catalogError('ذخیره پکیج در دسترس نیست.');
                var response = await window.fetch(endpoint.href, {
                    method: 'POST', credentials: 'same-origin', body: body.toString(),
                    headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }
                });
                var result = await response.json();
                if (!response.ok || !result || result.success !== true || !result.data) {
                    var detail = result && result.data;
                    var message = detail && typeof detail.message === 'string' ? detail.message : 'ذخیره پکیج انجام نشد. دوباره تلاش کنید.';
                    var failed = catalogError(message);
                    var controls = { name: nameInput, price: priceInput, sku: skuInput, image_id: upload, items: (rows[0] || {}).trigger, status: statusInput, visibility: visibilityInput };
                    if (detail && controls[detail.field]) failed.control = controls[detail.field];
                    throw failed;
                }
                var data = result.data;
                var confirmedId = Number(data.package_id);
                if (!Number.isSafeInteger(confirmedId) || confirmedId <= 0) throw catalogError('نتیجه ذخیره مشخص نیست. برای بررسی و بازیابی، دوباره ذخیره کنید.');
                var unchanged = captureFormState() === beforeForm;
                packageId = confirmedId;
                config.packageId = confirmedId;
                if (data.package && typeof data.package === 'object') config.package = data.package;
                savedPermalink = safeImageURL(data.permalink) || savedPermalink;
                var actualStatus = data.package && data.package.status ? String(data.package.status) : payload.status;
                updateSavedStatus(actualStatus);
                if (unchanged && !data.replayed) {
                    if (statusInput) statusInput.value = actualStatus === 'private' ? 'publish' : actualStatus;
                    if (visibilityInput && data.package && data.package.visibility) visibilityInput.value = data.package.visibility;
                    if (data.package && typeof data.package === 'object') {
                        if (nameInput && typeof data.package.name === 'string') nameInput.value = data.package.name;
                        if (skuInput && typeof data.package.sku === 'string') skuInput.value = data.package.sku;
                        if (priceInput && (typeof data.package.price === 'string' || typeof data.package.price === 'number')) {
                            var confirmedPrice = data.package.price === '' ? null : decimalToMinor(data.package.price);
                            if (data.package.price === '' || confirmedPrice !== null) priceInput.value = confirmedPrice === null ? '' : priceForInput(confirmedPrice);
                        }
                    }
                }
                var editURL = safeImageURL(data.edit_url);
                if (editURL && new URL(editURL).origin === window.location.origin && window.history && window.history.replaceState) {
                    try { window.history.replaceState(null, '', editURL); } catch (ignored) {}
                }
                retryFingerprint = '';
                retryToken = '';
                creationToken = makeRequestToken();
                update();
                var savedMessage = typeof data.message === 'string' && data.message ? data.message : 'پکیج ذخیره شد.';
                if (!unchanged || data.replayed) savedMessage += ' برای ذخیره تغییرات فعلی، دوباره ذخیره کنید.';
                showToast(savedMessage, false);
            } catch (error) {
                showToast(error.catalogMessage || 'ارتباط با سرور کامل نشد؛ برای بررسی نتیجه و بازیابی، دوباره ذخیره کنید.', true);
                if (error.control) error.control.focus();
            } finally {
                setSaving(false);
            }
        }

        root.addEventListener('submit', function (event) { event.preventDefault(); });
        saveButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                savePackage(button.dataset.packageAction || button.dataset.prototypeAction);
            });
        });
        if (config.package && typeof config.package === 'object') hydratePackage(config.package);
        setSaving(false);
        update();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
}());
