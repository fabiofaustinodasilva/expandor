<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $brandName = $brand ?? 'Expandor';
        $pageTitle = $settings->seo_title ?: ($settings->title ?: $brandName.' — CRM inteligente');
        $pageDescription = $settings->seo_description ?: ($settings->description ?: 'CRM de campo e gestão comercial.');
        $pageKeywords = $settings->seo_keywords ?: 'crm, vendas, saas, expandor';
        $ogImage = $settings->mediaUrl($settings->hero_image) ?: $settings->mediaUrl($settings->logo) ?: asset($heroFallbackImage ?? '/images/marketplace/hero-saas.svg');
        $primary = $settings->primary_color ?: '#3B82F6';
        $secondary = $settings->secondary_color ?: '#0F172A';
        $background = $settings->background_color ?: '#0B1220';
        $button = $settings->button_color ?: '#F59E0B';
        $navItems = $nav ?? [];
        $navActionItems = $navActions ?? [];
        $footerData = $footer ?? [];
        $heroSecondaryCta = $heroSecondary ?? ['text' => 'Solicitar demonstração', 'url' => '#demo'];
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="keywords" content="{{ $pageKeywords }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

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
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }

        img, video { max-width: 100%; height: auto; display: block; }

        .mkp-container {
            width: min(1120px, calc(100% - 2rem));
            margin-inline: auto;
        }

        /* Header */
        .mkp-header {
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(14px);
            background: rgba(11, 18, 32, 0.82);
            border-bottom: 1px solid var(--mkp-border);
        }

        .mkp-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 0;
            flex-wrap: wrap;
        }

        .mkp-logo {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.02em;
        }

        .mkp-logo img { height: 36px; width: auto; object-fit: contain; }

        .mkp-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 1rem;
            align-items: center;
        }

        .mkp-nav a {
            color: var(--mkp-muted);
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .mkp-nav a:hover { color: var(--mkp-text); }

        .mkp-header-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .mkp-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.65rem 1.15rem;
            border-radius: 0.65rem;
            font-weight: 600;
            font-size: 0.9rem;
            border: 0;
            cursor: pointer;
            transition: transform 0.15s, opacity 0.15s, box-shadow 0.15s;
        }

        .mkp-btn:hover { transform: translateY(-1px); }

        .mkp-btn-primary {
            background: var(--mkp-button);
            color: #111;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.25);
        }

        .mkp-btn-ghost {
            background: transparent;
            color: var(--mkp-text);
            border: 1px solid var(--mkp-border);
        }

        .mkp-btn-outline {
            background: transparent;
            color: var(--mkp-primary);
            border: 1px solid color-mix(in srgb, var(--mkp-primary) 50%, transparent);
        }

        /* Preview banner */
        .mkp-preview-banner {
            background: linear-gradient(90deg, color-mix(in srgb, var(--mkp-button) 25%, transparent), color-mix(in srgb, var(--mkp-primary) 20%, transparent));
            border-bottom: 1px solid var(--mkp-border);
            text-align: center;
            padding: 0.55rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--mkp-text);
        }

        /* Sections */
        .mkp-section {
            padding: 4.5rem 0;
            position: relative;
        }

        .mkp-section-alt {
            background: linear-gradient(180deg, transparent, rgba(15, 23, 42, 0.45) 50%, transparent);
        }

        .mkp-section-head {
            text-align: center;
            max-width: 640px;
            margin: 0 auto 2.5rem;
        }

        .mkp-eyebrow {
            display: inline-block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--mkp-primary);
            margin-bottom: 0.65rem;
            font-weight: 700;
        }

        .mkp-title {
            font-size: clamp(1.75rem, 4vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            margin: 0 0 0.75rem;
        }

        .mkp-subtitle {
            color: var(--mkp-muted);
            font-size: 1.05rem;
            margin: 0;
        }

        .mkp-fade {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }

        .mkp-fade.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hero */
        .mkp-hero {
            padding: 5rem 0 4rem;
            overflow: hidden;
        }

        .mkp-hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .mkp-hero-copy .mkp-title {
            font-size: clamp(2rem, 5vw, 3.25rem);
        }

        .mkp-hero-desc {
            color: var(--mkp-muted);
            font-size: 1.1rem;
            margin: 0 0 1.75rem;
            max-width: 32rem;
        }

        .mkp-hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .mkp-hero-media {
            position: relative;
            border-radius: calc(var(--mkp-radius) + 0.25rem);
            overflow: hidden;
            border: 1px solid var(--mkp-border);
            background: var(--mkp-surface);
            box-shadow: var(--mkp-shadow);
            aspect-ratio: 16 / 10;
        }

        .mkp-hero-media img,
        .mkp-hero-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mkp-hero-glow {
            position: absolute;
            inset: -30% -20% auto auto;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, color-mix(in srgb, var(--mkp-primary) 30%, transparent), transparent 70%);
            pointer-events: none;
            z-index: -1;
        }

        /* Feature cards */
        .mkp-features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
        }

        .mkp-feature-card {
            background: var(--mkp-surface);
            border: 1px solid var(--mkp-border);
            border-radius: var(--mkp-radius);
            padding: 1.5rem;
            transition: border-color 0.2s, transform 0.2s;
        }

        .mkp-feature-card:hover {
            border-color: color-mix(in srgb, var(--mkp-primary) 40%, transparent);
            transform: translateY(-3px);
        }

        .mkp-feature-card h3 {
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }

        .mkp-feature-card p {
            margin: 0;
            color: var(--mkp-muted);
            font-size: 0.92rem;
        }

        /* Video */
        .mkp-video-wrap {
            position: relative;
            border-radius: var(--mkp-radius);
            overflow: hidden;
            border: 1px solid var(--mkp-border);
            background: #000;
            aspect-ratio: 16 / 9;
        }

        .mkp-video-wrap iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }

        .mkp-videos-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        .mkp-video-card h4 {
            margin: 0.75rem 0 0;
            font-size: 1rem;
        }

        /* Gallery */
        .mkp-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .mkp-gallery-item {
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid var(--mkp-border);
            aspect-ratio: 4 / 3;
            background: var(--mkp-surface);
        }

        .mkp-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }

        .mkp-gallery-item:hover img { transform: scale(1.04); }

        /* Testimonials */
        .mkp-testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .mkp-testimonial {
            background: var(--mkp-surface);
            border: 1px solid var(--mkp-border);
            border-radius: var(--mkp-radius);
            padding: 1.5rem;
        }

        .mkp-testimonial-header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1rem;
        }

        .mkp-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--mkp-border);
            flex-shrink: 0;
        }

        .mkp-avatar-placeholder {
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--mkp-primary);
        }

        .mkp-stars {
            color: var(--mkp-button);
            font-size: 0.85rem;
            letter-spacing: 0.05em;
        }

        .mkp-testimonial-text {
            color: var(--mkp-muted);
            font-style: italic;
            margin: 0;
        }

        /* Plans */
        .mkp-plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            align-items: stretch;
        }

        .mkp-plan {
            background: var(--mkp-surface);
            border: 1px solid var(--mkp-border);
            border-radius: var(--mkp-radius);
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: border-color 0.2s, transform 0.2s;
        }

        .mkp-plan-featured {
            border-color: color-mix(in srgb, var(--mkp-button) 55%, transparent);
            box-shadow: 0 12px 40px rgba(245, 158, 11, 0.12);
        }

        .mkp-badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--mkp-button) 20%, transparent);
            color: var(--mkp-button);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.75rem;
        }

        .mkp-plan-price {
            font-size: 2rem;
            font-weight: 800;
            margin: 0.5rem 0;
            letter-spacing: -0.02em;
        }

        .mkp-plan-price small {
            font-size: 0.85rem;
            color: var(--mkp-muted);
            font-weight: 500;
        }

        .mkp-plan-desc {
            color: var(--mkp-muted);
            font-size: 0.92rem;
            flex: 1;
            margin-bottom: 1rem;
        }

        .mkp-plan-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1.25rem;
            font-size: 0.88rem;
        }

        .mkp-plan-features li {
            padding: 0.3rem 0;
            color: var(--mkp-muted);
        }

        .mkp-plan-features li::before {
            content: "✓ ";
            color: var(--mkp-primary);
            font-weight: 700;
        }

        /* FAQ */
        .mkp-faq-list {
            max-width: 720px;
            margin: 0 auto;
        }

        .mkp-faq-item {
            border: 1px solid var(--mkp-border);
            border-radius: 0.75rem;
            margin-bottom: 0.65rem;
            overflow: hidden;
            background: var(--mkp-surface);
        }

        .mkp-faq-q {
            width: 100%;
            text-align: left;
            background: transparent;
            border: 0;
            color: var(--mkp-text);
            font-weight: 600;
            padding: 1rem 1.25rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            font-size: 0.95rem;
        }

        .mkp-faq-q::after {
            content: "+";
            font-size: 1.25rem;
            color: var(--mkp-primary);
            transition: transform 0.2s;
        }

        .mkp-faq-item.is-open .mkp-faq-q::after { transform: rotate(45deg); }

        .mkp-faq-a {
            display: none;
            padding: 0 1.25rem 1rem;
            color: var(--mkp-muted);
            font-size: 0.92rem;
        }

        .mkp-faq-item.is-open .mkp-faq-a { display: block; }

        /* CTA */
        .mkp-cta {
            text-align: center;
            padding: 4rem 2rem;
            border-radius: calc(var(--mkp-radius) + 0.5rem);
            background: linear-gradient(135deg, color-mix(in srgb, var(--mkp-primary) 18%, transparent), color-mix(in srgb, var(--mkp-secondary) 60%, transparent));
            border: 1px solid var(--mkp-border);
        }

        /* About */
        .mkp-about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            align-items: center;
        }

        .mkp-about-text p {
            color: var(--mkp-muted);
            margin: 0;
        }

        .mkp-about-image {
            border-radius: var(--mkp-radius);
            overflow: hidden;
            border: 1px solid var(--mkp-border);
        }

        /* Footer */
        .mkp-footer {
            border-top: 1px solid var(--mkp-border);
            padding: 2.5rem 0;
            margin-top: 2rem;
        }

        .mkp-footer-inner {
            display: grid;
            grid-template-columns: 1.2fr 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }

        .mkp-footer-brand {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .mkp-footer-brand .mkp-logo img { height: 32px; }

        .mkp-footer-copy {
            color: var(--mkp-muted);
            font-size: 0.85rem;
        }

        .mkp-footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem 1.15rem;
        }

        .mkp-footer-links a {
            color: var(--mkp-muted);
            font-size: 0.88rem;
        }

        .mkp-footer-links a:hover { color: var(--mkp-text); }

        @media (max-width: 768px) {
            .mkp-footer-inner { grid-template-columns: 1fr; }
        }

        /* WhatsApp floating */
        .mkp-whatsapp {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 200;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #25D366;
            color: #fff;
            display: grid;
            place-items: center;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .mkp-whatsapp:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 32px rgba(37, 211, 102, 0.5);
        }

        /* Social links */
        .mkp-social {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .mkp-social a {
            color: var(--mkp-muted);
            font-size: 0.88rem;
            padding: 0.35rem 0.65rem;
            border: 1px solid var(--mkp-border);
            border-radius: 0.5rem;
            transition: color 0.2s, border-color 0.2s;
        }

        .mkp-social a:hover {
            color: var(--mkp-text);
            border-color: var(--mkp-primary);
        }

        .mkp-menu-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--mkp-border);
            color: var(--mkp-text);
            border-radius: 0.55rem;
            padding: 0.45rem 0.65rem;
            cursor: pointer;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .mkp-hero-grid,
            .mkp-about-grid { grid-template-columns: 1fr; }
            .mkp-menu-toggle { display: inline-flex; align-items: center; }
            .mkp-nav {
                display: none;
                width: 100%;
                flex-direction: column;
                gap: 0.35rem;
                padding: 0.75rem 0 0;
            }
            .mkp-nav.is-open { display: flex; }
            .mkp-header-inner { flex-wrap: wrap; }
            .mkp-header-actions { width: 100%; justify-content: stretch; }
            .mkp-header-actions .mkp-btn { flex: 1; text-align: center; font-size: 0.82rem; padding: 0.55rem 0.65rem; }
            .mkp-section { padding: 3rem 0; }
        }
    </style>
