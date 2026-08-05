{{-- Premium conversion UI: carousel, how-it-works, before/after, social proof, video modal --}}
<style>
    .mkp-carousel { position: relative; overflow: hidden; border-radius: var(--mkp-radius); border: 1px solid var(--mkp-border); background: var(--mkp-surface); }
    .mkp-carousel-track { display: flex; transition: transform 0.45s ease; }
    .mkp-carousel-slide { min-width: 100%; padding: 1rem; }
    .mkp-carousel-slide img { width: 100%; border-radius: 0.75rem; aspect-ratio: 16/10; object-fit: cover; background: #0F172A; }
    .mkp-carousel-caption { margin-top: 0.75rem; text-align: center; color: var(--mkp-muted); font-weight: 600; }
    .mkp-carousel-nav { display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem; }
    .mkp-carousel-btn {
        width: 40px; height: 40px; border-radius: 999px; border: 1px solid var(--mkp-border);
        background: rgba(15,23,42,.7); color: var(--mkp-text); cursor: pointer;
    }
    .mkp-carousel-dots { display: flex; justify-content: center; gap: 0.4rem; margin-top: 0.85rem; }
    .mkp-carousel-dot { width: 8px; height: 8px; border-radius: 999px; background: #475569; border: 0; padding: 0; cursor: pointer; }
    .mkp-carousel-dot.is-active { background: var(--mkp-button); width: 22px; }

    .mkp-steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }
    .mkp-step {
        background: var(--mkp-surface);
        border: 1px solid var(--mkp-border);
        border-radius: var(--mkp-radius);
        padding: 1.15rem;
        transition: transform 0.2s, border-color 0.2s;
    }
    .mkp-step:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--mkp-primary) 45%, transparent); }
    .mkp-step-num {
        width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
        background: color-mix(in srgb, var(--mkp-primary) 25%, transparent); color: var(--mkp-primary); font-weight: 800; margin-bottom: 0.75rem;
    }
    .mkp-step h3 { margin: 0 0 0.35rem; font-size: 1rem; }
    .mkp-step p { margin: 0; color: var(--mkp-muted); font-size: 0.9rem; }

    .mkp-ba-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
    .mkp-ba-card {
        border-radius: var(--mkp-radius); padding: 1.5rem; border: 1px solid var(--mkp-border);
        background: var(--mkp-surface); transition: transform 0.2s;
    }
    .mkp-ba-card:hover { transform: translateY(-2px); }
    .mkp-ba-card h3 { margin-top: 0; }
    .mkp-ba-card ul { list-style: none; padding: 0; margin: 0; }
    .mkp-ba-card li { padding: 0.55rem 0; border-bottom: 1px solid var(--mkp-border); color: var(--mkp-muted); }
    .mkp-ba-card.before h3 { color: #F87171; }
    .mkp-ba-card.after h3 { color: #4ADE80; }

    .mkp-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-top: 1.75rem;
    }
    .mkp-metric {
        text-align: center; padding: 1.25rem; border-radius: var(--mkp-radius);
        border: 1px solid var(--mkp-border); background: var(--mkp-surface);
        transition: transform 0.2s;
    }
    .mkp-metric:hover { transform: translateY(-2px); }
    .mkp-metric strong { display: block; font-size: 1.75rem; font-weight: 800; color: var(--mkp-button); }
    .mkp-metric span { color: var(--mkp-muted); font-size: 0.88rem; }

    .mkp-logos { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; margin-top: 1.5rem; opacity: 0.85; }
    .mkp-logos img { height: 36px; width: auto; object-fit: contain; filter: grayscale(1) brightness(1.2); }

    .mkp-video-thumb {
        position: relative; border-radius: var(--mkp-radius); overflow: hidden; border: 1px solid var(--mkp-border);
        cursor: pointer; max-width: 860px; margin: 0 auto;
    }
    .mkp-video-thumb img { width: 100%; display: block; aspect-ratio: 16/9; object-fit: cover; }
    .mkp-play {
        position: absolute; inset: 0; display: grid; place-items: center;
        background: rgba(0,0,0,.35);
    }
    .mkp-play span {
        width: 72px; height: 72px; border-radius: 999px; background: var(--mkp-button); color: #111;
        display: grid; place-items: center; font-size: 1.5rem; box-shadow: var(--mkp-shadow);
    }

    .mkp-modal {
        position: fixed; inset: 0; z-index: 300; display: none; place-items: center;
        background: rgba(0,0,0,.72); padding: 1rem;
    }
    .mkp-modal.is-open { display: grid; }
    .mkp-modal-dialog {
        width: min(920px, 100%); background: #0B1220; border-radius: 1rem; overflow: hidden;
        border: 1px solid var(--mkp-border); box-shadow: var(--mkp-shadow);
    }
    .mkp-modal-dialog iframe, .mkp-modal-dialog video { width: 100%; aspect-ratio: 16/9; border: 0; display: none; background: #000; }
    .mkp-modal-dialog iframe.is-active, .mkp-modal-dialog video.is-active { display: block; }
    .mkp-modal-close {
        position: absolute; top: 1rem; right: 1rem; border: 0; background: rgba(15,23,42,.9);
        color: #fff; width: 40px; height: 40px; border-radius: 999px; cursor: pointer; z-index: 2;
    }

    @media (max-width: 768px) {
        .mkp-ba-grid { grid-template-columns: 1fr; }
        .mkp-steps { grid-template-columns: 1fr 1fr; }
        .mkp-metrics { grid-template-columns: 1fr 1fr; }
        .mkp-carousel-slide { padding: 0.65rem; }
    }
    @media (max-width: 480px) {
        .mkp-steps, .mkp-metrics { grid-template-columns: 1fr; }
        .mkp-play span { width: 56px; height: 56px; font-size: 1.2rem; }
    }
</style>
