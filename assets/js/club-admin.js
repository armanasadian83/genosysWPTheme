(function () {
    'use strict';
    const root = document.querySelector('[data-club-admin]');
    const config = window.genosysClubAdmin;
    if (!root || !config) return;
    const q = (selector, scope = root) => scope.querySelector(selector);
    const qa = (selector, scope = root) => Array.from(scope.querySelectorAll(selector));
    const number = value => value === null || value === undefined || value === '' ? '—' : String(value).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
    const el = (tag, className = '', text = null) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== null) node.textContent = String(text);
        return node;
    };
    function icon(name) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('aria-hidden', 'true');
        const use = document.createElementNS(svg.namespaceURI, 'use');
        use.setAttribute('href', '#gca-i-' + name);
        svg.append(use);
        return svg;
    }
    function button(label, className = 'gca__button gca__button--outline') {
        const node = el('button', className, label);
        node.type = 'button';
        return node;
    }
    const palette = ['purple', 'gold', 'mint', 'coral', 'blue', 'slate'];
    function avatar(member, large = false) {
        const node = el('span', 'gca__avatar' + (large ? ' gca__avatar--large' : ''));
        node.dataset.color = palette[Math.abs(Number(member.id) || 0) % palette.length];
        let valid = false;
        try { valid = /^https?:$/.test(new URL(member.avatarUrl, location.href).protocol) && Boolean(member.avatarUrl); } catch (_) { /* Keep the local initial. */ }
        if (valid) {
            const image = el('img'); image.src = member.avatarUrl; image.alt = ''; image.loading = 'lazy';
            image.addEventListener('error', () => node.replaceChildren(document.createTextNode(member.initial || '؟')), { once: true });
            node.append(image);
        } else node.textContent = member.initial || '؟';
        return node;
    }
    function typeBadge(member) {
        const badge = el('span', 'gca__type', member.audienceLabel || (member.audience === 'clinic' ? 'کلینیک / بیوتی تراپیست' : 'مصرف کننده'));
        badge.dataset.audience = member.audience;
        return badge;
    }
    function stars(rating) {
        if (rating === null || rating === undefined || rating === '') return el('span', 'gca__muted', '—');
        const node = el('span', 'gca__stars');
        node.setAttribute('role', 'img'); node.setAttribute('aria-label', number(rating) + ' از ۵ ستاره');
        for (let value = 1; value <= 5; value++) node.append(el('span', value <= Number(rating) ? 'is-filled' : '', '★'));
        return node;
    }
    function chips(items, primary = '') {
        const node = el('div', 'gca__chips');
        if (primary) node.append(el('span', 'gca__chip gca__chip--primary', primary));
        (items || []).slice(0, 1).forEach(item => node.append(el('span', 'gca__chip', item)));
        if ((items || []).length > 1) node.append(el('span', 'gca__chip', '+' + number(items.length - 1)));
        if (!node.childNodes.length) node.textContent = '—';
        return node;
    }
    let context;
    try { context = JSON.parse(q('[data-club-initial]').textContent); } catch (_) { return; }
    let filters = { ...context.filters };
    let listController;
    let detailController;
    let currentMember = null;
    let previousFocus;
    let noticeTimer;
    let debounce;
    let detailPending = false;
    let pendingDrawerControls = [];
    const drawer = q('[data-club-drawer]');
    const smsDialog = q('[data-club-sms-dialog]');
    let smsCampaign = null;
    let smsMemberId = 0;
    let smsRequestId = '';
    let smsPending = false;
    let smsPreviewController;
    let smsPreviewVersion = 0;
    let overflowBeforeModal = '';
    function lockDrawerControls() {
        qa('button, input, textarea, select', drawer).forEach(node => {
            if (!node.disabled) { pendingDrawerControls.push(node); node.disabled = true; }
        });
    }
    function beginDrawerWrite() {
        if (detailPending) return false;
        detailPending = true; pendingDrawerControls = [];
        drawer.setAttribute('aria-busy', 'true'); lockDrawerControls();
        return true;
    }
    function endDrawerWrite() {
        detailPending = false; drawer.removeAttribute('aria-busy');
        pendingDrawerControls.forEach(node => { if (node.isConnected) node.disabled = false; });
        pendingDrawerControls = []; updateDrawerNavigation();
    }
    function updateModalScroll() {
        const open = drawer.open || smsDialog.open;
        if (open && !root.dataset.modalOpen) { overflowBeforeModal = document.documentElement.style.overflow; document.documentElement.style.overflow = 'hidden'; root.dataset.modalOpen = 'true'; }
        else if (!open && root.dataset.modalOpen) { document.documentElement.style.overflow = overflowBeforeModal; delete root.dataset.modalOpen; }
    }
    function toast(message) {
        const node = q('[data-club-notice]'); node.textContent = message; node.hidden = false;
        clearTimeout(noticeTimer); noticeTimer = setTimeout(() => { node.hidden = true; }, 6500);
    }
    async function request(action, payload = {}, signal) {
        const body = new URLSearchParams({ action, nonce: config.nonce });
        Object.entries(payload).forEach(([key, value]) => body.set(key, value === null || value === undefined ? '' : String(value)));
        const response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body, signal, headers: { Accept: 'application/json' } });
        let json;
        try { json = await response.json(); } catch (_) { throw new Error('پاسخ سرور قابل خواندن نیست. لطفاً دوباره تلاش کنید.'); }
        if (!response.ok || !json.success) throw new Error(json.data && json.data.message || 'انجام درخواست ممکن نشد. لطفاً دوباره تلاش کنید.');
        return json.data || {};
    }
    function renderStats() {
        const stats = context.stats || {};
        const items = [
            ['active', stats.active, 'عضو فعال', 'people', 'red'],
            ['new30', stats.new30, 'عضو جدید در ۳۰ روز اخیر', 'sparkle', 'green'],
            ['rating', stats.averageRating === null || stats.averageRating === undefined ? '—' : number(Number(stats.averageRating).toFixed(1)).replace('.', '٫') + ' از ۵', 'میانگین رضایت', 'star', 'amber'],
            ['nps', stats.nps, 'امتیاز پیشنهاددهندگی (NPS)', 'chart', 'charcoal']
        ];
        q('[data-club-stats]').replaceChildren(...items.map(([key, value, label, glyph, color]) => {
            const card = el('article', 'gca__stat'); card.dataset.stat = key;
            const symbol = el('span', 'gca__stat-icon'); symbol.dataset.color = color; symbol.append(icon(glyph));
            const copy = el('div', 'gca__stat-copy'); copy.append(el('strong', '', number(value)), el('span', '', label));
            card.append(symbol, copy); return card;
        }));
    }
    function renderOptions() {
        const options = context.options || {};
        ['skin', 'city', 'source'].forEach(key => {
            const select = q('[data-club-filter="' + key + '"]');
            const placeholder = select.options[0].textContent;
            select.replaceChildren(new Option(placeholder, ''));
            const source = key === 'city' ? options.cities : key === 'skin' ? options.skins : options.sources;
            (source || []).forEach(item => select.add(new Option(typeof item === 'string' ? item : item.label, typeof item === 'string' ? item : item.value)));
        });
        qa('[data-club-filter]').forEach(field => { field.value = filters[field.dataset.clubFilter] || ''; });
    }
    function renderTable() {
        const members = context.members || [];
        const rows = members.map(member => {
            const row = el('tr', 'gca__row'); row.dataset.clubMember = member.id;
            row.tabIndex = 0; row.setAttribute('aria-label', 'مشاهده جزئیات ' + member.name);
            const identityCell = el('td'); const identity = el('div', 'gca__member');
            const copy = el('div', 'gca__member-copy'); copy.append(el('strong', '', member.name || 'بدون نام'), el('bdi', '', number(member.phone || '—')));
            identity.append(avatar(member), copy); identityCell.append(identity);
            const type = el('td'); type.append(typeBadge(member));
            const city = el('td', 'gca__city', member.city || '—');
            const answers = el('td');
            answers.append(member.audience === 'clinic' ? chips(member.clientNeeds || [], (member.services || [])[0] || '') : chips(member.concerns, member.skinLabel));
            const rating = el('td', 'gca__rating-cell'); rating.append(stars(member.rating));
            if (member.nps !== null && member.nps !== undefined) rating.append(el('small', '', 'احتمال معرفی: ' + number(member.nps) + ' از ۱۰'));
            const orders = el('td', 'gca__orders-cell'); const order = member.orders || {};
            orders.append(el('strong', '', order.count === null || order.count === undefined ? '—' : number(order.count) + ' سفارش'));
            orders.append(el('small', '', order.count === null || order.count === undefined ? 'حساب متصل نیست' : order.count === 0 ? 'بدون خرید' : number(order.totalText || '—')));
            const date = el('td', 'gca__date-cell'); date.append(el('time', '', member.joinedText || '—'), el('small', '', member.joinedAgo || ''));
            const action = el('td', 'gca__action-cell'); const details = button('جزئیات'); details.dataset.clubDetails = member.id; details.setAttribute('aria-label', 'جزئیات ' + member.name); action.append(details);
            row.append(identityCell, type, city, answers, rating, orders, date, action);
            return row;
        });
        if (!rows.length) {
            const row = el('tr'); const cell = el('td', 'gca__empty'); cell.colSpan = 8;
            cell.append(el('strong', '', 'عضوی پیدا نشد'), el('p', '', context.total ? 'فیلترهای جستجو را تغییر دهید.' : 'پس از تکمیل پرسشنامه‌ی باشگاه، اعضا در این بخش نمایش داده می‌شوند.'));
            row.append(cell); rows.push(row);
        }
        q('[data-club-members]').replaceChildren(...rows);
    }
    function renderPagination() {
        const container = q('[data-club-pagination]'); const page = Number(context.page) || 1; const pages = Math.max(1, Number(context.pages) || 1);
        const controls = [];
        function pageButton(label, target, disabled, active = false) {
            const control = button(label, 'gca__page' + (active ? ' is-active' : '')); control.dataset.clubPage = target; control.disabled = disabled;
            if (active) control.setAttribute('aria-current', 'page');
            controls.push(control);
        }
        pageButton('قبلی', page - 1, page <= 1);
        const wanted = new Set([1, pages, page - 1, page, page + 1]);
        let last = 0;
        Array.from(wanted).filter(value => value >= 1 && value <= pages).sort((a, b) => a - b).forEach(value => {
            if (last && value - last > 1) controls.push(el('span', 'gca__page-gap', '…'));
            pageButton(number(value), value, false, value === page); last = value;
        });
        pageButton('بعدی', page + 1, page >= pages);
        container.replaceChildren(...controls);
        container.hidden = !context.total;
    }
    function render() {
        renderStats(); renderOptions(); renderTable(); renderPagination();
        qa('[data-club-count]').forEach(node => { node.textContent = number((context.counts || {})[node.dataset.clubCount] || 0); });
        qa('[data-club-status]').forEach(node => { const active = node.dataset.clubStatus === filters.status; node.classList.toggle('is-active', active); node.setAttribute('aria-pressed', String(active)); });
        const bulkSms = q('[data-club-sms-bulk]'); bulkSms.disabled = filters.status === 'cancelled'; bulkSms.title = bulkSms.disabled ? 'ارسال پیامک فقط برای اعضای فعال امکان‌پذیر است.' : '';
        q('[data-club-results]').textContent = context.total ? 'نمایش ' + number(context.from) + ' تا ' + number(context.to) + ' از ' + number(context.total) + ' عضو' : '۰ عضو';
        const url = context.exportUrl || '';
        if (url) { q('[data-club-export]').href = url; q('[data-club-export]').removeAttribute('aria-disabled'); }
        else q('[data-club-export]').setAttribute('aria-disabled', 'true');
    }
    async function loadList() {
        if (listController) listController.abort();
        listController = new AbortController();
        const controller = listController;
        const requestedFilters = JSON.stringify(filters);
        q('.gca__panel').setAttribute('aria-busy', 'true');
        q('[data-club-list-message]').hidden = true;
        try {
            const data = await request('genosys_club_list', { filters: requestedFilters }, controller.signal);
            if (controller !== listController || requestedFilters !== JSON.stringify(filters)) return;
            context = data.context; filters = { ...context.filters }; render(); updateDrawerNavigation();
        } catch (error) {
            if (error.name === 'AbortError') return;
            const message = q('[data-club-list-message]'); message.textContent = error.message; message.hidden = false;
        } finally { if (controller === listController) q('.gca__panel').removeAttribute('aria-busy'); }
    }
    function sectionTitle(text) { return el('h3', 'gca__detail-title', text); }
    function field(label, value, full = false, child = null) {
        const node = el('div', 'gca__detail-field' + (full ? ' gca__detail-field--full' : ''));
        node.append(el('span', 'gca__detail-label', label));
        if (child) node.append(child); else node.append(el('strong', '', value === null || value === undefined || value === '' ? '—' : value));
        return node;
    }
    function allChips(items) {
        const node = el('div', 'gca__chips');
        (items || []).forEach(item => node.append(el('span', 'gca__chip', item)));
        if (!node.childNodes.length) node.textContent = '—';
        return node;
    }
    function npsMeter(value) {
        const node = el('div', 'gca__nps'); const track = el('div', 'gca__nps-track');
        track.setAttribute('role', 'img'); track.setAttribute('aria-label', number(value) + ' از ۱۰');
        for (let score = 0; score <= 10; score++) track.append(el('span', score <= value ? 'is-filled' : ''));
        const labels = el('div', 'gca__nps-labels'); labels.append(el('span', '', '۰'), el('strong', '', number(value) + ' از ۱۰ · ' + (value >= 9 ? 'مروّج' : value >= 7 ? 'منفعل' : 'منتقد')), el('span', '', '۱۰'));
        node.dataset.level = value >= 9 ? 'good' : value >= 7 ? 'neutral' : 'low';
        node.append(track, labels); return node;
    }
    function renderDetail(member) {
        currentMember = member;
        const body = q('[data-club-detail]'); body.removeAttribute('aria-busy');
        const header = el('section', 'gca__member-hero'); const copy = el('div', 'gca__member-hero-copy');
        copy.append(el('h3', '', member.name || 'بدون نام'));
        copy.append(el('p', '', [member.city, member.audience === 'consumer' ? member.ageLabel : member.clinicName].filter(Boolean).join(' · ')));
        const badge = el('span', 'gca__club-badge', member.status === 'cancelled' ? 'عضویت لغو شده' : 'عضو باشگاه مشتریان'); badge.prepend(icon('star'));
        copy.append(badge, typeBadge(member)); header.append(avatar(member, true), copy);
        const actions = el('div', 'gca__detail-actions');
        const phone = String(member.phone || '').replace(/[^+0-9]/g, '');
        if (phone) { const call = el('a', 'gca__button gca__button--outline', 'تماس'); call.href = 'tel:' + phone; actions.append(call); }
        const sms = button('ارسال پیامک', 'gca__button'); sms.dataset.clubSmsMember = member.id; sms.disabled = member.status !== 'active' || !member.consent || !phone; actions.append(sms);
        const contact = el('div', 'gca__detail-grid');
        contact.append(field('شماره موبایل', number(member.phone || '—')), field('ایمیل', member.email), field('تاریخ عضویت', member.joinedText), field('دریافت پیامک', member.consent ? 'موافق ✓' : 'موافق نیست'));
        const responses = el('div', 'gca__detail-grid');
        if (member.audience === 'clinic') {
            responses.append(field('نام کلینیک / مرکز', member.clinicName), field('نام مسئول', member.contactName), field('شماره ثابت کلینیک', number(member.landline || '—')), field('نوع فعالیت', member.activityLabel), field('سابقه کاری', member.experienceLabel), field('مشتریان شما در ماه', member.monthlyClientsLabel), field('مصرف ماهانه', member.consumptionLabel, true), field('نوع خدمات', null, true, allChips(member.services)), field('نیاز مشتریان شما', null, true, allChips(member.clientNeeds)));
        } else {
            responses.append(field('نوع پوست', member.skinLabel), field('رضایت از ژنوسیس', null, false, stars(member.rating)), field('دغدغه‌های اصلی', null, true, allChips(member.concerns)), field('دسته‌بندی‌های مورد علاقه', null, true, allChips(member.categories)));
            if (member.nps !== null && member.nps !== undefined) responses.append(field('احتمال معرفی به دوستان', null, true, npsMeter(Number(member.nps))));
        }
        responses.append(field('آشنایی با ژنوسیس از طریق', member.sourceLabel, true));
        const nodes = [header, actions, sectionTitle('اطلاعات تماس'), contact, sectionTitle('پاسخ‌های پرسشنامه'), responses];
        if (member.message) { const comment = el('blockquote', 'gca__comment', member.message); nodes.push(el('p', 'gca__comment-label', 'نظر یا پیشنهاد'), comment); }
        nodes.push(sectionTitle('فعالیت خرید'));
        const orders = member.orders || {};
        if (orders.count === null || orders.count === undefined) nodes.push(el('p', 'gca__unlinked', 'عضویت بدون حساب کاربری؛ سفارش‌ها به حساب کاربری متصل نشده‌اند.'));
        else {
            const stats = el('div', 'gca__purchase-stats');
            [[number(orders.count), 'سفارش'], [number(orders.totalText || (orders.count === 0 ? '۰' : '—')), 'جمع خرید'], [number(orders.lastAgo || '—'), 'آخرین سفارش']].forEach(([value, label]) => { const box = el('div'); box.append(el('strong', '', value), el('span', '', label)); stats.append(box); });
            nodes.push(stats);
        }
        const timeline = el('ol', 'gca__timeline');
        (member.timeline || []).forEach(item => { const row = el('li'); row.append(el('span', '', item.label), el('small', '', item.dateText)); timeline.append(row); });
        if (timeline.childNodes.length) nodes.push(timeline);
        nodes.push(sectionTitle('یادداشت مدیر'));
        const note = el('textarea', 'gca__note'); note.rows = 4; note.maxLength = 5000; note.dataset.clubNote = ''; note.value = member.note || ''; note.placeholder = 'یادداشت خصوصی درباره‌ی این عضو (برای او نمایش داده نمی‌شود)…'; note.setAttribute('aria-label', 'یادداشت خصوصی مدیر');
        const noteActions = el('div', 'gca__note-actions'); const saveNote = button('ذخیره یادداشت'); saveNote.dataset.clubNoteSave = member.id; noteActions.append(el('span', '', 'فقط مدیران می‌بینند.'), saveNote); nodes.push(note, noteActions);
        const cancel = el('div', 'gca__cancel-box'); const cancelCopy = el('div'); const cancelled = member.status === 'cancelled';
        cancelCopy.append(el('strong', '', cancelled ? 'بازگردانی عضویت در باشگاه' : 'لغو عضویت در باشگاه'), el('p', '', cancelled ? 'همین نوع عضویت دوباره فعال می‌شود.' : 'فقط همین نوع عضویت غیرفعال می‌شود؛ عضویت دیگر کاربر باقی می‌ماند.'));
        const statusButton = button(cancelled ? 'بازگردانی عضویت' : 'لغو عضویت'); statusButton.dataset.clubChangeStatus = member.id; statusButton.dataset.status = cancelled ? 'active' : 'cancelled'; cancel.append(cancelCopy, statusButton); nodes.push(cancel);
        const deletion = el('section', 'gca__delete-box');
        const deletionCopy = el('div');
        deletionCopy.append(el('strong', '', 'حذف دائمی این عضویت'), el('p', '', 'فقط همین نوع عضویت و یادداشت خصوصی آن حذف می‌شود و امکان ثبت‌نام دوباره در همین نوع فراهم خواهد شد. حساب کاربری، سفارش‌ها و عضویت دیگر باقی می‌مانند.'));
        const deleteOpen = button('حذف دائمی عضویت', 'gca__button gca__button--danger'); deleteOpen.dataset.clubDeleteOpen = member.id;
        const deleteConfirmation = el('div', 'gca__delete-confirm'); deleteConfirmation.dataset.clubDeleteConfirmation = member.id; deleteConfirmation.hidden = true;
        deleteConfirmation.setAttribute('role', 'alertdialog'); deleteConfirmation.setAttribute('aria-labelledby', 'gca-delete-title'); deleteConfirmation.setAttribute('aria-describedby', 'gca-delete-description');
        const deleteTitle = el('h4', '', 'این عضویت برای همیشه حذف شود؟'); deleteTitle.id = 'gca-delete-title';
        const deleteDescription = el('p', '', 'عضویت «' + (member.audienceLabel || (member.audience === 'clinic' ? 'کلینیک / بیوتی تراپیست' : 'مصرف کننده')) + '» برای «' + (member.name || 'این عضو') + '» و یادداشت مدیر درباره‌ی آن، قابل بازگردانی نخواهد بود. سایر اطلاعات و عضویت دیگر این کاربر حذف نمی‌شود.'); deleteDescription.id = 'gca-delete-description';
        const deleteActions = el('div', 'gca__delete-actions');
        const deleteCancel = button('انصراف'); deleteCancel.dataset.clubDeleteCancel = member.id;
        const deleteConfirm = button('بله، حذف دائمی', 'gca__button'); deleteConfirm.dataset.clubDeleteConfirm = member.id;
        deleteActions.append(deleteCancel, deleteConfirm); deleteConfirmation.append(deleteTitle, deleteDescription, deleteActions); deletion.append(deletionCopy, deleteOpen, deleteConfirmation); nodes.push(deletion);
        const detailMessage = el('p', 'gca__detail-message'); detailMessage.dataset.clubDetailMessage = ''; detailMessage.setAttribute('role', 'status'); nodes.push(detailMessage);
        body.replaceChildren(...nodes); updateDrawerNavigation();
        if (detailPending) lockDrawerControls();
    }
    function updateDrawerNavigation() {
        const members = context.members || [];
        const index = currentMember ? members.findIndex(member => Number(member.id) === Number(currentMember.id)) : -1;
        q('[data-club-prev]').disabled = detailPending || index <= 0;
        q('[data-club-next]').disabled = detailPending || index < 0 || index >= members.length - 1;
    }
    async function openMember(id) {
        if (detailPending) return;
        if (detailController) detailController.abort();
        detailController = new AbortController(); const controller = detailController;
        currentMember = null; updateDrawerNavigation();
        if (!drawer.open) { previousFocus = document.activeElement; drawer.showModal(); updateModalScroll(); q('[data-club-close]').focus(); }
        const body = q('[data-club-detail]'); body.replaceChildren(el('p', 'gca__loading', 'در حال دریافت جزئیات…')); body.setAttribute('aria-busy', 'true'); body.scrollTop = 0;
        try {
            const data = await request('genosys_club_detail', { id }, controller.signal);
            if (controller !== detailController || !drawer.open) return;
            renderDetail(data.member);
        } catch (error) {
            if (error.name === 'AbortError') return;
            body.removeAttribute('aria-busy'); body.replaceChildren(el('p', 'gca__loading gca__error', error.message));
        }
    }
    function closeDrawer() { if (!detailPending && drawer.open) drawer.close(); }
    drawer.addEventListener('cancel', event => { if (detailPending) event.preventDefault(); });
    document.addEventListener('keydown', event => {
        if (detailPending && drawer.open && event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); }
    }, true);
    window.addEventListener('beforeunload', event => { if (detailPending) { event.preventDefault(); event.returnValue = ''; } });
    drawer.addEventListener('close', () => {
        if (detailPending) { if (!drawer.open) drawer.showModal(); updateModalScroll(); return; }
        if (detailController) detailController.abort(); updateModalScroll(); if (previousFocus && previousFocus.isConnected) previousFocus.focus();
    });
    drawer.addEventListener('click', event => { if (event.target === drawer) { const rect = drawer.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right) closeDrawer(); } });
    q('[data-club-close]').addEventListener('click', closeDrawer);
    ['prev', 'next'].forEach(direction => q('[data-club-' + direction + ']').addEventListener('click', () => {
        const members = context.members || []; const index = members.findIndex(member => currentMember && Number(member.id) === Number(currentMember.id));
        const target = members[index + (direction === 'next' ? 1 : -1)]; if (target) openMember(target.id);
    }));
    async function saveNote(control) {
        if (detailPending) return;
        const input = q('[data-club-note]'); const message = q('[data-club-detail-message]'); const memberId = Number(control.dataset.clubNoteSave);
        if (!input || !message || !currentMember || Number(currentMember.id) !== memberId) return;
        if (!beginDrawerWrite()) return;
        message.textContent = ''; message.classList.remove('gca__error');
        const stillViewingMember = () => drawer.open && currentMember && Number(currentMember.id) === memberId && input.isConnected && message.isConnected;
        try {
            const data = await request('genosys_club_note', { id: memberId, note: input.value });
            if (stillViewingMember()) { currentMember = data.member; input.value = data.member.note || ''; message.textContent = 'یادداشت ذخیره شد.'; }
            else toast('یادداشت ذخیره شد.');
        } catch (error) { if (stillViewingMember()) { message.textContent = error.message; message.classList.add('gca__error'); } else toast(error.message); }
        finally { endDrawerWrite(); }
    }
    async function changeStatus(control) {
        if (!currentMember || detailPending) return;
        const memberId = Number(control.dataset.clubChangeStatus);
        const restore = control.dataset.status === 'active';
        if (!window.confirm((restore ? 'عضویت «' : 'عضویت «') + currentMember.name + (restore ? '» در باشگاه بازگردانی شود؟' : '» در باشگاه لغو شود؟'))) return;
        if (!beginDrawerWrite()) return;
        try { const data = await request('genosys_club_status', { id: memberId, status: control.dataset.status }); if (drawer.open && currentMember && Number(currentMember.id) === memberId) renderDetail(data.member); toast(restore ? 'عضویت بازگردانی شد.' : 'عضویت لغو شد.'); await loadList(); }
        catch (error) { const message = q('[data-club-detail-message]'); if (message && currentMember && Number(currentMember.id) === memberId) { message.textContent = error.message; message.classList.add('gca__error'); } else toast(error.message); }
        finally { endDrawerWrite(); }
    }
    function openDeleteConfirmation(control) {
        if (detailPending || !currentMember || Number(currentMember.id) !== Number(control.dataset.clubDeleteOpen)) return;
        const confirmation = q('[data-club-delete-confirmation]');
        if (!confirmation) return;
        confirmation.hidden = false; q('[data-club-delete-cancel]', confirmation).focus();
        confirmation.scrollIntoView({ block: 'nearest', behavior: 'auto' });
    }
    function cancelDeleteConfirmation() {
        if (detailPending) return;
        const confirmation = q('[data-club-delete-confirmation]');
        if (confirmation) confirmation.hidden = true;
        const open = q('[data-club-delete-open]'); if (open) open.focus();
    }
    async function deleteMember(control) {
        const memberId = Number(control.dataset.clubDeleteConfirm);
        const confirmation = q('[data-club-delete-confirmation]');
        if (detailPending || !currentMember || Number(currentMember.id) !== memberId || !confirmation || confirmation.hidden) return;
        if (!beginDrawerWrite()) return;
        const label = control.textContent; control.textContent = 'در حال حذف…';
        const message = q('[data-club-detail-message]'); if (message) { message.textContent = 'در حال حذف همین عضویت…'; message.classList.remove('gca__error'); }
        if (listController) listController.abort();
        clearTimeout(debounce);
        try {
            const data = await request('genosys_club_delete', { id: memberId, confirm: 'delete', filters: JSON.stringify(filters) });
            if (data.context) { context = data.context; filters = { ...context.filters }; render(); }
            else await loadList();
            currentMember = null; endDrawerWrite(); closeDrawer();
            toast('عضویت انتخاب‌شده برای همیشه حذف شد. ثبت‌نام دوباره در همین نوع عضویت امکان‌پذیر است.');
            const heading = q('.gca__header h1'); if (heading) { heading.tabIndex = -1; heading.focus({ preventScroll: true }); }
        } catch (error) {
            if (message && message.isConnected) { message.textContent = error.message; message.classList.add('gca__error'); }
            else toast(error.message);
        } finally {
            endDrawerWrite();
            if (control.isConnected) control.textContent = label;
        }
    }
    function smsError(text) { const node = q('[data-club-sms-error]'); node.textContent = text; node.hidden = !text; }
    function resetSmsReview() {
        q('[data-club-sms-review]').hidden = true; q('[data-club-sms-confirm]').hidden = true;
        const review = q('[data-club-sms-review-button]'); review.hidden = false; review.disabled = !smsCampaign || !smsCampaign.available || !smsCampaign.count || !q('[data-club-sms-text]').value.trim() || smsPending;
    }
    async function previewSms() {
        if (smsPreviewController) smsPreviewController.abort();
        smsPreviewController = new AbortController();
        const version = ++smsPreviewVersion;
        smsCampaign = null; smsError(''); resetSmsReview();
        q('[data-club-sms-recipients]').textContent = 'در حال بررسی گیرندگان…';
        try {
            const data = await request('genosys_club_sms_preview', { filters: JSON.stringify(filters), member_id: smsMemberId }, smsPreviewController.signal);
            if (!smsDialog.open || version !== smsPreviewVersion) return;
            smsCampaign = data;
            q('[data-club-sms-recipients]').textContent = number(data.count || 0) + ' گیرنده‌ی واجد شرایط ارسال';
            const provider = q('[data-club-sms-provider]'); provider.textContent = data.message || ''; provider.hidden = !data.message;
            resetSmsReview();
        } catch (error) { if (error.name === 'AbortError' || version !== smsPreviewVersion) return; smsError(error.message); q('[data-club-sms-recipients]').textContent = 'بررسی گیرندگان انجام نشد.'; }
    }
    function openSms(memberId = 0) {
        if (smsPending || detailPending) return;
        if (!memberId && filters.status === 'cancelled') { toast('ارسال پیامک فقط برای اعضای فعال امکان‌پذیر است.'); return; }
        smsMemberId = Number(memberId) || 0; smsRequestId = ''; q('[data-club-sms-text]').value = ''; q('[data-club-sms-length]').textContent = '۰ / ۱۰۰۰';
        if (!smsDialog.open) { smsDialog.showModal(); updateModalScroll(); }
        q('[data-club-sms-text]').focus(); previewSms();
    }
    function closeSms() { if (!smsPending && smsDialog.open) smsDialog.close(); }
    smsDialog.addEventListener('close', () => { if (smsPreviewController) smsPreviewController.abort(); smsPreviewVersion++; smsCampaign = null; updateModalScroll(); });
    qa('[data-club-sms-close], [data-club-sms-cancel]').forEach(control => control.addEventListener('click', closeSms));
    smsDialog.addEventListener('cancel', event => { if (smsPending) event.preventDefault(); });
    smsDialog.addEventListener('click', event => { if (event.target === smsDialog) { const rect = smsDialog.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeSms(); } });
    q('[data-club-sms-bulk]').addEventListener('click', () => openSms());
    q('[data-club-sms-text]').addEventListener('input', event => { q('[data-club-sms-length]').textContent = number(event.target.value.length) + ' / ۱۰۰۰'; smsError(''); resetSmsReview(); });
    q('[data-club-sms-review-button]').addEventListener('click', () => {
        if (!smsCampaign || !smsCampaign.available || !smsCampaign.count || !q('[data-club-sms-text]').value.trim()) return;
        const review = q('[data-club-sms-review]'); review.textContent = 'این پیامک به ' + number(smsCampaign.count) + ' گیرنده ارسال می‌شود. متن و تعداد گیرندگان را بررسی و ارسال را تأیید کنید.'; review.hidden = false;
        q('[data-club-sms-review-button]').hidden = true; q('[data-club-sms-confirm]').hidden = false; q('[data-club-sms-confirm]').focus();
    });
    q('[data-club-sms-confirm]').addEventListener('click', async () => {
        if (smsPending || !smsCampaign || !smsCampaign.available || !q('[data-club-sms-text]').value.trim()) return;
        if (!window.crypto || !window.crypto.getRandomValues) { smsError('برای ارسال امن پیامک، مرورگر را به‌روزرسانی کنید.'); return; }
        if (!smsRequestId) smsRequestId = Array.from(crypto.getRandomValues(new Uint8Array(16))).map(value => value.toString(16).padStart(2, '0')).join('');
        smsPending = true; const control = q('[data-club-sms-confirm]'); control.disabled = true; q('[data-club-sms-text]').disabled = true; smsError('');
        try {
            const data = await request('genosys_club_sms_send', { campaign_id: smsCampaign.campaignId, text: q('[data-club-sms-text]').value, request_id: smsRequestId });
            q('[data-club-sms-review]').textContent = data.message || ('ارسال‌شده: ' + number(data.sent || 0) + ' · ناموفق: ' + number(data.failed || 0) + ' · نامشخص: ' + number(data.unconfirmed || 0));
            control.hidden = true; smsCampaign = null;
        } catch (error) { smsError(error.message); }
        finally { smsPending = false; control.disabled = false; q('[data-club-sms-text]').disabled = false; }
    });
    const senderSave = q('[data-club-sms-sender-save]');
    if (senderSave) senderSave.addEventListener('click', async () => {
        if (smsPending) return;
        senderSave.disabled = true; smsError('');
        try { const data = await request('genosys_club_sms_settings', { sender: q('[data-club-sms-sender]').value }); toast(data.message || 'خط فرستنده ذخیره شد.'); await previewSms(); }
        catch (error) { smsError(error.message); } finally { senderSave.disabled = false; }
    });
    qa('[data-club-filter]').forEach(control => {
        control.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', () => {
            filters[control.dataset.clubFilter] = control.value; filters.page = 1;
            clearTimeout(debounce); if (control.tagName === 'INPUT') debounce = setTimeout(loadList, 300); else loadList();
        });
    });
    root.addEventListener('click', event => {
        const status = event.target.closest('[data-club-status]');
        if (status) { clearTimeout(debounce); filters.status = status.dataset.clubStatus; filters.page = 1; loadList(); return; }
        const page = event.target.closest('[data-club-page]');
        if (page && !page.disabled) { clearTimeout(debounce); filters.page = Number(page.dataset.clubPage); loadList(); return; }
        const note = event.target.closest('[data-club-note-save]'); if (note) { saveNote(note); return; }
        const deleteOpen = event.target.closest('[data-club-delete-open]'); if (deleteOpen) { openDeleteConfirmation(deleteOpen); return; }
        const deleteCancel = event.target.closest('[data-club-delete-cancel]'); if (deleteCancel) { cancelDeleteConfirmation(); return; }
        const deleteConfirm = event.target.closest('[data-club-delete-confirm]'); if (deleteConfirm) { deleteMember(deleteConfirm); return; }
        const change = event.target.closest('[data-club-change-status]'); if (change) { changeStatus(change); return; }
        const sms = event.target.closest('[data-club-sms-member]'); if (sms && !sms.disabled) { openSms(sms.dataset.clubSmsMember); return; }
        const row = event.target.closest('[data-club-member]'); if (row) openMember(row.dataset.clubMember);
    });
    root.addEventListener('keydown', event => { const row = event.target.closest('[data-club-member]'); if (row && event.target === row && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); openMember(row.dataset.clubMember); } });
    q('[data-club-export]').addEventListener('click', event => { if (event.currentTarget.getAttribute('aria-disabled') === 'true') event.preventDefault(); });
    render();
}());
