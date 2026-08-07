{{-- Sprint 8.2.2 — Avatar circular com inicial, para usuários/entidades --}}
@props([
    'name' => '',
    'size' => 'md',
])
@php
    $initial = mb_strtoupper(mb_substr(trim((string) $name), 0, 1)) ?: '?';
    $sizeClass = match ($size) {
        'sm' => 'client-avatar--sm',
        'lg' => 'client-avatar--lg',
        default => 'client-avatar--md',
    };
@endphp
<div {{ $attributes->merge(['class' => 'client-avatar '.$sizeClass]) }} title="{{ $name }}" aria-hidden="true">
    {{ $initial }}
</div>
