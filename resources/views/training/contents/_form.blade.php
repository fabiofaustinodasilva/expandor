@php
    $selectedType = old('type', $content->type->value ?? 'text');
@endphp

<div class="form-group">
    <label for="category_id">Categoria</label>
    <select class="form-control" id="category_id" name="category_id" required>
        <option value="">Selecione</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}"
                @selected((string) old('category_id', $content->category_id ?? '') === (string) $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="title">Título</label>
    <input class="form-control" id="title" name="title" value="{{ old('title', $content->title ?? '') }}" required>
</div>

<div class="form-group">
    <label for="description">Descrição</label>
    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $content->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="type">Tipo</label>
    <select class="form-control" id="type" name="type" required>
        @foreach($types as $value => $label)
            <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="content">Conteúdo textual</label>
    <textarea class="form-control" id="content" name="content" rows="6">{{ old('content', $content->content ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="url">URL (vídeo/documento)</label>
    <input class="form-control" id="url" name="url" value="{{ old('url', $content->url ?? '') }}" placeholder="https://">
</div>

<label style="display:flex; gap:0.5rem; align-items:center; margin-bottom:1rem;">
    <input type="checkbox" name="active" value="1" @checked(old('active', $content->active ?? true))>
    Ativo
</label>
