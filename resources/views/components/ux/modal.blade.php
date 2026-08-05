@props([
    'id' => 'ux-modal',
    'title' => 'Confirmar',
])
<div id="{{ $id }}" class="ux-modal" hidden>
    <div class="ux-modal__backdrop" data-ux-modal-close="{{ $id }}"></div>
    <div class="ux-modal__panel card" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <div class="card-header">
            <h2 id="{{ $id }}-title" class="section-title">{{ $title }}</h2>
            <button type="button" class="btn btn-ghost" data-ux-modal-close="{{ $id }}" aria-label="Fechar">Fechar</button>
        </div>
        <div>{{ $slot }}</div>
    </div>
</div>
