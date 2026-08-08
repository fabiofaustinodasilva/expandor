<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0F1117">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apresentação — {{ config('app.name', 'Expandor') }}</title>
    <style>
        :root {
            --bg: #0F1117;
            --surface: #171A22;
            --border: #2A3142;
            --text: #F8FAFC;
            --muted: #94A3B8;
            --accent: #3B82F6;
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-top: env(safe-area-inset-top, 0px);
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, sans-serif;
            overflow: hidden;
        }
        .deck-shell {
            height: 100dvh;
            display: flex;
            flex-direction: column;
            padding: calc(0.65rem + var(--safe-top)) 0.75rem calc(0.75rem + var(--safe-bottom));
        }
        .deck-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
            flex-shrink: 0;
        }
        .deck-back {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            padding: 0 0.85rem;
            border-radius: 0.85rem;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.92rem;
            white-space: nowrap;
        }
        .deck-counter {
            color: var(--muted);
            font-size: 0.85rem;
            font-weight: 600;
        }
        .deck-viewport {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            position: relative;
            touch-action: pan-y;
            border-radius: 1rem;
            border: 1px solid var(--border);
            background: #0b0d12;
        }
        .deck-track {
            display: flex;
            height: 100%;
            transition: transform 0.28s ease;
            will-change: transform;
        }
        .deck-slide {
            flex: 0 0 100%;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            padding: 0.85rem;
            -webkit-overflow-scrolling: touch;
        }
        .deck-category {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 0 0 0.25rem;
        }
        .deck-name {
            margin: 0 0 0.75rem;
            font-size: clamp(1.25rem, 5vw, 1.75rem);
            line-height: 1.2;
        }
        .deck-media {
            margin-bottom: 0.85rem;
            border-radius: 0.85rem;
            overflow: hidden;
            background: #000;
            border: 1px solid var(--border);
        }
        .deck-media img,
        .deck-media video {
            display: block;
            width: 100%;
            max-height: 42vh;
            object-fit: contain;
            background: #000;
        }
        .deck-media iframe {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 9;
            border: 0;
            background: #000;
        }
        .deck-section { margin-bottom: 0.9rem; }
        .deck-section h2 {
            margin: 0 0 0.35rem;
            font-size: 0.95rem;
        }
        .deck-section p, .deck-section li {
            margin: 0;
            color: #CBD5E1;
            white-space: pre-wrap;
            font-size: 0.95rem;
            line-height: 1.45;
        }
        .deck-section ul {
            margin: 0;
            padding-left: 1.15rem;
        }
        .deck-section li { margin-bottom: 0.35rem; white-space: normal; }
        .deck-commercial {
            padding: 0.75rem 0.9rem;
            border-radius: 0.85rem;
            border: 1px solid var(--border);
            background: var(--surface);
            font-size: 0.95rem;
        }
        .deck-empty {
            display: grid;
            place-items: center;
            height: 100%;
            text-align: center;
            color: var(--muted);
            padding: 1.5rem;
        }
        .deck-nav {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.55rem;
            margin-top: 0.75rem;
            flex-shrink: 0;
        }
        .deck-nav button {
            min-height: 48px;
            border-radius: 0.85rem;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .deck-nav button:disabled {
            opacity: 0.35;
            cursor: default;
        }
        .deck-nav .is-next {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        @media (min-width: 768px) {
            .deck-shell { max-width: 720px; margin: 0 auto; }
            .deck-media img, .deck-media video { max-height: 48vh; }
        }
    </style>
</head>
<body>
@php
    $deckJson = json_encode($deck, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
@endphp
<div class="deck-shell" id="presentation-deck"
     data-start="{{ (int) $startIndex }}"
     data-map-url="{{ $mapUrl }}">
    <div class="deck-top">
        <a class="deck-back" id="deck-back-map" href="{{ $mapUrl }}">← Voltar ao mapa</a>
        <div class="deck-counter" id="deck-counter">0 / 0</div>
    </div>

    <div class="deck-viewport" id="deck-viewport">
        <div class="deck-track" id="deck-track"></div>
    </div>

    <div class="deck-nav">
        <button type="button" id="deck-prev" aria-label="Produto anterior">← Anterior</button>
        <button type="button" id="deck-next" class="is-next" aria-label="Próximo produto">Próximo →</button>
    </div>
</div>

<script>
(function () {
    const root = document.getElementById('presentation-deck');
    const track = document.getElementById('deck-track');
    const counter = document.getElementById('deck-counter');
    const prevBtn = document.getElementById('deck-prev');
    const nextBtn = document.getElementById('deck-next');
    const viewport = document.getElementById('deck-viewport');
    const products = {!! $deckJson !!} || [];
    let index = Math.min(Math.max(0, Number(root.dataset.start || 0)), Math.max(0, products.length - 1));
    let startX = 0;
    let deltaX = 0;
    let swiping = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function mediaHtml(item) {
        let html = '';
        if (item.image) {
            html += '<div class="deck-media"><img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '" loading="lazy"></div>';
        }
        if (item.video) {
            if (item.video_embed) {
                html += '<div class="deck-media"><iframe src="' + escapeHtml(item.video) + '" title="Vídeo ' + escapeHtml(item.name) + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
            } else {
                html += '<div class="deck-media"><video controls playsinline preload="metadata" muted><source src="' + escapeHtml(item.video) + '"></video></div>';
            }
        }
        return html;
    }

    function benefitsHtml(item) {
        const list = Array.isArray(item.benefits) ? item.benefits : [];
        if (!list.length) return '';
        return '<section class="deck-section"><h2>Benefícios</h2><ul>' +
            list.map(function (b) { return '<li>' + escapeHtml(b) + '</li>'; }).join('') +
            '</ul></section>';
    }

    function render() {
        if (!products.length) {
            track.innerHTML = '<div class="deck-slide"><div class="deck-empty">Nenhum produto ativo para apresentar.</div></div>';
            counter.textContent = '0 / 0';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
            return;
        }

        track.innerHTML = products.map(function (item) {
            return '<article class="deck-slide">' +
                '<p class="deck-category">' + escapeHtml(item.category) + '</p>' +
                '<h1 class="deck-name">' + escapeHtml(item.name) + '</h1>' +
                mediaHtml(item) +
                (item.description
                    ? '<section class="deck-section"><h2>Descrição</h2><p>' + escapeHtml(item.description) + '</p></section>'
                    : '') +
                benefitsHtml(item) +
                '<section class="deck-commercial"><strong>Preço:</strong> R$ ' + escapeHtml(item.price) + '</section>' +
                '</article>';
        }).join('');

        goTo(index, false);
    }

    function goTo(nextIndex, animate) {
        if (!products.length) return;
        index = Math.min(Math.max(0, nextIndex), products.length - 1);
        if (!animate) {
            track.style.transition = 'none';
        } else {
            track.style.transition = 'transform 0.28s ease';
        }
        track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
        counter.textContent = (index + 1) + ' / ' + products.length;
        prevBtn.disabled = index <= 0;
        nextBtn.disabled = index >= products.length - 1;
        if (!animate) {
            requestAnimationFrame(function () {
                track.style.transition = 'transform 0.28s ease';
            });
        }
    }

    prevBtn.addEventListener('click', function () { goTo(index - 1, true); });
    nextBtn.addEventListener('click', function () { goTo(index + 1, true); });

    viewport.addEventListener('touchstart', function (e) {
        if (!e.touches || !e.touches[0]) return;
        swiping = true;
        startX = e.touches[0].clientX;
        deltaX = 0;
        track.style.transition = 'none';
    }, { passive: true });

    viewport.addEventListener('touchmove', function (e) {
        if (!swiping || !e.touches || !e.touches[0]) return;
        deltaX = e.touches[0].clientX - startX;
        const width = viewport.clientWidth || 1;
        const offset = (-index * 100) + (deltaX / width * 100);
        track.style.transform = 'translate3d(' + offset + '%, 0, 0)';
    }, { passive: true });

    viewport.addEventListener('touchend', function () {
        if (!swiping) return;
        swiping = false;
        track.style.transition = 'transform 0.28s ease';
        if (deltaX < -56) goTo(index + 1, true);
        else if (deltaX > 56) goTo(index - 1, true);
        else goTo(index, true);
        deltaX = 0;
    });

    render();
})();
</script>
</body>
</html>
