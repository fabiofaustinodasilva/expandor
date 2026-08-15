import { mobileApi } from './mobile-api.js';
import { formatCurrency, escapeHtml } from './seller-labels.js';

function $(id) {
    return document.getElementById(id);
}

function isUnusableMediaOrigin(value) {
    try {
        const parsed = new URL(value);
        return parsed.protocol === 'capacitor:'
            || parsed.hostname === 'localhost'
            || parsed.hostname === '127.0.0.1';
    } catch {
        return true;
    }
}

function originFrom(value) {
    try {
        const parsed = new URL(value);

        return `${parsed.protocol}//${parsed.host}`;
    } catch {
        return '';
    }
}

function mediaOrigin() {
    const web = String(window.EXPANDOR_WEB_ORIGIN || '').trim().replace(/\/$/, '');
    const api = String(window.EXPANDOR_API_BASE || '').trim().replace(/\/$/, '');

    if (web && !isUnusableMediaOrigin(web)) {
        return originFrom(web);
    }
    if (api && !isUnusableMediaOrigin(api)) {
        return originFrom(api);
    }
    if (web) {
        return originFrom(web) || web;
    }
    if (api) {
        return originFrom(api) || api;
    }

    return '';
}

function publicStoragePath(raw) {
    const value = String(raw || '').trim().replace(/\\/g, '/');
    if (!value) {
        return '';
    }
    if (/^https?:\/\//i.test(value) || value.startsWith('data:') || value.startsWith('blob:')) {
        return value;
    }

    let path = value.replace(/^\/+/, '');
    if (path.startsWith('storage/')) {
        path = path.slice('storage/'.length).replace(/^\/+/, '');
    }
    if (/^(companies|platform|brands|users)\//.test(path)) {
        return `/storage/${path}`;
    }
    if (value.startsWith('/')) {
        return value.startsWith('/storage/') ? value : `/${path}`;
    }

    return `/${path}`;
}

function resolveMediaUrl(url) {
    if (!url) {
        return '';
    }
    const publicPath = publicStoragePath(url);
    if (/^https?:\/\//i.test(publicPath) || publicPath.startsWith('data:') || publicPath.startsWith('blob:')) {
        return publicPath;
    }

    const origin = mediaOrigin();
    if (!origin || !publicPath) {
        return '';
    }

    return origin + publicPath;
}

function isThumbMediaPath(value) {
    return /\/thumbs\//.test(String(value || ''));
}

