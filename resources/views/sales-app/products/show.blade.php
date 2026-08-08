@extends('layouts.sales-app')

@section('title', $product->name)

@section('content')
    <div style="margin-bottom:0.85rem;">
        <a href="{{ route('sales-app.products.index') }}" style="display:inline-flex; align-items:center; min-height:44px; font-weight:600; color:var(--muted);">← Voltar</a>
    </div>

    <p class="list-meta" style="margin:0 0 0.25rem;">{{ $product->categoryLabel() }}</p>
    <h1 class="page-title" style="margin-bottom:0.75rem;">{{ $product->name }}</h1>

    @if($product->imageOriginalUrl() || $product->imageUrl())
        <div style="margin-bottom:0.85rem;">
            <img src="{{ $product->imageOriginalUrl() ?: $product->imageUrl() }}"
                 alt="{{ $product->name }}"
                 style="width:100%; max-height:52vh; object-fit:contain; border-radius:1rem; background:#0b0d12; border:1px solid var(--border);">
        </div>
    @endif

    @if($product->embeddableVideoUrl())
        <div style="margin-bottom:0.85rem;">
            @php $video = $product->embeddableVideoUrl(); @endphp
            @if(str_contains((string) $video, 'youtube.com/embed') || str_contains((string) $video, 'player.vimeo.com'))
                <div style="position:relative; padding-bottom:56.25%; height:0; border-radius:1rem; overflow:hidden; border:1px solid var(--border);">
                    <iframe src="{{ $video }}"
                            title="Vídeo {{ $product->name }}"
                            style="position:absolute; inset:0; width:100%; height:100%; border:0;"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @else
                <video controls playsinline preload="metadata"
                       style="width:100%; max-height:52vh; border-radius:1rem; background:#000; border:1px solid var(--border);">
                    <source src="{{ $video }}">
                    Seu navegador não reproduz este vídeo.
                </video>
            @endif
        </div>
    @endif

    @if($product->description)
        <section style="margin-bottom:1rem;">
            <h2 style="font-size:1rem; margin:0 0 0.35rem;">Descrição</h2>
            <p style="margin:0; color:var(--text-secondary, var(--muted)); white-space:pre-wrap;">{{ $product->description }}</p>
        </section>
    @endif

    @if($product->benefitList() !== [])
        <section style="margin-bottom:1rem;">
            <h2 style="font-size:1rem; margin:0 0 0.35rem;">Benefícios</h2>
            <ul style="margin:0; padding-left:1.15rem;">
                @foreach($product->benefitList() as $benefit)
                    <li style="margin-bottom:0.35rem;">{{ $benefit }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section style="margin-bottom:1.25rem; padding:0.9rem 1rem; border:1px solid var(--border); border-radius:1rem; background:var(--surface);">
        <h2 style="font-size:1rem; margin:0 0 0.5rem;">Informações comerciais</h2>
        <div style="display:grid; gap:0.35rem; font-size:0.95rem;">
            <div><span style="color:var(--muted);">Preço:</span> R$ {{ number_format((float) $product->price, 2, ',', '.') }}</div>
            @if((float) $product->commission_amount > 0)
                <div><span style="color:var(--muted);">Comissão:</span> R$ {{ number_format((float) $product->commission_amount, 2, ',', '.') }}</div>
            @endif
        </div>
    </section>

    <div class="btn-row" style="grid-template-columns:1fr 1fr;">
        @if($prev)
            <a class="btn btn-ghost" href="{{ route('sales-app.products.show', $prev) }}" style="min-height:44px; text-align:center;">← Anterior</a>
        @else
            <span class="btn btn-ghost" style="min-height:44px; opacity:0.35; pointer-events:none; text-align:center;">← Anterior</span>
        @endif
        @if($next)
            <a class="btn btn-primary" href="{{ route('sales-app.products.show', $next) }}" style="min-height:44px; text-align:center;">Próximo →</a>
        @else
            <span class="btn btn-primary" style="min-height:44px; opacity:0.35; pointer-events:none; text-align:center;">Próximo →</span>
        @endif
    </div>
@endsection
