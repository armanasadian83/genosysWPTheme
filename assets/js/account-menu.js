/* Navbar account panel and search reveal; links keep their WordPress destinations. */
(function () {
    'use strict';

    var menu = document.querySelector('[data-account-menu]');
    var accountToggle = menu && menu.querySelector('[data-account-toggle]');
    var accountDropdown = menu && menu.querySelector('[data-account-dropdown]');
    var search = document.querySelector('[data-header-search]');
    var searchToggle = search && search.querySelector('[data-search-toggle]');
    var searchForm = search && search.querySelector('[data-search-form]');
    var searchInput = search && search.querySelector('[data-search-input]');
    if (!accountToggle || !accountDropdown || !searchToggle || !searchForm || !searchInput) return;
    var hoverMedia = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)');
    var accountCloseTimer;
    var searchCloseTimer;

    function canHover() {
        return hoverMedia && hoverMedia.matches;
    }

    function setAccountOpen(open, restoreFocus) {
        if (open && menu.hidden) return;
        accountToggle.setAttribute('aria-expanded', String(open));
        accountToggle.setAttribute('aria-label', open ? 'بستن منوی حساب کاربری' : 'باز کردن منوی حساب کاربری');
        accountDropdown.hidden = !open;
        if (restoreFocus) accountToggle.focus();
    }

    function setSearchOpen(open, restoreFocus, focusInput) {
        if (open && search.hidden) return;
        searchToggle.setAttribute('aria-expanded', String(open));
        searchToggle.hidden = open;
        searchForm.hidden = !open;
        if (open && focusInput) searchInput.focus();
        if (!open && document.activeElement === searchInput) searchInput.blur();
        if (!open && restoreFocus) searchToggle.focus();
    }

    accountToggle.addEventListener('click', function () {
        if (!searchForm.hidden && !searchInput.value.trim()) setSearchOpen(false, false, false);
        setAccountOpen(canHover() ? true : accountDropdown.hidden, false);
    });
    searchToggle.addEventListener('click', function () {
        setAccountOpen(false, false);
        setSearchOpen(true, false, true);
    });

    menu.addEventListener('pointerenter', function (event) {
        if (!canHover() || event.pointerType !== 'mouse') return;
        clearTimeout(accountCloseTimer);
        if (!searchForm.hidden && !searchInput.value.trim()) setSearchOpen(false, false, false);
        setAccountOpen(true, false);
    });
    menu.addEventListener('pointerleave', function (event) {
        if (!canHover() || event.pointerType !== 'mouse') return;
        accountCloseTimer = setTimeout(function () {
            if (!menu.matches(':hover')) setAccountOpen(false, false);
        }, 140);
    });
    search.addEventListener('pointerenter', function (event) {
        if (!canHover() || event.pointerType !== 'mouse') return;
        clearTimeout(searchCloseTimer);
        setAccountOpen(false, false);
        setSearchOpen(true, false, false);
    });
    search.addEventListener('pointerleave', function (event) {
        if (!canHover() || event.pointerType !== 'mouse') return;
        searchCloseTimer = setTimeout(function () {
            if (!search.matches(':hover') && !searchInput.value.trim()) setSearchOpen(false, false, false);
        }, 140);
    });
    searchInput.addEventListener('input', function () {
        searchInput.setCustomValidity('');
        if (!searchInput.value.trim() && !search.matches(':hover')) setSearchOpen(false, false, false);
    });
    searchForm.addEventListener('submit', function (event) {
        searchInput.value = searchInput.value.trim();
        if (!searchInput.value) {
            event.preventDefault();
            searchInput.setCustomValidity('نام محصول یا کد SKU را وارد کنید.');
            searchInput.reportValidity();
        }
    });

    document.addEventListener('click', function (event) {
        if (!menu.contains(event.target) && !accountDropdown.hidden) setAccountOpen(false, false);
        if (!search.contains(event.target) && !searchForm.hidden && !searchInput.value.trim()) setSearchOpen(false, false, false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === '/' && !event.ctrlKey && !event.altKey && !event.metaKey) {
            var target = event.target;
            var isEditing = target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(target.tagName));
            if (!isEditing && !search.hidden) {
                event.preventDefault();
                setAccountOpen(false, false);
                setSearchOpen(true, false, true);
            }
        } else if (event.key === 'Escape') {
            if (!accountDropdown.hidden) setAccountOpen(false, true);
            else if (!searchForm.hidden) setSearchOpen(false, true, false);
        }
    });
    menu.addEventListener('focusout', function (event) {
        if (!menu.contains(event.relatedTarget) && !menu.matches(':hover') && !accountDropdown.hidden) setAccountOpen(false, false);
    });
    search.addEventListener('focusout', function (event) {
        if (!search.contains(event.relatedTarget) && !search.matches(':hover') && !searchInput.value.trim() && !searchForm.hidden) setSearchOpen(false, false, false);
    });

    var mobileMenuToggle = document.querySelector('.menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function () {
            setAccountOpen(false, false);
            setSearchOpen(false, false, false);
        });
    }
}());
