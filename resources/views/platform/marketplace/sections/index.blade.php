@extends('layouts.platform')

@section('title', 'Marketplace — Seções')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Seções da Landing</h1>
            <div class="header-meta">Gerencie blocos de conteúdo exibidos na página pública.</div>
        </div>
        <a class="btn btn-primary" href="{{ route('platform.marketplace.sections.create') }}">Nova seção</a>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <table class="table">
            <thead>
            <tr>
                <th>Tipo</th>
                <th>Título</th>
                <th>Ordem</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($sections as $section)
                <tr>
                    <td><span class="badge">{{ $section->type->label() }}</span></td>
                    <td>{{ $section->title ?: '—' }}</td>
                    <td>{{ $section->order }}</td>
                    <td>
                        @if($section->active)
                            <span class="badge" style="color:var(--success);">Ativa</span>
                        @else
                            <span class="badge">Inativa</span>
                        @endif
                    </td>
                    <td>
                        <div class="actions" style="justify-content:flex-end; flex-wrap:wrap;">
                            <a class="btn btn-ghost" href="{{ route('platform.marketplace.sections.edit', $section) }}">Editar</a>
                            <form method="POST" action="{{ route('platform.marketplace.sections.toggle', $section) }}">
                                @csrf
                                <button class="btn btn-ghost" type="submit">
                                    {{ $section->active ? 'Desativar' : 'Ativar' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('platform.marketplace.sections.destroy', $section) }}"
                                  onsubmit="return confirm('Remover esta seção?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost" type="submit">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma seção cadastrada.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($sections->isNotEmpty())
        <div class="card">
            <h2 style="margin-top:0;">Reordenar seções</h2>
            <p class="header-meta">Use as setas para ajustar a ordem de exibição na landing.</p>

            <form method="POST" action="{{ route('platform.marketplace.sections.reorder') }}" id="mkp-reorder-form">
                @csrf
                <div id="mkp-reorder-list" style="display:flex; flex-direction:column; gap:0.5rem; margin-top:0.75rem;">
                    @foreach($sections->sortBy('order') as $section)
                        <div class="mkp-reorder-row" data-id="{{ $section->id }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.65rem 0.85rem; background:var(--bg-soft); border-radius:0.65rem; border:1px solid var(--border);">
                            <span class="badge" style="min-width:5.5rem;">{{ $section->type->label() }}</span>
                            <span style="flex:1;">{{ $section->title ?: 'Sem título' }}</span>
                            <span class="header-meta">ID {{ $section->id }}</span>
                            <div class="actions" style="margin:0;">
                                <button type="button" class="btn btn-ghost mkp-move-up" aria-label="Mover para cima">↑</button>
                                <button type="button" class="btn btn-ghost mkp-move-down" aria-label="Mover para baixo">↓</button>
                            </div>
                            <input type="hidden" name="order[]" value="{{ $section->id }}">
                        </div>
                    @endforeach
                </div>
                <div class="actions" style="margin-top:1rem;">
                    <button class="btn btn-primary" type="submit">Salvar ordem</button>
                </div>
            </form>
        </div>

        @push('scripts')
            <script>
                document.querySelectorAll('.mkp-move-up, .mkp-move-down').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var row = btn.closest('.mkp-reorder-row');
                        var list = document.getElementById('mkp-reorder-list');
                        if (btn.classList.contains('mkp-move-up') && row.previousElementSibling) {
                            list.insertBefore(row, row.previousElementSibling);
                        }
                        if (btn.classList.contains('mkp-move-down') && row.nextElementSibling) {
                            list.insertBefore(row.nextElementSibling, row);
                        }
                    });
                });
            </script>
        @endpush
    @endif
@endsection
