@extends('layouts.app')

@section('title', 'Editar campanha')

@section('content')
    @php
        $status = $campaign->status;
        $isDraft = $status === App\Domains\Campaigns\Enums\CampaignStatus::DRAFT;
        $isActive = $status === App\Domains\Campaigns\Enums\CampaignStatus::ACTIVE;
        $isPaused = $status === App\Domains\Campaigns\Enums\CampaignStatus::PAUSED;
        $isFinished = $status === App\Domains\Campaigns\Enums\CampaignStatus::FINISHED;
        $canDeleteSafely = $canDeleteSafely ?? false;
        $hasOperationalHistory = $hasOperationalHistory ?? false;
    @endphp

    <h1 class="page-title">Editar campanha</h1>
    <div class="card" style="max-width:820px;">
        <form method="POST" action="{{ route('campaigns.update', $campaign) }}">
            @csrf
            @method('PUT')
            @include('campaigns._form')
            <div class="actions" style="flex-wrap:wrap; gap:.5rem;">
                <button class="btn btn-primary" type="submit">Salvar alterações</button>
                <a class="btn btn-ghost" href="{{ route('campaigns.index') }}">Cancelar</a>
            </div>
            <p class="header-meta" style="margin-top:.75rem;">
                Status da campanha: <strong>{{ $status?->label() }}</strong>.
                @if($isFinished)
                    Campanha finalizada: o histórico permanece visível e novas operações de campo não devem usar esta campanha.
                @else
                    Para liberar o EXP Vendedor use <em>Ativar</em> (status precisa ser Ativa).
                @endif
            </p>
        </form>

        @can('update', $campaign)
            <div class="actions" style="flex-wrap:wrap; gap:.5rem; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
                @if($isDraft || $isPaused)
                    <form method="POST" action="{{ route('campaigns.activate', $campaign) }}">
                        @csrf
                        <button class="btn btn-primary" type="submit">Ativar</button>
                    </form>
                @endif

                @if(! $isFinished)
                    <form method="POST" action="{{ route('campaigns.finish', $campaign) }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">Finalizar campanha</button>
                    </form>
                @endif

                @if($canDeleteSafely)
                    <button class="btn btn-danger" type="button" id="open-delete-campaign">Excluir campanha</button>
                @endif
            </div>
        @endcan

        @if($hasOperationalHistory)
            <p class="header-meta" style="margin-top:1rem;" data-campaign-history-notice="1">
                Esta campanha possui histórico de operação e não pode ser excluída. Finalize a campanha para impedir novas operações sem perder os dados existentes.
            </p>
        @endif
    </div>

    @if($canDeleteSafely)
        <dialog id="delete-campaign-dialog" style="max-width:28rem; border:1px solid var(--border); border-radius:12px; padding:1.25rem; background:var(--card, #1b2030); color:inherit;">
            <form method="dialog" style="margin:0;">
                <h2 style="margin:0 0 .5rem; font-size:1.1rem;">Excluir campanha?</h2>
                <p class="header-meta" style="margin:0 0 1rem;">
                    Esta ação remove definitivamente a campanha e suas atribuições. Essa operação não poderá ser desfeita.
                </p>
                <div class="actions" style="justify-content:flex-end; gap:.5rem;">
                    <button class="btn btn-ghost" value="cancel">Cancelar</button>
                </div>
            </form>
            <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" style="margin-top:.5rem; display:flex; justify-content:flex-end;">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger" type="submit">Excluir campanha</button>
            </form>
        </dialog>
        <script>
            (function () {
                var dialog = document.getElementById('delete-campaign-dialog');
                var openBtn = document.getElementById('open-delete-campaign');
                if (!dialog || !openBtn) return;
                openBtn.addEventListener('click', function () {
                    if (typeof dialog.showModal === 'function') {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute('open', 'open');
                    }
                });
            })();
        </script>
    @endif
@endsection
