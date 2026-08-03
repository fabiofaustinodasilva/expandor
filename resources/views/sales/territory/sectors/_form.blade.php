<div class="form-group">
    <label for="city_id">Cidade</label>
    <select class="form-control" id="city_id" name="city_id" required>
        <option value="">Selecione</option>
        @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected(old('city_id', $sector?->city_id) == $city->id)>
                {{ $city->name }}/{{ $city->state }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $sector?->name) }}" required>
</div>

<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $sector?->description) }}</textarea>
</div>

<div class="form-group">
    <label for="active">Status</label>
    <select class="form-control" id="active" name="active">
        <option value="1" @selected(old('active', $sector?->active ?? true) == true)>Ativo</option>
        <option value="0" @selected(old('active', $sector?->active ?? true) == false)>Inativo</option>
    </select>
</div>
