@extends('layouts.app')

@section('title', 'Auditoria')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:center; margin-bottom:1rem;">
        <div>
            <h1 class="page-title" style="margin:0;">Auditoria</h1>
            <div class="header-meta">Histórico de ações da empresa · isolamento por tenant</div>
        </div>
        @can('privacy.view')
            <a class="btn btn-ghost" href="{{ route('company.privacy.index') }}">Privacidade LGPD</a>
        @endcan
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Saúde operacional</h2>
        <div class="header-meta" style="margin-bottom:0.75rem;">
            Status geral:
            <strong>{{ $health->ok ? 'OK' : 'Atenção' }}</strong>
            · Jobs falhos: {{ $health->failedJobs }}
        </div>
        <div class="grid grid-2">
            @foreach($health->checks as $name => $check)
                <div>
                    <strong>{{ $name }}</strong>
                    <div class="header-meta">{{ $check['message'] ?? '' }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Data</th>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Registro</th>
                <th>Antes</th>
                <th>Depois</th>
                <th>IP</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? '—' }}</td>
                    <td><span class="badge">{{ $log->action }}</span></td>
                    <td>{{ $audits->auditableLabel($log) }}</td>
                    <td>
                        <pre style="margin:0; white-space:pre-wrap; font-size:0.75rem; max-width:220px;">{{ $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                    </td>
                    <td>
                        <pre style="margin:0; white-space:pre-wrap; font-size:0.75rem; max-width:220px;">{{ $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                    </td>
                    <td>{{ $log->ip ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum evento de auditoria registrado.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
