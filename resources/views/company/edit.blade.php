@extends('layouts.operational')

@section('title', 'Empresa')

@section('page')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('operations.settings') }}" class="header-meta" style="text-decoration:none;">← Configurações</a>
    </div>
    <h1 class="page-title">Empresa</h1>
    <p class="header-meta" style="margin-bottom:1.1rem;">Dados cadastrais e segmento de mercado.</p>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('company.update', $company) }}">
            @csrf
            @method('PUT')

            <h2 style="margin:0 0 .85rem; font-size:1rem;">Dados básicos</h2>

            <div class="form-group">
                <label for="name">Nome fantasia</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $company->name) }}" required>
            </div>

            <div class="form-group">
                <label for="legal_name">Razão social</label>
                <input class="form-control" id="legal_name" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}">
            </div>

            <div class="form-group">
                <label for="document">CNPJ</label>
                <input class="form-control" id="document" name="document" value="{{ old('document', $company->document) }}" placeholder="00.000.000/0000-00">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input class="form-control" id="phone" name="phone" value="{{ old('phone', $company->phone) }}">
                </div>
                <div class="form-group">
                    <label for="whatsapp">WhatsApp comercial</label>
                    <input class="form-control" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}">
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input class="form-control" id="email" type="email" name="email" value="{{ old('email', $company->email) }}">
            </div>

            <div class="form-group">
                <label for="address">Endereço</label>
                <input class="form-control" id="address" name="address" value="{{ old('address', $company->address) }}">
            </div>

            <h2 style="margin:1.25rem 0 .85rem; font-size:1rem;">Segmento</h2>
            <div class="form-group">
                <label for="segment">Segmento de mercado</label>
                <select class="form-control" id="segment" name="segment">
                    <option value="">Selecione…</option>
                    @foreach(\App\Domains\Company\Enums\CompanySegment::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('segment', $company->segment) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="header-meta" style="margin:.4rem 0 0;">Afeta apenas textos comerciais (terminologia). Não muda regras do sistema.</p>
            </div>

            <div class="actions" style="margin-top:1.1rem;">
                <button class="btn btn-primary" type="submit">Salvar</button>
                <a class="btn btn-ghost" href="{{ route('company.show', $company) }}">Cancelar</a>
                @can('create', \App\Domains\Branding\Models\Brand::class)
                    <a class="btn btn-ghost" href="{{ route('company.branding.edit') }}">Identidade visual</a>
                @endcan
            </div>
        </form>
    </div>
@endsection
