<div class="form-group">
    <label for="city_id">Cidade</label>
    <select class="form-control" id="city_id" name="city_id" required>
        <option value="">Selecione</option>
        @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected(old('city_id', $address?->city_id) == $city->id)>
                {{ $city->name }}/{{ $city->state }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="sector_id">Setor</label>
    <select class="form-control" id="sector_id" name="sector_id">
        <option value="">Opcional</option>
        @foreach($sectors as $sector)
            <option value="{{ $sector->id }}" @selected(old('sector_id', $address?->sector_id) == $sector->id)>
                {{ $sector->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="street">Rua</label>
    <input class="form-control" id="street" name="street" value="{{ old('street', $address?->street) }}" required>
</div>

<div class="form-group">
    <label for="number">Número</label>
    <input class="form-control" id="number" name="number" value="{{ old('number', $address?->number) }}">
</div>

<div class="form-group">
    <label for="complement">Complemento</label>
    <input class="form-control" id="complement" name="complement" value="{{ old('complement', $address?->complement) }}">
</div>

<div class="form-group">
    <label for="neighborhood">Bairro</label>
    <input class="form-control" id="neighborhood" name="neighborhood" value="{{ old('neighborhood', $address?->neighborhood) }}">
</div>

<div class="form-group">
    <label for="zipcode">CEP</label>
    <input class="form-control" id="zipcode" name="zipcode" value="{{ old('zipcode', $address?->zipcode) }}">
</div>

<div class="grid grid-2">
    <div class="form-group">
        <label for="latitude">Latitude</label>
        <input class="form-control" id="latitude" name="latitude" value="{{ old('latitude', $address?->latitude) }}">
    </div>
    <div class="form-group">
        <label for="longitude">Longitude</label>
        <input class="form-control" id="longitude" name="longitude" value="{{ old('longitude', $address?->longitude) }}">
    </div>
</div>
