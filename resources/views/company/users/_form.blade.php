<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $user?->name) }}" required>
</div>

<div class="form-group">
    <label for="email">E-mail</label>
    <input class="form-control" id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required>
</div>

<div class="form-group">
    <label for="phone">Telefone</label>
    <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user?->phone) }}">
</div>

<div class="form-group">
    <label for="role_id">Perfil</label>
    <select class="form-control" id="role_id" name="role_id" required>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" @selected(old('role_id', $user?->role_id) == $role->id)>
                {{ $role->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="status">Status</label>
    <select class="form-control" id="status" name="status" required>
        @foreach(['active' => 'Ativo', 'inactive' => 'Inativo', 'blocked' => 'Bloqueado'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $user?->status ?? 'active') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="password">Senha {{ $user ? '(opcional)' : '' }}</label>
    <input class="form-control" id="password" type="password" name="password" {{ $user ? '' : 'required' }}>
</div>

<div class="form-group">
    <label for="password_confirmation">Confirmar senha</label>
    <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" {{ $user ? '' : 'required' }}>
</div>
