@extends('layouts.platform')

@section('title', $section ? 'Editar seção' : 'Nova seção')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.marketplace.sections.index') }}" class="header-meta" style="text-decoration:none;">← Seções</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">{{ $section ? 'Editar seção' : 'Nova seção' }}</h1>
        <div class="header-meta">Configure o bloco de conteúdo da landing page.</div>
    </div>

    <form method="POST"
          action="{{ $section ? route('platform.marketplace.sections.update', $section) : route('platform.marketplace.sections.store') }}"
          enctype="multipart/form-data"
          class="card"
          style="max-width:720px;">
        @csrf
        @if($section)
            @method('PUT')
        @endif

        <div class="form-group">
            <label for="type">Tipo</label>
            <select class="form-control" id="type" name="type" required>
                @foreach($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $section?->type?->value) === $type->value)>
                        {{ $type->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="title">Título</label>
            <input class="form-control" id="title" name="title" maxlength="180"
                   value="{{ old('title', $section?->title) }}">
        </div>

        <div class="form-group">
            <label for="subtitle">Subtítulo</label>
            <input class="form-control" id="subtitle" name="subtitle" maxlength="255"
                   value="{{ old('subtitle', $section?->subtitle) }}">
        </div>

        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $section?->description) }}</textarea>
            <div class="header-meta" style="margin-top:.35rem;">
                Para seções de recursos, use JSON: <code>[{"title":"CRM","description":"..."}]</code>
            </div>
        </div>

        <x-media-upload
            name="image"
            label="Imagem"
            :current-url="$section?->imageUrl()"
            remove-name="remove_image"
            accept="image/jpeg,image/png,image/webp"
            hint="PNG, JPG ou WEBP até 8MB."
        />

        <div class="form-group">
            <label for="video">Vídeo (URL)</label>
            <input class="form-control" id="video" name="video" type="url" maxlength="500"
                   value="{{ old('video', $section?->video) }}" placeholder="https://youtube.com/...">
        </div>

        <div class="grid grid-2">
            <div class="form-group">
                <label for="button_text">Texto do botão</label>
                <input class="form-control" id="button_text" name="button_text" maxlength="80"
                       value="{{ old('button_text', $section?->button_text) }}">
            </div>
            <div class="form-group">
                <label for="button_url">URL do botão</label>
                <input class="form-control" id="button_url" name="button_url" maxlength="500"
                       value="{{ old('button_url', $section?->button_url) }}" placeholder="/cadastro">
            </div>
        </div>

        <div class="form-group">
            <label for="order">Ordem</label>
            <input class="form-control" id="order" name="order" type="number" min="0" max="9999"
                   value="{{ old('order', $section?->order ?? 0) }}">
        </div>

        <div class="form-group">
            <label style="display:inline-flex; gap:.45rem; align-items:center;">
                <input type="checkbox" name="active" value="1" @checked(old('active', $section?->active ?? true))>
                Seção ativa
            </label>
        </div>

        <div class="actions" style="margin-top:1rem;">
            <button class="btn btn-primary" type="submit">{{ $section ? 'Salvar alterações' : 'Criar seção' }}</button>
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.sections.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
