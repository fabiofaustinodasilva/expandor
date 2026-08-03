<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $resident?->name) }}" required>
</div>

<div class="form-group">
    <label for="phone">Telefone</label>
    <input class="form-control" id="phone" name="phone" value="{{ old('phone', $resident?->phone) }}">
</div>

<div class="form-group">
    <label for="email">E-mail</label>
    <input class="form-control" id="email" type="email" name="email" value="{{ old('email', $resident?->email) }}">
</div>

<div class="form-group">
    <label for="document">Documento</label>
    <input class="form-control" id="document" name="document" value="{{ old('document', $resident?->document) }}">
</div>

@if(!empty($showStatus))
    <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status" required>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $resident?->status?->value ?? 'active') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
@endif

<div class="form-group">
    <label style="display:flex; gap:0.5rem; align-items:center;">
        <input type="checkbox" name="is_primary_contact" value="1"
            @checked(old('is_primary_contact', $resident?->is_primary_contact))>
        Contato principal do cliente
    </label>
</div>

<div class="form-group">
    <label for="notes">Observações</label>
    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $resident?->notes) }}</textarea>
</div>
