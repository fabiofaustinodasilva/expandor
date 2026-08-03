@extends('layouts.app')

@section('title', 'Nova pergunta IA')

@section('content')
    <div style="margin-bottom:1rem;">
        <h1 class="page-title" style="margin:0;">Nova pergunta</h1>
        <div class="header-meta">A resposta é apenas uma sugestão. Nenhuma ação será executada automaticamente.</div>
    </div>

    <div class="card" style="max-width:720px;">
        <form method="POST" action="{{ route('ai.conversations.store') }}">
            @csrf

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="context_type">Contexto</label>
                <select class="form-control" name="context_type" id="context_type" required>
                    @foreach($contextTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('context_type', 'sales') === $type->value)>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
                @error('context_type')
                    <div class="header-meta" style="color:#b91c1c;">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label for="question">Pergunta</label>
                <textarea class="form-control" name="question" id="question" rows="5" required maxlength="4000">{{ old('question') }}</textarea>
                @error('question')
                    <div class="header-meta" style="color:#b91c1c;">{{ $message }}</div>
                @enderror
            </div>

            <div class="actions">
                <a class="btn btn-ghost" href="{{ route('ai.conversations.index') }}">Cancelar</a>
                <button class="btn btn-primary" type="submit">Pedir sugestão</button>
            </div>
        </form>
    </div>
@endsection