function originalUrlFromThumb(value) {
    const resolved = resolveMediaUrl(value);
    if (!resolved || !isThumbMediaPath(resolved)) {
        return '';
    }

    return resolved.replace(/\/thumbs\/([^/?#]+)(\?.*)?$/, '/$1$2');
}

function deckImageUrls(item) {
    const originalRaw = item.image_original
        || (!isThumbMediaPath(item.image) ? item.image : '');
    const thumbRaw = item.image_thumb
        || (isThumbMediaPath(item.image) ? item.image : '');
    let original = resolveMediaUrl(originalRaw);
    const thumb = resolveMediaUrl(thumbRaw);
    if (!original && thumb) {
        original = originalUrlFromThumb(thumbRaw) || originalUrlFromThumb(thumb);
        if (original === thumb) {
            original = '';
        }
    }

    return {
        original: original || '',
        thumb: thumb || '',
        resolved: original || thumb || '',
    };
}

function logDeckImage(level, extra) {
    const payload = {
        product_id: extra.product_id ?? null,
        original: extra.original ?? null,
        thumb: extra.thumb ?? null,
        resolved: extra.resolved ?? null,
    };
    if (level === 'warn') {
        console.warn('[EXP ProductDeck] image', payload);

        return;
    }
    console.info('[EXP ProductDeck] image', payload);
}

function preloadDeckImage(url) {
    if (!url || typeof Image === 'undefined') {
        return;
    }
    const img = new Image();
    img.src = url;
}

function mediaHtml(item) {
    const name = escapeHtml(item.name || 'Produto');
    if (item.video && item.video_embed) {
        return `<div class="deck-media"><iframe src="${escapeHtml(item.video)}" title="Vídeo ${name}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>`;
    }
    if (item.video && !item.video_embed) {
        const src = escapeHtml(resolveMediaUrl(item.video) || item.video);
        return `<div class="deck-media"><video controls playsinline preload="metadata" muted><source src="${src}"></video></div>`;
    }

    const urls = deckImageUrls(item);
    logDeckImage('info', { product_id: item.id, original: urls.original, thumb: urls.thumb, resolved: urls.resolved });
    if (!urls.resolved) {
        return `<div class="deck-media"><div class="deck-media-empty" data-deck-fallback="1">${name}</div></div>`;
    }

    return `<div class="deck-media"><img src="${escapeHtml(urls.resolved)}" alt="${name}" data-product-id="${escapeHtml(item.id)}" data-original-src="${escapeHtml(urls.original)}" data-thumb-src="${escapeHtml(urls.thumb)}"></div>`;
}

function bindDeckImages(root) {
    root?.querySelectorAll('.deck-media img')?.forEach((img) => {
        img.addEventListener('error', () => {
            const original = img.getAttribute('data-original-src') || '';
            const thumb = img.getAttribute('data-thumb-src') || '';
            const current = img.getAttribute('src') || '';
            if (thumb && current !== thumb && original && current === original) {
                img.src = thumb;
                logDeckImage('warn', {
                    product_id: img.getAttribute('data-product-id'),
                    original,
                    thumb,
                    resolved: thumb,
                });

                return;
            }
            logDeckImage('warn', {
                product_id: img.getAttribute('data-product-id'),
                original,
                thumb,
                resolved: current,
            });
            const fallback = document.createElement('div');
            fallback.className = 'deck-media-empty';
            fallback.dataset.deckFallback = 'error';
            fallback.textContent = img.getAttribute('alt') || 'Produto';
            img.replaceWith(fallback);
        });
    });
}

export const PresentationScreen = {
    products: [],
    index: 0,
    startX: 0,
    deltaX: 0,
    swiping: false,
    detailsOpen: false,
    bound: false,
    onContract: null,

    bind() {
        if (this.bound) {
            return;
        }
        this.bound = true;

        $('presentation-close')?.addEventListener('click', () => this.close());
        $('deck-details')?.addEventListener('click', () => this.openDetails());
        $('deck-details-close')?.addEventListener('click', () => this.closeDetails());
        $('deck-details-backdrop')?.addEventListener('click', () => this.closeDetails());
        $('deck-contract')?.addEventListener('click', () => this.contractCurrent());
        $('deck-prev')?.addEventListener('click', () => this.goTo(this.index - 1, true));
        $('deck-next')?.addEventListener('click', () => this.goTo(this.index + 1, true));

        const viewport = $('deck-viewport');
        viewport?.addEventListener('touchstart', (event) => {
            if (this.detailsOpen || !event.touches?.[0]) {
                return;
            }
            this.swiping = true;
            this.startX = event.touches[0].clientX;
            this.deltaX = 0;
            const track = $('deck-track');
            if (track) {
                track.style.transition = 'none';
            }
        }, { passive: true });

        viewport?.addEventListener('touchmove', (event) => {
            if (this.detailsOpen || !this.swiping || !event.touches?.[0]) {
                return;
            }
            this.deltaX = event.touches[0].clientX - this.startX;
            const width = viewport.clientWidth || 1;
            const offset = (-this.index * 100) + (this.deltaX / width * 100);
            const track = $('deck-track');
            if (track) {
                track.style.transform = `translate3d(${offset}%, 0, 0)`;
            }
        }, { passive: true });

        viewport?.addEventListener('touchend', () => {
            if (this.detailsOpen) {
                this.swiping = false;
                this.deltaX = 0;

                return;
            }
            if (!this.swiping) {
                return;
            }
            this.swiping = false;
            const track = $('deck-track');
            if (track) {
                track.style.transition = 'transform 0.28s ease';
            }
            if (this.deltaX < -56) {
                this.goTo(this.index + 1, true);
            } else if (this.deltaX > 56) {
                this.goTo(this.index - 1, true);
            } else {
                this.goTo(this.index, true);
            }
            this.deltaX = 0;
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.detailsOpen) {
                this.closeDetails();
            }
        });
    },

    async open() {
        this.bind();
        const pane = $('pane-presentation');
        if (!pane) {
            return;
        }

        pane.hidden = false;
        $('screen-app')?.classList.add('presentation-open');
        await this.load();
    },

    close() {
        this.closeDetails();
        const pane = $('pane-presentation');
        if (pane) {
            pane.hidden = true;
        }
        $('screen-app')?.classList.remove('presentation-open');
    },

    async load() {
        const track = $('deck-track');
        const counter = $('deck-counter');
        if (!track) {
            return;
        }

        track.innerHTML = '';
        if (counter) {
            counter.textContent = '…';
        }

        try {
            const payload = await mobileApi.products();
            this.products = payload.data || [];
        } catch (error) {
            track.innerHTML = `<div class="deck-empty"><p>Não foi possível carregar</p><p>${escapeHtml(error.message)}</p></div>`;
            this.syncChrome();

            return;
        }

        this.index = 0;
        this.render();
    },

    render() {
        const root = $('presentation-deck');
        const track = $('deck-track');
        if (!track) {
            return;
        }

        root?.querySelector('.deck-empty')?.remove();

        if (!this.products.length) {
            track.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'deck-empty';
            empty.textContent = 'Nenhum produto ativo para apresentar.';
            root?.appendChild(empty);
            this.syncChrome();

            return;
        }

        track.innerHTML = this.products.map((item) => `
            <article class="deck-slide" data-product-id="${escapeHtml(item.id)}">
                ${mediaHtml(item)}
                <div class="deck-caption">
                    ${item.category ? `<p class="deck-category">${escapeHtml(item.category)}</p>` : ''}
                    <h1 class="deck-name">${escapeHtml(item.name)}</h1>
                </div>
            </article>`).join('');

        bindDeckImages(track);
        this.goTo(this.index, false);
    },

    goTo(nextIndex, animate) {
        if (!this.products.length) {
            this.syncChrome();

            return;
        }
        if (this.detailsOpen) {
            this.closeDetails();
        }
        this.index = Math.min(Math.max(0, nextIndex), this.products.length - 1);
        const track = $('deck-track');
        if (track) {
            track.style.transition = animate ? 'transform 0.28s ease' : 'none';
            track.style.transform = `translate3d(${-this.index * 100}%, 0, 0)`;
            if (!animate) {
                requestAnimationFrame(() => {
                    track.style.transition = 'transform 0.28s ease';
                });
            }
        }
        this.syncChrome();
        const next = this.products[this.index + 1];
        if (next) {
            preloadDeckImage(deckImageUrls(next).resolved);
        }
    },

    syncChrome() {
        const counter = $('deck-counter');
        const prevBtn = $('deck-prev');
        const nextBtn = $('deck-next');
        const contractBtn = $('deck-contract');
        const detailsBtn = $('deck-details');
        const total = this.products.length;

        if (counter) {
            counter.textContent = total ? `${this.index + 1} / ${total}` : '0 / 0';
        }
        if (prevBtn) {
            prevBtn.disabled = !total || this.index <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = !total || this.index >= total - 1;
        }
        if (!total) {
            contractBtn?.setAttribute('aria-disabled', 'true');
            if (detailsBtn) {
                detailsBtn.disabled = true;
            }

            return;
        }
        const item = this.products[this.index];
        contractBtn?.removeAttribute('aria-disabled');
        if (contractBtn) {
            contractBtn.dataset.productId = String(item.id);
            contractBtn.setAttribute('aria-label', `Contratar ${item.name || 'produto'}`);
        }
        if (detailsBtn) {
            detailsBtn.disabled = false;
            detailsBtn.dataset.productId = String(item.id);
        }
    },

    fillDetailsPanel(item) {
        const detailsTitle = $('deck-details-title');
        const detailsCategory = $('deck-details-category');
        const detailsBody = $('deck-details-body');
        if (!item) {
            return;
        }
        if (detailsTitle) {
            detailsTitle.textContent = item.name || 'Detalhes';
        }
        if (detailsCategory) {
            detailsCategory.textContent = item.category || '';
        }
        let html = '';
        if (item.description) {
            html += `<section class="deck-sheet-section"><h3>Descrição</h3><p>${escapeHtml(item.description)}</p></section>`;
        }
        const benefits = Array.isArray(item.benefits) ? item.benefits : [];
        if (benefits.length) {
            html += `<section class="deck-sheet-section"><h3>Benefícios</h3><ul>${
                benefits.map((benefit) => `<li>${escapeHtml(benefit)}</li>`).join('')
            }</ul></section>`;
        }
        if (item.price != null && item.price !== '') {
            html += `<section class="deck-sheet-section"><h3>Preço</h3><div class="deck-sheet-price">${escapeHtml(formatCurrency(item.price))}</div></section>`;
        }
        if (!html) {
            html = '<section class="deck-sheet-section"><p>Sem detalhes adicionais cadastrados para este produto.</p></section>';
        }
        if (detailsBody) {
            detailsBody.innerHTML = html;
        }
    },

    openDetails() {
        if (!this.products.length) {
            return;
        }
        const detailsSheet = $('deck-details-sheet');
        if (!detailsSheet) {
            return;
        }
        this.fillDetailsPanel(this.products[this.index]);
        this.detailsOpen = true;
        detailsSheet.classList.add('is-open');
        detailsSheet.setAttribute('aria-hidden', 'false');
        $('presentation-deck')?.classList.add('is-details-open');
        this.swiping = false;
        this.deltaX = 0;
        $('deck-details-close')?.focus();
    },

    closeDetails() {
        const detailsSheet = $('deck-details-sheet');
        if (!detailsSheet) {
            return;
        }
        this.detailsOpen = false;
        detailsSheet.classList.remove('is-open');
        detailsSheet.setAttribute('aria-hidden', 'true');
        $('presentation-deck')?.classList.remove('is-details-open');
        $('deck-details')?.focus();
    },

    contractCurrent() {
        if (!this.products.length) {
            return;
        }
        const item = this.products[this.index];
        if (typeof this.onContract === 'function') {
            this.onContract(item.id);
        }
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorPresentationScreen = PresentationScreen;
    window.ExpandorPresentationScreen.resolveMediaUrl = resolveMediaUrl;
    window.ExpandorPresentationScreen.publicStoragePath = publicStoragePath;
    window.ExpandorPresentationScreen.deckImageUrls = deckImageUrls;
}
