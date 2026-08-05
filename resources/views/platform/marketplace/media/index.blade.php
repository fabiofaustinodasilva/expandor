@extends('layouts.platform')

@section('title', 'Site — Mídias')

@section('content')
    <div style="margin-bottom:1.1rem;">
        <h1 class="page-title" style="margin:0;">Mídias e conteúdo auxiliar</h1>
        <div class="header-meta">Galeria, vídeos, depoimentos e perguntas frequentes da landing.</div>
    </div>

    <div class="grid grid-2">
        {{-- Card 1: Adicionar mídia --}}
        <div class="card">
            <h2 style="margin-top:0;">Adicionar mídia</h2>
            <form method="POST" action="{{ route('platform.marketplace.media.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="media_type">Tipo</label>
                    <select class="form-control" id="media_type" name="type" required>
                        <option value="image" @selected(old('type') === 'image')>Imagem</option>
                        <option value="video" @selected(old('type') === 'video')>Vídeo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="media_title">Título</label>
                    <input class="form-control" id="media_title" name="title" maxlength="180" value="{{ old('title') }}">
                </div>

                <div class="form-group">
                    <label for="media_caption">Legenda</label>
                    <input class="form-control" id="media_caption" name="caption" maxlength="500" value="{{ old('caption') }}">
                </div>

                <div class="form-group">
                    <label for="external_url">URL externa</label>
                    <input class="form-control" id="external_url" name="external_url" type="url" maxlength="500"
                           value="{{ old('external_url') }}" placeholder="https://youtube.com/...">
                    <div class="header-meta" style="margin-top:.35rem;">Obrigatório para vídeos embed. Opcional para imagens.</div>
                </div>

                <div class="form-group">
                    <label for="media_file">Arquivo</label>
                    <input class="form-control" id="media_file" name="file" type="file">
                </div>

                <div class="form-group">
                    <label for="media_thumbnail">Miniatura (vídeo)</label>
                    <input class="form-control" id="media_thumbnail" name="thumbnail" type="file" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="media_order">Ordem</label>
                    <input class="form-control" id="media_order" name="order" type="number" min="0" value="{{ old('order', 0) }}">
                </div>

                <div class="form-group">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                        Ativo
                    </label>
                </div>

                <button class="btn btn-primary" type="submit">Adicionar mídia</button>
            </form>
        </div>

        {{-- Card 2: Lista de mídias --}}
        <div class="card">
            <h2 style="margin-top:0;">Mídias cadastradas</h2>
            @if($items->isEmpty())
                <p class="header-meta">Nenhuma mídia cadastrada.</p>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Ordem</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td><span class="badge">{{ $item->type }}</span></td>
                            <td>{{ $item->title ?: '—' }}</td>
                            <td>{{ $item->order }}</td>
                            <td>
                                <form method="POST" action="{{ route('platform.marketplace.media.destroy', $item) }}"
                                      onsubmit="return confirm('Remover esta mídia?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Card 3: Depoimentos --}}
        <div class="card">
            <h2 style="margin-top:0;">Adicionar depoimento</h2>
            <form method="POST" action="{{ route('platform.marketplace.media.testimonials.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="t_name">Nome</label>
                    <input class="form-control" id="t_name" name="name" maxlength="120" required value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label for="t_company">Empresa</label>
                    <input class="form-control" id="t_company" name="company" maxlength="120" value="{{ old('company') }}">
                </div>

                <div class="form-group">
                    <label for="t_text">Depoimento</label>
                    <textarea class="form-control" id="t_text" name="text" rows="3" required maxlength="2000">{{ old('text') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="t_rating">Avaliação (1–5)</label>
                    <input class="form-control" id="t_rating" name="rating" type="number" min="1" max="5" value="{{ old('rating', 5) }}">
                </div>

                <div class="form-group">
                    <label for="t_avatar">Avatar</label>
                    <input class="form-control" id="t_avatar" name="avatar" type="file" accept="image/*">
                </div>

                <div class="form-group">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                        Ativo
                    </label>
                </div>

                <button class="btn btn-primary" type="submit">Adicionar depoimento</button>
            </form>

            @if($testimonials->isNotEmpty())
                <h3 style="margin-top:1.5rem;">Depoimentos cadastrados</h3>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Empresa</th>
                        <th>Nota</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($testimonials as $t)
                        <tr>
                            <td>{{ $t->name }}</td>
                            <td>{{ $t->company ?: '—' }}</td>
                            <td>{{ $t->rating ?: '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('platform.marketplace.media.testimonials.destroy', $t) }}"
                                      onsubmit="return confirm('Remover este depoimento?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Card 4: FAQ --}}
        <div class="card">
            <h2 style="margin-top:0;">Adicionar FAQ</h2>
            <form method="POST" action="{{ route('platform.marketplace.media.faqs.store') }}">
                @csrf

                <div class="form-group">
                    <label for="faq_question">Pergunta</label>
                    <input class="form-control" id="faq_question" name="question" maxlength="255" required value="{{ old('question') }}">
                </div>

                <div class="form-group">
                    <label for="faq_answer">Resposta</label>
                    <textarea class="form-control" id="faq_answer" name="answer" rows="4" required maxlength="5000">{{ old('answer') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="faq_order">Ordem</label>
                    <input class="form-control" id="faq_order" name="order" type="number" min="0" value="{{ old('order', 0) }}">
                </div>

                <div class="form-group">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="active" value="1" @checked(old('active', true))>
                        Ativo
                    </label>
                </div>

                <button class="btn btn-primary" type="submit">Adicionar FAQ</button>
            </form>

            @if($faqs->isNotEmpty())
                <h3 style="margin-top:1.5rem;">FAQs cadastrados</h3>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Pergunta</th>
                        <th>Ordem</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($faqs as $faq)
                        <tr>
                            <td>{{ Str::limit($faq->question, 60) }}</td>
                            <td>{{ $faq->order }}</td>
                            <td>
                                <form method="POST" action="{{ route('platform.marketplace.media.faqs.destroy', $faq) }}"
                                      onsubmit="return confirm('Remover este FAQ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost" type="submit">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection
