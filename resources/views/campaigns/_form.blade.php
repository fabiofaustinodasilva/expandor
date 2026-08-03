@php
    $selectedUsers = old('user_ids', isset($campaign) ? $campaign->users->pluck('id')->all() : []);
    $selectedSectors = old('sector_ids', isset($campaign) ? $campaign->sectors->pluck('id')->all() : []);
    $selectedCity = old('city_id', $campaign->city_id ?? '');
@endphp

<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $campaign->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $campaign->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="city_id">Cidade</label>
    <select class="form-control" id="city_id" name="city_id" required>
        <option value="">Selecione</option>
        @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected((string) $selectedCity === (string) $city->id)>
                {{ $city->name }}/{{ $city->state }}
            </option>
        @endforeach
    </select>
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label for="start_date">Início</label>
        <input class="form-control" type="date" id="start_date" name="start_date"
               value="{{ old('start_date', isset($campaign) ? $campaign->start_date?->format('Y-m-d') : '') }}">
    </div>
    <div class="form-group">
        <label for="end_date">Fim</label>
        <input class="form-control" type="date" id="end_date" name="end_date"
               value="{{ old('end_date', isset($campaign) ? $campaign->end_date?->format('Y-m-d') : '') }}">
    </div>
</div>

<div class="form-group">
    <label for="goal_visits">Meta de visitas</label>
    <input class="form-control" type="number" min="0" id="goal_visits" name="goal_visits"
           value="{{ old('goal_visits', $campaign->goal_visits ?? 0) }}">
</div>

@if(!isset($campaign))
    <div class="form-group">
        <label for="status">Status inicial</label>
        <select class="form-control" id="status" name="status">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="form-group">
    <label>Vendedores</label>
    <div class="card" style="padding:0.85rem; max-height:220px; overflow:auto;">
        @forelse($sellers as $seller)
            <label style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">
                <input type="checkbox" name="user_ids[]" value="{{ $seller->id }}"
                    @checked(in_array($seller->id, $selectedUsers, true))>
                <span>{{ $seller->name }} <small style="color:var(--muted);">({{ $seller->role?->name }})</small></span>
            </label>
        @empty
            <div class="header-meta">Nenhum vendedor disponível.</div>
        @endforelse
    </div>
</div>

<div class="form-group">
    <label>Setores</label>
    <div class="card" style="padding:0.85rem; max-height:220px; overflow:auto;" id="sector-options">
        @forelse($sectors as $sector)
            <label class="sector-option" data-city-id="{{ $sector->city_id }}"
                   style="display:flex; gap:0.55rem; align-items:center; margin-bottom:0.45rem; color:var(--text);">
                <input type="checkbox" name="sector_ids[]" value="{{ $sector->id }}"
                    @checked(in_array($sector->id, $selectedSectors, true))>
                <span>{{ $sector->name }}</span>
            </label>
        @empty
            <div class="header-meta">Nenhum setor disponível.</div>
        @endforelse
    </div>
</div>

<script>
    (function () {
        const citySelect = document.getElementById('city_id');
        const options = document.querySelectorAll('.sector-option');
        if (!citySelect) return;

        function filterSectors() {
            const cityId = citySelect.value;
            options.forEach((option) => {
                const match = !cityId || option.dataset.cityId === cityId;
                option.style.display = match ? 'flex' : 'none';
                if (!match) {
                    const input = option.querySelector('input[type="checkbox"]');
                    if (input) input.checked = false;
                }
            });
        }

        citySelect.addEventListener('change', filterSectors);
        filterSectors();
    })();
</script>
