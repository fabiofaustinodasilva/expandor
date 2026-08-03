@extends('layouts.app')

@section('title', 'Privacidade LGPD')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Privacidade e LGPD</h1>
            <div class="header-meta">Exportação, anonimização e consentimentos · {{ $company->name }}</div>
        </div>
        @can('viewAny', App\Domains\Audit\Models\AuditLog::class)
            <a class="btn btn-ghost" href="{{ route('company.audit.index') }}">Auditoria</a>
        @endcan
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Exportação de dados</h2>
            <p class="header-meta">Gera um arquivo JSON com dados da empresa atual (somente este tenant).</p>
            @can('privacy.manage', $company)
                <form method="POST" action="{{ route('company.privacy.export') }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Gerar exportação</button>
                </form>
            @endcan

            <table class="table" style="margin-top:1rem;">
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Solicitante</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($exports as $export)
                    <tr>
                        <td>{{ $export->requested_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $export->status?->label() }}</td>
                        <td>{{ $export->requester?->name }}</td>
                        <td>
                            @if($export->file_path)
                                <a class="btn btn-ghost" href="{{ route('company.privacy.export.download', $export) }}">Download</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhuma exportação.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $exports->links() }}
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Consentimentos</h2>
            @can('privacy.manage', $company)
                <form method="POST" action="{{ route('company.privacy.consents.store') }}" style="margin-bottom:1rem;">
                    @csrf
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label for="consent_resident_id">Morador</label>
                        <select class="form-control" name="resident_id" id="consent_resident_id" required>
                            <option value="">Selecione</option>
                            @foreach($residents as $resident)
                                <option value="{{ $resident->id }}">{{ $resident->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label for="consent_type">Tipo</label>
                        <select class="form-control" name="consent_type" id="consent_type" required>
                            @foreach(App\Domains\Security\Enums\ConsentType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label for="granted">Situação</label>
                        <select class="form-control" name="granted" id="granted" required>
                            <option value="1">Concedido</option>
                            <option value="0">Revogado</option>
                        </select>
                    </div>
                    <input type="hidden" name="source" value="web">
                    <button class="btn btn-primary" type="submit">Registrar consentimento</button>
                </form>
            @endcan

            <table class="table">
                <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Sujeito</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
                </thead>
                <tbody>
                @forelse($consents as $consent)
                    <tr>
                        <td>{{ $consent->consent_type?->label() }}</td>
                        <td>{{ class_basename((string) $consent->subject_type) }} #{{ $consent->subject_id }}</td>
                        <td>{{ $consent->granted ? 'Concedido' : 'Revogado' }}</td>
                        <td>{{ $consent->captured_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Nenhum consentimento.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $consents->links() }}
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Solicitações de anonimização</h2>
        @can('privacy.manage', $company)
            <form method="POST" action="{{ route('company.privacy.anonymizations.store') }}" style="margin-bottom:1rem; max-width:640px;">
                @csrf
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label for="anon_resident_id">Morador</label>
                    <select class="form-control" name="resident_id" id="anon_resident_id" required>
                        <option value="">Selecione</option>
                        @foreach($residents as $resident)
                            <option value="{{ $resident->id }}">{{ $resident->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0.75rem;">
                    <label for="reason">Motivo</label>
                    <textarea class="form-control" name="reason" id="reason" rows="2"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Solicitar anonimização</button>
            </form>
        @endcan

        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Sujeito</th>
                <th>Status</th>
                <th>Solicitante</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($anonymizations as $item)
                <tr>
                    <td>{{ $item->requested_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ class_basename((string) $item->subject_type) }} #{{ $item->subject_id }}</td>
                    <td>{{ $item->status?->label() }}</td>
                    <td>{{ $item->requester?->name }}</td>
                    <td>
                        @can('privacy.manage', $company)
                            @if($item->status?->value === 'pending')
                                <form method="POST" action="{{ route('company.privacy.anonymizations.process', $item) }}">
                                    @csrf
                                    <button class="btn btn-ghost" type="submit">Processar</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhuma solicitação.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $anonymizations->links() }}
    </div>
@endsection
