<script>
(function () {
    document.querySelectorAll('[data-mkp-carousel]').forEach(function (root) {
        var track = root.querySelector('[data-mkp-track]');
        var slides = root.querySelectorAll('[data-mkp-slide]');
        var dotsWrap = root.querySelector('[data-mkp-dots]');
        if (!track || !slides.length) return;
        var index = 0;
        var timer = null;

        function go(i) {
            index = (i + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (-index * 100) + '%)';
            if (dotsWrap) {
                dotsWrap.querySelectorAll('button').forEach(function (dot, di) {
                    dot.classList.toggle('is-active', di === index);
                });
            }
        }

        if (dotsWrap) {
            slides.forEach(function (_, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'mkp-carousel-dot' + (i === 0 ? ' is-active' : '');
                b.setAttribute('aria-label', 'Slide ' + (i + 1));
                b.addEventListener('click', function () { go(i); restart(); });
                dotsWrap.appendChild(b);
            });
        }

        root.querySelector('[data-mkp-prev]')?.addEventListener('click', function () { go(index - 1); restart(); });
        root.querySelector('[data-mkp-next]')?.addEventListener('click', function () { go(index + 1); restart(); });

        function restart() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            clearInterval(timer);
            timer = setInterval(function () { go(index + 1); }, 4500);
        }
        restart();
    });

    document.querySelectorAll('[data-mkp-field-ops]').forEach(function (root) {
        var tabs = root.querySelectorAll('[data-mkp-field-tab]');
        function show(key) {
            tabs.forEach(function (tab) {
                var on = tab.getAttribute('data-mkp-field-tab') === key;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            root.querySelectorAll('[data-mkp-field-panel], [data-mkp-field-copy]').forEach(function (el) {
                var on = el.getAttribute('data-mkp-field-panel') === key || el.getAttribute('data-mkp-field-copy') === key;
                el.classList.toggle('is-active', on);
                if (on) el.removeAttribute('hidden');
                else el.setAttribute('hidden', 'hidden');
            });
        }
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                show(tab.getAttribute('data-mkp-field-tab'));
            });
        });
    });

    var modal = document.getElementById('mkp-demo-modal');
    var frame = document.getElementById('mkp-demo-frame');
    var videoEl = document.getElementById('mkp-demo-video');
    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        if (frame) { frame.classList.remove('is-active'); frame.removeAttribute('src'); }
        if (videoEl) { videoEl.classList.remove('is-active'); videoEl.pause(); videoEl.removeAttribute('src'); videoEl.load(); }
    }
    document.querySelectorAll('[data-mkp-demo-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!modal) return;
            var src = btn.getAttribute('data-src');
            var isMp4 = btn.getAttribute('data-mp4') === '1';
            modal.classList.add('is-open');
            if (isMp4 && videoEl) {
                videoEl.classList.add('is-active');
                videoEl.src = src;
                videoEl.play();
            } else if (frame) {
                frame.classList.add('is-active');
                frame.src = src;
            }
        });
    });
    document.getElementById('mkp-demo-close')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
})();
</script>
