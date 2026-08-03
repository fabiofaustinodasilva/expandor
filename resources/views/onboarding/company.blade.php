@extends('layouts.app')

@section('title', 'Empresa — Onboarding')

@section('content')
    <div data-saas-onboarding="1" data-saas-step="company">
        <div style="margin-bottom:1.15rem;">
            <h1 class="page-title" style="margin:0;">Dados da empresa</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">Configure as informações principais do seu negócio.</p>
        </div>

        @include('onboarding.partials.saas-progress', ['progress' => $progress])

        <div class="card">
            <form method="POST" action="{{ route('onboarding.company.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="name">Nome da empresa</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $company->name) }}" required>
                        @error('name') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="logo">Logo</label>
                        <input id="logo" name="logo" type="file" accept="image/*">
                        @error('logo') <div class="header-meta" style="color:var(--accent-2);">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $company->phone) }}">
                    </div>
                    <div>
                        <label for="whatsapp">WhatsApp</label>
                        <input id="whatsapp" name="whatsapp" type="text" value="{{ old('whatsapp', $company->whatsapp) }}">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label for="address">Endereço</label>
                        <input id="address" name="address" type="text" value="{{ old('address', $company->address) }}">
                    </div>
                    <div>
                        <label for="city">Cidade</label>
                        <input id="city" name="city" type="text" value="{{ old('city') }}">
                    </div>
                    <div>
                        <label for="state">Estado</label>
                        <input id="state" name="state" type="text" maxlength="2" placeholder="UF" value="{{ old('state') }}">
                    </div>
                </div>

                <div style="margin-top:1.25rem; display:flex; gap:.75rem; flex-wrap:wrap;">
                    <button class="btn btn-primary" type="submit">Salvar e continuar</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.index') }}">Voltar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
