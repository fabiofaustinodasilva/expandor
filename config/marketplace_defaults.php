<?php

/**
 * Conteúdo padrão público do Expandor.
 * Posicionamento: inteligência comercial e vendas externas para provedores.
 * O CMS sobrescreve estes valores; a landing não deve hardcodar textos.
 */
return [

    'brand' => 'Expandor',

    'settings' => [
        'title' => 'Expandor',
        'subtitle' => 'A plataforma de inteligência comercial e vendas externas para provedores que querem crescer com controle, processo e uma equipe de campo mais eficiente.',
        'description' => 'Sua equipe está na rua. Agora você pode enxergar onde estão as oportunidades, acompanhar os retornos e transformar cada visita em uma operação comercial organizada.',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#0F172A',
        'background_color' => '#0B1220',
        'button_color' => '#F59E0B',
        'whatsapp_message' => 'Olá! Quero agendar uma demonstração do Expandor para a operação comercial do meu provedor.',
        'seo_title' => 'Expandor — Inteligência comercial e vendas externas para provedores',
        'seo_description' => 'Plataforma de inteligência comercial para provedores de internet: mapa comercial, operação porta a porta, gestão de vendedores externos e conversão de visitas em vendas.',
        'seo_keywords' => 'CRM para provedores, vendas externas para provedores, gestão de vendedores externos, mapa comercial, porta a porta, provedor de internet, operação de campo',
    ],

    'ui' => [
        'menu' => 'Menu',
        'login' => 'Entrar',
        'already_have_account' => 'Já tenho conta',
        'recommended' => 'Mais escolhido',
        'per_month' => '/mês',
        'subscribe_now' => 'Agendar demonstração',
        'start_free_trial' => 'Agendar demonstração',
        'request_demo' => 'Agendar demonstração',
        'see_in_action' => 'Ver o Expandor em ação',
        'before_label' => 'Antes',
        'after_label' => 'Depois',
        'carousel_prev' => 'Anterior',
        'carousel_next' => 'Próximo',
        'play_demo' => 'Ver demonstração',
        'preview_banner' => 'Modo preview — alterações já salvas',
        'back_home' => 'Voltar ao início',
        'challenge' => 'Desafio',
        'solution' => 'Solução',
        'result' => 'Resultado',
        'success_stories' => 'Histórias de quem já usa',
        'success_stories_subtitle' => 'Operações de campo que organizaram território, equipe e vendas com o Expandor.',
        'know_expandor' => 'Quero conhecer o Expandor',
        'price_on_request' => 'Sob consulta',
    ],

    'nav' => [
        ['label' => 'Início', 'href' => '#inicio'],
        ['label' => 'Operação de campo', 'href' => '#produto'],
        ['label' => 'Como funciona', 'href' => '#demonstracao'],
        ['label' => 'Para o gestor', 'href' => '#gestor'],
        ['label' => 'Planos', 'href' => '#planos'],
        ['label' => 'Dúvidas', 'href' => '#faq'],
    ],

    'nav_actions' => [
        [
            'label' => 'Agendar demonstração',
            'href' => '#demo',
            'style' => 'primary',
        ],
        [
            'label' => 'Entrar',
            'href' => 'login',
            'style' => 'ghost',
            'route' => true,
        ],
    ],

    'footer' => [
        'text' => 'Inteligência comercial e operação de vendas externas para provedores que querem transformar território em vendas.',
        'rights' => 'Todos os direitos reservados.',
        'links' => [
            ['label' => 'Início', 'href' => '#inicio'],
            ['label' => 'Planos', 'href' => '#planos'],
            ['label' => 'Dúvidas', 'href' => '#faq'],
            ['label' => 'Agendar demonstração', 'href' => '#demo'],
        ],
    ],

    'section_order' => [
        'hero',
        'showcase',
        'video',
        'about',
        'benefits',
        'how_it_works',
        'before_after',
        'features',
        'social_proof',
        'segments',
        'testimonials',
        'plans',
        'faq',
        'cta',
    ],

    'sections' => [
        'hero' => [
            'title' => 'Transforme território em vendas.',
            'subtitle' => 'A plataforma de inteligência comercial e vendas externas para provedores que querem crescer com controle, processo e uma equipe de campo mais eficiente.',
            'description' => 'Sua equipe está na rua. Agora você pode enxergar onde estão as oportunidades, acompanhar os retornos e transformar cada visita em uma operação comercial organizada.',
            'button_text' => 'Agendar demonstração',
            'button_url' => '#demo',
            'button_text_secondary' => 'Ver o Expandor em ação',
            'button_url_secondary' => '#produto',
            'image' => '/images/marketplace/product/hero-mapa.png',
            'image_alt' => 'Mapa operacional do Expandor com oportunidades comerciais distribuídas no território',
            'image_shot' => 'hero-mapa',
            'video' => null,
            'order' => 1,
        ],
        'showcase' => [
            'title' => 'O vendedor leva a operação na mão.',
            'subtitle' => 'Telas da operação de campo',
            'description' => 'Mapa, agenda, clientes, produtos e vendas em uma experiência criada para a rotina de campo.',
            'order' => 2,
        ],
        'how_it_works' => [
            'title' => 'Como a operação se conecta',
            'subtitle' => 'Do território ao escritório',
            'description' => null,
            'order' => 3,
        ],
        'before_after' => [
            'title' => 'Menos bagunça. Mais resultado.',
            'subtitle' => 'O que muda no dia a dia da equipe de campo',
            'description' => null,
            'order' => 4,
        ],
        'benefits' => [
            'title' => 'O que o provedor ganha',
            'subtitle' => 'Controle, processo e conversão — sem lista infinita de features.',
            'description' => null,
            'order' => 5,
        ],
        'segments' => [
            'title' => 'Feito para a operação comercial de provedores',
            'subtitle' => 'Vendas externas, porta a porta e gestão territorial',
            'description' => null,
            'order' => 6,
        ],
        'about' => [
            'title' => 'Não é apenas um mapa.',
            'subtitle' => 'Memória comercial do território',
            'description' => 'Cada ponto pode carregar histórico, cliente, atendimento e próxima ação. Sua equipe deixa de visitar casas no escuro e passa a trabalhar com contexto.',
            'image' => '/images/marketplace/product/inteligencia-ponto.png',
            'image_alt' => 'Ficha de inteligência de um ponto no mapa do Expandor, com histórico comercial e próxima ação',
            'image_shot' => 'inteligencia-ponto',
            'order' => 99,
        ],
        'features' => [
            'title' => 'Tudo que a operação de campo precisa',
            'subtitle' => 'Recursos reais para provedores que vendem na rua',
            'description' => null,
            'features' => [
                ['title' => 'Equipe sob controle', 'description' => 'Acompanhe visitas, retornos e vendas da operação sem perseguir a equipe no WhatsApp.', 'icon' => 'users'],
                ['title' => 'Visitas que não se perdem', 'description' => 'Registre cada atendimento e transforme “volta amanhã” em retorno acompanhável.', 'icon' => 'map-pin'],
                ['title' => 'Clientes organizados', 'description' => 'Histórico claro de cada porta, cada conversa e cada oportunidade.', 'icon' => 'users'],
                ['title' => 'Mapa operacional', 'description' => 'Veja território, pontos e oportunidades com contexto comercial.', 'icon' => 'map'],
                ['title' => 'Campanhas por região', 'description' => 'Monte campanhas com meta, prazo e área — e acompanhe o resultado.', 'icon' => 'flag'],
                ['title' => 'Agenda do dia', 'description' => 'Organize a rota e os retornos no EXP Vendedor, sem planilha.', 'icon' => 'calendar'],
                ['title' => 'Produtos na palma da mão', 'description' => 'Apresente planos e ofertas com clareza na porta do cliente.', 'icon' => 'target'],
                ['title' => 'Resultados e comissões', 'description' => 'O vendedor vê a própria evolução; o escritório recebe a venda organizada.', 'icon' => 'chart'],
            ],
            'order' => 7,
        ],
        'social_proof' => [
            'title' => 'Construído a partir da operação real de vendas em campo.',
            'subtitle' => 'Desenvolvido a partir de problemas reais encontrados na rotina comercial de provedores.',
            'description' => null,
            'order' => 8,
        ],
        'video' => [
            'title' => 'Da rua ao fechamento. Tudo conectado.',
            'subtitle' => 'Veja o Expandor funcionando',
            'description' => 'Veja como uma oportunidade nasce no território, recebe atendimento, vira retorno ou venda e chega organizada ao escritório.',
            'image' => '/images/marketplace/product/hero-mapa.png',
            'video' => null,
            'order' => 9,
        ],
        'testimonials' => [
            'title' => 'Quem já usa recomenda',
            'subtitle' => 'Depoimentos de gestores de equipes de campo',
            'description' => null,
            'order' => 10,
        ],
        'plans' => [
            'title' => 'Todo o poder do Expandor. Escolha pelo tamanho da sua equipe.',
            'subtitle' => 'Planos comerciais',
            'description' => 'Os três planos principais incluem o mesmo conjunto de recursos. A diferença é o tamanho da operação.',
            'order' => 11,
        ],
        'faq' => [
            'title' => 'Dúvidas frequentes',
            'subtitle' => 'Respostas diretas para donos e gestores de provedores',
            'description' => null,
            'order' => 12,
        ],
        'cta' => [
            'title' => 'Quer ver funcionando na sua operação?',
            'subtitle' => 'Agende uma demonstração',
            'description' => 'Agende uma demonstração do Expandor e veja mapa, EXP Vendedor, retornos e vendas no contexto do seu provedor.',
            'button_text' => 'Agendar demonstração',
            'button_url' => '#demo',
            'order' => 13,
        ],
    ],

    'showcase' => [
        ['title' => 'Mapa', 'image' => '/images/marketplace/product/exp-mapa.png', 'shot' => 'exp-mapa'],
        ['title' => 'Agenda', 'image' => '/images/marketplace/product/exp-agenda.png', 'shot' => 'exp-agenda'],
        ['title' => 'Produtos', 'image' => '/images/marketplace/product/exp-produtos.png', 'shot' => 'exp-produtos'],
        ['title' => 'Venda', 'image' => '/images/marketplace/product/exp-venda-realizada.png', 'shot' => 'exp-venda-realizada'],
        ['title' => 'Resultados', 'image' => '/images/marketplace/product/exp-resultado.png', 'shot' => 'exp-resultado'],
        ['title' => 'Comissão', 'image' => '/images/marketplace/product/exp-comissao.png', 'shot' => 'exp-comissao'],
    ],

    'field_ops' => [
        [
            'key' => 'mapa',
            'label' => 'Mapa',
            'copy' => 'Cada imóvel vira uma oportunidade acompanhável.',
            'shot' => 'exp-mapa',
            'alt' => 'Mapa do EXP Vendedor com imóveis e oportunidades no território',
        ],
        [
            'key' => 'agenda',
            'label' => 'Agenda',
            'copy' => 'Nunca mais perca um “volta amanhã”.',
            'shot' => 'exp-agenda',
            'alt' => 'Agenda do EXP Vendedor com retornos de clientes',
        ],
        [
            'key' => 'produtos',
            'label' => 'Produtos',
            'copy' => 'Apresente seus planos de forma profissional na porta do cliente.',
            'shot' => 'exp-produtos',
            'alt' => 'Catálogo de produtos do EXP Vendedor para apresentação em campo',
        ],
        [
            'key' => 'venda',
            'label' => 'Venda',
            'copy' => 'Do atendimento à contratação sem sair do fluxo.',
            'shot' => 'exp-venda-realizada',
            'alt' => 'Tela de venda realizada no EXP Vendedor, com confirmação e comissão',
        ],
        [
            'key' => 'resultados',
            'label' => 'Resultados',
            'copy' => 'O vendedor acompanha sua própria evolução.',
            'shot' => 'exp-resultado',
            'alt' => 'Painel de resultados do EXP Vendedor com desempenho do dia',
        ],
        [
            'key' => 'comissao',
            'label' => 'Comissão',
            'copy' => 'Mais transparência para quem vende.',
            'shot' => 'exp-comissao',
            'alt' => 'Tela de comissões do EXP Vendedor com valores da operação',
        ],
    ],

    'journey' => [
        [
            'step' => 1,
            'title' => 'Território',
            'copy' => 'Pontos e oportunidades no mapa.',
            'shot' => 'inteligencia-ponto',
            'alt' => 'Inteligência de um ponto comercial no mapa operacional do Expandor',
        ],
        [
            'step' => 2,
            'title' => 'Atendimento',
            'copy' => 'O vendedor trabalha direto no território.',
            'shot' => 'exp-mapa',
            'alt' => 'Mapa do EXP Vendedor usado no atendimento em campo',
        ],
        [
            'step' => 3,
            'title' => 'Retorno',
            'copy' => 'Quem pediu para voltar não é esquecido.',
            'shot' => 'exp-agenda',
            'alt' => 'Agenda do EXP Vendedor com retornos a cumprir',
        ],
        [
            'step' => 4,
            'title' => 'Apresentação',
            'copy' => 'O produto é apresentado profissionalmente.',
            'shot' => 'exp-produtos',
            'alt' => 'Apresentação de produtos no EXP Vendedor',
        ],
        [
            'step' => 5,
            'title' => 'Contratação',
            'copy' => 'Dados organizados no momento da venda.',
            'shot' => 'exp-venda',
            'alt' => 'Formulário de venda do EXP Vendedor com dados da contratação',
        ],
        [
            'step' => 6,
            'title' => 'Venda concluída',
            'copy' => 'Venda registrada, comissão gerada e escritório avisado.',
            'shot' => 'exp-venda-realizada',
            'alt' => 'Confirmação de venda realizada no EXP Vendedor',
        ],
    ],

    'manager' => [
        'title' => 'Enquanto o vendedor está na rua, o gestor enxerga a operação.',
        'subtitle' => 'Visão da gestão',
        'description' => 'Acompanhe vendas, visitas, retornos, equipe e desempenho comercial a partir de uma visão centralizada.',
        'shots' => [
            [
                'shot' => 'dashboard-gestor',
                'alt' => 'Dashboard do gestor Expandor com indicadores de vendas, visitas e retornos',
            ],
            [
                'shot' => 'equipe-gestor',
                'alt' => 'Tela de equipe do gestor Expandor com vendedores da operação',
            ],
            [
                'shot' => 'financeiro-gestor',
                'alt' => 'Visão financeira do gestor Expandor com desempenho comercial',
            ],
        ],
    ],

    'map_memory' => [
        'title' => 'Não é apenas um mapa.',
        'subtitle' => 'É memória comercial do território.',
        'description' => 'Cada ponto pode carregar histórico, cliente, atendimento e próxima ação. Sua equipe deixa de visitar casas no escuro e passa a trabalhar com contexto.',
        'shot' => 'inteligencia-ponto',
        'alt' => 'Ficha de inteligência de um ponto no mapa do Expandor, com histórico comercial e próxima ação',
    ],

    'sale_close' => [
        'title' => 'A venda não termina na porta do cliente.',
        'subtitle' => 'Do campo ao escritório',
        'description' => 'Depois da confirmação, o Expandor registra a venda, atualiza a operação, gera a comissão no fluxo existente e permite encaminhar os dados organizados ao escritório.',
        'shot' => 'exp-venda-realizada',
        'alt' => 'Tela de venda realizada no EXP Vendedor, com confirmação e encaminhamento ao escritório',
    ],

    'how_it_works' => [
        ['step' => 1, 'title' => 'Organize o território', 'description' => 'Pontos, oportunidades e contexto comercial no mapa.', 'icon' => 'map'],
        ['step' => 2, 'title' => 'Leve a operação para a rua', 'description' => 'O vendedor usa o EXP Vendedor no celular.', 'icon' => 'users'],
        ['step' => 3, 'title' => 'Capture cada retorno', 'description' => 'Quem pediu para voltar entra na agenda.', 'icon' => 'calendar'],
        ['step' => 4, 'title' => 'Apresente e feche', 'description' => 'Produtos, contratação e venda no mesmo fluxo.', 'icon' => 'flag'],
        ['step' => 5, 'title' => 'Enxergue no escritório', 'description' => 'O gestor acompanha equipe, visitas e resultados.', 'icon' => 'chart'],
        ['step' => 6, 'title' => 'Construa memória', 'description' => 'Cada visita deixa o território mais inteligente.', 'icon' => 'trending'],
    ],

    'before_after' => [
        'before' => [
            'Vendedores sem acompanhamento',
            'Informações espalhadas em papel e WhatsApp',
            'Dificuldade para medir resultados',
            'Clientes esquecidos e retornos perdidos',
        ],
        'after' => [
            'Equipe organizada e visível para o gestor',
            'Mapa com memória comercial do território',
            'Agenda, vendas e comissões no mesmo fluxo',
            'Clientes e visitas sob controle',
        ],
    ],

    'benefits' => [
        [
            'title' => 'Mais controle',
            'description' => 'Saiba o que está acontecendo na operação comercial.',
        ],
        [
            'title' => 'Menos oportunidade perdida',
            'description' => 'Transforme “vou pensar” e “volta amanhã” em retornos acompanháveis.',
        ],
        [
            'title' => 'Vendedor mais preparado',
            'description' => 'Produtos, agenda, clientes e vendas na mão.',
        ],
        [
            'title' => 'Território com memória',
            'description' => 'Cada visita ajuda a construir inteligência comercial.',
        ],
    ],

    'segments' => [
        ['title' => 'Provedores de internet', 'description' => 'Operação porta a porta, território e equipe externa com controle.'],
        ['title' => 'Telecom e telefonia', 'description' => 'Times de rua com meta, retorno e apresentação de planos.'],
        ['title' => 'Energia solar', 'description' => 'Visitas técnicas e comerciais no mesmo fluxo.'],
        ['title' => 'Representantes comerciais', 'description' => 'Carteira, rota e retorno sob controle.'],
        ['title' => 'Qualquer venda externa organizada', 'description' => 'Se a equipe vende na rua e o escritório precisa enxergar, o Expandor entra na camada comercial.'],
    ],

    'social_proof' => [
        'title' => 'Construído a partir da operação real de vendas em campo.',
        'metrics' => [
            ['key' => 'sellers', 'label' => 'Vendedores gerenciados'],
            ['key' => 'customers', 'label' => 'Clientes cadastrados'],
            ['key' => 'visits', 'label' => 'Visitas realizadas'],
            ['key' => 'campaigns', 'label' => 'Campanhas criadas'],
        ],
    ],

    'commercial_plans' => [
        'philosophy' => 'Todo o poder do Expandor. Escolha pelo tamanho da sua equipe.',
        'note' => '*Plano Scale sujeito à política de uso justo.',
        'cta_label' => 'Agendar demonstração',
        'cta_href' => '#demo',
        'features' => [
            'Mapa operacional',
            'EXP Vendedor',
            'Agenda e retornos',
            'Clientes',
            'Campanhas',
            'Apresentação de produtos',
            'Fluxo de vendas',
            'Resultados',
            'Comissões',
            'Gestão da operação',
        ],
        'plans' => [
            [
                'key' => 'start',
                'name' => 'Start',
                'price_label' => 'R$ 349',
                'period' => '/mês',
                'audience' => 'Até 2 vendedores',
                'featured' => false,
                'badge' => null,
                'cta_label' => 'Agendar demonstração',
            ],
            [
                'key' => 'pro',
                'name' => 'Pro',
                'price_label' => 'R$ 449',
                'period' => '/mês',
                'audience' => 'Até 5 vendedores',
                'featured' => true,
                'badge' => 'Mais escolhido',
                'cta_label' => 'Agendar demonstração',
            ],
            [
                'key' => 'scale',
                'name' => 'Scale',
                'price_label' => 'R$ 649',
                'period' => '/mês',
                'audience' => 'Vendedores ilimitados*',
                'featured' => false,
                'badge' => null,
                'cta_label' => 'Agendar demonstração',
            ],
        ],
        'enterprise' => [
            'key' => 'enterprise',
            'name' => 'Enterprise',
            'price_label' => 'Sob consulta',
            'period' => null,
            'audience' => 'Operações maiores, múltiplas necessidades ou integrações especiais.',
            'cta_label' => 'Agendar demonstração',
        ],
    ],

    'testimonials' => [],

    'faqs' => [
        [
            'question' => 'O Expandor substitui meu ERP?',
            'answer' => 'Não. O Expandor não precisa substituir o sistema de gestão do provedor. Ele atua na camada de inteligência e operação comercial em campo, ajudando a organizar território, vendedores, oportunidades, retornos e vendas.',
            'order' => 1,
            'active' => true,
        ],
        [
            'question' => 'Preciso instalar no computador?',
            'answer' => 'Não. Gestores e o escritório usam o Expandor pelo navegador. O vendedor usa o EXP Vendedor no celular.',
            'order' => 2,
            'active' => true,
        ],
        [
            'question' => 'O vendedor usa pelo celular?',
            'answer' => 'Sim. A rotina de campo roda no EXP Vendedor: mapa, agenda, clientes, produtos e vendas.',
            'order' => 3,
            'active' => true,
        ],
        [
            'question' => 'Posso acompanhar retornos?',
            'answer' => 'Sim. Pedidos de retorno entram na agenda e deixam de depender de lembrete solto no WhatsApp.',
            'order' => 4,
            'active' => true,
        ],
        [
            'question' => 'Consigo cadastrar meus próprios produtos?',
            'answer' => 'Sim. O provedor cadastra seus planos e produtos para o vendedor apresentar em campo.',
            'order' => 5,
            'active' => true,
        ],
        [
            'question' => 'O sistema funciona para qualquer provedor?',
            'answer' => 'Sim. Cada empresa opera a própria base — território, equipe, clientes, produtos e vendas — de forma isolada.',
            'order' => 6,
            'active' => true,
        ],
        [
            'question' => 'Existe plano grátis?',
            'answer' => 'Não. O Expandor não oferece plano gratuito. Os planos Start, Pro e Scale são mensais; o Enterprise é sob consulta.',
            'order' => 7,
            'active' => true,
        ],
        [
            'question' => 'Como contratar?',
            'answer' => 'Agende uma demonstração. O onboarding comercial acontece depois que alinhamos a operação do seu provedor.',
            'order' => 8,
            'active' => true,
        ],
    ],

    'demo_form' => [
        'title' => 'Agendar demonstração',
        'subtitle' => 'Conte um pouco sobre a operação comercial do seu provedor. Retornamos em breve.',
        'submit' => 'Agendar demonstração',
        'segments' => [
            'Internet / Provedor',
            'Telecom / Telefonia',
            'Energia solar',
            'Representantes comerciais',
            'Distribuidora',
            'Construção',
            'Purificadores',
            'TV',
            'Outro (vendas porta a porta)',
        ],
    ],

    'demo_cta' => [
        'eyebrow' => 'Quer ver funcionando na sua operação?',
        'title' => 'Agende uma demonstração do Expandor.',
        'button' => 'Agendar demonstração',
        'href' => '#demo',
    ],
];
