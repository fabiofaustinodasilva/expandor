{{-- Sprint 8.1.1 — Design System Expandor (shared polish, no business rules) --}}
<style>
    /* ── Tokens (fallbacks; ThemeService / layout :root override) ── */
    :root {
        --ds-radius: 0.75rem;
        --ds-radius-lg: 1rem;
        --ds-shadow: 0 8px 28px rgba(0, 0, 0, 0.28);
        --ds-shadow-hover: 0 12px 32px rgba(0, 0, 0, 0.34);
        --ds-transition: 0.18s ease;
        --ds-focus: color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 60%, transparent);
        --ds-bp: 900px;
    }

    /* ── Accessibility: focus rings ── */
    :focus-visible {
        outline: 2px solid var(--ds-focus);
        outline-offset: 2px;
    }
    .btn:focus:not(:focus-visible),
    a:focus:not(:focus-visible),
    button:focus:not(:focus-visible),
    input:focus:not(:focus-visible),
    select:focus:not(:focus-visible),
    textarea:focus:not(:focus-visible) {
        outline: none;
    }

    /* ── Typography ── */
    .page-title, h1.page-title {
        margin: 0 0 0.35rem;
        font-size: clamp(1.35rem, 2.2vw, 1.65rem);
        font-weight: 700;
        letter-spacing: -0.01em;
        color: var(--text, #F3F5F9);
        line-height: 1.25;
    }
    .page-subtitle, .header-meta {
        color: var(--muted, #9AA3B5);
        font-size: 0.92rem;
        line-height: 1.45;
    }
    .section-title, h2.section-title {
        margin: 0 0 0.5rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--text, #F3F5F9);
    }
    .helper-text, .form-hint {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.82rem;
        color: var(--muted, #9AA3B5);
    }
    .content label, .form-group label {
        display: block;
        margin-bottom: 0.35rem;
        color: var(--muted, #9AA3B5);
        font-size: 0.88rem;
        font-weight: 560;
    }

    /* ── Buttons ── */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border: 1px solid transparent;
        border-radius: var(--ds-radius);
        padding: 0.65rem 1.05rem;
        cursor: pointer;
        font-weight: 650;
        font-size: 0.92rem;
        line-height: 1.2;
        text-decoration: none;
        transition: background var(--ds-transition), border-color var(--ds-transition),
            color var(--ds-transition), box-shadow var(--ds-transition), transform 0.12s ease;
        white-space: nowrap;
    }
    .btn:hover:not(:disabled):not(.is-loading) { transform: translateY(-1px); }
    .btn:active:not(:disabled) { transform: translateY(0); }
    .btn:disabled, .btn.is-loading, .btn[aria-disabled="true"] {
        opacity: 0.55;
        cursor: not-allowed;
        transform: none;
        pointer-events: none;
    }
    .btn-primary {
        background: var(--accent, var(--primary, #F59E0B));
        color: var(--button-text, #111);
        border-color: transparent;
    }
    .btn-primary:hover:not(:disabled) {
        box-shadow: 0 6px 18px color-mix(in srgb, var(--accent, var(--primary, #F59E0B)) 35%, transparent);
    }
    .btn-secondary {
        background: var(--bg-soft, #1E2330);
        color: var(--text, #F3F5F9);
        border-color: var(--border, #2A3142);
    }
    .btn-ghost {
        background: transparent;
        color: var(--text, #F3F5F9);
        border-color: var(--border, #2A3142);
    }
    .btn-ghost:hover:not(:disabled) {
        background: color-mix(in srgb, var(--bg-soft, #1E2330) 70%, transparent);
    }
    .btn-outline {
        background: transparent;
        color: var(--accent, var(--primary, #3B82F6));
        border-color: color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 55%, var(--border, #2A3142));
    }
    .btn-danger {
        background: var(--accent-2, var(--highlight, var(--danger, #EF4444)));
        color: #fff;
    }
    .btn-success {
        background: var(--success, #22C55E);
        color: #0B1220;
    }
    .btn.is-loading {
        position: relative;
        color: transparent !important;
    }
    .btn.is-loading::after {
        content: "";
        position: absolute;
        width: 1rem;
        height: 1rem;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: ux-spin 0.7s linear infinite;
        color: var(--button-text, #111);
        opacity: 0.9;
    }
    .btn-block, .btn-full { width: 100%; }
    .btn-ghost.is-loading::after,
    .btn-outline.is-loading::after,
    .btn-secondary.is-loading::after {
        color: var(--text, #F3F5F9);
    }

    /* ── Cards ── */
    .card {
        background: var(--bg-elevated, #171A22);
        border: 1px solid var(--border, #2A3142);
        border-radius: var(--ds-radius-lg);
        padding: 1.2rem;
        color: var(--text, #F3F5F9);
        box-shadow: var(--ds-shadow);
        transition: border-color var(--ds-transition), box-shadow var(--ds-transition), transform 0.15s ease;
    }
    .card:hover {
        border-color: color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 28%, var(--border, #2A3142));
    }
    a.card:hover, .card-link:hover {
        box-shadow: var(--ds-shadow-hover);
        transform: translateY(-1px);
    }

    /* ── Inputs ── */
    .form-group { margin-bottom: 1rem; }
    .form-control,
    .content input:not([type=checkbox]):not([type=radio]):not([type=file]),
    .content select,
    .content textarea,
    .form-group input:not([type=checkbox]):not([type=radio]):not([type=file]),
    .form-group select,
    .form-group textarea {
        width: 100%;
        background: var(--bg, #0F1117);
        border: 1px solid var(--border, #2A3142);
        color: var(--text, #F3F5F9);
        border-radius: var(--ds-radius);
        padding: 0.7rem 0.85rem;
        font-size: 0.95rem;
        transition: border-color var(--ds-transition), box-shadow var(--ds-transition);
    }
    .form-control::placeholder,
    .content input::placeholder,
    .content textarea::placeholder {
        color: color-mix(in srgb, var(--muted, #9AA3B5) 80%, transparent);
    }
    .form-control:focus,
    .content input:focus,
    .content select:focus,
    .content textarea:focus,
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 55%, var(--border));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 18%, transparent);
        outline: none;
    }
    .form-control.is-invalid,
    .input-error {
        border-color: color-mix(in srgb, var(--accent-2, #EF4444) 65%, var(--border));
    }

    /* ── Tables ── */
    .table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: var(--ds-radius);
    }
    .table { width: 100%; border-collapse: collapse; min-width: 480px; }
    .table th, .table td {
        text-align: left;
        padding: 0.8rem 0.6rem;
        border-bottom: 1px solid var(--border, #2A3142);
        vertical-align: top;
        font-size: 0.93rem;
    }
    .table th {
        color: var(--muted, #9AA3B5);
        font-size: 0.78rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* ── Badges ── */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        background: var(--bg-soft, #1E2330);
        font-size: 0.72rem;
        font-weight: 650;
        color: var(--text, #F3F5F9);
    }
    .badge-success { background: color-mix(in srgb, var(--success, #22C55E) 18%, transparent); color: var(--success, #22C55E); }
    .badge-warning { background: color-mix(in srgb, var(--warning, #F59E0B) 18%, transparent); color: var(--warning, #F59E0B); }
    .badge-danger { background: color-mix(in srgb, var(--accent-2, #EF4444) 18%, transparent); color: var(--accent-2, #EF4444); }
    .badge-primary { background: color-mix(in srgb, var(--accent, var(--primary, #3B82F6)) 20%, transparent); }

    /* ── Loading / skeleton ── */
    .ux-skeleton {
        display: block;
        background: linear-gradient(90deg, var(--bg-soft, #1E2330) 25%, var(--bg-elevated, #171A22) 50%, var(--bg-soft, #1E2330) 75%);
        background-size: 200% 100%;
        animation: ux-shimmer 1.2s ease-in-out infinite;
        border-radius: 0.5rem;
        min-height: 0.85rem;
    }
    .ux-skeleton--title { height: 1.5rem; width: 40%; margin-bottom: 0.75rem; }
    .ux-skeleton--line { height: 0.85rem; width: 100%; margin-bottom: 0.5rem; }
    .ux-skeleton--line:last-child { width: 70%; }
    .ux-skeleton--card { height: 7.5rem; width: 100%; border-radius: var(--ds-radius-lg); }
    @keyframes ux-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .ux-spinner {
        width: 1.35rem;
        height: 1.35rem;
        border: 2px solid var(--border, #2A3142);
        border-top-color: var(--accent, var(--primary, #3B82F6));
        border-radius: 50%;
        animation: ux-spin 0.7s linear infinite;
        display: inline-block;
        vertical-align: middle;
    }
    @keyframes ux-spin { to { transform: rotate(360deg); } }

    .ux-loading-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(15, 17, 23, 0.45);
        backdrop-filter: blur(2px);
        align-items: center;
        justify-content: center;
    }
    .ux-loading-overlay.is-active { display: flex; }
    .ux-loading-box {
        background: var(--bg-elevated, #171A22);
        border: 1px solid var(--border, #2A3142);
        border-radius: var(--ds-radius-lg);
        padding: 1.25rem 1.5rem;
        display: flex;
        gap: 0.75rem;
        align-items: center;
        color: var(--text, #F3F5F9);
        box-shadow: var(--ds-shadow);
    }

    /* ── Empty state ── */
    .ux-empty {
        text-align: center;
        padding: 2.5rem 1.25rem;
        color: var(--muted, #9AA3B5);
        border: 1px dashed var(--border, #2A3142);
        border-radius: var(--ds-radius-lg);
        background: color-mix(in srgb, var(--bg-elevated, #171A22) 80%, transparent);
    }
    .ux-empty__title {
        color: var(--text, #F3F5F9);
        font-weight: 700;
        font-size: 1.05rem;
        margin: 0 0 0.35rem;
    }
    .ux-empty__desc { margin: 0 0 1rem; font-size: 0.92rem; }
    .empty-friendly { color: var(--muted, #9AA3B5); }

    /* ── Toast ── */
    .ux-toast-host {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-width: min(22rem, calc(100vw - 2rem));
        pointer-events: none;
    }
    .ux-toast {
        pointer-events: auto;
        padding: 0.85rem 1rem;
        border-radius: var(--ds-radius);
        border: 1px solid var(--border, #2A3142);
        background: var(--bg-elevated, #171A22);
        color: var(--text, #F3F5F9);
        box-shadow: var(--ds-shadow);
        animation: ux-toast-in 0.25s ease;
        font-size: 0.92rem;
    }
    .ux-toast--success { border-color: color-mix(in srgb, var(--success, #22C55E) 55%, var(--border)); }
    .ux-toast--error { border-color: color-mix(in srgb, var(--accent-2, #EF4444) 55%, var(--border)); }
    @keyframes ux-toast-in {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Alerts ── */
    .alert {
        border-radius: var(--ds-radius);
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border, #2A3142);
        background: var(--bg-elevated, #171A22);
    }
    .alert-success {
        border-color: color-mix(in srgb, var(--success, #22C55E) 45%, var(--border));
        background: color-mix(in srgb, var(--success, #22C55E) 12%, var(--bg-elevated, #171A22));
    }
    .alert-error {
        border-color: color-mix(in srgb, var(--accent-2, #EF4444) 45%, var(--border));
        background: color-mix(in srgb, var(--accent-2, #EF4444) 12%, var(--bg-elevated, #171A22));
    }
    .alert-warning {
        border-color: color-mix(in srgb, var(--warning, #F59E0B) 45%, var(--border));
        background: color-mix(in srgb, var(--warning, #F59E0B) 12%, var(--bg-elevated, #171A22));
    }
    .alert-info {
        border-color: color-mix(in srgb, var(--accent, #3B82F6) 40%, var(--border));
        background: color-mix(in srgb, var(--accent, #3B82F6) 10%, var(--bg-elevated, #171A22));
    }

    /* ── Page header + breadcrumb (Sprint 8.1.4) ── */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.35rem;
        flex-wrap: wrap;
    }
    .page-header__main { min-width: 0; flex: 1; }
    .page-header__actions {
        display: flex;
        gap: 0.55rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .page-header .page-title { margin-bottom: 0.25rem; }
    .page-header .page-subtitle { margin: 0; max-width: 42rem; }
    .breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin: 0 0 0.55rem;
        font-size: 0.78rem;
        color: var(--muted, #9AA3B5);
    }
    .breadcrumb__sep { opacity: 0.55; user-select: none; }
    .breadcrumb__link {
        color: var(--muted, #9AA3B5);
        text-decoration: none;
        transition: color var(--ds-transition);
    }
    .breadcrumb__link:hover { color: var(--text, #F3F5F9); }
    .breadcrumb__current { color: var(--text, #F3F5F9); font-weight: 600; }

    /* ── Metric cards ── */
    .metric-card {
        padding: 1.15rem 1.2rem;
        min-height: 6.25rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .metric-card:hover { transform: none; }
    .metric-card__label {
        color: var(--muted, #9AA3B5);
        font-size: 0.78rem;
        font-weight: 650;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.45rem;
    }
    .metric-card__value {
        font-size: clamp(1.45rem, 2.4vw, 1.85rem);
        font-weight: 750;
        letter-spacing: -0.02em;
        line-height: 1.15;
        color: var(--text, #F3F5F9);
    }
    .metric-card__hint {
        margin-top: 0.4rem;
        font-size: 0.8rem;
        color: var(--muted, #9AA3B5);
    }
    .grid-metrics {
        display: grid;
        gap: 0.85rem;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        margin-bottom: 1.25rem;
    }
    .panel-section { margin-bottom: 1.35rem; }
    .panel-section__head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin: -0.15rem 0 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid var(--border, #2A3142);
    }
    .card-header h2, .card-header .section-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
    }
    .filter-bar {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: minmax(0, 2fr) repeat(3, minmax(0, 1fr)) auto;
        align-items: end;
        margin-bottom: 1rem;
        padding: 1rem;
    }
    .content input[type=checkbox],
    .content input[type=radio],
    .form-group input[type=checkbox],
    .form-group input[type=radio] {
        width: 1.05rem;
        height: 1.05rem;
        accent-color: var(--accent, var(--primary, #F59E0B));
        cursor: pointer;
    }
    .ux-modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: grid;
        place-items: center;
        padding: 1rem;
        animation: ux-toast-in 0.2s ease;
    }
    .ux-modal[hidden] { display: none !important; }
    .ux-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(8, 10, 16, 0.62);
        backdrop-filter: blur(3px);
    }
    .ux-modal__panel {
        position: relative;
        z-index: 1;
        width: min(480px, 100%);
        max-height: min(86vh, 720px);
        overflow: auto;
        padding: 1.35rem;
        box-shadow: var(--ds-shadow-hover);
    }
    .ux-empty__icon {
        width: 2.75rem;
        height: 2.75rem;
        margin: 0 auto 0.85rem;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: color-mix(in srgb, var(--bg-soft, #1E2330) 80%, transparent);
        color: var(--muted, #9AA3B5);
        font-size: 1.15rem;
    }
    .table tbody tr {
        transition: background var(--ds-transition);
    }
    .table tbody tr:hover td {
        background: color-mix(in srgb, var(--bg-soft, #1E2330) 55%, transparent);
    }

    /* ── Sidebar mobile (platform / app) ── */
    .shell-nav-toggle {
        display: none;
        background: transparent;
        border: 1px solid var(--border, #2A3142);
        color: var(--text, #F3F5F9);
        border-radius: var(--ds-radius);
        padding: 0.45rem 0.75rem;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.85rem;
    }
    .actions { display: flex; gap: 0.65rem; align-items: center; flex-wrap: wrap; }

    /* Scrollbar (webkit) */
    .content::-webkit-scrollbar,
    .table-wrap::-webkit-scrollbar,
    .sidebar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .content::-webkit-scrollbar-thumb,
    .table-wrap::-webkit-scrollbar-thumb,
    .sidebar::-webkit-scrollbar-thumb {
        background: var(--border, #2A3142);
        border-radius: 999px;
    }

        @media (max-width: 900px) {
            .ux-toast-host { top: auto; bottom: 4.5rem; right: 0.75rem; left: 0.75rem; max-width: none; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr !important; }
            .filter-bar { grid-template-columns: 1fr !important; }
            .page-header { flex-direction: column; align-items: stretch; }
            .page-header__actions { width: 100%; }
            .page-header__actions .btn { flex: 1 1 auto; }
        .shell-nav-toggle { display: inline-flex; align-items: center; }
        .shell .sidebar {
            max-height: 0;
            overflow: hidden;
            padding-top: 0;
            padding-bottom: 0;
            border-bottom: 0;
            transition: max-height 0.28s ease, padding 0.28s ease;
        }
        .shell.is-nav-open .sidebar {
            max-height: 70vh;
            overflow-y: auto;
            padding: 1rem;
            border-bottom: 1px solid var(--border, #2A3142);
        }
        .shell .sidebar .brand { display: none; }
        .shell.is-nav-open .sidebar .brand {
            display: flex;
            min-height: 2.75rem;
            padding: 0.35rem 0.65rem 0.85rem;
            margin-bottom: 0.25rem;
        }
        .content { padding: 1rem; }
        .header { padding: 0.85rem 1rem; }
        .btn { white-space: normal; }
    }

    @media (max-width: 480px) {
        .page-title { font-size: 1.25rem; }
        .card { padding: 1rem; }
        .actions { gap: 0.45rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
<div class="ux-toast-host" id="ux-toast-host" aria-live="polite"></div>
<div class="ux-loading-overlay" id="ux-loading-overlay" aria-hidden="true">
    <div class="ux-loading-box"><span class="ux-spinner" aria-hidden="true"></span><span>Carregando…</span></div>
</div>
<script>
(function () {
    window.ExpandorUX = window.ExpandorUX || {};
    window.ExpandorUX.toast = function (message, type) {
        var host = document.getElementById('ux-toast-host');
        if (!host || !message) return;
        var el = document.createElement('div');
        el.className = 'ux-toast ux-toast--' + (type === 'error' ? 'error' : 'success');
        el.setAttribute('role', 'status');
        el.textContent = message;
        host.appendChild(el);
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity .25s';
            setTimeout(function () { el.remove(); }, 280);
        }, 4200);
    };
    window.ExpandorUX.showLoading = function () {
        var o = document.getElementById('ux-loading-overlay');
        if (o) { o.classList.add('is-active'); o.setAttribute('aria-hidden', 'false'); }
    };
    window.ExpandorUX.hideLoading = function () {
        var o = document.getElementById('ux-loading-overlay');
        if (o) { o.classList.remove('is-active'); o.setAttribute('aria-hidden', 'true'); }
    };
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
            ExpandorUX.toast(@json(session('success')), 'success');
        @endif
        @if(session('error'))
            ExpandorUX.toast(@json(session('error')), 'error');
        @endif

        document.querySelectorAll('.shell-nav-toggle').forEach(function (btn) {
            // Sprint 8.2.6: client-mobile.js owns drawer + backdrop when present.
            if (document.querySelector('script[src*="client-mobile"]')) return;
            btn.addEventListener('click', function () {
                var shell = btn.closest('.shell');
                if (!shell) return;
                var open = shell.classList.toggle('is-nav-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        document.querySelectorAll('.content table.table, .op-page table.table').forEach(function (table) {
            if (table.parentElement && (
                table.parentElement.classList.contains('table-wrap')
                || table.parentElement.classList.contains('client-data-table')
                || table.parentElement.classList.contains('client-table-scroll')
            )) return;
            if (table.closest('.client-data-table')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-wrap';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    });
})();
</script>
