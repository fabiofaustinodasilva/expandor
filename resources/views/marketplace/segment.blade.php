<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $pageTitle = $segmentPage->title.' — '.($settings->title ?: 'Expandor');
        $pageDescription = $segmentPage->description ?: ($settings->seo_description ?: 'CRM de campo e gestão comercial.');
        $primary = $settings->primary_color ?: '#3B82F6';
        $secondary = $settings->secondary_color ?: '#0F172A';
        $background = $settings->background_color ?: '#0B1220';
        $button = $settings->button_color ?: '#F59E0B';
        $heroImage = $segmentPage->mediaUrl($segmentPage->hero_image) ?: $settings->mediaUrl($settings->hero_image);
        $heroVideo = $segmentPage->hero_video ?: $settings->hero_video;
        $featureItems = $segmentPage->features ?: [];
        $ctaUrl = $segmentPage->cta_url ?: '#demo';
        $ctaText = $segmentPage->cta_text ?: 'Solicitar demonstração';
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ Str::limit(strip_tags($pageDescription), 160) }}">

    @if($settings->mediaUrl($settings->favicon))
        <link rel="icon" href="{{ $settings->mediaUrl($settings->favicon) }}">
    @endif

    <style>
        :root {
            --mkp-primary: {{ $primary }};
            --mkp-secondary: {{ $secondary }};
            --mkp-bg: {{ $background }};
            --mkp-button: {{ $button }};
            --mkp-text: #F1F5F9;
            --mkp-muted: #94A3B8;
            --mkp-border: rgba(148, 163, 184, 0.18);
            --mkp-surface: rgba(15, 23, 42, 0.72);
            --mkp-radius: 1rem;
            --mkp-shadow: 0 24px 48px rgba(0, 0, 0, 0.35);
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--mkp-bg);
            color: var(--mkp-text);
            line-height: 1.6;
        }
        a { color: inherit; text-decoration: none; }
        img, video { max-width: 100%; height: auto; display: block; }

        .mkp-container { width: min(1120px, calc(100% - 2rem)); margin-inline: auto; }

        .mkp-header {
            position: sticky; top: 0; z-index: 100;
            backdrop-filter: blur(14px);
            background: rgba(11, 18, 32, 0.82);
            border-bottom: 1px solid var(--mkp-border);
        }
        .mkp-header-inner {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; padding: 0.85rem 0; flex-wrap: wrap;
        }
        .mkp-logo { display: flex; align-items: center; gap: 0.6rem; font-weight: 800; }
        .mkp-logo img { height: 36px; width: auto; }
        .mkp-header-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }

        .mkp-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0.65rem 1.15rem; border-radius: 0.65rem;
            font-weight: 600; font-size: 0.9rem; border: 0; cursor: pointer;
            transition: transform 0.15s;
        }
        .mkp-btn:hover { transform: translateY(-1px); }
        .mkp-btn-primary { background: var(--mkp-button); color: #111; }
        .mkp-btn-ghost { background: transparent; color: var(--mkp-text); border: 1px solid var(--mkp-border); }
        .mkp-btn-outline { background: transparent; color: var(--mkp-primary); border: 1px solid color-mix(in srgb, var(--mkp-primary) 50%, transparent); }

        .mkp-section { padding: 4.5rem 0; }
        .mkp-section-alt { background: linear-gradient(180deg, transparent, rgba(15, 23, 42, 0.45) 50%, transparent); }
        .mkp-eyebrow { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--mkp-primary); font-weight: 700; }
        .mkp-title { font-size: clamp(1.75rem, 4vw, 2.75rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1.15; margin: 0 0 0.75rem; }
        .mkp-subtitle { color: var(--mkp-muted); font-size: 1.05rem; margin: 0; }

        .mkp-hero { padding: 5rem 0 4rem; }
        .mkp-hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; }
        .mkp-hero-desc { color: var(--mkp-muted); font-size: 1.1rem; margin: 0 0 1.75rem; max-width: 32rem; }
        .mkp-hero-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; }
        .mkp-hero-media {
            border-radius: calc(var(--mkp-radius) + 0.25rem); overflow: hidden;
            border: 1px solid var(--mkp-border); background: var(--mkp-surface);
            box-shadow: var(--mkp-shadow); aspect-ratio: 16 / 10;
        }
        .mkp-hero-media img, .mkp-hero-media video { width: 100%; height: 100%; object-fit: cover; }

        .mkp-section-head { text-align: center; max-width: 640px; margin: 0 auto 2.5rem; }
        .mkp-features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; }
        .mkp-feature-card {
            background: var(--mkp-surface); border: 1px solid var(--mkp-border);
            border-radius: var(--mkp-radius); padding: 1.5rem;
        }
        .mkp-feature-card h3 { margin: 0 0 0.5rem; font-size: 1.1rem; }
        .mkp-feature-card p { margin: 0; color: var(--mkp-muted); font-size: 0.92rem; }

        .mkp-footer { border-top: 1px solid var(--mkp-border); padding: 2.5rem 0; margin-top: 2rem; }
        .mkp-footer-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; }
        .mkp-footer-copy { color: var(--mkp-muted); font-size: 0.85rem; }

        .mkp-social { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .mkp-social a {
            color: var(--mkp-muted); font-size: 0.88rem; padding: 0.35rem 0.65rem;
            border: 1px solid var(--mkp-border); border-radius: 0.5rem;
        }

        .mkp-whatsapp {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 200;
            width: 56px; height: 56px; border-radius: 50%; background: #25D366; color: #fff;
            display: grid; place-items: center;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
        }

        .mkp-fade { opacity: 0; transform: translateY(24px); transition: opacity 0.7s ease, transform 0.7s ease; }
        .mkp-fade.is-visible { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .mkp-hero-grid { grid-template-columns: 1fr; }
            .mkp-section { padding: 3rem 0; }
        }
    </style>
</head>
<body>

<header class="mkp-header">
    <div class="mkp-container mkp-header-inner">
        <a href="{{ route('marketplace.home') }}" class="mkp-logo">
            @if($settings->mediaUrl($settings->logo))
                <img src="{{ $settings->mediaUrl($settings->logo) }}" alt="{{ $settings->title ?: 'Expandor' }}">
            @else
                Expandor
            @endif
        </a>

        <div class="mkp-header-actions">
            <a class="mkp-btn mkp-btn-ghost" href="{{ route('marketplace.home') }}">← Início</a>
            <a href="#demo" class="mkp-btn mkp-btn-outline">{{ $ctaText }}</a>
            <a class="mkp-btn mkp-btn-primary" href="{{ route('signup.create') }}" data-mkp-event="marketplace.signup_started">Começar teste grátis</a>
        </div>
    </div>
</header>

<main>
    <section class="mkp-section mkp-hero mkp-fade" data-mkp-view="marketplace.hero_view">
        <div class="mkp-container">
            <div class="mkp-hero-grid">
                <div class="mkp-hero-copy">
                    @if($segmentPage->subtitle)
                        <span class="mkp-eyebrow">{{ $segmentPage->subtitle }}</span>
                    @endif
                    <h1 class="mkp-title">{{ $segmentPage->title }}</h1>
                    @if($segmentPage->description)
                        <p class="mkp-hero-desc">{{ $segmentPage->description }}</p>
                    @endif
                    <div class="mkp-hero-actions">
                        <a class="mkp-btn mkp-btn-primary" href="{{ $ctaUrl }}" data-mkp-event="marketplace.signup_started">{{ $ctaText }}</a>
                        <a class="mkp-btn mkp-btn-outline" href="{{ route('marketplace.plans') }}">Ver planos</a>
                    </div>
                </div>
                @if($heroVideo || $heroImage)
                    <div class="mkp-hero-media">
                        @if($heroVideo)
                            <video src="{{ $heroVideo }}" autoplay muted loop playsinline loading="lazy"></video>
                        @else
                            <img src="{{ $heroImage }}" alt="" loading="lazy">
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if(!empty($featureItems))
        <section class="mkp-section mkp-section-alt mkp-fade" data-mkp-view="marketplace.feature_view">
            <div class="mkp-container">
                <div class="mkp-section-head">
                    <h2 class="mkp-title">Recursos para {{ $segmentPage->title }}</h2>
                </div>
                <div class="mkp-features-grid">
                    @foreach($featureItems as $item)
                        <article class="mkp-feature-card">
                            <h3>{{ $item['title'] ?? '' }}</h3>
                            <p>{{ $item['description'] ?? '' }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('marketplace.partials.growth-blocks')
</main>

<footer class="mkp-footer">
    <div class="mkp-container mkp-footer-inner">
        <div class="mkp-footer-copy">
            &copy; {{ date('Y') }} {{ $settings->title ?: 'Expandor' }}.
            <a href="{{ route('marketplace.home') }}" style="color:var(--mkp-primary);">Voltar ao início</a>
        </div>
        <x-marketplace-social-links :settings="$settings" />
    </div>
</footer>

<x-marketplace-whats-app-button :settings="$settings" :context="$whatsappContext ?? null" />

<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const eventsUrl = @json(route('marketplace.events.store'));
    let lastEventAt = 0;
    const trackedViews = {};

    function trackEvent(event, metadata) {
        const now = Date.now();
        if (now - lastEventAt < 400) return;
        lastEventAt = now;
        fetch(eventsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ event, metadata: metadata || {} }),
            keepalive: true,
        }).catch(function () {});
    }

    document.querySelectorAll('[data-mkp-event]').forEach(function (el) {
        el.addEventListener('click', function () {
            trackEvent(el.getAttribute('data-mkp-event'), {});
        });
    });

    var fadeEls = document.querySelectorAll('.mkp-fade');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    var viewEvent = entry.target.getAttribute('data-mkp-view');
                    if (viewEvent && !trackedViews[viewEvent]) {
                        trackedViews[viewEvent] = true;
                        trackEvent(viewEvent, { segment: @json($segmentPage->slug) });
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(function (el) { observer.observe(el); });
    } else {
        fadeEls.forEach(function (el) { el.classList.add('is-visible'); });
    }
})();
</script>
</body>
</html>
