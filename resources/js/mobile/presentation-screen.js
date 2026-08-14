import { mobileApi } from './mobile-api.js';
import { formatCurrency, escapeHtml } from './seller-labels.js';

function $(id) {
    return document.getElementById(id);
}

function resolveMediaUrl(url) {
    if (!url) {
        return '';
    }
    if (/^https?:\/\//i.test(url) || url.startsWith('data:') || url.startsWith('blob:')) {
        return url;
    }
    const base = String(window.EXPANDOR_API_BASE || '').replace(/\/$/, '');
    if (url.startsWith('/')) {
        return base + url;
    }

    return `${base}/${url}`;
}

function mediaHtml(item) {
    const name = escapeHtml(item.name || 'Produto');
    if (item.video && item.video_embed) {
        return `<div class="deck-media"><iframe src="${escapeHtml(resolveMediaUrl(item.video))}" title="Vídeo ${name}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>`;
    }
    if (item.video && !item.video_embed) {
        return `<div class="deck-media"><video controls playsinline preload="metadata" muted><source src="${escapeHtml(resolveMediaUrl(item.video))}"></video></div>`;
    }
    if (item.image) {
        return `<div class="deck-media"><img src="${escapeHtml(resolveMediaUrl(item.image))}" alt="${name}"></div>`;
    }

    return `<div class="deck-media"><div class="deck-media-empty">${name}</div></div>`;
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
}
