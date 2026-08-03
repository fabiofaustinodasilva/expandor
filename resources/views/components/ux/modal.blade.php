@props([
    'id' => 'ux-modal',
    'title' => 'Confirmar',
])
<div id="{{ $id }}" class="ux-modal" hidden>
    <div class="ux-modal__backdrop" data-ux-modal-close="{{ $id }}"></div>
    <div class="ux-modal__panel card" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <h2 id="{{ $id }}-title" style="margin:0 0 .75rem; font-size:1.05rem;">{{ $title }}</h2>
        <div>{{ $slot }}</div>
    </div>
</div>
