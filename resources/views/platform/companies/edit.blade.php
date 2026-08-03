@extends('layouts.platform')

@section('title', 'Editar '.$company->name)

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Editar empresa</h1>
        <div class="header-meta">{{ $company->name }}</div>
    </div>

    <div class="card" style="max-width:760px;">
        <form method="POST" action="{{ route('platform.companies.update', $company) }}">
            @csrf
            @method('PUT')

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="name">Nome</label>
                <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="legal_name">Razão social</label>
                <input class="form-control" type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $company->legal_name) }}">
            </div>

            <div class="grid grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label for="document">Documento</label>
                    <input class="form-control" type="text" name="document" id="document" value="{{ old('document', $company->document) }}">
                </div>
                <div class="form-group">
                    <label for="segment">Segmento</label>
                    <input class="form-control" type="text" name="segment" id="segment" value="{{ old('segment', $company->segment) }}">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="email">E-mail</label>
                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $company->email) }}">
            </div>

            <div class="grid grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', $company->phone) }}">
                </div>
                <div class="form-group">
                    <label for="whatsapp">WhatsApp</label>
                    <input class="form-control" type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="address">Endereço</label>
                <textarea class="form-control" name="address" id="address" rows="3">{{ old('address', $company->address) }}</textarea>
            </div>

            <div class="actions">
                <a class="btn btn-ghost" href="{{ route('platform.companies.show', $company) }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Salvar</button>
            </div>
        </form>
    </div>
@endsection
