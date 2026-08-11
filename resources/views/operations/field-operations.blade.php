@extends('layouts.operational')

@section('title', 'Operação de Campo')

@section('page')
    <x-client.page-header
        title="Operação de Campo"
        description="Regras da empresa para o mapa e o fluxo de campo. Valem para todos os vendedores."
        eyebrow="Configurações"
    >
        <x-client.secondary-button :href="route('operations.settings')">Voltar</x-client.secondary-button>
    </x-client.page-header>

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

        <div style="display:flex;gap:.75rem;justify-content:flex-end;flex-wrap:wrap;">
            <a href="{{ route('operations.settings') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar regras</button>
        </div>
    </form>
@endsection
