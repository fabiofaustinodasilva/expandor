@extends('layouts.platform')

@section('title', 'Marketplace — Configuração')

@section('content')
    <div style="margin-bottom:1rem;">
        <a href="{{ route('platform.dashboard') }}" class="header-meta" style="text-decoration:none;">← Dashboard</a>
    </div>

    <div style="display:flex; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; flex-wrap:wrap; align-items:flex-start;">
        <div>
            <h1 class="page-title" style="margin:0;">Configuração do Marketplace</h1>
            <div class="header-meta">Identidade visual, SEO e integrações da landing page pública.</div>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <form method="POST" action="{{ route('platform.marketplace.settings.restore') }}"
                  onsubmit="return confirm('Restaurar o conteúdo padrão do Marketplace? Textos de seções, FAQ e depoimentos serão substituídos. Logos e mídias enviadas serão preservados.');">
                @csrf
                <button type="submit" class="btn btn-ghost">Restaurar Conteúdo Padrão</button>
            </form>
            <a class="btn btn-ghost" href="{{ route('platform.marketplace.preview') }}" target="_blank" rel="noopener">Visualizar preview</a>
        </div>
    </div>

    <form method="POST" action="{{ route('platform.marketplace.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-2">
            <div class="card">
                <h2 style="margin-top:0;">Marca e conteúdo</h2>

                <x-media-upload
                    name="logo"
                    label="Logo"
                    :current-url="$settings->mediaUrl($settings->logo)"
                    remove-name="remove_logo"
                    accept="image/jpeg,image/png,image/webp"
                    hint="PNG, JPG ou WEBP até 5MB."
                />

                <x-media-upload
                    name="favicon"
                    label="Favicon"
                    :current-url="$settings->mediaUrl($settings->favicon)"
                    remove-name="remove_favicon"
                    accept="image/png,image/x-icon,image/webp,.ico"
                    hint="Ícone exibido na aba do navegador. Até 1MB."
                    preview-height="40px"
                />

                <x-media-upload
                    name="hero_image"
                    label="Imagem do hero"
                    :current-url="$settings->mediaUrl($settings->hero_image)"
                    remove-name="remove_hero_image"
                    accept="image/jpeg,image/png,image/webp"
                    hint="Imagem principal da seção hero. Até 8MB."
                    preview-height="96px"
                />

                <x-media-upload
                    name="og_image"
                    label="Imagem Open Graph (social)"
                    :current-url="$settings->mediaUrl($settings->og_image)"
                    remove-name="remove_og_image"
                    accept="image/jpeg,image/png,image/webp"
                    hint="Imagem usada em compartilhamentos (WhatsApp, LinkedIn, etc.). Até 8MB."
                    preview-height="96px"
                />

                <div class="form-group">
                    <label for="hero_video">Vídeo do hero (URL)</label>
                    <input class="form-control" id="hero_video" name="hero_video" type="text"
                           value="{{ old('hero_video', $settings->hero_video) }}" placeholder="https://...">
                    <div class="header-meta" style="margin-top:.35rem;">URL de vídeo MP4 ou link externo.</div>
                </div>

                <div class="form-group">
                    <label for="demo_video_url">Vídeo demonstração (YouTube, Vimeo ou MP4)</label>
                    <input class="form-control" id="demo_video_url" name="demo_video_url" type="text"
                           value="{{ old('demo_video_url', $settings->demo_video_url) }}" placeholder="https://youtube.com/watch?v=...">
                </div>

                <div class="form-group">
                    <label for="title">Título</label>
                    <input class="form-control" id="title" name="title" maxlength="180"
                           value="{{ old('title', $settings->title) }}">
                </div>

                <div class="form-group">
                    <label for="subtitle">Subtítulo</label>
                    <input class="form-control" id="subtitle" name="subtitle" maxlength="255"
                           value="{{ old('subtitle', $settings->subtitle) }}">
                </div>

                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $settings->description) }}</textarea>
                </div>
            </div>

            <div class="card" style="align-self:start;">
                <h2 style="margin-top:0;">Cores</h2>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="primary_color">Cor primária</label>
                        <input class="form-control" id="primary_color" name="primary_color" type="color"
                               value="{{ old('primary_color', $settings->primary_color ?: '#3B82F6') }}">
                    </div>
                    <div class="form-group">
                        <label for="secondary_color">Cor secundária</label>
                        <input class="form-control" id="secondary_color" name="secondary_color" type="color"
                               value="{{ old('secondary_color', $settings->secondary_color ?: '#0F172A') }}">
                    </div>
                    <div class="form-group">
                        <label for="background_color">Cor de fundo</label>
                        <input class="form-control" id="background_color" name="background_color" type="color"
                               value="{{ old('background_color', $settings->background_color ?: '#0B1220') }}">
                    </div>
                    <div class="form-group">
                        <label for="button_color">Cor dos botões</label>
                        <input class="form-control" id="button_color" name="button_color" type="color"
                               value="{{ old('button_color', $settings->button_color ?: '#F59E0B') }}">
                    </div>
                </div>

                <h2>WhatsApp</h2>
                <input type="hidden" name="whatsapp_enabled" value="0">
                <div class="form-group">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $settings->whatsapp_enabled))>
                        Ativar botão flutuante
                    </label>
                </div>
                <div class="form-group">
                    <label for="whatsapp_number">Número (com DDI)</label>
                    <input class="form-control" id="whatsapp_number" name="whatsapp_number" maxlength="40"
                           value="{{ old('whatsapp_number', $settings->whatsapp_number) }}" placeholder="5562999999999">
                </div>
                <div class="form-group">
                    <label for="whatsapp_message">Mensagem padrão</label>
                    <input class="form-control" id="whatsapp_message" name="whatsapp_message" maxlength="255"
                           value="{{ old('whatsapp_message', $settings->whatsapp_message) }}">
                </div>

                @php
                    $waPreview = $settings->whatsappLink();
                    $waDigits = $settings->whatsappDigits();
                @endphp
                <div class="card" style="background:rgba(37,211,102,.08); border:1px solid rgba(37,211,102,.25); margin-top:.75rem;">
                    <h3 style="margin:0 0 .5rem;">Preview WhatsApp</h3>
                    @if($settings->hasWhatsAppButton() && $waPreview)
                        <div class="header-meta">✔ WhatsApp ativo</div>
                        <div style="margin-top:.35rem;">Número: <strong>+{{ $waDigits }}</strong></div>
                        <div style="margin-top:.35rem;">Mensagem padrão: <em>{{ $settings->whatsapp_message ?: 'Olá! Gostaria de conhecer o Expandor.' }}</em></div>
                        <div style="margin-top:.75rem;">
                            <a class="btn btn-primary" href="{{ $waPreview }}" target="_blank" rel="noopener">Abrir conversa</a>
                        </div>
                    @else
                        <div class="header-meta">WhatsApp inativo — ative o botão e informe o número para exibir na Landing.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-2" style="margin-top:1rem;">
            <div class="card">
                <h2 style="margin-top:0;">Redes sociais</h2>

                <div class="form-group">
                    <input type="hidden" name="instagram_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="instagram_enabled" value="1" @checked(old('instagram_enabled', $settings->instagram_enabled))>
                        Instagram
                    </label>
                    <input class="form-control" name="instagram_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('instagram_url', $settings->instagram_url) }}" placeholder="https://instagram.com/...">
                </div>

                <div class="form-group">
                    <input type="hidden" name="facebook_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="facebook_enabled" value="1" @checked(old('facebook_enabled', $settings->facebook_enabled))>
                        Facebook
                    </label>
                    <input class="form-control" name="facebook_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('facebook_url', $settings->facebook_url) }}" placeholder="https://facebook.com/...">
                </div>

                <div class="form-group">
                    <input type="hidden" name="linkedin_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="linkedin_enabled" value="1" @checked(old('linkedin_enabled', $settings->linkedin_enabled))>
                        LinkedIn
                    </label>
                    <input class="form-control" name="linkedin_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('linkedin_url', $settings->linkedin_url) }}" placeholder="https://linkedin.com/...">
                </div>

                <div class="form-group">
                    <input type="hidden" name="youtube_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="youtube_enabled" value="1" @checked(old('youtube_enabled', $settings->youtube_enabled))>
                        YouTube
                    </label>
                    <input class="form-control" name="youtube_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('youtube_url', $settings->youtube_url) }}" placeholder="https://youtube.com/...">
                </div>

                <div class="form-group">
                    <input type="hidden" name="tiktok_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="tiktok_enabled" value="1" @checked(old('tiktok_enabled', $settings->tiktok_enabled))>
                        TikTok
                    </label>
                    <input class="form-control" name="tiktok_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('tiktok_url', $settings->tiktok_url) }}" placeholder="https://tiktok.com/@...">
                </div>

                <div class="form-group">
                    <input type="hidden" name="twitter_enabled" value="0">
                    <label style="display:inline-flex; gap:.45rem; align-items:center;">
                        <input type="checkbox" name="twitter_enabled" value="1" @checked(old('twitter_enabled', $settings->twitter_enabled))>
                        X (Twitter)
                    </label>
                    <input class="form-control" name="twitter_url" type="url" style="margin-top:.5rem;"
                           value="{{ old('twitter_url', $settings->twitter_url) }}" placeholder="https://x.com/...">
                </div>

                @php $socialPreview = $settings->socialNetworks(); @endphp
                <div class="card" style="margin-top:.75rem; background:rgba(59,130,246,.08); border:1px solid rgba(59,130,246,.25);">
                    <h3 style="margin:0 0 .5rem;">Preview redes</h3>
                    @if(count($socialPreview) > 0)
                        <ul style="margin:0; padding-left:1.1rem;">
                            @foreach($socialPreview as $network)
                                <li style="margin-bottom:.35rem;">
                                    ✔ {{ $network['label'] }} —
                                    <a href="{{ $network['url'] }}" target="_blank" rel="noopener">{{ $network['url'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="header-meta">Nenhuma rede ativa. Marque a rede e informe a URL para exibir no rodapé da Landing.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <h2 style="margin-top:0;">SEO</h2>

                <div class="form-group">
                    <label for="seo_title">Título SEO</label>
                    <input class="form-control" id="seo_title" name="seo_title" maxlength="180"
                           value="{{ old('seo_title', $settings->seo_title) }}">
                </div>

                <div class="form-group">
                    <label for="seo_description">Descrição SEO</label>
                    <textarea class="form-control" id="seo_description" name="seo_description" rows="3">{{ old('seo_description', $settings->seo_description) }}</textarea>
                </div>

                <div class="form-group">
                    <label for="seo_keywords">Palavras-chave</label>
                    <input class="form-control" id="seo_keywords" name="seo_keywords" maxlength="255"
                           value="{{ old('seo_keywords', $settings->seo_keywords) }}" placeholder="crm, vendas, saas">
                </div>

                <div class="actions" style="margin-top:1.25rem;">
                    <button class="btn btn-primary" type="submit">Salvar configuração</button>
                </div>
            </div>
        </div>

        @php
            $conversion = old('conversion_content', $settings->conversion_content ?? []);
            $howLines = collect($conversion['how_it_works'] ?? [])
                ->map(fn ($s) => trim(($s['title'] ?? '').(filled($s['description'] ?? null) ? ' | '.$s['description'] : '')))
                ->filter()
                ->implode("\n");
            $beforeText = implode("\n", $conversion['before_after']['before'] ?? []);
            $afterText = implode("\n", $conversion['before_after']['after'] ?? []);
        @endphp

        <div class="card" style="margin-top:1rem;">
            <h2 style="margin-top:0;">Conteúdo de conversão (Premium)</h2>
            <div class="header-meta" style="margin-bottom:1rem;">
                Personalize prova social, jornada e antes/depois. Deixe em branco para usar o conteúdo padrão.
                Imagens da plataforma: use <a href="{{ route('platform.marketplace.media.index') }}">Marketplace → Mídia</a> (galeria ativa alimenta o carrossel).
            </div>

            <div class="form-group">
                <label for="social_proof_title">Título da prova social</label>
                <input class="form-control" id="social_proof_title" name="conversion_content[social_proof_title]" maxlength="255"
                       value="{{ $conversion['social_proof_title'] ?? '' }}"
                       placeholder="Empresas organizam suas operações comerciais com Expandor">
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="before_text">Antes (um item por linha)</label>
                    <textarea class="form-control" id="before_text" name="conversion_content[before_text]" rows="5"
                              placeholder="vendedores sem acompanhamento">{{ $beforeText }}</textarea>
                </div>
                <div class="form-group">
                    <label for="after_text">Depois (um item por linha)</label>
                    <textarea class="form-control" id="after_text" name="conversion_content[after_text]" rows="5"
                              placeholder="equipe organizada">{{ $afterText }}</textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="how_it_works_text">Como funciona (um passo por linha: Título | Descrição)</label>
                <textarea class="form-control" id="how_it_works_text" name="conversion_content[how_it_works_text]" rows="7"
                          placeholder="Cadastre sua equipe | Convide vendedores...">{{ $howLines }}</textarea>
            </div>

            <div class="grid grid-2">
                <div class="form-group">
                    <label for="metric_sellers">Override métrica — vendedores</label>
                    <input class="form-control" id="metric_sellers" name="conversion_content[metrics][sellers]" type="number" min="0"
                           value="{{ $conversion['metrics']['sellers'] ?? '' }}" placeholder="Automático do sistema">
                </div>
                <div class="form-group">
                    <label for="metric_customers">Override métrica — clientes</label>
                    <input class="form-control" id="metric_customers" name="conversion_content[metrics][customers]" type="number" min="0"
                           value="{{ $conversion['metrics']['customers'] ?? '' }}" placeholder="Automático do sistema">
                </div>
                <div class="form-group">
                    <label for="metric_visits">Override métrica — visitas</label>
                    <input class="form-control" id="metric_visits" name="conversion_content[metrics][visits]" type="number" min="0"
                           value="{{ $conversion['metrics']['visits'] ?? '' }}" placeholder="Automático do sistema">
                </div>
                <div class="form-group">
                    <label for="metric_campaigns">Override métrica — campanhas</label>
                    <input class="form-control" id="metric_campaigns" name="conversion_content[metrics][campaigns]" type="number" min="0"
                           value="{{ $conversion['metrics']['campaigns'] ?? '' }}" placeholder="Automático do sistema">
                </div>
            </div>

            <div class="actions" style="margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Salvar configuração</button>
            </div>
        </div>
    </form>
@endsection
