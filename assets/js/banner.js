(() => {
  const banner = document.querySelector('.hero-banner');
  if (!banner) return;
  const slides = [...banner.querySelectorAll('.hero-slide')];
  const dots = [...banner.querySelectorAll('.banner-dot')];
  const playback = banner.querySelector('.banner-playback');
  const status = banner.querySelector('.banner-status');
  const menu = document.querySelector('.menu-toggle');
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const interval = Number(banner.dataset.interval) || 6000;
  let current = 0;
  let timer;
  let transitioning = false;
  let paused = motion.matches;
  let touchStart;
  let mouseDrag;

  const hasFocus = () => banner.contains(document.activeElement);
  const canRotate = () => !paused && !mouseDrag && !document.hidden && !hasFocus() && (!menu || menu.getAttribute('aria-expanded') !== 'true');
  const schedule = () => {
    window.clearTimeout(timer);
    if (canRotate()) timer = window.setTimeout(() => show(current + 1, false), interval);
  };
  const updatePlayback = () => {
    playback.dataset.paused = String(paused);
    playback.setAttribute('aria-label', paused ? 'شروع پخش خودکار' : 'توقف پخش خودکار');
  };
  const show = (requested, manual = true, direction) => {
    const next = (requested + slides.length) % slides.length;
    if (transitioning || mouseDrag || next === current) return;
    const image = slides[next].querySelector('img');
    if (!image.complete || !image.naturalWidth) {
      schedule();
      return;
    }
    window.clearTimeout(timer);
    transitioning = true;
    banner.dataset.direction = direction || (requested < current ? 'previous' : 'next');
    const previous = slides[current];
    const incoming = slides[next];
    // Establish the incoming image's off-screen position before animating it.
    incoming.getBoundingClientRect();
    previous.classList.remove('is-active');
    previous.classList.add('is-leaving');
    previous.setAttribute('aria-hidden', 'true');
    incoming.classList.add('is-active');
    incoming.setAttribute('aria-hidden', 'false');
    current = next;
    dots.forEach((dot, index) => dot.setAttribute('aria-pressed', String(index === current)));
    if (manual) status.textContent = incoming.getAttribute('aria-label');
    window.setTimeout(() => {
      previous.classList.remove('is-leaving');
      transitioning = false;
    }, motion.matches ? 0 : 700);
    schedule();
  };

  banner.querySelector('.banner-next').addEventListener('click', () => show(current + 1, true, 'next'));
  banner.querySelector('.banner-previous').addEventListener('click', () => show(current - 1, true, 'previous'));
  dots.forEach(dot => dot.addEventListener('click', () => show(Number(dot.dataset.slide))));
  playback.addEventListener('click', () => {
    paused = !paused;
    updatePlayback();
    schedule();
  });
  banner.addEventListener('keydown', event => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    show(current + (event.key === 'ArrowLeft' ? 1 : -1), true, event.key === 'ArrowLeft' ? 'next' : 'previous');
  });
  banner.addEventListener('focusin', () => window.clearTimeout(timer));
  banner.addEventListener('focusout', () => window.setTimeout(schedule, 0));
  document.addEventListener('visibilitychange', schedule);
  if (menu) new MutationObserver(schedule).observe(menu, { attributes: true, attributeFilter: ['aria-expanded'] });
  const onMotionChange = () => {
    if (motion.matches) paused = true;
    updatePlayback();
    schedule();
  };
  if (motion.addEventListener) motion.addEventListener('change', onMotionChange);
  else motion.addListener(onMotionChange);

  banner.addEventListener('touchstart', event => {
    if (event.touches.length !== 1 || event.target.closest('button, a')) return;
    touchStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
  }, { passive: true });
  banner.addEventListener('touchend', event => {
    if (!touchStart || !event.changedTouches.length) return;
    const dx = event.changedTouches[0].clientX - touchStart.x;
    const dy = event.changedTouches[0].clientY - touchStart.y;
    touchStart = null;
    if (Math.abs(dx) < 48 || Math.abs(dx) <= Math.abs(dy) * 1.2) return;
    show(current + (dx > 0 ? 1 : -1), true, dx > 0 ? 'next' : 'previous');
  }, { passive: true });
  banner.addEventListener('touchcancel', () => { touchStart = null; }, { passive: true });

  // Desktop dragging follows the pointer, then settles on a single slide.
  const finishMouseDrag = (cancelled = false) => {
    if (!mouseDrag) return;
    const drag = mouseDrag;
    mouseDrag = null;
    banner.classList.remove('is-pointer-down');
    if (banner.hasPointerCapture(drag.pointerId)) banner.releasePointerCapture(drag.pointerId);
    if (!drag.started) {
      schedule();
      return;
    }
    const previous = slides[current];
    const incoming = slides[drag.target];
    const distance = Math.abs(drag.dx);
    const velocity = distance / Math.max(performance.now() - drag.startedAt, 1);
    const advance = !cancelled && (distance >= Math.min(140, drag.width * .15) || (distance >= 48 && velocity > .5));
    // Restore CSS transitions while retaining the pointer's current positions.
    banner.classList.remove('is-dragging');
    previous.getBoundingClientRect();
    transitioning = true;
    if (advance) {
      previous.classList.remove('is-active');
      previous.classList.add('is-leaving');
      previous.setAttribute('aria-hidden', 'true');
      incoming.classList.add('is-active');
      incoming.setAttribute('aria-hidden', 'false');
      current = drag.target;
      dots.forEach((dot, index) => dot.setAttribute('aria-pressed', String(index === current)));
      status.textContent = incoming.getAttribute('aria-label');
    }
    previous.style.removeProperty('transform');
    incoming.style.removeProperty('transform');
    window.setTimeout(() => {
      previous.classList.remove('is-leaving');
      incoming.classList.remove('is-drag-target');
      transitioning = false;
    }, motion.matches ? 0 : 700);
    schedule();
  };
  banner.addEventListener('pointerdown', event => {
    if (event.pointerType !== 'mouse' || event.button !== 0 || !event.isPrimary || transitioning || mouseDrag || event.target.closest('button, a, input, select, textarea')) return;
    mouseDrag = { pointerId: event.pointerId, x: event.clientX, y: event.clientY, dx: 0, width: banner.clientWidth, started: false, target: null, startedAt: performance.now() };
    window.clearTimeout(timer);
    banner.classList.add('is-pointer-down');
    banner.setPointerCapture(event.pointerId);
    event.preventDefault();
  });
  banner.addEventListener('pointermove', event => {
    if (!mouseDrag || event.pointerId !== mouseDrag.pointerId) return;
    if ((event.buttons & 1) === 0) {
      finishMouseDrag(true);
      return;
    }
    const dx = event.clientX - mouseDrag.x;
    const dy = event.clientY - mouseDrag.y;
    if (!mouseDrag.started && (Math.abs(dx) < 6 || Math.abs(dx) <= Math.abs(dy))) return;
    const direction = dx >= 0 ? 'next' : 'previous';
    const target = (current + (dx >= 0 ? 1 : -1) + slides.length) % slides.length;
    const image = slides[target].querySelector('img');
    if (!image.complete || !image.naturalWidth) return;
    if (!mouseDrag.started) {
      mouseDrag.started = true;
      mouseDrag.startedAt = performance.now();
      banner.classList.add('is-dragging');
      if (hasFocus()) document.activeElement.blur();
    }
    if (mouseDrag.target !== null && mouseDrag.target !== target) {
      slides[mouseDrag.target].classList.remove('is-drag-target');
      slides[mouseDrag.target].style.removeProperty('transform');
    }
    mouseDrag.target = target;
    mouseDrag.dx = Math.max(-mouseDrag.width, Math.min(mouseDrag.width, dx));
    banner.dataset.direction = direction;
    slides[target].classList.add('is-drag-target');
    slides[current].style.transform = `translateX(${mouseDrag.dx}px)`;
    slides[target].style.transform = `translateX(${mouseDrag.dx + (dx >= 0 ? -mouseDrag.width : mouseDrag.width)}px)`;
  });
  banner.addEventListener('pointerup', event => {
    if (mouseDrag && event.pointerId === mouseDrag.pointerId) finishMouseDrag();
  });
  banner.addEventListener('pointercancel', () => finishMouseDrag(true));
  banner.addEventListener('lostpointercapture', () => finishMouseDrag(true));
  window.addEventListener('blur', () => finishMouseDrag(true));
  document.addEventListener('visibilitychange', () => { if (document.hidden) finishMouseDrag(true); });
  banner.addEventListener('dragstart', event => { if (event.target.closest('.hero-slides')) event.preventDefault(); });

  updatePlayback();
  banner.classList.add('is-ready');
  schedule();
})();