</head>
<body>

@if(!empty($preview))
    <div class="mkp-preview-banner">
        Modo preview — alterações já salvas no CMS
    </div>
@endif

<header class="mkp-header">
    <div class="mkp-container mkp-header-inner">
        <a href="#inicio" class="mkp-logo">
            @if($settings->mediaUrl($settings->logo))
                <img src="{{ $settings->mediaUrl($settings->logo) }}" alt="{{ $brandName }}" loading="lazy">
            @else
                {{ $brandName }}
            @endif
        </a>

        <button type="button" class="mkp-menu-toggle" id="mkp-menu-toggle" aria-expanded="false" aria-controls="mkp-nav">Menu</button>

        <nav class="mkp-nav" id="mkp-nav" aria-label="Navegação principal">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="mkp-header-actions">
            @foreach($navActionItems as $action)
                @php
                    $actionHref = !empty($action['route'])
                        ? route($action['href'])
                        : ($action['href'] ?? '#');
                    $actionClass = match ($action['style'] ?? 'ghost') {
                        'primary' => 'mkp-btn mkp-btn-primary',
                        'outline' => 'mkp-btn mkp-btn-outline',
                        default => 'mkp-btn mkp-btn-ghost',
                    };
                @endphp
                <a class="{{ $actionClass }}"
                   href="{{ $actionHref }}"
                   @if(!empty($action['event'])) data-mkp-event="{{ $action['event'] }}" @endif>
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>

