{{-- Sprint 8.1.4 — cabeçalho de página padronizado --}}
@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])
<header {{ $attributes->merge(['class' => 'page-header']) }}>
    <div class="page-header__main">
        @if(!empty($breadcrumbs))
            <nav class="breadcrumb" aria-label="Navegação estrutural">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span class="breadcrumb__sep" aria-hidden="true">/</span>
                    @endif
                    @if(!empty($crumb['href']) && !$loop->last)
                        <a class="breadcrumb__link" href="{{ $crumb['href'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span class="breadcrumb__current" @if($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </nav>
        @endif
        <h1 class="page-title">{{ $title }}</h1>
        @if($description)
            <p class="page-subtitle">{{ $description }}</p>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="page-header__actions">
            {{ $slot }}
        </div>
    @endif
</header>
