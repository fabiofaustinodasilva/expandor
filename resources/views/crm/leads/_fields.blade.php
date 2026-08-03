<div class="form-grid">
    <div>
        <label>Nome</label>
        <input type="text" name="name" value="{{ old('name', $lead->name ?? '') }}" required>
    </div>
    <div>
        <label>E-mail</label>
        <input type="email" name="email" value="{{ old('email', $lead->email ?? '') }}">
    </div>
    <div>
        <label>Telefone</label>
        <input type="text" name="phone" value="{{ old('phone', $lead->phone ?? '') }}">
    </div>
    <div>
        <label>Origem</label>
        <select name="source">
            @foreach($sources as $value => $label)
                <option value="{{ $value }}" @selected(old('source', optional($lead)->source?->value ?? 'manual') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Status</label>
        <select name="status">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', optional($lead)->status?->value ?? 'new') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Responsável</label>
        <select name="assigned_to">
            <option value="">—</option>
            @foreach($sellers as $seller)
                <option value="{{ $seller->id }}" @selected((int) old('assigned_to', optional($lead)->assigned_to ?? auth()->id()) === $seller->id)>{{ $seller->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Campanha</label>
        <select name="campaign_id">
            <option value="">—</option>
            @foreach($campaigns as $campaign)
                <option value="{{ $campaign->id }}" @selected((int) old('campaign_id', optional($lead)->campaign_id ?? 0) === $campaign->id)>{{ $campaign->name }}</option>
            @endforeach
        </select>
    </div>
    <div style="grid-column:1/-1;">
        <label>Observações</label>
        <textarea name="notes" rows="3">{{ old('notes', optional($lead)->notes ?? '') }}</textarea>
    </div>
</div>
