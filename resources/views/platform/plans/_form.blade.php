@php
    /** @var \App\Domains\Company\Models\Plan|null $plan */
    $featureMap = $featureMap ?? [];
@endphp

<div class="form-group" style="margin-bottom:1rem;">
    <label for="name">Nome</label>
    <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $plan->name ?? '') }}" required>
    @error('name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
</div>

<div class="form-group" style="margin-bottom:1rem;">
    <label for="slug">Slug (opcional)</label>
    <input class="form-control" type="text" name="slug" id="slug" value="{{ old('slug', $plan->slug ?? '') }}" placeholder="gerado a partir do nome">
    @error('slug')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
</div>

<div class="form-group" style="margin-bottom:1rem;">
    <label for="description">Descrição</label>
    <textarea class="form-control" name="description" id="description" rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
</div>

<div class="grid grid-2" style="margin-bottom:1rem;">
    <div class="form-group">
        <label for="price">Preço mensal</label>
        <input class="form-control" type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price', $plan->price ?? '0') }}" required>
    </div>
    <div class="form-group">
        <label for="price_yearly">Preço anual</label>
        <input class="form-control" type="number" step="0.01" min="0" name="price_yearly" id="price_yearly" value="{{ old('price_yearly', $plan->price_yearly ?? '') }}">
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:1rem;">
    <div class="form-group">
        <label for="trial_days">Dias de trial</label>
        <input class="form-control" type="number" min="0" max="365" name="trial_days" id="trial_days" value="{{ old('trial_days', $plan->trial_days ?? '') }}">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" name="status" id="status" required>
            @php $status = old('status', $plan->status ?? \App\Domains\Company\Models\Plan::STATUS_ACTIVE); @endphp
            <option value="active" @selected($status === 'active')>Ativo</option>
            <option value="inactive" @selected($status === 'inactive')>Inativo</option>
        </select>
    </div>
</div>

<div class="grid grid-2" style="margin-bottom:1rem;">
    <div class="form-group">
        <label for="display_order">Ordem de exibição</label>
        <input class="form-control" type="number" min="0" max="9999" name="display_order" id="display_order" value="{{ old('display_order', $plan->display_order ?? 100) }}">
        <div class="header-meta">Menor número aparece primeiro no marketplace.</div>
    </div>
    <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom:0.35rem;">
        @php $featured = (bool) old('is_featured', $plan->is_featured ?? false); @endphp
        <label style="display:flex; gap:.6rem; align-items:center; color:var(--text);">
            <input type="hidden" name="is_featured" value="0">
            <input type="checkbox" name="is_featured" value="1" id="is_featured" @checked($featured)>
            Badge Mais vendido
        </label>
    </div>
</div>

<h2 style="margin:1.25rem 0 .85rem; font-size:1rem;">Limites</h2>
<div class="grid grid-2" style="margin-bottom:1rem;">
    <div class="form-group">
        <label for="max_users">Máx. usuários</label>
        <input class="form-control" type="number" min="0" name="max_users" id="max_users" value="{{ old('max_users', $plan->max_users ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_properties">Máx. clientes/pontos</label>
        <input class="form-control" type="number" min="0" name="max_properties" id="max_properties" value="{{ old('max_properties', $plan->max_properties ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_campaigns">Máx. campanhas</label>
        <input class="form-control" type="number" min="0" name="max_campaigns" id="max_campaigns" value="{{ old('max_campaigns', $plan->max_campaigns ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_teams">Máx. equipes</label>
        <input class="form-control" type="number" min="0" name="max_teams" id="max_teams" value="{{ old('max_teams', $plan->max_teams ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_products">Máx. produtos</label>
        <input class="form-control" type="number" min="0" name="max_products" id="max_products" value="{{ old('max_products', $plan->max_products ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_storage_mb">Máx. storage (MB)</label>
        <input class="form-control" type="number" min="0" name="max_storage_mb" id="max_storage_mb" value="{{ old('max_storage_mb', $plan->max_storage_mb ?? '') }}" placeholder="ilimitado">
    </div>
    <div class="form-group">
        <label for="max_visits">Máx. visitas</label>
        <input class="form-control" type="number" min="0" name="max_visits" id="max_visits" value="{{ old('max_visits', $plan->max_visits ?? '') }}" placeholder="ilimitado">
    </div>
</div>

<h2 style="margin:1.25rem 0 .85rem; font-size:1rem;">Features</h2>
<div class="grid grid-2" style="margin-bottom:1.25rem;">
    @foreach($featureLabels as $key => $label)
        @php
            $checked = (bool) old("features.{$key}", $featureMap[$key] ?? false);
        @endphp
        <label style="display:flex; gap:.6rem; align-items:center; color:var(--text);">
            <input type="hidden" name="features[{{ $key }}]" value="0">
            <input type="checkbox" name="features[{{ $key }}]" value="1" @checked($checked)>
            {{ $label }}
        </label>
    @endforeach
</div>
