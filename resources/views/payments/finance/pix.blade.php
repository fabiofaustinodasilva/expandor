@extends('layouts.app')

@section('title', 'PIX — Fatura')

@section('content')
    <div class="card" style="max-width:640px;">
        <h1 class="page-title" style="margin-top:0;">Pagar com PIX</h1>
        <p><strong>Valor:</strong> R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</p>
        <p><strong>Vencimento:</strong> {{ optional($invoice->due_at)->format('d/m/Y') }}</p>
        <p><strong>Status:</strong> {{ $invoice->status->label() }} (pagamento só confirma após webhook)</p>

        @if(!empty($charge['qr_base64']))
            <img src="data:image/png;base64,{{ $charge['qr_base64'] }}" alt="QR Code PIX" style="max-width:240px; display:block; margin:1rem 0;">
        @endif

        @if(!empty($charge['qr_code']))
            <label for="pix_copy">PIX Copia e Cola</label>
            <textarea id="pix_copy" class="form-control" rows="4" readonly>{{ $charge['qr_code'] }}</textarea>
        @endif

        <p style="margin-top:1rem;"><a href="{{ route('company.finance.index') }}">← Voltar</a></p>
    </div>
@endsection
