@extends('layouts.platform')

@section('title', 'Feature Flags')

@section('content')
    <h1 class="page-title">Feature Flags</h1>
    <p class="header-meta">Catálogo global. Overrides são aplicados por empresa na ficha do cliente.</p>

    <div class="card">
        <table class="table">
            <thead>
            <tr>
                <th>Flag</th>
                <th>Chave</th>
                <th>Default</th>
                <th>Descrição</th>
            </tr>
            </thead>
            <tbody>
            @foreach($flags as $flag)
                <tr>
                    <td><strong>{{ $flag->name }}</strong></td>
                    <td>{{ $flag->key }}</td>
                    <td>{{ $flag->default_enabled ? 'ON' : 'OFF' }}</td>
                    <td>{{ $flag->description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
