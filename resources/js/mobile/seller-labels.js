/**
 * Labels PT-BR centralizados — EXP Vendedor (somente apresentação).
 */

const COMMISSION_STATUS = {
    pending: 'Pendente',
    approved: 'Aprovada',
    paid: 'Pago',
};

const PROPERTY_STATUS = {
    new: 'Novo',
    interested: 'Interessado',
    installation_requested: 'Venda realizada',
    return_later: 'Retorno marcado',
    no_interest: 'Sem interesse',
    customer: 'Cliente',
};

export function visitStatusLabel(value) {
    const row = {
        interested: 'Interessado',
        installation_requested: 'Venda realizada',
        return_later: 'Retornar depois',
        no_interest: 'Sem interesse',
        not_home: 'Não encontrado',
        wrong_address: 'Endereço incorreto',
    }[String(value || '').toLowerCase()];

    return row || String(value || '—');
}

export function commissionStatusLabel(value) {
    if (! value) {
        return '—';
    }

    return COMMISSION_STATUS[String(value).toLowerCase()] || String(value);
}

export function propertyStatusLabel(value) {
    if (! value) {
        return '—';
    }

    return PROPERTY_STATUS[String(value).toLowerCase()] || String(value);
}

export function formatCurrency(value) {
    const n = Number(value);
    if (Number.isNaN(n)) {
        return '—';
    }

    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

export function appVersionLabel() {
    return String(window.EXPANDOR_APP_VERSION || '—');
}

export function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

if (typeof window !== 'undefined') {
    window.ExpandorSellerLabels = {
        commissionStatusLabel,
        propertyStatusLabel,
        visitStatusLabel,
        formatCurrency,
        appVersionLabel,
        escapeHtml,
    };
}
