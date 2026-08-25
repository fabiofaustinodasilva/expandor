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
                    <td>
                        <div>{{ $plan->name }}</div>
                        @if(! $plan->can_be_deleted && filled($plan->deletion_block_reason))
                            <div class="header-meta" style="margin-top:0.25rem; max-width:16rem; line-height:1.35;">
                                {{ $plan->deletion_block_reason }}
                            </div>
                        @endif
                    </td>
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
                        <div class="actions" style="justify-content:flex-end; flex-wrap:wrap; gap:0.35rem;">
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
                            @if($plan->can_be_deleted)
                                <button
                                    class="btn btn-ghost"
                                    type="button"
                                    style="color:#fca5a5; border-color:rgba(239,68,68,0.35);"
                                    data-plan-delete-open
                                    data-plan-name="{{ $plan->name }}"
                                    data-plan-action="{{ route('platform.plans.destroy', $plan) }}"
                                >Excluir</button>
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

    <dialog id="plan-delete-dialog" style="border:1px solid var(--border); border-radius:12px; padding:0; background:var(--bg-elevated); color:var(--text); max-width:min(420px, 92vw); width:100%;">
        <form method="POST" id="plan-delete-form" style="padding:1.25rem;">
            @csrf
            @method('DELETE')
            <h2 style="margin:0 0 0.5rem; font-size:1.05rem;" id="plan-delete-title">Excluir plano?</h2>
            <p class="header-meta" style="margin:0 0 1.25rem; line-height:1.45;">Esta ação é permanente.</p>
            <div class="actions" style="justify-content:flex-end; flex-wrap:wrap; gap:0.5rem;">
                <button class="btn btn-ghost" type="button" data-plan-delete-cancel>Cancelar</button>
                <button class="btn btn-primary" type="submit" style="background:#EF4444; border-color:#EF4444;">Excluir definitivamente</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('plan-delete-dialog');
    const form = document.getElementById('plan-delete-form');
    const title = document.getElementById('plan-delete-title');
    if (!dialog || !form || !title) return;

    document.querySelectorAll('[data-plan-delete-open]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const name = btn.getAttribute('data-plan-name') || 'este plano';
            const action = btn.getAttribute('data-plan-action');
            if (!action) return;
            title.textContent = `Excluir plano ${name}?`;
            form.setAttribute('action', action);
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                form.submit();
            }
        });
    });

    document.querySelectorAll('[data-plan-delete-cancel]').forEach((btn) => {
        btn.addEventListener('click', () => dialog.close());
    });
})();
</script>
@endpush
