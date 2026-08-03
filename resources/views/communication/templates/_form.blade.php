<div class="form-group">
    <label for="name">Nome</label>
    <input class="form-control" id="name" name="name" value="{{ old('name', $template->name ?? '') }}" required>
</div>

<div class="form-group">
    <label for="event">Evento</label>
    <select class="form-control" id="event" name="event" required>
        @foreach($events as $value => $label)
            <option value="{{ $value }}" @selected(old('event', $template->event->value ?? 'generic') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="content">Conteúdo</label>
    <textarea class="form-control" id="content" name="content" rows="6" required>{{ old('content', $template->content ?? '') }}</textarea>
    <div class="header-meta" style="margin-top:0.35rem;">Variáveis: @{{nome}}, @{{telefone}}, @{{email}}</div>
</div>

<label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
    <input type="checkbox" name="active" value="1" @checked(old('active', $template->active ?? true))>
    Ativo
</label>
