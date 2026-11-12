/* Decorative hero film starts automatically; supporting clips remain on demand. */
(function () {
    'use strict';
    var origin = 'https://player.vimeo.com';
    var allowed = ['1044239323', '1029545187', '1030958117'];
    var active = new Map();
    var current = null;
    var background = null;
    var hosted = document.querySelector('video[data-brand-hosted-video]');

    function playHosted() {
        if (!hosted || hosted.error || document.hidden) { return; }
        hosted.muted = true;
        var playback = hosted.play();
        if (playback && typeof playback.catch === 'function') {
            playback.catch(function () { /* Keep the poster if autoplay is unavailable. */ });
        }
    }

    if (hosted) {
        var hostedHero = hosted.closest('.gs-brand-hero');
        hosted.addEventListener('playing', function () {
            if (hostedHero) { hostedHero.classList.add('is-video-playing'); }
        });
        hosted.addEventListener('error', function () {
            if (hostedHero) { hostedHero.classList.remove('is-video-playing'); }
        });
        hosted.querySelectorAll('source').forEach(function (source) {
            source.addEventListener('error', function () {
                if (hostedHero) { hostedHero.classList.remove('is-video-playing'); }
            });
        });
        if (!hosted.paused && hosted.readyState >= 2 && hostedHero) { hostedHero.classList.add('is-video-playing'); }
        playHosted();
        window.addEventListener('pageshow', playHosted);
    }

    function backgroundCommand(method, value) {
        if (!background || !background.loaded || !background.frame.contentWindow) { return; }
        var message = { method: method };
        if (value !== undefined) { message.value = value; }
        background.frame.contentWindow.postMessage(JSON.stringify(message), origin);
    }

    function backgroundFailed() {
        if (!background) { return; }
        background.hero.classList.remove('is-video-playing');
        background.frame.remove();
        background = null;
    }

    function prepareBackground() {
        if (!background || background.prepared) { return; }
        background.prepared = true;
        ['play', 'timeupdate', 'pause', 'error'].forEach(function (event) {
            backgroundCommand('addEventListener', event);
        });
        backgroundCommand('play');
    }

    function statusFor(stage) {
        var media = stage.parentElement;
        var status = media.querySelector('.gs-brand-video__status');
        if (!status) {
            status = document.createElement('p');
            status.className = 'gs-brand-video__status';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            media.appendChild(status);
        }
        return status;
    }

    function tell(frame, method) {
        var state = active.get(frame);
        if (state && state.loaded && frame.contentWindow) {
            frame.contentWindow.postMessage(JSON.stringify({ method: method }), origin);
        }
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== origin) { return; }
        var message = event.data;
        if (typeof message === 'string') {
            if (message.length > 16384) { return; }
            try { message = JSON.parse(message); } catch (error) { return; }
        }
        if (!message || typeof message !== 'object') { return; }
        if (background && event.source === background.frame.contentWindow) {
            if (message.event === 'error') {
                backgroundFailed();
            } else if (message.event === 'ready' || message.method === 'ping') {
                background.loaded = true;
                prepareBackground();
            } else if (message.event === 'play' || (message.event === 'timeupdate' && message.data && Number(message.data.seconds) > 0)) {
                background.hero.classList.add('is-video-playing');
            } else if (message.event === 'pause') {
                background.hero.classList.remove('is-video-playing');
            }
            return;
        }
        active.forEach(function (state, frame) {
            if (event.source !== frame.contentWindow) { return; }
            if (message.event === 'error') {
                state.restore();
            } else if (message.event === 'ready' || message.method === 'ping') {
                state.loaded = true;
                window.clearTimeout(state.timer);
                state.stage.removeAttribute('aria-busy');
                state.status.textContent = '';
                if (frame !== current || document.hidden) { tell(frame, 'pause'); }
            }
        });
    });

    var layer = document.querySelector('[data-brand-background-video="1044239323"]');
    if (layer) {
        var heroFrame = document.createElement('iframe');
        heroFrame.className = 'gs-brand-hero__frame';
        heroFrame.title = 'فیلم معرفی ژنوسیس';
        heroFrame.tabIndex = -1;
        heroFrame.setAttribute('aria-hidden', 'true');
        heroFrame.setAttribute('inert', '');
        heroFrame.setAttribute('allow', 'autoplay');
        heroFrame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        heroFrame.src = origin + '/video/1044239323?background=1&autoplay=1&muted=1&loop=1&controls=0&keyboard=0&autopause=0&playsinline=1&unmute_button=0&pip=0&fullscreen=0&dnt=1&title=0&byline=0&portrait=0';
        background = { frame: heroFrame, hero: layer.closest('.gs-brand-hero'), loaded: false, prepared: false };
        heroFrame.addEventListener('error', backgroundFailed);
        heroFrame.addEventListener('load', function () {
            if (background && background.frame === heroFrame) {
                background.loaded = true;
                backgroundCommand('ping');
            }
        });
        layer.appendChild(heroFrame);
    }

    document.querySelectorAll('button[data-brand-video]').forEach(function (button) {
        var id = button.getAttribute('data-brand-video');
        var stage = button.closest('.gs-brand-video');
        if (!stage || allowed.indexOf(id) === -1) { return; }
        button.disabled = false;
        button.addEventListener('click', function () {
            if (button.disabled) { return; }
            button.disabled = true;
            active.forEach(function (state, other) { tell(other, 'pause'); });
            var frame = document.createElement('iframe');
            frame.className = 'gs-brand-video__frame';
            frame.title = button.getAttribute('data-brand-video-title') || 'ویدئوی معرفی ژنوسیس';
            frame.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            frame.setAttribute('allowfullscreen', '');
            frame.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            frame.src = origin + '/video/' + id + '?autoplay=1&muted=1&loop=1&autopause=0&dnt=1&title=0&byline=0&portrait=0';
            current = frame;
            var status = statusFor(stage);
            status.textContent = 'در حال بارگذاری ویدئو…';
            stage.setAttribute('aria-busy', 'true');
            var state = {
                stage: stage,
                status: status,
                loaded: false,
                timer: 0,
                restore: function () {
                    if (!active.has(frame)) { return; }
                    window.clearTimeout(state.timer);
                    active.delete(frame);
                    if (current === frame) { current = null; }
                    var hadFocus = document.activeElement === frame;
                    frame.replaceWith(button);
                    button.disabled = false;
                    stage.removeAttribute('aria-busy');
                    status.textContent = 'ویدئو بارگذاری نشد. دوباره تلاش کنید یا از پیوند تماشای ویدئو استفاده کنید.';
                    if (hadFocus) { button.focus({ preventScroll: true }); }
                }
            };
            frame.addEventListener('error', state.restore);
            frame.addEventListener('load', function () {
                state.loaded = true;
                tell(frame, 'ping');
                if (frame !== current || document.hidden) { tell(frame, 'pause'); }
            });
            active.set(frame, state);
            button.replaceWith(frame);
            frame.focus({ preventScroll: true });
            state.timer = window.setTimeout(state.restore, 15000);
        });
    });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            active.forEach(function (state, frame) { tell(frame, 'pause'); });
            if (hosted) { hosted.pause(); }
        }
        else if (hosted) { playHosted(); }
        else if (background && background.loaded && background.prepared) { backgroundCommand('play'); }
    });
}());
