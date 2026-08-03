@props([
    'type' => 'success',
])
@php
    $class = $type === 'error' ? 'alert alert-error' : 'alert alert-success';
@endphp
<div {{ $attributes->merge(['class' => $class, 'role' => 'status']) }}>{{ $slot }}</div>
