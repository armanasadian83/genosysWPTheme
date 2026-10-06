(() => {
  const button = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#primary-navigation');
  const closeButton = nav && nav.querySelector('.mobile-menu-close');
  if (!button || !nav || !closeButton) return;
  const mobile = window.matchMedia('(max-width: 820px)');
  const root = document.documentElement;
  const originalRole = nav.getAttribute('role');
  let open = false;
  let background = [];
  root.classList.add('js');

  // Keep the rest of the page out of the modal's focus and interaction flow.
  const lockBackground = () => {
    let panel = nav;
    while (panel.parentElement && panel !== document.body) {
      for (const sibling of panel.parentElement.children) {
        if (sibling === panel || /^(SCRIPT|STYLE|LINK)$/.test(sibling.tagName)) continue;
        background.push({ element: sibling, wasInert: sibling.hasAttribute('inert') });
        sibling.setAttribute('inert', '');
      }
      panel = panel.parentElement;
    }
  };
  const unlockBackground = () => {
    for (const { element, wasInert } of background) {
      if (!wasInert) element.removeAttribute('inert');
    }
    background = [];
  };
  const setOpen = (requested, restoreFocus = false) => {
    const expanded = mobile.matches && requested;
    const wasOpen = open;
    open = expanded;
    button.setAttribute('aria-expanded', String(expanded));
    button.setAttribute('aria-label', expanded ? 'بستن منو' : 'باز کردن منو');
    nav.hidden = mobile.matches && !expanded;
    root.classList.toggle('mobile-menu-open', expanded);
    document.body.classList.toggle('mobile-menu-open', expanded);
    if (expanded) {
      nav.setAttribute('role', 'dialog');
      nav.setAttribute('aria-modal', 'true');
      nav.setAttribute('tabindex', '-1');
      if (!wasOpen) {
        lockBackground();
        closeButton.focus({ preventScroll: true });
      }
    } else {
      if (originalRole === null) nav.removeAttribute('role');
      else nav.setAttribute('role', originalRole);
      nav.removeAttribute('aria-modal');
      nav.removeAttribute('tabindex');
      unlockBackground();
      if (wasOpen && restoreFocus && mobile.matches) button.focus({ preventScroll: true });
    }
  };
  button.addEventListener('click', () => setOpen(!open, true));
  closeButton.addEventListener('click', () => setOpen(false, true));
  nav.addEventListener('click', event => {
    if (event.target.closest('a') && mobile.matches) setOpen(false, true);
  });
  document.addEventListener('keydown', event => {
    if (!open) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      setOpen(false, true);
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = [...nav.querySelectorAll('a[href], button:not([disabled]), [tabindex="0"]')]
      .filter(element => element.getClientRects().length && !element.closest('[inert]'));
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (!first) {
      event.preventDefault();
      nav.focus();
    } else if (event.shiftKey && (document.activeElement === first || !nav.contains(document.activeElement))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !nav.contains(document.activeElement))) {
      event.preventDefault();
      first.focus();
    }
  });
  document.addEventListener('focusin', event => {
    if (open && !nav.contains(event.target)) closeButton.focus({ preventScroll: true });
  });
  const onBreakpointChange = () => {
    const focused = document.activeElement;
    const focusWasInNav = nav.contains(focused);
    setOpen(false);
    if (mobile.matches && focusWasInNav) button.focus({ preventScroll: true });
    if (!mobile.matches && (focused === button || (focusWasInNav && !focused.getClientRects().length))) {
      const firstLink = nav.querySelector('.primary-menu a');
      if (firstLink) firstLink.focus({ preventScroll: true });
    }
  };
  if (mobile.addEventListener) mobile.addEventListener('change', onBreakpointChange);
  else mobile.addListener(onBreakpointChange);
  setOpen(false);
})();
