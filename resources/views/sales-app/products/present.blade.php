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
            --bg: #0B0D12;
            --text: #F8FAFC;
            --muted: #94A3B8;
            --surface: rgba(23, 26, 34, 0.82);
            --border: rgba(148, 163, 184, 0.28);
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
            position: relative;
            height: 100dvh;
            width: 100%;
            overflow: hidden;
            background: #000;
        }
        .deck-chrome {
            position: absolute;
            z-index: 5;
            left: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: calc(0.55rem + var(--safe-top)) 0.65rem 0.35rem;
            pointer-events: none;
        }
        .deck-chrome > * { pointer-events: auto; }
        .deck-chrome-left {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            min-width: 0;
            flex: 1 1 auto;
        }
        .deck-back,
        .deck-details,
        .deck-contract {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 0 0.85rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.86rem;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            white-space: nowrap;
            cursor: pointer;
            font-family: inherit;
        }
        .deck-back { opacity: 0.92; }
        .deck-details {
            opacity: 0.92;
            flex: 0 0 auto;
        }
        .deck-contract {
            border-color: rgba(148, 163, 184, 0.35);
            color: #E2E8F0;
            font-weight: 700;
            flex: 0 0 auto;
            margin-left: auto;
        }
        .deck-contract[aria-disabled="true"],
        .deck-details:disabled {
            opacity: 0.4;
            pointer-events: none;
        }
        .deck-counter {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: calc(0.7rem + var(--safe-top));
            z-index: 4;
            font-size: 0.78rem;
            font-weight: 600;
            color: #E2E8F0;
            background: rgba(15, 17, 23, 0.55);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.28rem 0.65rem;
            backdrop-filter: blur(8px);
            pointer-events: none;
        }
        .deck-viewport {
            position: absolute;
            inset: 0;
            overflow: hidden;
            touch-action: pan-y;
        }
        .deck-track {
            display: flex;
            height: 100%;
            width: 100%;
            transition: transform 0.28s ease;
            will-change: transform;
        }
        .deck-slide {
            flex: 0 0 100%;
            width: 100%;
            height: 100%;
            position: relative;
            background: #000;
        }
        .deck-media {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            background: #000;
        }
        .deck-media img,
        .deck-media video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }
        .deck-media iframe {
            width: 100%;
            height: min(70dvh, 100%);
            border: 0;
            background: #000;
        }
        .deck-media-empty {
            color: var(--muted);
            font-size: 0.95rem;
            padding: 1.5rem;
            text-align: center;
        }
        .deck-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 3;
            padding: 3.5rem 0.9rem calc(4.25rem + var(--safe-bottom));
            background: linear-gradient(to top, rgba(0,0,0,0.78) 0%, rgba(0,0,0,0.35) 55%, transparent 100%);
            pointer-events: none;
        }
        .deck-category {
            margin: 0 0 0.2rem;
            color: #CBD5E1;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .deck-name {
            margin: 0;
            font-size: clamp(1.15rem, 4.6vw, 1.55rem);
            line-height: 1.2;
            font-weight: 750;
            text-shadow: 0 1px 8px rgba(0,0,0,0.45);
        }
        .deck-nav {
            position: absolute;
            z-index: 5;
            left: 0.55rem;
            right: 0.55rem;
            bottom: calc(0.65rem + var(--safe-bottom));
            display: flex;
            gap: 0.45rem;
            justify-content: space-between;
        }
        .deck-nav button {
            min-height: 40px;
            min-width: 40px;
            padding: 0 0.75rem;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(15, 17, 23, 0.55);
            color: #E2E8F0;
            font-weight: 600;
            font-size: 0.8rem;
            backdrop-filter: blur(8px);
            cursor: pointer;
        }
        .deck-nav button:disabled {
            opacity: 0.28;
            cursor: default;
        }
        .deck-empty {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            text-align: center;
            color: var(--muted);
            padding: 1.5rem;
            z-index: 2;
        }
        .deck-sheet {
            position: absolute;
            inset: 0;
            z-index: 20;
            display: none;
            flex-direction: column;
            justify-content: flex-end;
        }
        .deck-sheet.is-open { display: flex; }
        .deck-sheet-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            border: 0;
            padding: 0;
            cursor: pointer;
        }
        .deck-sheet-panel {
            position: relative;
            z-index: 1;
            max-height: min(68dvh, 34rem);
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0;
            padding: 0.55rem 1rem calc(1rem + var(--safe-bottom));
            border-radius: 1.15rem 1.15rem 0 0;
            background: #171A22;
            border: 1px solid var(--border);
            border-bottom: 0;
            color: var(--text);
            box-shadow: 0 -12px 40px rgba(0, 0, 0, 0.45);
        }
        .deck-sheet-handle {
            width: 2.5rem;
            height: 0.28rem;
            border-radius: 999px;
            background: rgba(148, 163, 184, 0.45);
            margin: 0.15rem auto 0.75rem;
        }
        .deck-sheet-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.85rem;
        }
        .deck-sheet-top h2 {
            margin: 0;
            font-size: 1.05rem;
            line-height: 1.25;
        }
        .deck-sheet-close {
            flex: 0 0 auto;
            min-height: 44px;
            min-width: 44px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(15, 17, 23, 0.65);
            color: #E2E8F0;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            font-family: inherit;
        }
        .deck-sheet-meta {
            margin: 0 0 0.85rem;
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .deck-sheet-section {
            margin: 0 0 0.9rem;
        }
        .deck-sheet-section h3 {
            margin: 0 0 0.35rem;
            font-size: 0.82rem;
            color: #CBD5E1;
            font-weight: 700;
        }
        .deck-sheet-section p,
        .deck-sheet-section li {
            margin: 0;
            color: #E2E8F0;
            font-size: 0.95rem;
            line-height: 1.45;
            white-space: pre-wrap;
        }
        .deck-sheet-section ul {
            margin: 0;
            padding-left: 1.1rem;
        }
        .deck-sheet-section li {
            margin-bottom: 0.3rem;
            white-space: normal;
        }
        .deck-sheet-price {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            padding: 0.45rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid var(--border);
            background: rgba(15, 17, 23, 0.7);
            font-weight: 700;
            font-size: 0.95rem;
        }
        .deck-shell.is-details-open .deck-viewport {
            pointer-events: none;
        }
        @media (min-width: 768px) {
            .deck-shell { max-width: 900px; margin: 0 auto; }
            .deck-sheet-panel {
                max-width: 900px;
                margin: 0 auto;
                border-radius: 1.15rem 1.15rem 0 0;
            }
        }
        @media (max-width: 430px) {
            .deck-back, .deck-details, .deck-contract { padding: 0 0.7rem; font-size: 0.8rem; }
        }
        @media (max-width: 360px) {
            .deck-back, .deck-details, .deck-contract { padding: 0 0.55rem; font-size: 0.75rem; }
            .deck-name { font-size: 1.05rem; }
            .deck-chrome { gap: 0.35rem; }
        }
        @media (max-width: 320px) {
            .deck-back { max-width: 7.2rem; overflow: hidden; text-overflow: ellipsis; }
        }
    </style>
</head>
<body>
@php
    $deckJson = json_encode($deck, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);
@endphp
<div class="deck-shell" id="presentation-deck"
     data-start="{{ (int) $startIndex }}"
     data-map-url="{{ $mapUrl }}"
     data-contract-url-base="{{ $contractUrlBase }}">
    <div class="deck-chrome">
        <div class="deck-chrome-left">
            <a class="deck-back" id="deck-back-map" href="{{ $mapUrl }}">Voltar ao mapa</a>
            <button type="button" class="deck-details" id="deck-details" aria-haspopup="dialog" aria-controls="deck-details-sheet">Detalhes</button>
        </div>
        <a class="deck-contract" id="deck-contract" href="{{ $mapUrl }}">Contratar</a>
    </div>
    <div class="deck-counter" id="deck-counter" aria-live="polite">0 / 0</div>

    <div class="deck-viewport" id="deck-viewport">
        <div class="deck-track" id="deck-track"></div>
    </div>

    <div class="deck-nav" aria-label="Navegação da apresentação">
        <button type="button" id="deck-prev" aria-label="Produto anterior">←</button>
        <button type="button" id="deck-next" aria-label="Próximo produto">→</button>
    </div>

    <div class="deck-sheet" id="deck-details-sheet" aria-hidden="true">
        <button type="button" class="deck-sheet-backdrop" id="deck-details-backdrop" aria-label="Fechar detalhes"></button>
        <div class="deck-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="deck-details-title">
            <div class="deck-sheet-handle" aria-hidden="true"></div>
            <div class="deck-sheet-top">
                <h2 id="deck-details-title">Detalhes</h2>
                <button type="button" class="deck-sheet-close" id="deck-details-close">Fechar</button>
            </div>
            <p class="deck-sheet-meta" id="deck-details-category"></p>
            <div id="deck-details-body"></div>
        </div>
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
    const contractBtn = document.getElementById('deck-contract');
    const detailsBtn = document.getElementById('deck-details');
    const detailsSheet = document.getElementById('deck-details-sheet');
    const detailsBackdrop = document.getElementById('deck-details-backdrop');
    const detailsClose = document.getElementById('deck-details-close');
    const detailsTitle = document.getElementById('deck-details-title');
    const detailsCategory = document.getElementById('deck-details-category');
    const detailsBody = document.getElementById('deck-details-body');
    const products = {!! $deckJson !!} || [];
    const contractUrlBase = root.dataset.contractUrlBase || '';
    let index = Math.min(Math.max(0, Number(root.dataset.start || 0)), Math.max(0, products.length - 1));
    let startX = 0;
    let deltaX = 0;
    let swiping = false;
    let detailsOpen = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function mediaHtml(item) {
        if (item.video && item.video_embed) {
            return '<div class="deck-media"><iframe src="' + escapeHtml(item.video) + '" title="Vídeo ' + escapeHtml(item.name) + '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
        }
        if (item.video && !item.video_embed) {
            return '<div class="deck-media"><video controls playsinline preload="metadata" muted><source src="' + escapeHtml(item.video) + '"></video></div>';
        }
        if (item.image) {
            return '<div class="deck-media"><img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.name) + '" loading="lazy"></div>';
        }
        return '<div class="deck-media"><div class="deck-media-empty">' + escapeHtml(item.name || 'Produto') + '</div></div>';
    }

    function syncContractLink() {
        if (!contractBtn) return;
        if (!products.length) {
            contractBtn.setAttribute('aria-disabled', 'true');
            contractBtn.href = root.dataset.mapUrl || '#';
            if (detailsBtn) detailsBtn.disabled = true;
            return;
        }
        const item = products[index];
        contractBtn.removeAttribute('aria-disabled');
        contractBtn.href = contractUrlBase + encodeURIComponent(String(item.id));
        contractBtn.dataset.productId = String(item.id);
        contractBtn.setAttribute('aria-label', 'Contratar ' + (item.name || 'produto'));
        if (detailsBtn) {
            detailsBtn.disabled = false;
            detailsBtn.dataset.productId = String(item.id);
        }
    }

    function fillDetailsPanel(item) {
        if (!item) return;
        if (detailsTitle) detailsTitle.textContent = item.name || 'Detalhes';
        if (detailsCategory) detailsCategory.textContent = item.category || '';
        let html = '';
        if (item.description) {
            html += '<section class="deck-sheet-section"><h3>Descrição</h3><p>' + escapeHtml(item.description) + '</p></section>';
        }
        const benefits = Array.isArray(item.benefits) ? item.benefits : [];
        if (benefits.length) {
            html += '<section class="deck-sheet-section"><h3>Benefícios</h3><ul>' +
                benefits.map(function (b) { return '<li>' + escapeHtml(b) + '</li>'; }).join('') +
                '</ul></section>';
        }
        if (item.price) {
            html += '<section class="deck-sheet-section"><h3>Preço</h3><div class="deck-sheet-price">R$ ' + escapeHtml(item.price) + '</div></section>';
        }
        if (!html) {
            html = '<section class="deck-sheet-section"><p>Sem detalhes adicionais cadastrados para este produto.</p></section>';
        }
        if (detailsBody) detailsBody.innerHTML = html;
    }

    function openDetails() {
        if (!products.length || !detailsSheet) return;
        const item = products[index];
        fillDetailsPanel(item);
        detailsOpen = true;
        detailsSheet.classList.add('is-open');
        detailsSheet.setAttribute('aria-hidden', 'false');
        root.classList.add('is-details-open');
        swiping = false;
        deltaX = 0;
        if (detailsClose) detailsClose.focus();
    }

    function closeDetails() {
        if (!detailsSheet) return;
        detailsOpen = false;
        detailsSheet.classList.remove('is-open');
        detailsSheet.setAttribute('aria-hidden', 'true');
        root.classList.remove('is-details-open');
        if (detailsBtn) detailsBtn.focus();
    }

    function render() {
        if (!products.length) {
            track.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'deck-empty';
            empty.textContent = 'Nenhum produto ativo para apresentar.';
            root.appendChild(empty);
            counter.textContent = '0 / 0';
            prevBtn.disabled = true;
            nextBtn.disabled = true;
            syncContractLink();
            return;
        }

        track.innerHTML = products.map(function (item) {
            return '<article class="deck-slide" data-product-id="' + escapeHtml(item.id) + '">' +
                mediaHtml(item) +
                '<div class="deck-caption">' +
                    (item.category ? '<p class="deck-category">' + escapeHtml(item.category) + '</p>' : '') +
                    '<h1 class="deck-name">' + escapeHtml(item.name) + '</h1>' +
                '</div>' +
            '</article>';
        }).join('');

        goTo(index, false);
    }

    function goTo(nextIndex, animate) {
        if (!products.length) return;
        if (detailsOpen) closeDetails();
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
        syncContractLink();
        if (!animate) {
            requestAnimationFrame(function () {
                track.style.transition = 'transform 0.28s ease';
            });
        }
    }

    prevBtn.addEventListener('click', function () { goTo(index - 1, true); });
    nextBtn.addEventListener('click', function () { goTo(index + 1, true); });
    detailsBtn?.addEventListener('click', function () { openDetails(); });
    detailsClose?.addEventListener('click', function () { closeDetails(); });
    detailsBackdrop?.addEventListener('click', function () { closeDetails(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && detailsOpen) closeDetails();
    });

    viewport.addEventListener('touchstart', function (e) {
        if (detailsOpen) return;
        if (!e.touches || !e.touches[0]) return;
        swiping = true;
        startX = e.touches[0].clientX;
        deltaX = 0;
        track.style.transition = 'none';
    }, { passive: true });

    viewport.addEventListener('touchmove', function (e) {
        if (detailsOpen || !swiping || !e.touches || !e.touches[0]) return;
        deltaX = e.touches[0].clientX - startX;
        const width = viewport.clientWidth || 1;
        const offset = (-index * 100) + (deltaX / width * 100);
        track.style.transform = 'translate3d(' + offset + '%, 0, 0)';
    }, { passive: true });

    viewport.addEventListener('touchend', function () {
        if (detailsOpen) {
            swiping = false;
            deltaX = 0;
            return;
        }
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
