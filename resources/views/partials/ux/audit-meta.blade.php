@php
    $createdBy = $createdBy ?? null;
    $updatedAt = $updatedAt ?? null;
    $responsible = $responsible ?? null;
@endphp
@if($createdBy || $updatedAt || $responsible)
    <p class="header-meta ux-audit-meta" style="margin-top:1rem;">
        @if($createdBy)
            <span>Criado por {{ $createdBy }}</span>
        @endif
        @if($createdBy && ($updatedAt || $responsible))
            <span> · </span>
        @endif
        @if($updatedAt)
            <span>Última atualização {{ $updatedAt }}</span>
        @endif
        @if($updatedAt && $responsible)
            <span> · </span>
        @endif
        @if($responsible)
            <span>Responsável {{ $responsible }}</span>
        @endif
    </p>
@endif
