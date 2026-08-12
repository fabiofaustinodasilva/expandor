/**
 * Carrinho de venda mobile — espelha sale-finalize-fields.blade.php + SaleFieldsPolicyResolver.
 */

const FIELD_IDS = {
    name: 'sale-name',
    phone: 'sale-phone',
    whatsapp: 'sale-whatsapp',
    document: 'sale-document',
    rg: 'sale-rg',
    email: 'sale-email',
    notes: 'sale-notes',
};

let sellableProducts = [];
let requiredFields = {};
let fieldLabels = {};

function $(id) {
    return document.getElementById(id);
}

function formatCurrency(value) {
    const amount = Number(value);
    if (Number.isNaN(amount)) {
        return 'R$ 0,00';
    }

    return amount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function productById(id) {
    return sellableProducts.find((row) => Number(row.id) === Number(id));
}

function renderRequiredMarkers() {
    Object.entries(FIELD_IDS).forEach(([key, id]) => {
        const marker = $(`${id}-required`);
        if (marker) {
            marker.hidden = !requiredFields[key];
        }
    });

    const productRequired = $('sale-product-required');
    if (productRequired) {
        productRequired.hidden = !requiredFields.product;
    }
}

export function initSaleForm(products = [], saleFields = {}) {
    sellableProducts = Array.isArray(products) ? products : [];
    requiredFields = saleFields.required || {};
    fieldLabels = saleFields.labels || {};
    renderRequiredMarkers();
    renderCartLines();
}

export function renderCartLines() {
    const root = $('sale-cart-lines');
    const empty = $('sale-cart-empty');
    const totalEl = $('sale-cart-total');
    if (!root) {
        return;
    }

    const lines = root.querySelectorAll('.sale-cart-line');
    if (lines.length === 0) {
        addCartLine(false);
    }

    let total = 0;
    root.querySelectorAll('.sale-cart-line').forEach((line) => {
        const select = line.querySelector('.sale-cart-product');
        const qtyInput = line.querySelector('.sale-cart-qty');
        const product = productById(select?.value);
        const qty = Math.max(1, Number(qtyInput?.value || 1));
        if (product) {
            total += Number(product.price || 0) * qty;
        }
    });

    if (empty) {
        empty.hidden = root.querySelectorAll('.sale-cart-line').length > 0;
    }
    if (totalEl) {
        totalEl.textContent = formatCurrency(total);
    }
}

export function addCartLine(focus = true) {
    const root = $('sale-cart-lines');
    if (!root || sellableProducts.length === 0) {
        return;
    }

    const index = root.querySelectorAll('.sale-cart-line').length;
    const options = sellableProducts.map((product) =>
        `<option value="${product.id}">${product.name} — ${formatCurrency(product.price)}</option>`,
    ).join('');

    const line = document.createElement('div');
    line.className = 'sale-cart-line';
    line.innerHTML = `
        <div class="sale-cart-line__grid">
            <select class="field-select sale-cart-product" aria-label="Produto ${index + 1}">
                <option value="">Selecionar produto</option>
                ${options}
            </select>
            <input class="field-input sale-cart-qty" type="number" min="1" max="9999" value="1" inputmode="numeric" aria-label="Quantidade">
            <button type="button" class="btn btn-ghost sale-cart-remove" aria-label="Remover produto">×</button>
        </div>
    `;

    root.appendChild(line);
    line.querySelector('.sale-cart-product')?.addEventListener('change', renderCartLines);
    line.querySelector('.sale-cart-qty')?.addEventListener('input', renderCartLines);
    line.querySelector('.sale-cart-remove')?.addEventListener('click', () => {
        if (root.querySelectorAll('.sale-cart-line').length <= 1) {
            line.querySelector('.sale-cart-product').value = '';
            line.querySelector('.sale-cart-qty').value = '1';
            renderCartLines();

            return;
        }
        line.remove();
        renderCartLines();
    });

    if (focus) {
        line.querySelector('.sale-cart-product')?.focus();
    }

    renderCartLines();
}

export function collectCartItems() {
    const root = $('sale-cart-lines');
    if (!root) {
        return [];
    }

    const items = [];
    root.querySelectorAll('.sale-cart-line').forEach((line) => {
        const productId = Number(line.querySelector('.sale-cart-product')?.value);
        const quantity = Math.max(1, Number(line.querySelector('.sale-cart-qty')?.value || 1));
        if (productId > 0) {
            items.push({ product_id: productId, quantity });
        }
    });

    return items;
}

export function validateSaleForm() {
    const labels = fieldLabels;
    const checks = [
        ['name', FIELD_IDS.name, labels.name || 'Nome'],
        ['phone', FIELD_IDS.phone, labels.phone || 'Telefone'],
        ['whatsapp', FIELD_IDS.whatsapp, labels.whatsapp || 'WhatsApp'],
        ['document', FIELD_IDS.document, labels.document || 'CPF'],
        ['rg', FIELD_IDS.rg, labels.rg || 'RG'],
        ['email', FIELD_IDS.email, labels.email || 'E-mail'],
        ['notes', FIELD_IDS.notes, labels.notes || 'Observações'],
    ];

    for (const [key, id, label] of checks) {
        if (!requiredFields[key]) {
            continue;
        }
        const value = $(id)?.value?.trim();
        if (!value) {
            return `Informe ${label.toLowerCase()}.`;
        }
    }

    const items = collectCartItems();
    if (requiredFields.product && items.length === 0) {
        return 'Adicione ao menos um produto à venda.';
    }

    return '';
}

export function collectSalePayload() {
    return {
        customer_name: $(FIELD_IDS.name)?.value?.trim() || undefined,
        customer_phone: $(FIELD_IDS.phone)?.value?.trim() || undefined,
        customer_whatsapp: $(FIELD_IDS.whatsapp)?.value?.trim() || undefined,
        customer_document: $(FIELD_IDS.document)?.value?.trim() || undefined,
        customer_rg: $(FIELD_IDS.rg)?.value?.trim() || undefined,
        customer_email: $(FIELD_IDS.email)?.value?.trim() || undefined,
        sale_notes: $(FIELD_IDS.notes)?.value?.trim() || undefined,
        items: collectCartItems(),
    };
}

export function resetSaleForm() {
    Object.values(FIELD_IDS).forEach((id) => {
        const el = $(id);
        if (el) {
            el.value = '';
        }
    });
    const root = $('sale-cart-lines');
    if (root) {
        root.innerHTML = '';
    }
    renderCartLines();
}

if (typeof window !== 'undefined') {
    window.ExpandorSaleCart = {
        initSaleForm,
        renderCartLines,
        addCartLine,
        collectCartItems,
        validateSaleForm,
        collectSalePayload,
        resetSaleForm,
    };
}