<main>
    @foreach($sections as $section)
        @if(!$section->active)
            @continue
        @endif

        @switch($section->type)
            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Hero)
                @php
                    $heroTitle = $section->title ?: $settings->title;
                    $heroSubtitle = $section->subtitle ?: $settings->subtitle;
                    $heroDesc = $section->description ?: $settings->description;
                    $heroImage = $section->imageUrl() ?: $settings->mediaUrl($settings->hero_image) ?: asset($heroFallbackImage ?? '/images/marketplace/hero-saas.svg');
                    $heroVideo = $section->videoUrl() ?: ($settings->hero_video ?: null);
                @endphp
                <section id="inicio" class="mkp-section mkp-hero mkp-fade" data-mkp-view="marketplace.hero_view">
                    <div class="mkp-container">
                        <div class="mkp-hero-grid">
                            <div class="mkp-hero-copy">
                                <div class="mkp-hero-glow" aria-hidden="true"></div>
                                @if($heroTitle)
                                    <h1 class="mkp-title">{!! nl2br(e($heroTitle)) !!}</h1>
                                @endif
                                @if($heroSubtitle)
                                    <p class="mkp-subtitle" style="margin-bottom:1rem;">{{ $heroSubtitle }}</p>
                                @endif
                                @if($heroDesc)
                                    <p class="mkp-hero-desc">{{ $heroDesc }}</p>
                                @endif
                                <div class="mkp-hero-actions">
                                    @if($section->button_text)
                                        <a class="mkp-btn mkp-btn-primary"
                                           href="{{ $section->button_url ?: route('signup.create') }}"
                                           data-mkp-event="marketplace.signup_started">
                                            {{ $section->button_text }}
                                        </a>
                                    @else
                                        <a class="mkp-btn mkp-btn-primary" href="{{ route('signup.create') }}" data-mkp-event="marketplace.signup_started">Teste grátis</a>
                                    @endif
                                    <a class="mkp-btn mkp-btn-outline" href="{{ $heroSecondaryCta['url'] ?? '#demo' }}">
                                        {{ $heroSecondaryCta['text'] ?? 'Solicitar demonstração' }}
                                    </a>
                                    @if($settings->whatsappLink())
                                        <a class="mkp-btn mkp-btn-ghost" href="{{ $settings->whatsappLink() }}" target="_blank" rel="noopener" data-mkp-event="marketplace.whatsapp_clicked">WhatsApp</a>
                                    @endif
                                </div>
                            </div>
                            <div class="mkp-hero-media">
                                @if($heroVideo)
                                    <video src="{{ $heroVideo }}" autoplay muted loop playsinline loading="lazy"></video>
                                @else
                                    <img src="{{ $heroImage }}" alt="{{ $brandName }}" loading="lazy">
                                @endif
                            </div>
                        </div>
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::About)
                <section id="quem-somos" class="mkp-section mkp-section-alt mkp-fade">
                    <div class="mkp-container">
                        <div class="mkp-about-grid">
                            <div class="mkp-about-text">
                                @if($section->subtitle)
                                    <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                                @endif
                                @if($section->title)
                                    <h2 class="mkp-title">{{ $section->title }}</h2>
                                @endif
                                @if($section->description)
                                    <p>{{ $section->description }}</p>
                                @endif
                                @if($section->button_text)
                                    <div style="margin-top:1.5rem;">
                                        <a class="mkp-btn mkp-btn-primary" href="{{ $section->button_url ?: route('signup.create') }}">{{ $section->button_text }}</a>
                                    </div>
                                @endif
                            </div>
                            @if($section->imageUrl())
                                <div class="mkp-about-image">
                                    <img src="{{ $section->imageUrl() }}" alt="" loading="lazy">
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Features)
                @php
                    $featureItems = json_decode($section->description ?? '', true);
                    if (! is_array($featureItems)) {
                        $featureItems = [];
                    }
                @endphp
                <section id="recursos" class="mkp-section mkp-fade" data-mkp-view="marketplace.feature_view">
                    <div class="mkp-container">
                        <div class="mkp-section-head">
                            @if($section->subtitle)
                                <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                            @endif
                            <h2 class="mkp-title">{{ $section->title ?: 'Recursos' }}</h2>
                        </div>
                        <div class="mkp-features-grid">
                            @foreach($featureItems as $item)
                                <article class="mkp-feature-card mkp-fade">
                                    <h3>{{ $item['title'] ?? '' }}</h3>
                                    <p>{{ $item['description'] ?? '' }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Video)
                @php
                    $embedFromUrl = function (?string $url): ?string {
                        if (! filled($url)) {
                            return null;
                        }
                        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/', $url, $m)) {
                            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0';
                        }
                        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
                            return 'https://player.vimeo.com/video/'.$m[1];
                        }

                        return $url;
                    };
                    $sectionEmbed = $embedFromUrl($section->videoUrl());
                @endphp
                <section id="demonstracao" class="mkp-section mkp-section-alt mkp-fade">
                    <div class="mkp-container">
                        <div class="mkp-section-head">
                            @if($section->subtitle)
                                <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                            @endif
                            <h2 class="mkp-title">{{ $section->title ?: 'Demonstração' }}</h2>
                            @if($section->description)
                                <p class="mkp-subtitle">{{ $section->description }}</p>
                            @endif
                        </div>

                        @if($sectionEmbed)
                            <div class="mkp-video-wrap" data-mkp-video>
                                <iframe src="{{ $sectionEmbed }}" title="Demonstração" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                            </div>
                        @elseif($videos->isNotEmpty())
                            <div class="mkp-videos-grid">
                                @foreach($videos as $video)
                                    @php $embed = $embedFromUrl($video->displayUrl()); @endphp
                                    @if($embed)
                                        <div class="mkp-video-card mkp-fade">
                                            <div class="mkp-video-wrap" data-mkp-video>
                                                <iframe src="{{ $embed }}" title="{{ $video->title ?: 'Vídeo' }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                            </div>
                                            @if($video->title)
                                                <h4>{{ $video->title }}</h4>
                                            @endif
                                            @if($video->caption)
                                                <p class="mkp-subtitle" style="margin-top:0.35rem;font-size:0.88rem;">{{ $video->caption }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="mkp-hero-media" style="max-width:860px;margin:0 auto;">
                                <img src="{{ $section->imageUrl() ?: asset($videoFallbackImage ?? '/images/marketplace/product-preview.svg') }}"
                                     alt="{{ $section->title ?: 'Demonstração' }}" loading="lazy">
                            </div>
                        @endif
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Gallery)
                @if($gallery->isNotEmpty())
                    <section class="mkp-section mkp-fade">
                        <div class="mkp-container">
                            <div class="mkp-section-head">
                                @if($section->subtitle)
                                    <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                                @endif
                                <h2 class="mkp-title">{{ $section->title ?: 'Galeria' }}</h2>
                            </div>
                            <div class="mkp-gallery-grid">
                                @foreach($gallery as $item)
                                    @if($item->displayUrl())
                                        <figure class="mkp-gallery-item mkp-fade">
                                            <img src="{{ $item->displayUrl() }}" alt="{{ $item->title ?: 'Galeria' }}" loading="lazy">
                                        </figure>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Testimonials)
                <section id="clientes" class="mkp-section mkp-section-alt mkp-fade">
                    <div class="mkp-container">
                        <div class="mkp-section-head">
                            @if($section->subtitle)
                                <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                            @endif
                            <h2 class="mkp-title">{{ $section->title ?: 'Clientes' }}</h2>
                        </div>
                        <div class="mkp-testimonials-grid">
                            @foreach($testimonials as $t)
                                <blockquote class="mkp-testimonial mkp-fade">
                                    <div class="mkp-testimonial-header">
                                        @if($t->avatarUrl())
                                            <img class="mkp-avatar" src="{{ $t->avatarUrl() }}" alt="{{ $t->name }}" loading="lazy">
                                        @else
                                            <div class="mkp-avatar mkp-avatar-placeholder">{{ mb_substr($t->name, 0, 1) }}</div>
                                        @endif
                                        <div>
                                            <strong>{{ $t->name }}</strong>
                                            @if($t->company)
                                                <div class="mkp-subtitle" style="font-size:0.82rem;">{{ $t->company }}</div>
                                            @endif
                                            @if($t->rating)
                                                <div class="mkp-stars" aria-label="{{ $t->rating }} estrelas">
                                                    @for($s = 0; $s < $t->rating; $s++)★@endfor
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mkp-testimonial-text">"{{ $t->text }}"</p>
                                </blockquote>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Plans)
                @if($plans->isNotEmpty())
                    <section id="planos" class="mkp-section mkp-fade" data-mkp-view="marketplace.plan_view">
                        <div class="mkp-container">
                            <div class="mkp-section-head">
                                @if($section->subtitle)
                                    <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                                @endif
                                <h2 class="mkp-title">{{ $section->title ?: 'Planos' }}</h2>
                                @if($section->description)
                                    <p class="mkp-subtitle">{{ $section->description }}</p>
                                @endif
                            </div>
                            <div class="mkp-plans-grid">
                                @foreach($plans as $plan)
                                    @php
                                        $planFeatures = collect($plan->featureMap())
                                            ->filter(fn ($enabled) => $enabled)
                                            ->keys()
                                            ->map(fn ($key) => $featureLabels[$key] ?? $key)
                                            ->values();
                                    @endphp
                                    <article class="mkp-plan {{ $plan->is_featured ? 'mkp-plan-featured' : '' }} mkp-fade">
                                        @if($plan->is_featured)
                                            <span class="mkp-badge">Recomendado</span>
                                        @endif
                                        <h3 style="margin:0;">{{ $plan->name }}</h3>
                                        <div class="mkp-plan-price">
                                            @if((float) $plan->price <= 0)
                                                Grátis
                                            @else
                                                R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                                <small>/mês</small>
                                            @endif
                                        </div>
                                        @if($plan->description)
                                            <p class="mkp-plan-desc">{{ $plan->description }}</p>
                                        @endif
                                        @if($planFeatures->isNotEmpty())
                                            <ul class="mkp-plan-features">
                                                @foreach($planFeatures as $label)
                                                    <li>{{ $label }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if((float) $plan->price > 0)
                                            <a class="mkp-btn mkp-btn-primary"
                                               href="{{ route('marketplace.subscribe', ['plan_id' => $plan->id]) }}"
                                               data-mkp-event="marketplace.plan_clicked"
                                               data-mkp-meta='@json(["plan_id" => $plan->id])'>
                                                Assinar agora
                                            </a>
                                        @else
                                            <a class="mkp-btn mkp-btn-outline"
                                               href="{{ route('signup.create') }}"
                                               data-mkp-event="marketplace.signup_started">
                                                Começar teste
                                            </a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Faq)
                <section id="faq" class="mkp-section mkp-section-alt mkp-fade">
                    <div class="mkp-container">
                        <div class="mkp-section-head">
                            @if($section->subtitle)
                                <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                            @endif
                            <h2 class="mkp-title">{{ $section->title ?: 'Perguntas frequentes' }}</h2>
                        </div>
                        <div class="mkp-faq-list">
                            @foreach($faqs as $faq)
                                <div class="mkp-faq-item">
                                    <button type="button" class="mkp-faq-q" aria-expanded="false">{{ $faq->question }}</button>
                                    <div class="mkp-faq-a">{{ $faq->answer }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
                @break

            @case(\App\Domains\Marketplace\Enums\MarketplaceSectionType::Cta)
                <section id="contato" class="mkp-section mkp-fade">
                    <div class="mkp-container">
                        <div class="mkp-cta">
                            @if($section->subtitle)
                                <span class="mkp-eyebrow">{{ $section->subtitle }}</span>
                            @endif
                            <h2 class="mkp-title">{{ $section->title ?: 'Pronto para começar?' }}</h2>
                            @if($section->description)
                                <p class="mkp-subtitle" style="margin-bottom:1.5rem;">{{ $section->description }}</p>
                            @endif
                            <div style="display:flex;flex-wrap:wrap;gap:0.75rem;justify-content:center;">
                                @if($section->button_text)
                                    <a class="mkp-btn mkp-btn-primary" href="{{ $section->button_url ?: route('signup.create') }}" data-mkp-event="marketplace.signup_started">{{ $section->button_text }}</a>
                                @else
                                    <a class="mkp-btn mkp-btn-primary" href="{{ route('signup.create') }}" data-mkp-event="marketplace.signup_started">Teste grátis</a>
                                @endif
                                <a class="mkp-btn mkp-btn-ghost" href="{{ route('login') }}">Já tenho conta</a>
                            </div>
                        </div>
                    </div>
                </section>
                @break
        @endswitch
    @endforeach

    @include('marketplace.partials.growth-blocks')
</main>

<footer class="mkp-footer">
    <div class="mkp-container mkp-footer-inner">
        <div class="mkp-footer-brand">
            <a href="#inicio" class="mkp-logo">
                @if($settings->mediaUrl($settings->logo))
                    <img src="{{ $settings->mediaUrl($settings->logo) }}" alt="{{ $brandName }}" loading="lazy">
                @else
                    {{ $brandName }}
                @endif
            </a>
            <div class="mkp-footer-copy">
                &copy; {{ date('Y') }} {{ $settings->title ?: $brandName }}. {{ $footerData['rights'] ?? 'Todos os direitos reservados.' }}
            </div>
        </div>
        <nav class="mkp-footer-links" aria-label="Rodapé">
            @foreach(($footerData['links'] ?? []) as $link)
                @php
                    $footerHref = !empty($link['route']) ? route($link['href']) : ($link['href'] ?? '#');
                @endphp
                <a href="{{ $footerHref }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>
        <x-marketplace-social-links :settings="$settings" />
    </div>
</footer>

<x-marketplace-whats-app-button :settings="$settings" :context="$whatsappContext ?? null" />

<script>
(function () {
    var menuToggle = document.getElementById('mkp-menu-toggle');
    var nav = document.getElementById('mkp-nav');
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const eventsUrl = @json(route('marketplace.events.store'));
    let lastEventAt = 0;

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
            var event = el.getAttribute('data-mkp-event');
            var metaRaw = el.getAttribute('data-mkp-meta');
            var metadata = {};
            if (metaRaw) {
                try { metadata = JSON.parse(metaRaw); } catch (e) {}
            }
            trackEvent(event, metadata);
        });
    });

    document.querySelectorAll('[data-mkp-video]').forEach(function (wrap) {
        var iframe = wrap.querySelector('iframe');
        if (!iframe) return;
        var tracked = false;
        iframe.addEventListener('load', function () {
            wrap.addEventListener('click', function () {
                if (tracked) return;
                tracked = true;
                trackEvent('marketplace.video_started', { src: iframe.src });
            }, { once: true });
        });
    });

    var fadeEls = document.querySelectorAll('.mkp-fade');
    var trackedViews = {};
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    var viewEvent = entry.target.getAttribute('data-mkp-view');
                    if (viewEvent && !trackedViews[viewEvent]) {
                        trackedViews[viewEvent] = true;
                        trackEvent(viewEvent, {});
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        fadeEls.forEach(function (el) { observer.observe(el); });
        document.querySelectorAll('[data-mkp-view]').forEach(function (el) {
            if (!el.classList.contains('mkp-fade')) {
                observer.observe(el);
            }
        });
    } else {
        fadeEls.forEach(function (el) { el.classList.add('is-visible'); });
        document.querySelectorAll('[data-mkp-view]').forEach(function (el) {
            var viewEvent = el.getAttribute('data-mkp-view');
            if (viewEvent) trackEvent(viewEvent, {});
        });
    }

    document.querySelectorAll('.mkp-faq-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.mkp-faq-item');
            var open = item.classList.contains('is-open');
            document.querySelectorAll('.mkp-faq-item.is-open').forEach(function (i) {
                i.classList.remove('is-open');
                i.querySelector('.mkp-faq-q')?.setAttribute('aria-expanded', 'false');
            });
            if (!open) {
                item.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });
})();
</script>
</body>
</html>
