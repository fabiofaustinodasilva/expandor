{{-- Sprint 8.2.2 — Navegação empresa (PT-BR, seções via NavVisibility/ClientNav) --}}
@php
    $navUser = $authUser ?? auth()->user();
    $navSections = $navUser ? \App\Support\ClientArea\ClientNav::sections($navUser) : [];
    $open = function (array $patterns): bool {
        return request()->routeIs(...$patterns);
    };
@endphp
<nav aria-label="Menu principal" class="ent-nav">
    @foreach($navSections as $section)
        @php $sectionOpen = $open(collect($section['items'])->flatMap(fn ($item) => $item['patterns'])->all()); @endphp
        <div class="nav-section {{ $sectionOpen ? 'is-open' : '' }}" data-nav-section>
            <button type="button" class="nav-section-toggle" aria-expanded="{{ $sectionOpen ? 'true' : 'false' }}">
                <span class="nav-section-left">
                    <span class="nav-ico" aria-hidden="true"><i data-lucide="{{ $section['icon'] }}" class="w-4 h-4"></i></span>
                    {{ $section['label'] }}
                </span>
                <span class="nav-chevron" aria-hidden="true"></span>
            </button>
            <div class="nav-section-body">
                @foreach($section['items'] as $item)
                    <a class="nav-link {{ $open($item['patterns']) ? 'active' : '' }}"
                       href="{{ route($item['route'], $item['params']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endforeach
</nav>
<script>
(function () {
    document.querySelectorAll('[data-nav-section]').forEach(function (section) {
        var btn = section.querySelector('.nav-section-toggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var open = section.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    if (window.lucide) { window.lucide.createIcons(); }
})();
</script>
