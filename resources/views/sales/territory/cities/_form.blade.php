<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $city?->name) }}" required>
</div>

<div class="form-group">
    <label for="state">UF</label>
    <input class="form-control" id="state" name="state" maxlength="2" value="{{ old('state', $city?->state) }}" required>
</div>

<div class="form-group">
    <label for="ibge_code">Código IBGE</label>
    <input class="form-control" id="ibge_code" name="ibge_code" value="{{ old('ibge_code', $city?->ibge_code) }}">
</div>

<div class="form-group">
    <label for="active">Status</label>
    <select class="form-control" id="active" name="active">
        <option value="1" @selected(old('active', $city?->active ?? true) == true)>Ativa</option>
        <option value="0" @selected(old('active', $city?->active ?? true) == false)>Inativa</option>
    </select>
</div>
