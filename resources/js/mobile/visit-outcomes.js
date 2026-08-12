/**
 * Resultados de visita — espelha MapController::$outcomeStatuses e MapMarkerColor (PHP).
 * Valores internos = VisitStatus enum. Não alterar.
 */

export const MAP_MARKER_COLORS = {
    GREEN: '#22c55e',
    BLUE: '#3b82f6',
    ORANGE: '#f97316',
    YELLOW: '#eab308',
    RED: '#ef4444',
    GRAY: '#9ca3af',
    SLATE: '#64748b',
};

/** Ordem operacional de campo (mesma matriz do mapa web). */
export const VISIT_OUTCOMES = [
    {
        value: 'interested',
        label: 'Interessado',
        color: MAP_MARKER_COLORS.BLUE,
        mark: '',
        icon: 'user-check',
    },
    {
        value: 'return_later',
        label: 'Retornar depois',
        color: MAP_MARKER_COLORS.ORANGE,
        mark: 'R',
        icon: 'calendar',
    },
    {
        value: 'installation_requested',
        label: 'Venda realizada',
        color: MAP_MARKER_COLORS.GREEN,
        mark: '',
        icon: 'badge-check',
    },
    {
        value: 'not_home',
        label: 'Não encontrado',
        color: MAP_MARKER_COLORS.GRAY,
        mark: '',
        icon: 'home',
    },
    {
        value: 'no_interest',
        label: 'Sem interesse',
        color: MAP_MARKER_COLORS.SLATE,
        mark: '×',
        icon: 'ban',
    },
];

const VISIT_TO_PROPERTY = {
    interested: 'interested',
    installation_requested: 'installation_requested',
    return_later: 'return_later',
    no_interest: 'no_interest',
    not_home: null,
    wrong_address: null,
};

export function propertyStatusForVisit(visitStatus) {
    return VISIT_TO_PROPERTY[String(visitStatus)] ?? null;
}

/** MapMarkerColor::forStatus (PropertyStatus) */
export function markerColorForPropertyStatus(status) {
    const map = {
        customer: MAP_MARKER_COLORS.GREEN,
        installation_requested: MAP_MARKER_COLORS.GREEN,
        interested: MAP_MARKER_COLORS.BLUE,
        return_later: MAP_MARKER_COLORS.ORANGE,
        no_interest: MAP_MARKER_COLORS.SLATE,
        new: MAP_MARKER_COLORS.RED,
    };

    return map[String(status)] || MAP_MARKER_COLORS.GRAY;
}

/** MapMarkerColor::markForStatus */
export function markerMarkForPropertyStatus(status) {
    if (status === 'return_later') {
        return 'R';
    }
    if (status === 'no_interest') {
        return '×';
    }

    return '';
}

export function markerStyleFromItem(item) {
    const color = item?.color || markerColorForPropertyStatus(item?.status);
    const mark = markerMarkForPropertyStatus(item?.status);

    return { color, mark };
}

export function visitOutcomeByValue(value) {
    return VISIT_OUTCOMES.find((row) => row.value === value) || null;
}

if (typeof window !== 'undefined') {
    window.ExpandorVisitOutcomes = {
        VISIT_OUTCOMES,
        MAP_MARKER_COLORS,
        propertyStatusForVisit,
        markerColorForPropertyStatus,
        markerMarkForPropertyStatus,
        markerStyleFromItem,
        visitOutcomeByValue,
    };
}
