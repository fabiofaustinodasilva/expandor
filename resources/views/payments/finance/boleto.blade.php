@extends('layouts.app')

@section('title', 'Boleto — Fatura')

@section('content')
    <div class="card" style="max-width:640px;">
        <h1 class="page-title" style="margin-top:0;">Boleto</h1>
        <p><strong>Valor:</strong> R$ {{ number_format((float) $invoice->amount_due, 2, ',', '.') }}</p>
        <p><strong>Vencimento:</strong> {{ optional($invoice->due_at)->format('d/m/Y') }}</p>
        <p><strong>Status:</strong> {{ $invoice->status->label() }} (confirmação via webhook)</p>

        @if(!empty($charge['digitable_line']))
            <p><strong>Linha digitável:</strong></p>
            <textarea class="form-control" rows="2" readonly>{{ $charge['digitable_line'] }}</textarea>
        @endif

        @if(!empty($charge['boleto_url']))
            <p style="margin-top:1rem;">
                <a class="btn btn-primary" href="{{ $charge['boleto_url'] }}" target="_blank" rel="noopener">Abrir boleto</a>
            </p>
        @endif

        <form method="POST" action="{{ route('company.finance.invoice.second-copy', $invoice) }}" style="margin-top:1rem;">
            @csrf
            <input type="hidden" name="method" value="boleto">
            <button class="btn btn-ghost" type="submit">Segunda via</button>
        </form>

        <p style="margin-top:1rem;"><a href="{{ route('company.finance.index') }}">← Voltar</a></p>
    </div>
@endsection
