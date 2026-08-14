/**
 * Carrinho + ficha CONFIRMAR VENDA — mesmos campos da Web (SaleFieldsPolicyResolver).
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

const DUE_DAYS = [5, 10, 15, 20, 25, 30];

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

function maskCpf(raw) {
    const d = String(raw || '').replace(/\D/g, '').slice(0, 11);
    if (d.length <= 3) return d;
    if (d.length <= 6) return `${d.slice(0, 3)}.${d.slice(3)}`;
    if (d.length <= 9) return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6)}`;

    return `${d.slice(0, 3)}.${d.slice(3, 6)}.${d.slice(6, 9)}-${d.slice(9)}`;
}

function maskPhone(raw) {
    const d = String(raw || '').replace(/\D/g, '').slice(0, 11);
    if (d.length <= 2) return d.length ? `(${d}` : '';
    if (d.length <= 6) return `(${d.slice(0, 2)}) ${d.slice(2)}`;
    if (d.length <= 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`;

    return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`;
}

function maskBirth(raw) {
    const d = String(raw || '').replace(/\D/g, '').slice(0, 8);
    if (d.length <= 2) return d;
    if (d.length <= 4) return `${d.slice(0, 2)}/${d.slice(2)}`;

    return `${d.slice(0, 2)}/${d.slice(2, 4)}/${d.slice(4)}`;
}

function bindMask(id, masker) {
    const el = $(id);
    if (!el || el.dataset.maskBound === '1') {
        return;
    }
    el.dataset.maskBound = '1';
    el.addEventListener('input', () => {
        const start = el.selectionStart;
        el.value = masker(el.value);
        try {
            el.setSelectionRange(el.value.length, el.value.length);
        } catch {
            /* ignore */
        }
        void start;
        updateSaleReview();
    });
}

function renderRequiredMarkers() {
    Object.keys(FIELD_KEYS).forEach((key) => {
        const marker = $(`${fieldId(key)}-required`);
        if (marker) {
            marker.hidden = !requiredFields[key];
        }
        const extra = $(`${fieldId(key)}-wrap`);
        if (extra && (key === 'rg' || key === 'email' || key === 'whatsapp' || key === 'notes')) {
            extra.hidden = !requiredFields[key];
        }
    });

    const productRequired = $(`${activePrefix}sale-product-required`);
    if (productRequired) {
        productRequired.hidden = !requiredFields.product;
    }
}

function bindDueDayChips() {
    const root = $(`${activePrefix}sale-due-chips`);
    const hidden = $(fieldId('due-day'));
    if (!root) {
        return;
    }
    if (root.dataset.bound === '1') {
        root.querySelectorAll('.due-day-chip').forEach((btn) => {
            btn.classList.toggle('is-selected', hidden && String(hidden.value) === btn.dataset.day);
        });

        return;
    }
    root.dataset.bound = '1';
    root.innerHTML = DUE_DAYS.map((day) =>
        `<button type="button" class="due-day-chip" data-day="${day}">${day}</button>`,
    ).join('');
    root.addEventListener('click', (event) => {
        const btn = event.target.closest('.due-day-chip');
        if (!btn) {
            return;
        }
        const day = btn.dataset.day;
        if (hidden) {
            hidden.value = day;
        }
        root.querySelectorAll('.due-day-chip').forEach((chip) => {
            chip.classList.toggle('is-selected', chip.dataset.day === day);
        });
        updateSaleReview();
    });
}

function selectedProductSummary() {
    const items = collectCartItems();
    if (!items.length) {
        return { label: 'Nenhum produto', total: 0 };
    }
    const names = items.map((item) => {
        const product = productById(item.product_id);
        const qty = item.quantity > 1 ? ` ×${item.quantity}` : '';

        return `${product?.name || 'Produto'}${qty}`;
    });
    let total = 0;
    items.forEach((item) => {
        const product = productById(item.product_id);
        total += Number(product?.price || 0) * item.quantity;
    });

    return { label: names.join(', '), total };
}

