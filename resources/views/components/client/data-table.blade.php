{{-- Sprint 8.2.2 — Wrapper simples de tabela para telas da área do cliente --}}
@props([
    'headers' => [],
    'zebra' => false,
])
<div {{ $attributes->merge(['class' => 'client-data-table'.($zebra ? ' client-data-table--zebra' : '')]) }}>
    <table class="table">
        @if(!empty($headers))
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
