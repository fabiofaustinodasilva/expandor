@extends('layouts.operational')

@section('title', 'Operação de Campo')

@section('page')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>
    <h1 class="page-title">Operação de Campo</h1>
    <p class="header-meta" style="margin-bottom:1.25rem;">
        Regras da empresa para o mapa e o fluxo de campo. Valem para todos os vendedores (tenancy).
        Em breve estas opções poderão ser refinadas por campanha.
    </p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('operations.settings.field.update') }}" class="card" style="max-width:42rem;display:grid;gap:1.5rem;padding:1.25rem;">
        @csrf
        @method('PUT')

        <fieldset style="border:0;margin:0;padding:0;">
            <legend style="font-weight:700;font-size:1.05rem;margin-bottom:.65rem;">Visibilidade dos Pontos</legend>
            <p class="header-meta" style="margin:0 0 .75rem;">Quem o vendedor enxerga no mapa.</p>
            @foreach($visibilityOptions as $option)
                <label style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:.55rem;cursor:pointer;">
                    <input type="radio" name="points_visibility" value="{{ $option->value }}"
                        @checked(old('points_visibility', $policy->visibility->value) === $option->value)>
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
            @error('points_visibility')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;margin:0;padding:0;">
            <legend style="font-weight:700;font-size:1.05rem;margin-bottom:.65rem;">Exibição dos Pontos</legend>
            <p class="header-meta" style="margin:0 0 .75rem;">Filtro padrão da empresa no mapa.</p>
            @foreach($displayOptions as $option)
                <label style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:.55rem;cursor:pointer;">
                    <input type="radio" name="points_display" value="{{ $option->value }}"
                        @checked(old('points_display', $policy->display->value) === $option->value)>
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
            @error('points_display')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;margin:0;padding:0;">
            <legend style="font-weight:700;font-size:1.05rem;margin-bottom:.65rem;">Editar pontos de outro vendedor</legend>
            <p class="header-meta" style="margin:0 0 .75rem;">Além do criador, quem pode editar o ponto.</p>
            @foreach($editOthersOptions as $option)
                <label style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:.55rem;cursor:pointer;">
                    <input type="radio" name="points_edit_others" value="{{ $option->value }}"
                        @checked(old('points_edit_others', $policy->editOthers->value) === $option->value)>
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
            @error('points_edit_others')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
        </fieldset>

        <fieldset style="border:0;margin:0;padding:0;">
            <legend style="font-weight:700;font-size:1.05rem;margin-bottom:.65rem;">Excluir pontos</legend>
            <p class="header-meta" style="margin:0 0 .75rem;">Quem pode remover um ponto do mapa.</p>
            @foreach($deleteOptions as $option)
                <label style="display:flex;gap:.65rem;align-items:flex-start;margin-bottom:.55rem;cursor:pointer;">
                    <input type="radio" name="points_delete" value="{{ $option->value }}"
                        @checked(old('points_delete', $policy->delete->value) === $option->value)>
                    <span>{{ $option->label() }}</span>
                </label>
            @endforeach
            @error('points_delete')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
        </fieldset>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;">
            <a href="{{ route('operations.settings') }}" class="team-btn-ghost" style="text-decoration:none;padding:.55rem 1rem;">Cancelar</a>
            <button type="submit" class="team-btn-primary" style="border:0;cursor:pointer;padding:.55rem 1rem;border-radius:.75rem;background:#38bdf8;color:#0f172a;font-weight:700;">Salvar</button>
        </div>
    </form>
@endsection
