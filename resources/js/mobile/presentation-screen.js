import { mobileApi } from './mobile-api.js';
import { formatCurrency, escapeHtml } from './seller-labels.js';

function $(id) {
    return document.getElementById(id);
}

function paintIcons(root = document) {
    try {
        window.lucide?.createIcons?.({ attrs: { 'stroke-width': 2 } });
    } catch {
        /* optional */
    }
}

export const PresentationScreen = {
    products: [],

    async open() {
        const pane = $('pane-presentation');
        if (! pane) {
            return;
        }

        pane.hidden = false;
        $('screen-app')?.classList.add('presentation-open');
        await this.load();
        paintIcons(pane);
    },

    close() {
        const pane = $('pane-presentation');
        if (pane) {
            pane.hidden = true;
        }
        $('screen-app')?.classList.remove('presentation-open');
    },

    async load() {
        const list = $('presentation-list');
        if (! list) {
            return;
        }

        list.innerHTML = '<p class="muted">Carregando produtos…</p>';

        try {
            const payload = await mobileApi.products();
            this.products = payload.data || [];
        } catch (error) {
            list.innerHTML = `<div class="empty-state"><p class="empty-state__title">Não foi possível carregar</p><p class="empty-state__text">${escapeHtml(error.message)}</p></div>`;

            return;
        }

        if (! this.products.length) {
            list.innerHTML = `
                <div class="empty-state">
                    <p class="empty-state__title">Nenhum produto ativo</p>
                    <p class="empty-state__text">Peça ao gestor para ativar produtos vendáveis.</p>
                </div>`;

            return;
        }

        list.innerHTML = this.products.map((product) => {
            const img = product.image
                ? `<img class="product-card__img" src="${escapeHtml(product.image)}" alt="">`
                : `<div class="product-card__img"></div>`;

            return `
                <article class="product-card">
                    ${img}
                    <div class="product-card__body">
                        <h3 class="card__title">${escapeHtml(product.name)}</h3>
                        <p class="card__meta">${escapeHtml(product.description || '')}</p>
                        <p class="product-card__price">${formatCurrency(product.price)}</p>
                    </div>
                </article>`;
        }).join('');
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorPresentationScreen = PresentationScreen;
}
