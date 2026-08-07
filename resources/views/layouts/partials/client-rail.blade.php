{{-- Sprint 8.2.2 — Rail de navegação primária (área do cliente) --}}
@php
    $authUser = $authUser ?? auth()->user();
    $authUser?->loadMissing('role');
    $company = $company ?? $authUser?->company;
    $railItems = $authUser ? \App\Support\ClientArea\ClientNav::railItems($authUser) : [];
@endphp
<aside class="op-rail" aria-label="Navegação {{ $brand->name() }}">
    <a href="{{ route('map.index') }}" class="op-rail-brand" title="{{ $brand->name() }}">
        @if($brand->logoMark())
            <img src="{{ $brand->logoMark() }}" alt="{{ $brand->name() }}">
        @else
            <span>{{ \Illuminate\Support\Str::limit($brand->name(), 8, '') }}</span>
        @endif
    </a>
    <div class="op-rail-user" title="{{ $authUser?->name }}">
        <strong>{{ $authUser?->name }}</strong>
        <span>{{ $authUser?->role?->name ?? ($company?->name ?? '') }}</span>
    </div>

    @foreach($railItems as $item)
        <a href="{{ route($item['route'], $item['params']) }}"
           class="{{ request()->routeIs(...$item['patterns']) ? 'active' : '' }}"
           title="{{ $item['label'] }}">
            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i><span>{{ $item['label'] }}</span>
        </a>
    @endforeach

    <div class="flex-1"></div>
    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}" title="Meu perfil">
        <i data-lucide="user-round" class="w-5 h-5"></i><span>Perfil</span>
    </a>
    <a href="{{ route('operations.more') }}" class="{{ request()->routeIs('operations.more') ? 'active' : '' }}" title="Mais opções">
        <i data-lucide="ellipsis" class="w-5 h-5"></i><span>Mais</span>
    </a>
    <form method="POST" action="{{ route('logout') }}">@csrf
        <button type="submit" title="Sair"><i data-lucide="log-out" class="w-5 h-5"></i><span>Sair</span></button>
    </form>
</aside>
