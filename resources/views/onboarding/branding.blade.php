@extends('layouts.app')

@section('title', 'Identidade — Onboarding')

@section('content')
    <x-saas-onboarding-shell :progress="$progress" step-key="branding">
        <div style="margin-bottom:1rem;">
            <h1 class="page-title" style="margin:0;">Identidade visual</h1>
            <p class="header-meta" style="margin:.35rem 0 0;">
                Marca do seu tenant (cliente SaaS) — separada da marca da plataforma Expandor.
            </p>
        </div>

        <div class="saas-onb-card" data-saas-branding="1">
            <form method="POST" action="{{ route('onboarding.branding.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-2" style="gap:1rem;">
                    <div>
                        <label for="display_name">Nome comercial</label>
                        <input id="display_name" name="display_name" type="text"
                               value="{{ old('display_name', $brand->displayName ?? $company->name) }}" required>
                    </div>
                    <div>
                        <label for="logo">Logo</label>
                        <input id="logo" name="logo" type="file" accept="image/*">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label for="slogan">Slogan</label>
                        <input id="slogan" name="slogan" type="text" value="{{ old('slogan', $brand->slogan) }}">
                    </div>
                    <div>
                        <label for="primary_color">Cor principal</label>
                        <input id="primary_color" name="primary_color" type="color"
                               value="{{ old('primary_color', $brand->primaryColor()) }}">
                    </div>
                    <div>
                        <label for="secondary_color">Cor secundária</label>
                        <input id="secondary_color" name="secondary_color" type="color"
                               value="{{ old('secondary_color', $brand->secondaryColor()) }}">
                    </div>
                    <div>
                        <label for="highlight_color">Destaque</label>
                        <input id="highlight_color" name="highlight_color" type="color"
                               value="{{ old('highlight_color', $brand->highlightColor()) }}">
                    </div>
                </div>
                <div class="saas-onb-actions">
                    <button class="btn btn-primary" type="submit">Salvar identidade</button>
                    <a class="btn btn-ghost" href="{{ route('onboarding.deal') }}">Voltar</a>
                </div>
            </form>
            <form method="POST" action="{{ route('onboarding.skip') }}" style="margin-top:.75rem;">
                @csrf
                <input type="hidden" name="step" value="branding">
                <button class="btn btn-ghost" type="submit">Pular por agora</button>
            </form>
        </div>
    </x-saas-onboarding-shell>
@endsection
