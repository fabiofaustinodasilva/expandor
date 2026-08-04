@extends('layouts.platform')

@section('title', 'Marketplace — Cases')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Cases de sucesso</h1>
        <div class="header-meta">Histórias de clientes exibidas na landing e páginas de segmento.</div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Novo case</h2>
        <form method="POST" action="{{ route('platform.marketplace.cases.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="company_name">Empresa *</label>
                    <input class="form-control" id="company_name" name="company_name" required maxlength="180"
                           value="{{ old('company_name') }}">
                </div>
                <div class="form-group">
                    <label for="segment">Segmento</label>
                    <input class="form-control" id="segment" name="segment" maxlength="120"
                           value="{{ old('segment') }}" placeholder="provedor-internet">
                </div>
            </div>

            <div class="form-group">
                <label for="challenge">Desafio</label>
                <textarea class="form-control" id="challenge" name="challenge" rows="2">{{ old('challenge') }}</textarea>
            </div>

            <div class="form-group">
                <label for="solution">Solução</label>
                <textarea class="form-control" id="solution" name="solution" rows="2">{{ old('solution') }}</textarea>
            </div>

            <div class="form-group">
                <label for="result">Resultado</label>
                <textarea class="form-control" id="result" name="result" rows="2">{{ old('result') }}</textarea>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="video">Vídeo (URL)</label>
                    <input class="form-control" id="video" name="video" maxlength="500"
                           value="{{ old('video') }}">
                </div>
                <div class="form-group">
                    <label for="image">Imagem</label>
                    <input class="form-control" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <div class="form-group">
                <label style="display:inline-flex;gap:.45rem;align-items:center;">
                    <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                    Ativo
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Adicionar case</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Cases cadastrados</h2>
        <table class="table">
            <thead>
            <tr>
                <th>Empresa</th>
                <th>Segmento</th>
                <th>Resultado</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($cases as $case)
                <tr>
                    <td>{{ $case->company_name }}</td>
                    <td>{{ $case->segment ?: '—' }}</td>
                    <td>{{ Str::limit($case->result, 60) ?: '—' }}</td>
                    <td>
                        @if($case->active)
                            <span class="badge" style="color:var(--success);">Ativo</span>
                        @else
                            <span class="badge">Inativo</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('platform.marketplace.cases.destroy', $case) }}"
                              onsubmit="return confirm('Remover este case?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost" type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum case cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
