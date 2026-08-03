@extends('layouts.platform')

@section('title', $company->name)

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">{{ $company->name }}</h1>
            <div class="header-meta">
                {{ $company->email }} · {{ $company->status }}
                @if($company->trashed())
                    · <span class="badge">excluída</span>
                @endif
            </div>
        </div>
        <div class="actions" style="flex-wrap:wrap;">
            <a class="btn btn-ghost" href="{{ route('platform.companies.index') }}">Voltar</a>
            @unless($company->trashed())
                <a class="btn btn-ghost" href="{{ route('platform.companies.edit', $company) }}">Editar</a>
                @if($company->status === 'active')
                    <form method="POST" action="{{ route('platform.companies.suspend', $company) }}">
                        @csrf
                        <input type="hidden" name="reason" value="Suspensão administrativa">
                        <button class="btn btn-ghost" type="submit">Suspender</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('platform.companies.activate', $company) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Reativar</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('platform.health.recalculate', $company) }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Recalcular health</button>
                </form>
                <form method="POST" action="{{ route('platform.companies.destroy', $company) }}" onsubmit="return confirm('Excluir logicamente esta empresa?');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="reason" value="Exclusão administrativa">
                    <button class="btn btn-ghost" type="submit" style="border-color:#7f1d1d;color:#fca5a5;">Excluir</button>
                </form>
            @else
                <form method="POST" action="{{ route('platform.companies.restore', $company) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Restaurar</button>
                </form>
            @endunless
        </div>
    </div>

    <div class="grid grid-2" style="margin-bottom:1rem;">
        <div class="card">
            <h2 style="margin-top:0;">Health Score</h2>
            <div style="font-size:2rem; font-weight:700;">{{ $health->score }}</div>
            <div class="header-meta">{{ $health->riskLevel }} · {{ $health->calculatedAt }}</div>
            <ul style="padding-left:1.1rem;">
                @foreach($health->factors as $key => $value)
                    <li><strong>{{ $key }}:</strong> {{ is_bool($value) ? ($value ? 'sim' : 'não') : ($value ?? '—') }}</li>
                @endforeach
            </ul>
        </div>
        <div class="card">
            <h2 style="margin-top:0;">Assinatura</h2>
            @php $subscription = $company->subscriptions->first(); @endphp
            <p><strong>Plano:</strong> {{ $subscription?->plan?->name ?? '—' }}</p>
            <p><strong>Status:</strong> {{ $subscription?->status ?? '—' }}</p>
            <p><strong>Início:</strong> {{ optional($subscription?->starts_at)->format('d/m/Y') ?: '—' }}</p>
            <p><strong>Trial até:</strong> {{ optional($subscription?->trial_ends_at)->format('d/m/Y H:i') ?: '—' }}</p>
            <p><strong>Próxima cobrança:</strong> {{ optional($subscription?->next_billing_at)->format('d/m/Y') ?: '—' }}</p>
            <p><strong>Gateway:</strong> {{ $subscription?->gateway ?: '—' }}</p>
            <p><strong>WhatsApp:</strong> {{ $company->whatsapp ?: '—' }}</p>
            @unless($company->trashed())
                @if($subscription?->status === \App\Domains\Company\Models\Subscription::STATUS_TRIAL
                    || ($subscription?->trial_ends_at && $subscription->status !== \App\Domains\Company\Models\Subscription::STATUS_ACTIVE))
                    <form method="POST" action="{{ route('platform.companies.convert-trial', $company) }}" style="margin-top:1rem;">
                        @csrf
                        <button class="btn btn-primary" type="submit">Converter para cliente</button>
                    </form>
                @endif
            @endunless
        </div>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Usuários</h2>
            <table class="table">
                <thead><tr><th>Nome</th><th>Papel</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}<div class="header-meta">{{ $user->email }}</div></td>
                        <td>{{ $user->role?->name }}</td>
                        <td>
                            @unless($company->trashed())
                                @can('platform.impersonate')
                                    @unless($user->is_platform_admin)
                                        <form method="POST" action="{{ route('platform.impersonation.store', $company) }}">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="reason" value="Suporte plataforma">
                                            <button class="btn btn-ghost" type="submit">Impersonar</button>
                                        </form>
                                    @endunless
                                @endcan
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @unless($company->trashed())
                @php
                    $administrators = $users->filter(fn ($u) => $u->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR);
                @endphp
                @if($administrators->isNotEmpty())
                    <h3 style="margin-top:1.5rem;">Reset de senha do administrador</h3>
                    <form method="POST" action="{{ route('platform.companies.reset-admin-password', $company) }}">
                        @csrf
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <label for="user_id">Administrador</label>
                            <select class="form-control" name="user_id" id="user_id">
                                @foreach($administrators as $adminUser)
                                    <option value="{{ $adminUser->id }}">{{ $adminUser->name }} — {{ $adminUser->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-2" style="margin-bottom:0.75rem;">
                            <div class="form-group">
                                <label for="password">Nova senha</label>
                                <input class="form-control" type="password" name="password" id="password" required minlength="8" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="password_confirmation">Confirmar</label>
                                <input class="form-control" type="password" name="password_confirmation" id="password_confirmation" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                        <button class="btn btn-primary" type="submit">Redefinir senha</button>
                    </form>
                @endif
            @endunless
        </div>

        <div class="card">
            <h2 style="margin-top:0;">Feature Flags</h2>
            @unless($company->trashed())
                @can('platform.manageFeatureFlags')
                    @foreach($flags as $flag)
                        <form method="POST" action="{{ route('platform.flags.update', $company) }}" style="display:flex; gap:0.5rem; align-items:center; margin-bottom:0.65rem;">
                            @csrf
                            <input type="hidden" name="key" value="{{ $flag->key }}">
                            <input type="hidden" name="enabled" value="{{ $flag->enabled ? 0 : 1 }}">
                            <div style="flex:1;">
                                <strong>{{ $flag->name }}</strong>
                                <div class="header-meta">{{ $flag->key }} · {{ $flag->enabled ? 'ON' : 'OFF' }}{{ $flag->hasOverride ? ' (override)' : '' }}</div>
                            </div>
                            <button class="btn btn-ghost" type="submit">{{ $flag->enabled ? 'Desativar' : 'Ativar' }}</button>
                        </form>
                    @endforeach
                @else
                    <p class="header-meta">Sem permissão para gerenciar flags.</p>
                @endcan
            @else
                <p class="header-meta">Empresa excluída — flags somente leitura após restaurar.</p>
            @endunless
        </div>
    </div>
@endsection
