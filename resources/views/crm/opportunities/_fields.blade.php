<div class="form-grid">
    <div>
        <label>Título</label>
        <input type="text" name="title" value="{{ old('title', optional($opportunity)->title ?? '') }}" required>
    </div>
    <div>
        <label>Valor</label>
        <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', optional($opportunity)->amount ?? 0) }}">
    </div>
    <div>
        <label>Probabilidade (%)</label>
        <input type="number" min="0" max="100" name="probability" value="{{ old('probability', optional($opportunity)->probability ?? 10) }}">
    </div>
    <div>
        <label>Estágio</label>
        <select name="pipeline_stage_id">
            @foreach($stages as $stage)
                <option value="{{ $stage->id }}" @selected((int) old('pipeline_stage_id', optional($opportunity)->pipeline_stage_id ?? 0) === $stage->id)>{{ $stage->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Dono</label>
        <select name="owner_id">
            <option value="">—</option>
            @foreach($sellers as $seller)
                <option value="{{ $seller->id }}" @selected((int) old('owner_id', optional($opportunity)->owner_id ?? auth()->id()) === $seller->id)>{{ $seller->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Fechamento previsto</label>
        <input type="date" name="expected_close_date" value="{{ old('expected_close_date', optional(optional($opportunity)->expected_close_date)->format('Y-m-d')) }}">
    </div>
    <div style="grid-column:1/-1;">
        <label>Observações</label>
        <textarea name="notes" rows="3">{{ old('notes', optional($opportunity)->notes ?? '') }}</textarea>
    </div>
</div>
