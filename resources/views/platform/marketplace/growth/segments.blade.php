@extends('layouts.platform')

@section('title', 'Site — Segmentos')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Páginas por segmento</h1>
        <div class="header-meta">Páginas por segmento exibidas em /marketplace/{slug}.</div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Novo segmento</h2>
        <form method="POST" action="{{ route('platform.marketplace.segments.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="slug">Slug (URL)</label>
                    <input class="form-control" id="slug" name="slug" maxlength="120"
                           value="{{ old('slug') }}" placeholder="provedor-internet">
                </div>
                <div class="form-group">
                    <label for="title">Título *</label>
                    <input class="form-control" id="title" name="title" required maxlength="180"
                           value="{{ old('title') }}">
                </div>
                <div class="form-group">
                    <label for="subtitle">Subtítulo</label>
                    <input class="form-control" id="subtitle" name="subtitle" maxlength="255"
                           value="{{ old('subtitle') }}">
                </div>
                <div class="form-group">
                    <label for="hero_video">Vídeo do hero (URL)</label>
                    <input class="form-control" id="hero_video" name="hero_video" maxlength="500"
                           value="{{ old('hero_video') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Descrição</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label for="features">Recursos (um por linha: Título|Descrição)</label>
                <textarea class="form-control" id="features" name="features" rows="4"
                          placeholder="Território|Organize áreas de atuação por vendedor">{{ old('features') }}</textarea>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="cta_text">Texto do CTA</label>
                    <input class="form-control" id="cta_text" name="cta_text" maxlength="80"
                           value="{{ old('cta_text', 'Solicitar demonstração') }}">
                </div>
                <div class="form-group">
                    <label for="cta_url">URL do CTA</label>
                    <input class="form-control" id="cta_url" name="cta_url" maxlength="500"
                           value="{{ old('cta_url', '#demo') }}">
                </div>
            </div>

            <div class="form-group">
                <label for="hero_image">Imagem do hero</label>
                <input class="form-control" id="hero_image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group">
                <label style="display:inline-flex;gap:.45rem;align-items:center;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                    Ativo
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Criar segmento</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Segmentos cadastrados</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Slug</th>
                <th>Título</th>
                <th>Status</th>
                <th>URL</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($segments as $segment)
                <tr>
                    <td><code>{{ $segment->slug }}</code></td>
                    <td>{{ $segment->title }}</td>
                    <td>
                        @if($segment->active)
                            <span class="badge" style="color:var(--success);">Ativo</span>
                        @else
                            <span class="badge">Inativo</span>
                        @endif
                    </td>
                    <td>
                        <a class="header-meta" href="{{ route('marketplace.segment', $segment->slug) }}" target="_blank" rel="noopener">
                            /marketplace/{{ $segment->slug }}
                        </a>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('platform.marketplace.segments.destroy', $segment) }}"
                              onsubmit="return confirm('Remover este segmento?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum segmento cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
