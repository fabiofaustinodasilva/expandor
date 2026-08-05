<?php

/**
 * Conteúdo padrão público do Expandor (Sprint 8.1).
 * Linguagem de resultados para vendas porta a porta — sem jargão técnico.
 * O CMS sobrescreve estes valores; a landing não deve hardcodar textos.
 */
return [

    'brand' => 'Expandor',

    'settings' => [
        'title' => 'Expandor',
        'subtitle' => 'Organize sua equipe de vendas porta a porta e acompanhe resultados em tempo real.',
        'description' => 'Controle visitas, clientes, campanhas e vendedores em um só lugar — feito para quem vende na rua.',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#0F172A',
        'background_color' => '#0B1220',
        'button_color' => '#F59E0B',
        'whatsapp_message' => 'Olá! Quero conhecer o Expandor para minha equipe de vendas.',
        'seo_title' => 'Expandor — Sistema para vendas porta a porta',
        'seo_description' => 'Organize vendedores, visitas e clientes. Mais vendas, menos planilhas. Feito para equipes de campo.',
        'seo_keywords' => 'vendas porta a porta, equipe de vendas, visitas, campanhas, internet, telecom, energia solar',
    ],

    'ui' => [
        'menu' => 'Menu',
        'login' => 'Entrar',
        'already_have_account' => 'Já tenho conta',
        'recommended' => 'Recomendado',
        'free' => 'Grátis',
        'per_month' => '/mês',
        'subscribe_now' => 'Assinar agora',
        'start_free_trial' => 'Começar agora',
        'request_demo' => 'Solicitar demonstração',
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
        'success_stories_subtitle' => 'Empresas que organizaram a operação de campo com o Expandor.',
    ],

    'nav' => [
        ['label' => 'Início', 'href' => '#inicio'],
        ['label' => 'Como funciona', 'href' => '#como-funciona'],
        ['label' => 'Benefícios', 'href' => '#beneficios'],
        ['label' => 'Para quem é', 'href' => '#segmentos'],
        ['label' => 'Recursos', 'href' => '#recursos'],
        ['label' => 'Planos', 'href' => '#planos'],
        ['label' => 'Dúvidas', 'href' => '#faq'],
    ],

    'nav_actions' => [
        [
            'label' => 'Solicitar demonstração',
            'href' => '#demo',
            'style' => 'outline',
        ],
        [
            'label' => 'Entrar',
            'href' => 'login',
            'style' => 'ghost',
            'route' => true,
        ],
        [
            'label' => 'Começar agora',
            'href' => 'signup.create',
            'style' => 'primary',
            'route' => true,
            'event' => 'marketplace.signup_started',
        ],
    ],

    'footer' => [
        'text' => 'Sistema de vendas porta a porta para empresas que querem mais controle e mais resultado.',
        'rights' => 'Todos os direitos reservados.',
        'links' => [
            ['label' => 'Início', 'href' => '#inicio'],
            ['label' => 'Planos', 'href' => '#planos'],
            ['label' => 'Dúvidas', 'href' => '#faq'],
            ['label' => 'Começar agora', 'href' => 'signup.create', 'route' => true],
        ],
    ],

    'section_order' => [
        'hero',
        'showcase',
        'how_it_works',
        'before_after',
        'benefits',
        'segments',
        'about',
        'features',
        'social_proof',
        'video',
        'testimonials',
        'plans',
        'faq',
        'cta',
    ],

    'sections' => [
        'hero' => [
            'title' => 'Organize sua equipe de vendas porta a porta e venda mais',
            'subtitle' => 'Controle visitas, clientes e vendedores no mapa — tudo em um único lugar.',
            'description' => 'Nunca mais perca um cliente. Agende retornos, acompanhe metas e veja resultados em tempo real.',
            'button_text' => 'Começar agora',
            'button_url' => '/cadastro',
            'button_text_secondary' => 'Solicitar demonstração',
            'button_url_secondary' => '#demo',
            'image' => '/images/marketplace/screens/dashboard.svg',
            'video' => null,
            'order' => 1,
        ],
        'showcase' => [
            'title' => 'Veja o Expandor em ação',
            'subtitle' => 'Telas da operação de campo',
            'description' => null,
            'order' => 2,
        ],
        'how_it_works' => [
            'title' => 'Como funciona',
            'subtitle' => 'Do time ao resultado, em passos simples',
            'description' => null,
            'order' => 3,
        ],
        'before_after' => [
            'title' => 'Menos bagunça. Mais resultado.',
            'subtitle' => 'O que muda no dia a dia da sua equipe',
            'description' => null,
            'order' => 4,
        ],
        'benefits' => [
            'title' => 'O que sua empresa ganha',
            'subtitle' => 'Mais vendas. Mais organização. Mais controle.',
            'description' => null,
            'order' => 5,
        ],
        'segments' => [
            'title' => 'Feito para quem vende porta a porta',
            'subtitle' => 'Internet, telecom, energia solar e muito mais',
            'description' => null,
            'order' => 6,
        ],
        'about' => [
            'title' => 'Sobre o Expandor',
            'subtitle' => 'Pensado para a rua',
            'description' => 'O Expandor nasceu para equipes que vendem fora do escritório: visitas, território, clientes e metas no mesmo fluxo.',
            'order' => 99,
        ],
        'features' => [
            'title' => 'Tudo que sua operação de campo precisa',
            'subtitle' => 'Benefícios reais para quem vende na rua',
            'description' => null,
            'features' => [
                ['title' => 'Equipe sob controle', 'description' => 'Saiba o que cada vendedor fez hoje — sem perseguir no WhatsApp.', 'icon' => 'users'],
                ['title' => 'Visitas que não se perdem', 'description' => 'Registre cada atendimento e nunca esqueça um retorno.', 'icon' => 'map-pin'],
                ['title' => 'Clientes organizados', 'description' => 'Histórico claro de cada porta batida e cada conversa.', 'icon' => 'users'],
                ['title' => 'Mapa inteligente', 'description' => 'Veja vendedores e território no mapa em tempo real.', 'icon' => 'map'],
                ['title' => 'Campanhas por região', 'description' => 'Monte campanhas com meta, prazo e área — e acompanhe o resultado.', 'icon' => 'flag'],
                ['title' => 'Agenda do dia', 'description' => 'Organize a rota e os retornos sem planilha.', 'icon' => 'calendar'],
                ['title' => 'Metas visíveis', 'description' => 'Acompanhe metas da equipe enquanto o dia ainda acontece.', 'icon' => 'target'],
                ['title' => 'Resultados na hora', 'description' => 'Números claros para decidir rápido e vender mais.', 'icon' => 'chart'],
            ],
            'order' => 7,
        ],
        'social_proof' => [
            'title' => 'Empresas organizam suas equipes de campo com Expandor',
            'subtitle' => 'Números da operação',
            'description' => null,
            'order' => 8,
        ],
        'video' => [
            'title' => 'Veja como funciona na prática',
            'subtitle' => 'Uma visão rápida do dia a dia no Expandor',
            'description' => 'Do cadastro da equipe ao acompanhamento das visitas.',
            'image' => '/images/marketplace/product-preview.svg',
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
            'title' => 'Escolha o plano ideal',
            'subtitle' => 'Comece pequeno e cresça com a sua equipe',
            'description' => 'Comece agora e evolua conforme sua operação aumenta.',
            'order' => 11,
        ],
        'faq' => [
            'title' => 'Dúvidas frequentes',
            'subtitle' => 'Respostas rápidas antes de começar',
            'description' => null,
            'order' => 12,
        ],
        'cta' => [
            'title' => 'Pronto para organizar sua equipe de vendas?',
            'subtitle' => 'Comece agora e veja o resultado no campo.',
            'description' => 'Menos papel, menos planilhas, menos WhatsApp solto — mais visitas e mais clientes.',
            'button_text' => 'Começar agora',
            'button_url' => '/cadastro',
            'order' => 13,
        ],
    ],

    'showcase' => [
        ['title' => 'Painel principal', 'image' => '/images/marketplace/screens/dashboard.svg'],
        ['title' => 'Mapa de campanhas', 'image' => '/images/marketplace/screens/map.svg'],
        ['title' => 'Agenda dos vendedores', 'image' => '/images/marketplace/screens/agenda.svg'],
        ['title' => 'Clientes', 'image' => '/images/marketplace/screens/crm.svg'],
        ['title' => 'Acompanhamento comercial', 'image' => '/images/marketplace/screens/pipeline.svg'],
        ['title' => 'Comissões', 'image' => '/images/marketplace/screens/commissions.svg'],
        ['title' => 'Ranking da equipe', 'image' => '/images/marketplace/screens/ranking.svg'],
        ['title' => 'Resultados', 'image' => '/images/marketplace/screens/conversion.svg'],
    ],

    'how_it_works' => [
        ['step' => 1, 'title' => 'Cadastre sua equipe', 'description' => 'Inclua vendedores e gestores em minutos.', 'icon' => 'users'],
        ['step' => 2, 'title' => 'Crie campanhas', 'description' => 'Defina região, meta e período.', 'icon' => 'flag'],
        ['step' => 3, 'title' => 'Acompanhe no mapa', 'description' => 'Veja vendedores e visitas no território.', 'icon' => 'map'],
        ['step' => 4, 'title' => 'Controle visitas e clientes', 'description' => 'Registre cada atendimento e retorno.', 'icon' => 'map-pin'],
        ['step' => 5, 'title' => 'Analise resultados', 'description' => 'Metas, ranking e indicadores claros.', 'icon' => 'chart'],
        ['step' => 6, 'title' => 'Aumente as vendas', 'description' => 'Mais organização, mais produtividade, mais resultado.', 'icon' => 'trending'],
    ],

    'before_after' => [
        'before' => [
            'Vendedores sem acompanhamento',
            'Informações espalhadas em papel e WhatsApp',
            'Dificuldade para medir resultados',
            'Clientes esquecidos e retornos perdidos',
        ],
        'after' => [
            'Equipe organizada e visível',
            'Mapa inteligente da operação',
            'Indicadores em tempo real',
            'Clientes e visitas sob controle',
        ],
    ],

    'benefits' => [
        'Mais vendas com a mesma equipe',
        'Mais organização no dia a dia',
        'Mais produtividade no campo',
        'Mais controle para o gestor',
        'Mais clientes acompanhados',
        'Mais visitas concluídas',
        'Mais resultados mensuráveis',
        'Menos papel e planilhas',
        'Menos WhatsApp desorganizado',
        'Menos perda de clientes',
    ],

    'segments' => [
        ['title' => 'Internet e provedores', 'description' => 'Equipes que vendem e instalam na casa do cliente.'],
        ['title' => 'Telecom e telefonia', 'description' => 'Representantes e times de rua com meta diária.'],
        ['title' => 'Energia solar', 'description' => 'Visitas técnicas e comerciais no mesmo fluxo.'],
        ['title' => 'Representantes comerciais', 'description' => 'Carteira, rota e retorno sob controle.'],
        ['title' => 'Distribuidoras', 'description' => 'Campanhas por região e acompanhamento de equipe.'],
        ['title' => 'Construção', 'description' => 'Visitas a obras e clientes com histórico claro.'],
        ['title' => 'Purificadores e água', 'description' => 'Porta a porta com agenda e retornos.'],
        ['title' => 'TV e entretenimento', 'description' => 'Vendas externas com metas e ranking.'],
        ['title' => 'Qualquer venda porta a porta', 'description' => 'Se sua equipe vende na rua, o Expandor foi feito para você.'],
    ],

    'social_proof' => [
        'title' => 'Empresas organizam suas equipes de campo com Expandor',
        'metrics' => [
            ['key' => 'sellers', 'label' => 'Vendedores gerenciados'],
            ['key' => 'customers', 'label' => 'Clientes cadastrados'],
            ['key' => 'visits', 'label' => 'Visitas realizadas'],
            ['key' => 'campaigns', 'label' => 'Campanhas criadas'],
        ],
    ],

    'testimonials' => [
        [
            'name' => 'Ana Ribeiro',
            'company' => 'Provedor Campo Norte',
            'text' => 'Em uma semana a equipe passou a registrar visitas e retornos. Paramos de perder cliente no WhatsApp.',
            'rating' => 5,
            'active' => true,
            'order' => 1,
        ],
        [
            'name' => 'Lucas Mendes',
            'company' => 'Solar Atlas',
            'text' => 'Mapa e agenda no mesmo lugar. Hoje sei onde cada vendedor está e o que falta fechar.',
            'rating' => 5,
            'active' => true,
            'order' => 2,
        ],
        [
            'name' => 'Carla Souza',
            'company' => 'Telecom Rede+',
            'text' => 'Metas e ranking claros. A produtividade da equipe subiu e o gestor finalmente enxerga o campo.',
            'rating' => 5,
            'active' => true,
            'order' => 3,
        ],
    ],

    'faqs' => [
        [
            'question' => 'Posso testar sem cartão?',
            'answer' => 'Sim. Você pode experimentar o Expandor com a sua equipe antes de escolher um plano.',
            'order' => 1,
            'active' => true,
        ],
        [
            'question' => 'Serve para equipe de vendas porta a porta?',
            'answer' => 'Sim. O Expandor foi feito para empresas que vendem na rua: visitas, mapa, clientes e metas.',
            'order' => 2,
            'active' => true,
        ],
        [
            'question' => 'Funciona para internet, telecom e energia solar?',
            'answer' => 'Sim. Também atende representantes, distribuidoras, construção, purificadores, TV e telefonia.',
            'order' => 3,
            'active' => true,
        ],
        [
            'question' => 'Como peço uma demonstração?',
            'answer' => 'Use o botão Solicitar demonstração na página e nossa equipe entra em contato.',
            'order' => 4,
            'active' => true,
        ],
    ],

    'demo_form' => [
        'title' => 'Solicitar demonstração',
        'subtitle' => 'Conte um pouco sobre sua operação de campo. Retornamos em breve.',
        'submit' => 'Solicitar demonstração',
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
];
