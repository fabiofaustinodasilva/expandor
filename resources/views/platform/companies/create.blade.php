@extends('layouts.platform')

@section('title', 'Nova empresa')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Nova empresa cliente</h1>
        <div class="header-meta">Cria a empresa, associa o plano e o administrador inicial</div>
    </div>

    <div class="card" style="max-width:760px;">
        <form method="POST" action="{{ route('platform.companies.store') }}">
            @csrf

            <h2 style="margin-top:0;">Empresa</h2>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="company_name">Nome</label>
                <input class="form-control" type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required>
                @error('company_name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="legal_name">Razão social</label>
                <input class="form-control" type="text" name="legal_name" id="legal_name" value="{{ old('legal_name') }}">
            </div>

            <div class="grid grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label for="document">Documento</label>
                    <input class="form-control" type="text" name="document" id="document" value="{{ old('document') }}">
                </div>
                <div class="form-group">
                    <label for="company_phone">Telefone</label>
                    <input class="form-control" type="text" name="company_phone" id="company_phone" value="{{ old('company_phone') }}">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="company_email">E-mail da empresa</label>
                <input class="form-control" type="email" name="company_email" id="company_email" value="{{ old('company_email') }}">
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label for="plan_id">Plano</label>
                <select class="form-control" name="plan_id" id="plan_id" required>
                    <option value="">Selecione</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected((string) old('plan_id') === (string) $plan->id)>
                            {{ $plan->name }}
                            @if((float) $plan->price > 0)
                                — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                            @else
                                — Gratuito
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('plan_id')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
            </div>

            <h2>Administrador inicial</h2>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="admin_name">Nome</label>
                <input class="form-control" type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}" required>
                @error('admin_name')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
            </div>

            <div class="grid grid-2" style="margin-bottom:1rem;">
                <div class="form-group">
                    <label for="admin_email">E-mail</label>
                    <input class="form-control" type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required>
                    @error('admin_email')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_phone">Telefone</label>
                    <input class="form-control" type="text" name="admin_phone" id="admin_phone" value="{{ old('admin_phone') }}">
                </div>
            </div>

            <div class="grid grid-2" style="margin-bottom:1.5rem;">
                <div class="form-group">
                    <label for="admin_password">Senha</label>
                    <input class="form-control" type="password" name="admin_password" id="admin_password" required minlength="8">
                    @error('admin_password')<div class="header-meta" style="color:#f87171;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="admin_password_confirmation">Confirmar senha</label>
                    <input class="form-control" type="password" name="admin_password_confirmation" id="admin_password_confirmation" required minlength="8">
                </div>
            </div>

            <div class="actions">
                <a class="btn btn-ghost" href="{{ route('platform.companies.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Criar empresa</button>
            </div>
        </form>
    </div>
@endsection
