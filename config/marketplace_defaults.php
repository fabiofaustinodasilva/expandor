<?php

/**
 * Conteúdo padrão do Marketplace público (Sprint 8.0.3 — Premium Conversion).
 * CMS personaliza; defaults garantem landing completa sem cadastro.
 */
return [

    'brand' => 'Expandor',

    'settings' => [
        'title' => 'Expandor',
        'subtitle' => 'Controle clientes, vendedores, campanhas, visitas, mapas e resultados em tempo real em uma única plataforma.',
        'description' => 'O Expandor ajuda empresas com equipes externas a vender mais e controlar toda a operação comercial.',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#0F172A',
        'background_color' => '#0B1220',
        'button_color' => '#F59E0B',
        'whatsapp_message' => 'Olá! Quero saber mais sobre o Expandor.',
        'seo_title' => 'Expandor - Plataforma inteligente para vendas externas',
        'seo_description' => 'Gerencie vendedores, clientes, campanhas e resultados com inteligência.',
        'seo_keywords' => 'crm, vendas externas, campo, campanhas, mapa, expandor, gestão comercial',
    ],

    'nav' => [
        ['label' => 'Início', 'href' => '#inicio'],
        ['label' => 'Produto', 'href' => '#produto'],
        ['label' => 'Como funciona', 'href' => '#como-funciona'],
        ['label' => 'Recursos', 'href' => '#recursos'],
        ['label' => 'Planos', 'href' => '#planos'],
        ['label' => 'Clientes', 'href' => '#clientes'],
        ['label' => 'FAQ', 'href' => '#faq'],
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
            'label' => 'Começar teste grátis',
            'href' => 'signup.create',
            'style' => 'primary',
            'route' => true,
            'event' => 'marketplace.signup_started',
        ],
    ],

    'footer' => [
        'links' => [
            ['label' => 'Início', 'href' => '#inicio'],
            ['label' => 'Produto', 'href' => '#produto'],
            ['label' => 'Planos', 'href' => '#planos'],
            ['label' => 'FAQ', 'href' => '#faq'],
            ['label' => 'Começar teste grátis', 'href' => 'signup.create', 'route' => true],
        ],
        'rights' => 'Todos os direitos reservados.',
    ],

    'section_order' => [
        'hero',
        'showcase',
        'how_it_works',
        'before_after',
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
            'title' => 'Transforme sua equipe de vendas externas em uma operação inteligente',
            'subtitle' => 'Controle clientes, vendedores, campanhas, visitas, mapas e resultados em tempo real em uma única plataforma.',
            'description' => 'O Expandor ajuda empresas com equipes externas a vender mais e controlar toda a operação.',
            'button_text' => 'Começar teste grátis',
            'button_url' => '/cadastro',
            'button_text_secondary' => 'Solicitar demonstração',
            'button_url_secondary' => '#demo',
            'image' => '/images/marketplace/screens/dashboard.svg',
            'video' => null,
            'order' => 1,
        ],
        'showcase' => [
            'title' => 'Veja o Expandor funcionando',
            'subtitle' => 'Telas reais da operação comercial',
            'description' => null,
            'order' => 2,
        ],
        'how_it_works' => [
            'title' => 'Como funciona',
            'subtitle' => 'Do time ao resultado em seis passos',
            'description' => null,
            'order' => 3,
        ],
        'before_after' => [
            'title' => 'Da operação caótica ao controle total',
            'subtitle' => 'O que muda com o Expandor',
            'description' => null,
            'order' => 4,
        ],
        'about' => [
            'title' => 'Quem Somos',
            'subtitle' => 'Feito para operação comercial real',
            'description' => 'O Expandor nasceu para unir CRM, território, visitas, pipeline e comissões em uma experiência SaaS clara — do primeiro cliente ao time em escala.',
            'order' => 99,
        ],
        'features' => [
            'title' => 'Recursos',
            'subtitle' => 'Tudo que sua operação precisa',
            'description' => null,
            'features' => [
                ['title' => 'CRM', 'description' => 'Centralize clientes, histórico e próximos passos em um só lugar.'],
                ['title' => 'Pipeline', 'description' => 'Acompanhe cada negócio do lead ao fechamento com clareza.'],
                ['title' => 'Agenda', 'description' => 'Organize visitas, follow-ups e compromissos da equipe.'],
                ['title' => 'WhatsApp', 'description' => 'Aproxime conversas comerciais do fluxo de trabalho do time.'],
                ['title' => 'Clientes', 'description' => 'Gerencie carteira, relacionamento e oportunidades com contexto.'],
                ['title' => 'Dashboard', 'description' => 'Tenha visão executiva de funil, metas e desempenho.'],
                ['title' => 'Equipe', 'description' => 'Controle usuários, papéis e permissões com segurança.'],
                ['title' => 'Metas', 'description' => 'Defina objetivos e acompanhe o progresso comercial.'],
                ['title' => 'Mapa', 'description' => 'Visualize campanhas e vendedores no território.'],
                ['title' => 'Comissões', 'description' => 'Acompanhe resultados e ranking da equipe.'],
            ],
            'order' => 5,
        ],
        'social_proof' => [
            'title' => 'Empresas organizam suas operações comerciais com Expandor',
            'subtitle' => 'Números da plataforma',
            'description' => null,
            'order' => 6,
        ],
        'video' => [
            'title' => 'Vídeo demonstrativo',
            'subtitle' => 'Veja o sistema em ação',
            'description' => 'Uma visão rápida da jornada comercial no Expandor.',
            'image' => '/images/marketplace/product-preview.svg',
            'video' => null,
            'order' => 7,
        ],
        'testimonials' => [
            'title' => 'Clientes',
            'subtitle' => 'Quem já opera com Expandor',
            'description' => null,
            'order' => 8,
        ],
        'plans' => [
            'title' => 'Planos',
            'subtitle' => 'Escolha o ritmo certo para crescer',
            'description' => 'Comece o teste grátis e evolua conforme sua operação escala.',
            'order' => 9,
        ],
        'faq' => [
            'title' => 'Perguntas frequentes',
            'subtitle' => 'Respostas objetivas antes de começar',
            'description' => null,
            'order' => 10,
        ],
        'cta' => [
            'title' => 'Pronto para transformar sua operação de campo?',
            'subtitle' => 'Comece o teste grátis em minutos.',
            'description' => 'Sem cartão para explorar. Configure sua empresa e leve o time para um processo comercial único.',
            'button_text' => 'Começar teste grátis',
            'button_url' => '/cadastro',
            'order' => 11,
        ],
    ],

    'showcase' => [
        ['title' => 'Dashboard principal', 'image' => '/images/marketplace/screens/dashboard.svg'],
        ['title' => 'Mapa de campanhas', 'image' => '/images/marketplace/screens/map.svg'],
        ['title' => 'Agenda dos vendedores', 'image' => '/images/marketplace/screens/agenda.svg'],
        ['title' => 'CRM de clientes', 'image' => '/images/marketplace/screens/crm.svg'],
        ['title' => 'Pipeline comercial', 'image' => '/images/marketplace/screens/pipeline.svg'],
        ['title' => 'Comissão dos vendedores', 'image' => '/images/marketplace/screens/commissions.svg'],
        ['title' => 'Ranking da equipe', 'image' => '/images/marketplace/screens/ranking.svg'],
        ['title' => 'Dashboard de conversão', 'image' => '/images/marketplace/screens/conversion.svg'],
    ],

    'how_it_works' => [
        ['step' => 1, 'title' => 'Cadastre sua equipe', 'description' => 'Convide vendedores e gestores com papéis e permissões claros.'],
        ['step' => 2, 'title' => 'Crie campanhas', 'description' => 'Monte campanhas por território, meta e período.'],
        ['step' => 3, 'title' => 'Acompanhe vendedores no mapa', 'description' => 'Veja onde a equipe está e o que está em andamento.'],
        ['step' => 4, 'title' => 'Controle visitas e clientes', 'description' => 'Registre visitas, follow-ups e histórico no CRM.'],
        ['step' => 5, 'title' => 'Analise resultados', 'description' => 'Dashboards, funil e indicadores em tempo real.'],
        ['step' => 6, 'title' => 'Aumente vendas', 'description' => 'Priorize o que gera resultado e escale com processo.'],
    ],

    'before_after' => [
        'before' => [
            'Vendedores sem acompanhamento',
            'Informações espalhadas',
            'Dificuldade para medir resultados',
        ],
        'after' => [
            'Equipe organizada',
            'Mapa inteligente',
            'Indicadores em tempo real',
        ],
    ],

    'social_proof' => [
        'title' => 'Empresas organizam suas operações comerciais com Expandor',
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
            'company' => 'Campo Norte',
            'text' => 'Em uma semana organizamos o funil e a equipe passou a seguir o mesmo processo. O mapa e a agenda mudaram nossa rotina.',
            'rating' => 5,
            'active' => true,
            'order' => 1,
        ],
        [
            'name' => 'Lucas Mendes',
            'company' => 'VendaMais',
            'text' => 'Mapa, visitas e comissões no mesmo lugar — finalmente paramos de improvisar planilhas e WhatsApp solto.',
            'rating' => 5,
            'active' => true,
            'order' => 2,
        ],
        [
            'name' => 'Carla Souza',
            'company' => 'Atlas Comercial',
            'text' => 'O pipeline ficou visível para toda a gestão. Hoje sabemos onde está cada negócio e o que precisa de atenção.',
            'rating' => 5,
            'active' => true,
            'order' => 3,
        ],
    ],

    'faqs' => [
        [
            'question' => 'Posso testar sem cartão?',
            'answer' => 'Sim. O teste grátis permite explorar o workspace antes de assinar um plano pago.',
            'order' => 1,
            'active' => true,
        ],
        [
            'question' => 'O Expandor serve para equipes de campo?',
            'answer' => 'Sim. O produto foi pensado para operação comercial com território, visitas, agenda e acompanhamento em tempo real.',
            'order' => 2,
            'active' => true,
        ],
        [
            'question' => 'Como funciona a assinatura?',
            'answer' => 'Escolha um plano, conclua o checkout e o workspace é provisionado automaticamente após a confirmação.',
            'order' => 3,
            'active' => true,
        ],
        [
            'question' => 'Posso personalizar a landing do Marketplace?',
            'answer' => 'Sim. O CMS da plataforma permite ajustar textos, imagens, FAQ, vídeo e prova social. Sem configuração, a landing já vem completa.',
            'order' => 4,
            'active' => true,
        ],
    ],
];
