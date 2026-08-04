{{-- Sprint 8.0 RC — polish CSS compartilhado (sem alterar regras de negócio) --}}
<style>
    /* Loading / skeleton */
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
    .ux-skeleton--card { height: 7.5rem; width: 100%; border-radius: 1rem; }
    @keyframes ux-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .ux-spinner {
        width: 1.35rem;
        height: 1.35rem;
        border: 2px solid var(--border, #2A3142);
        border-top-color: var(--accent, #3B82F6);
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
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        display: flex;
        gap: 0.75rem;
        align-items: center;
        color: var(--text, #F3F5F9);
        box-shadow: 0 16px 40px rgba(0,0,0,0.35);
    }

    /* Empty state */
    .ux-empty {
        text-align: center;
        padding: 2.5rem 1.25rem;
        color: var(--muted, #9AA3B5);
        border: 1px dashed var(--border, #2A3142);
        border-radius: 1rem;
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

    /* Toast */
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
        border-radius: 0.75rem;
        border: 1px solid var(--border, #2A3142);
        background: var(--bg-elevated, #171A22);
        color: var(--text, #F3F5F9);
        box-shadow: 0 12px 32px rgba(0,0,0,0.35);
        animation: ux-toast-in 0.25s ease;
        font-size: 0.92rem;
    }
    .ux-toast--success { border-color: color-mix(in srgb, var(--success, #22C55E) 55%, var(--border)); }
    .ux-toast--error { border-color: color-mix(in srgb, var(--accent-2, #EF4444) 55%, var(--border)); }
    @keyframes ux-toast-in {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Alert polish */
    .alert {
        border-radius: 0.85rem;
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

    /* Form consistency */
    .form-group { margin-bottom: 1rem; }
    .form-control, .content input:not([type=checkbox]):not([type=radio]):not([type=file]),
    .content select, .content textarea {
        width: 100%;
        background: var(--bg, #0F1117);
        border: 1px solid var(--border, #2A3142);
        color: var(--text, #F3F5F9);
        border-radius: 0.65rem;
        padding: 0.7rem 0.85rem;
    }
    .content label { display: block; margin-bottom: 0.35rem; color: var(--muted, #9AA3B5); font-size: 0.9rem; }

    @media (max-width: 900px) {
        .ux-toast-host { top: auto; bottom: 4.5rem; right: 0.75rem; left: 0.75rem; max-width: none; }
        .grid-2 { grid-template-columns: 1fr !important; }
    }
</style>
<div class="ux-toast-host" id="ux-toast-host" aria-live="polite"></div>
<div class="ux-loading-overlay" id="ux-loading-overlay" aria-hidden="true">
    <div class="ux-loading-box"><span class="ux-spinner"></span><span>Carregando…</span></div>
</div>
<script>
(function () {
    window.ExpandorUX = window.ExpandorUX || {};
    window.ExpandorUX.toast = function (message, type) {
        var host = document.getElementById('ux-toast-host');
        if (!host || !message) return;
        var el = document.createElement('div');
        el.className = 'ux-toast ux-toast--' + (type === 'error' ? 'error' : 'success');
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
    });
})();
</script>
