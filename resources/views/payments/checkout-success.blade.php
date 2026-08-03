@extends('layouts.guest')

@section('title', 'Pagamento recebido')

@section('content')
    <div class="card" style="max-width:560px;">
        <h1 style="margin-top:0;">Pagamento em processamento</h1>
        <p class="muted">
            @if($session)
                Recebemos sua solicitação para <strong>{{ $session->company_name }}</strong>.
                Assim que o gateway confirmar o pagamento, sua empresa será provisionada automaticamente
                e você receberá o login por e-mail ({{ $session->buyer_email }}).
            @else
                Assim que o pagamento for confirmado, enviaremos o acesso por e-mail.
            @endif
        </p>
        <a class="btn" href="{{ route('login') }}">Ir para o login</a>
    </div>
@endsection
