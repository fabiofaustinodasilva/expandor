@extends('layouts.operational')

@section('title', 'Venda')

@section('page')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>
    <h1 class="page-title">Venda</h1>
    <p class="header-meta" style="margin-bottom:1.25rem;">
        Campos obrigatórios ao finalizar uma <strong>{{ $commercial::saleCompleted() }}</strong>.
        Prospecção (sem venda) continua rápida — nenhum destes campos é pedido.
    </p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('operations.settings.sale.update') }}" class="card" style="max-width:36rem;padding:1.25rem;">
        @csrf
        @method('PUT')

        <fieldset style="border:0;margin:0;padding:0;">
            <legend style="font-weight:700;font-size:1.05rem;margin-bottom:.35rem;">Campos obrigatórios para finalizar uma venda</legend>
            <p class="header-meta" style="margin:0 0 1rem;">Marque o que o vendedor precisa informar na etapa Finalizar venda.</p>

            @foreach($fieldKeys as $key)
                <label style="display:flex;gap:.65rem;align-items:center;margin-bottom:.55rem;cursor:pointer;">
                    <input type="checkbox" name="required_fields[]" value="{{ $key }}"
                        @checked(in_array($key, old('required_fields', $policy->required()), true))>
                    <span>{{ $fieldLabels[$key] ?? $key }}</span>
                </label>
            @endforeach
            @error('required_fields')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
            @error('required_fields.*')<div class="header-meta" style="color:#fca5a5;">{{ $message }}</div>@enderror
        </fieldset>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:1.25rem;">
            <a href="{{ route('operations.settings') }}" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
    </form>
@endsection
