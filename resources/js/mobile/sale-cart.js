/**
 * Carrinho de venda mobile — espelha sale-finalize-fields.blade.php + SaleFieldsPolicyResolver.
 * prefix: '' → sale-* | 'create-' → create-sale-*
 */

const FIELD_KEYS = {
    name: 'name',
    phone: 'phone',
    whatsapp: 'whatsapp',
    document: 'document',
    rg: 'rg',
    email: 'email',
    notes: 'notes',
};

let sellableProducts = [];
let requiredFields = {};
let fieldLabels = {};
let activePrefix = '';

function fieldId(key) {
    return `${activePrefix}sale-${key}`;
}

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
    Object.keys(FIELD_KEYS).forEach((key) => {
        const marker = $(`${fieldId(key)}-required`);
        if (marker) {
            marker.hidden = !requiredFields[key];
        }
    });

    const productRequired = $(`${activePrefix}sale-product-required`);
    if (productRequired) {
        productRequired.hidden = !requiredFields.product;
    }
}

export function setSalePrefix(prefix = '') {
    activePrefix = prefix || '';
}

export function initSaleForm(products = [], saleFields = {}, prefix = '') {
    setSalePrefix(prefix);
    sellableProducts = Array.isArray(products) ? products : [];
    requiredFields = saleFields.required || {};
    fieldLabels = saleFields.labels || {};
    renderRequiredMarkers();
    renderCartLines();
}

export function renderCartLines() {
    const root = $(`${activePrefix}sale-cart-lines`);
    const empty = $(`${activePrefix}sale-cart-empty`);
    const totalEl = $(`${activePrefix}sale-cart-total`);
    if (!root) {
        return;
    }

    if (root.querySelectorAll('.sale-cart-line').length === 0) {
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
    const root = $(`${activePrefix}sale-cart-lines`);
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
    const root = $(`${activePrefix}sale-cart-lines`);
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
        ['name', fieldId('name'), labels.name || 'Nome'],
        ['phone', fieldId('phone'), labels.phone || 'Telefone'],
        ['whatsapp', fieldId('whatsapp'), labels.whatsapp || 'WhatsApp'],
        ['document', fieldId('document'), labels.document || 'CPF'],
        ['rg', fieldId('rg'), labels.rg || 'RG'],
        ['email', fieldId('email'), labels.email || 'E-mail'],
        ['notes', fieldId('notes'), labels.notes || 'Observações'],
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
        customer_name: $(fieldId('name'))?.value?.trim() || undefined,
        customer_phone: $(fieldId('phone'))?.value?.trim() || undefined,
        customer_whatsapp: $(fieldId('whatsapp'))?.value?.trim() || undefined,
        customer_document: $(fieldId('document'))?.value?.trim() || undefined,
        customer_rg: $(fieldId('rg'))?.value?.trim() || undefined,
        customer_email: $(fieldId('email'))?.value?.trim() || undefined,
        sale_notes: $(fieldId('notes'))?.value?.trim() || undefined,
        items: collectCartItems(),
    };
}

export function resetSaleForm(prefix) {
    if (prefix !== undefined) {
        setSalePrefix(prefix);
    }
    Object.keys(FIELD_KEYS).forEach((key) => {
        const el = $(fieldId(key));
        if (el) {
            el.value = '';
        }
    });
    const root = $(`${activePrefix}sale-cart-lines`);
    if (root) {
        root.innerHTML = '';
    }
    renderCartLines();
}

if (typeof window !== 'undefined') {
    window.ExpandorSaleCart = {
        initSaleForm,
        setSalePrefix,
        renderCartLines,
        addCartLine,
        collectCartItems,
        validateSaleForm,
        collectSalePayload,
        resetSaleForm,
    };
}