function maskCpfReview(value) {
    const d = String(value || '').replace(/\D/g, '');
    if (d.length < 11) {
        return value || '—';
    }

    return `***.***.***-${d.slice(-2)}`;
}

export function updateSaleReview() {
    const box = $(`${activePrefix}sale-review`);
    if (!box) {
        return;
    }
    const name = $(fieldId('name'))?.value?.trim() || '—';
    const document = maskCpfReview($(fieldId('document'))?.value);
    const products = selectedProductSummary();
    const due = $(fieldId('due-day'))?.value;
    const street = $(fieldId('install-street'))?.value?.trim() || '';
    const number = $(fieldId('install-number'))?.value?.trim() || '';
    const neighborhood = $(fieldId('install-neighborhood'))?.value?.trim() || '';
    const city = $(fieldId('install-city'))?.value?.trim() || '';
    const line = [street, number].filter(Boolean).join(', ') || '—';

    box.innerHTML = `
        <p class="sale-review__name">${escapeText(name)}</p>
        <p class="sale-review__meta">CPF: ${escapeText(document)}</p>
        <p class="sale-review__meta">${escapeText(products.label)}</p>
        <p class="sale-review__meta">${formatCurrency(products.total)}</p>
        <p class="sale-review__meta">Vencimento: ${due ? `Dia ${due}` : '—'}</p>
        <p class="sale-review__meta">${escapeText(line)}</p>
        <p class="sale-review__meta">${escapeText(neighborhood || '—')}</p>
        <p class="sale-review__meta">${escapeText(city || '—')}</p>
    `;
}

function escapeText(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
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
    bindDueDayChips();
    bindMask(fieldId('document'), maskCpf);
    bindMask(fieldId('phone'), maskPhone);
    bindMask(fieldId('whatsapp'), maskPhone);
    bindMask(fieldId('birth'), maskBirth);
    ['name', 'install-street', 'install-number', 'install-neighborhood', 'install-reference', 'install-city']
        .forEach((key) => {
            const el = $(fieldId(key));
            if (el && el.dataset.reviewBound !== '1') {
                el.dataset.reviewBound = '1';
                el.addEventListener('input', updateSaleReview);
            }
        });
    updateSaleReview();
}

export function applySaleCity({ city = '', cityId = null, locked = false } = {}) {
    const input = $(fieldId('install-city'));
    const hidden = $(fieldId('install-city-id'));
    const hint = $(`${activePrefix}sale-install-city-hint`);
    const name = String(city || '').trim();
    const placeholderOnly = !name || /^selecionar$/i.test(name);

    if (input) {
        input.value = placeholderOnly ? '' : name;
        if (locked && !placeholderOnly) {
            input.readOnly = true;
            input.placeholder = '';
        } else {
            input.readOnly = false;
            input.placeholder = 'Digite a cidade';
        }
    }
    if (hidden) {
        hidden.value = cityId ? String(cityId) : '';
    }
    if (hint) {
        hint.hidden = !(locked && !placeholderOnly);
    }
    updateSaleReview();
}

