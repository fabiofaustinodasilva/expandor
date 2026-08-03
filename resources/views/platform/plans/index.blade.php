@extends('layouts.platform')

@section('title', 'Planos')

@section('content')
    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
        <div>
            <h1 class="page-title" style="margin:0;">Planos SaaS</h1>
            <div class="header-meta">Catálogo comercial da plataforma</div>
        </div>
        <a class="btn btn-primary" href="{{ route('platform.plans.create') }}">Novo plano</a>
    </div>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Slug</th>
                <th>Mensal</th>
                <th>Anual</th>
                <th>Trial</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($plans as $plan)
                <tr>
                    <td>{{ $plan->name }}</td>
                    <td><span class="badge">{{ $plan->slug }}</span></td>
                    <td>R$ {{ number_format((float) $plan->price, 2, ',', '.') }}</td>
                    <td>
                        @if($plan->price_yearly !== null)
                            R$ {{ number_format((float) $plan->price_yearly, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $plan->trial_days !== null ? $plan->trial_days.' dias' : '—' }}</td>
                    <td>
                        <span class="badge">{{ $plan->status }}</span>
                    </td>
                    <td>
                        <div class="actions" style="justify-content:flex-end; flex-wrap:wrap;">
                            <a class="btn btn-ghost" href="{{ route('platform.plans.edit', $plan) }}">Editar</a>
                            @if($plan->status === \App\Domains\Company\Models\Plan::STATUS_ACTIVE)
                                <form method="POST" action="{{ route('platform.plans.deactivate', $plan) }}">
                                    @csrf
                                    <button class="btn btn-ghost" type="submit">Desativar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('platform.plans.activate', $plan) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">Ativar</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhum plano cadastrado.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $plans->links() }}
        </div>
    </div>
@endsection
