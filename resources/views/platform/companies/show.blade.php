@extends('layouts.platform')

@section('title', $company->name)

@section('content')
    @php
        $subscription = $company->subscriptions->first();
        $administrators = $users->filter(fn ($u) => $u->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR);
        $primaryAdmin = $administrators->first();
    @endphp

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
            <h2 style="margin-top:0;">Métricas operacionais</h2>
            <p><strong>Criada em:</strong> {{ $ops->createdAt ? \Illuminate\Support\Carbon::parse($ops->createdAt)->format('d/m/Y H:i') : '—' }}</p>
            <p><strong>Último acesso:</strong> {{ $ops->lastAccessAt ? \Illuminate\Support\Carbon::parse($ops->lastAccessAt)->format('d/m/Y H:i') : '—' }}</p>
            <p><strong>Plano:</strong> {{ $ops->planName ?? '—' }}</p>
            <p><strong>Status assinatura:</strong> {{ $ops->subscriptionStatus ?? '—' }}</p>
            <p><strong>Trial restante:</strong> {{ $ops->trialDaysRemaining !== null ? $ops->trialDaysRemaining.' dias' : '—' }}</p>
            <p><strong>Trial até:</strong> {{ $ops->trialEndsAt ? \Illuminate\Support\Carbon::parse($ops->trialEndsAt)->format('d/m/Y H:i') : '—' }}</p>
            <p><strong>Término:</strong> {{ $ops->endsAt ? \Illuminate\Support\Carbon::parse($ops->endsAt)->format('d/m/Y') : '—' }}</p>
            <p><strong>Próxima cobrança:</strong> {{ $ops->nextBillingAt ? \Illuminate\Support\Carbon::parse($ops->nextBillingAt)->format('d/m/Y') : '—' }}</p>
            <p><strong>Usuários:</strong> {{ number_format($ops->usersCount, 0, ',', '.') }}</p>
            <p><strong>Clientes/pontos:</strong> {{ number_format($ops->customersCount, 0, ',', '.') }}</p>
            <p><strong>Visitas:</strong> {{ number_format($ops->visitsCount, 0, ',', '.') }}</p>
            <p>
                <strong>Storage:</strong>
                {{ number_format($ops->storageUsedMb, 2, ',', '.') }} MB
                @if($ops->storageLimitMb)
                    / {{ number_format($ops->storageLimitMb, 0, ',', '.') }} MB
                @endif
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom:1rem;">
        <h2 style="margin-top:0;">Assinatura</h2>
        <div class="grid grid-2">
            <div>
                <p><strong>Plano:</strong> {{ $subscription?->plan?->name ?? '—' }}</p>
                <p><strong>Status:</strong> {{ $subscription?->status ?? '—' }}</p>
                <p><strong>Início:</strong> {{ optional($subscription?->starts_at)->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Trial até:</strong> {{ optional($subscription?->trial_ends_at)->format('d/m/Y H:i') ?: '—' }}</p>
                <p><strong>Próxima cobrança:</strong> {{ optional($subscription?->next_billing_at)->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Término:</strong> {{ optional($subscription?->ends_at)->format('d/m/Y') ?: '—' }}</p>
                <p><strong>Cancelada em:</strong> {{ optional($subscription?->cancelled_at)->format('d/m/Y H:i') ?: '—' }}</p>
                <p><strong>Gateway:</strong> {{ $subscription?->gateway ?: '—' }}</p>
            </div>

            @unless($company->trashed())
                <div>
                    @if($subscription?->status === \App\Domains\Company\Models\Subscription::STATUS_TRIAL
                        || ($subscription?->trial_ends_at && $subscription->status !== \App\Domains\Company\Models\Subscription::STATUS_ACTIVE))
                        <form method="POST" action="{{ route('platform.companies.convert-trial', $company) }}" style="margin-bottom:1rem;">
                            @csrf
                            <button class="btn btn-primary" type="submit">Converter trial em cliente</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('platform.companies.subscription.renew-trial', $company) }}" style="margin-bottom:1rem;">
                        @csrf
                        <div class="form-group" style="margin-bottom:.5rem;">
                            <label for="trial_days">Renovar trial (dias)</label>
                            <input class="form-control" type="number" min="1" max="365" name="days" id="trial_days" value="{{ old('days', 14) }}" required>
                        </div>
                        <button class="btn btn-ghost" type="submit">Renovar trial</button>
                    </form>

                    <form method="POST" action="{{ route('platform.companies.subscription.change-plan', $company) }}" style="margin-bottom:1rem;">
                        @csrf
                        <div class="form-group" style="margin-bottom:.5rem;">
                            <label for="plan_id">Alterar plano</label>
                            <select class="form-control" name="plan_id" id="plan_id" required>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" @selected((int) old('plan_id', $subscription?->plan_id) === (int) $plan->id)>
                                        {{ $plan->name }} — R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-ghost" type="submit">Trocar plano</button>
                    </form>

                    <form method="POST" action="{{ route('platform.companies.subscription.dates', $company) }}" style="margin-bottom:1rem;">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-2" style="margin-bottom:.5rem;">
                            <div class="form-group">
                                <label for="ends_at">Término</label>
                                <input class="form-control" type="date" name="ends_at" id="ends_at" value="{{ old('ends_at', optional($subscription?->ends_at)->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group">
                                <label for="next_billing_at">Próxima cobrança</label>
                                <input class="form-control" type="date" name="next_billing_at" id="next_billing_at" value="{{ old('next_billing_at', optional($subscription?->next_billing_at)->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group">
                                <label for="trial_ends_at">Fim do trial</label>
                                <input class="form-control" type="date" name="trial_ends_at" id="trial_ends_at" value="{{ old('trial_ends_at', optional($subscription?->trial_ends_at)->format('Y-m-d')) }}">
                            </div>
                        </div>
                        <button class="btn btn-ghost" type="submit">Atualizar datas</button>
                    </form>

                    <div class="actions" style="flex-wrap:wrap;">
                        @if($subscription?->status !== \App\Domains\Company\Models\Subscription::STATUS_CANCELLED)
                            <form method="POST" action="{{ route('platform.companies.subscription.cancel', $company) }}" onsubmit="return confirm('Cancelar assinatura?');">
                                @csrf
                                <input type="hidden" name="reason" value="Cancelamento administrativo">
                                <button class="btn btn-ghost" type="submit" style="border-color:#7f1d1d;color:#fca5a5;">Cancelar assinatura</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('platform.companies.subscription.reactivate', $company) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit">Reativar assinatura</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endunless
        </div>

        <h3 style="margin-top:1.5rem;">Histórico da assinatura</h3>
        <table class="table">
            <thead>
            <tr>
                <th>Evento</th>
                <th>Ator</th>
                <th>Quando</th>
            </tr>
            </thead>
            <tbody>
            @forelse($subscriptionEvents as $event)
                <tr>
                    <td>
                        <strong>{{ $event->event }}</strong>
                        @if(!empty($event->payload))
                            <div class="header-meta">{{ \Illuminate\Support\Str::limit(json_encode($event->payload, JSON_UNESCAPED_UNICODE), 120) }}</div>
                        @endif
                    </td>
                    <td>{{ $event->actor?->email ?? '—' }}</td>
                    <td>{{ optional($event->created_at)->format('d/m/Y H:i') ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sem eventos registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0;">Usuários</h2>
            <table class="table">
                <thead><tr><th>Nome</th><th>Papel</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}<div class="header-meta">{{ $user->email }}</div></td>
                        <td>{{ $user->role?->name }}</td>
                        <td><span class="badge">{{ $user->status }}</span></td>
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
                <h3 style="margin-top:1.5rem;">Gestão do administrador</h3>

                @if($primaryAdmin)
                    <form method="POST" action="{{ route('platform.companies.admin-contact', $company) }}" style="margin-bottom:1.25rem;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="user_id" value="{{ $primaryAdmin->id }}">
                        <div class="form-group" style="margin-bottom:.75rem;">
                            <label for="admin_email">E-mail</label>
                            <input class="form-control" type="email" name="email" id="admin_email" value="{{ old('email', $primaryAdmin->email) }}" required>
                        </div>
                        <div class="grid grid-2" style="margin-bottom:.75rem;">
                            <div class="form-group">
                                <label for="admin_phone">Telefone</label>
                                <input class="form-control" type="text" name="phone" id="admin_phone" value="{{ old('phone', $primaryAdmin->phone) }}">
                            </div>
                            <div class="form-group">
                                <label for="admin_whatsapp">WhatsApp</label>
                                <input class="form-control" type="text" name="whatsapp" id="admin_whatsapp" value="{{ old('whatsapp', $primaryAdmin->whatsapp) }}">
                            </div>
                        </div>
                        <button class="btn btn-ghost" type="submit">Atualizar contato</button>
                    </form>

                    <div class="actions" style="flex-wrap:wrap; margin-bottom:1.25rem;">
                        @if($primaryAdmin->status === \App\Domains\Company\Models\User::STATUS_BLOCKED)
                            <form method="POST" action="{{ route('platform.companies.admin-unblock', $company) }}">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $primaryAdmin->id }}">
                                <button class="btn btn-primary" type="submit">Desbloquear login</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('platform.companies.admin-block', $company) }}" onsubmit="return confirm('Bloquear login deste administrador?');">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $primaryAdmin->id }}">
                                <button class="btn btn-ghost" type="submit" style="border-color:#7f1d1d;color:#fca5a5;">Bloquear login</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('platform.companies.admin-force-logout', $company) }}">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $primaryAdmin->id }}">
                            <button class="btn btn-ghost" type="submit">Forçar logout</button>
                        </form>
                    </div>
                @endif

                @if($administrators->isNotEmpty())
                    <h3 style="margin-top:1rem;">Reset de senha do administrador</h3>
                    <form method="POST" action="{{ route('platform.companies.reset-admin-password', $company) }}" style="margin-bottom:1.25rem;">
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

                <h3 style="margin-top:1rem;">Trocar administrador</h3>
                <form method="POST" action="{{ route('platform.companies.change-administrator', $company) }}">
                    @csrf
                    <div class="form-group" style="margin-bottom:.75rem;">
                        <label for="new_admin_user_id">Novo administrador</label>
                        <select class="form-control" name="user_id" id="new_admin_user_id" required>
                            @foreach($users->where('is_platform_admin', false) as $candidate)
                                <option value="{{ $candidate->id }}" @selected($candidate->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR)>
                                    {{ $candidate->name }} — {{ $candidate->email }} ({{ $candidate->role?->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-ghost" type="submit" onclick="return confirm('Confirmar troca de administrador?');">Definir como administrador</button>
                </form>
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