export function prefillSaleForm(data = {}) {
    const set = (key, value) => {
        const el = $(fieldId(key));
        if (el && value != null && value !== '' && !el.value) {
            el.value = value;
        }
    };
    set('name', data.name);
    set('phone', data.phone ? maskPhone(data.phone) : '');
    set('whatsapp', data.whatsapp ? maskPhone(data.whatsapp) : '');
    set('document', data.document ? maskCpf(data.document) : '');
    set('birth', data.birth_date);
    set('install-street', data.street);
    set('install-number', data.number);
    set('install-neighborhood', data.neighborhood);
    set('install-reference', data.reference);
    applySaleCity({
        city: data.city,
        cityId: data.city_id,
        locked: Boolean(data.city_locked),
    });
    updateSaleReview();
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
    updateSaleReview();
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

export function seedCartWithProduct(productId) {
    const pid = Number(productId);
    const product = productById(pid);
    if (!pid || !product || product.available === false) {
        return false;
    }

    const root = $(`${activePrefix}sale-cart-lines`);
    if (!root) {
        return false;
    }

    root.innerHTML = '';
    addCartLine(false);
    const select = root.querySelector('.sale-cart-product');
    if (select) {
        select.value = String(product.id);
    }
    renderCartLines();

    return true;
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
    const name = $(fieldId('name'))?.value?.trim();
    if (!name && (requiredFields.name !== false)) {
        return `Informe ${(labels.name || 'o nome completo').toLowerCase()}.`;
    }
    const document = $(fieldId('document'))?.value?.trim();
    if (!document) {
        return 'Informe o CPF.';
    }
    const birth = $(fieldId('birth'))?.value?.trim();
    if (!birth) {
        return 'Informe a data de nascimento.';
    }
    const phone = $(fieldId('phone'))?.value?.trim();
    if (!phone && requiredFields.phone) {
        return `Informe ${(labels.phone || 'o telefone').toLowerCase()}.`;
    }
    const street = $(fieldId('install-street'))?.value?.trim();
    if (!street) {
        return 'Informe a rua / avenida.';
    }
    const due = $(fieldId('due-day'))?.value;
    if (!due) {
        return 'Selecione o vencimento.';
    }
    const checks = [
        ['whatsapp', fieldId('whatsapp'), labels.whatsapp || 'WhatsApp'],
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
    const phone = $(fieldId('phone'))?.value?.trim() || undefined;
    const whatsapp = $(fieldId('whatsapp'))?.value?.trim() || phone;
    const due = $(fieldId('due-day'))?.value;

    return {
        complete_sale: true,
        customer_name: $(fieldId('name'))?.value?.trim() || undefined,
        customer_phone: phone,
        customer_whatsapp: whatsapp,
        customer_document: $(fieldId('document'))?.value?.trim() || undefined,
        customer_rg: $(fieldId('rg'))?.value?.trim() || undefined,
        customer_email: $(fieldId('email'))?.value?.trim() || undefined,
        customer_birth_date: $(fieldId('birth'))?.value?.trim() || undefined,
        sale_notes: $(fieldId('notes'))?.value?.trim() || undefined,
        due_day: due ? Number(due) : undefined,
        install_street: $(fieldId('install-street'))?.value?.trim() || undefined,
        install_number: $(fieldId('install-number'))?.value?.trim() || undefined,
        install_neighborhood: $(fieldId('install-neighborhood'))?.value?.trim() || undefined,
        install_reference: $(fieldId('install-reference'))?.value?.trim() || undefined,
        install_city: $(fieldId('install-city'))?.value?.trim() || undefined,
        items: collectCartItems(),
    };
}

export function resetSaleForm(prefix) {
    if (prefix !== undefined) {
        setSalePrefix(prefix);
    }
    ['name', 'phone', 'whatsapp', 'document', 'rg', 'email', 'notes', 'birth',
        'install-street', 'install-number', 'install-neighborhood', 'install-reference',
        'install-city', 'due-day'].forEach((key) => {
        const el = $(fieldId(key));
        if (el) {
            el.value = '';
        }
    });
    const chips = $(`${activePrefix}sale-due-chips`);
    chips?.querySelectorAll('.due-day-chip').forEach((chip) => chip.classList.remove('is-selected'));
    const root = $(`${activePrefix}sale-cart-lines`);
    if (root) {
        root.innerHTML = '';
    }
    renderCartLines();
    updateSaleReview();
}

if (typeof window !== 'undefined') {
    window.ExpandorSaleCart = {
        initSaleForm,
        setSalePrefix,
        renderCartLines,
        addCartLine,
        seedCartWithProduct,
        collectCartItems,
        validateSaleForm,
        collectSalePayload,
        resetSaleForm,
        prefillSaleForm,
        applySaleCity,
        updateSaleReview,
    };
}
