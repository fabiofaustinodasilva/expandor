<?php

/**
 * Conteúdo padrão do Marketplace público.
 * O CMS apenas personaliza — estes defaults garantem landing completa
 * mesmo em instalação limpa sem nenhum cadastro no CMS.
 */
return [

    'brand' => 'Expandor',

    'settings' => [
        'title' => 'Expandor — CRM inteligente para vendas',
        'subtitle' => 'CRM + Pipeline + WhatsApp + Equipe + Gestão Comercial em uma única plataforma.',
        'description' => 'O Expandor une CRM, pipeline, agenda, WhatsApp e gestão de equipe para sua operação comercial vender mais com clareza.',
        'primary_color' => '#3B82F6',
        'secondary_color' => '#0F172A',
        'background_color' => '#0B1220',
        'button_color' => '#F59E0B',
        'whatsapp_enabled' => false,
        'whatsapp_message' => 'Olá! Quero saber mais sobre o Expandor.',
        'instagram_enabled' => false,
        'seo_title' => 'Expandor — CRM inteligente para vendas',
        'seo_description' => 'Venda mais, organize o comercial e escale sua empresa. CRM + Pipeline + WhatsApp + Equipe em uma única plataforma. Teste grátis.',
        'seo_keywords' => 'crm, vendas, saas, expandor, gestão comercial, pipeline, whatsapp',
    ],

    'nav' => [
        ['label' => 'Início', 'href' => '#inicio'],
        ['label' => 'Recursos', 'href' => '#recursos'],
        ['label' => 'Planos', 'href' => '#planos'],
        ['label' => 'Clientes', 'href' => '#clientes'],
        ['label' => 'FAQ', 'href' => '#faq'],
        ['label' => 'Demonstração', 'href' => '#demonstracao'],
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
            'label' => 'Teste grátis',
            'href' => 'signup.create',
            'style' => 'primary',
            'route' => true,
            'event' => 'marketplace.signup_started',
        ],
    ],

    'footer' => [
        'links' => [
            ['label' => 'Início', 'href' => '#inicio'],
            ['label' => 'Recursos', 'href' => '#recursos'],
            ['label' => 'Planos', 'href' => '#planos'],
            ['label' => 'FAQ', 'href' => '#faq'],
            ['label' => 'Teste grátis', 'href' => 'signup.create', 'route' => true],
        ],
        'rights' => 'Todos os direitos reservados.',
    ],

    'section_order' => [
        'hero',
        'about',
        'features',
        'video',
        'testimonials',
        'plans',
        'faq',
        'cta',
    ],

    'sections' => [
        'hero' => [
            'title' => "Venda mais.\nOrganize seu comercial.\nEscale sua empresa.",
            'subtitle' => 'CRM + Pipeline + WhatsApp + Equipe + Gestão Comercial em uma única plataforma.',
            'description' => null,
            'button_text' => 'Teste grátis',
            'button_url' => '/cadastro',
            'button_text_secondary' => 'Solicitar demonstração',
            'button_url_secondary' => '#demo',
            'image' => '/images/marketplace/hero-saas.svg',
            'video' => null,
            'order' => 1,
        ],
        'about' => [
            'title' => 'Quem Somos',
            'subtitle' => 'Feito para operação comercial real',
            'description' => 'O Expandor nasceu para unir CRM, território, visitas, pipeline e comissões em uma experiência SaaS clara — do primeiro cliente ao time em escala. Acreditamos que vender bem exige processo, visibilidade e velocidade no mesmo lugar.',
            'button_text' => null,
            'button_url' => null,
            'image' => null,
            'order' => 2,
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
                ['title' => 'Marketplace', 'description' => 'Página pública pronta para converter visitantes em trials.'],
                ['title' => 'Inteligência Comercial', 'description' => 'Insights para priorizar o que gera mais resultado.'],
            ],
            'order' => 3,
        ],
        'video' => [
            'title' => 'Demonstração do CRM',
            'subtitle' => 'Veja o sistema funcionando',
            'description' => 'Uma visão rápida da jornada comercial no Expandor — do lead ao fechamento.',
            'image' => '/images/marketplace/product-preview.svg',
            'video' => null,
            'order' => 4,
        ],
        'testimonials' => [
            'title' => 'Clientes',
            'subtitle' => 'Quem já opera com Expandor',
            'description' => null,
            'order' => 5,
        ],
        'plans' => [
            'title' => 'Planos',
            'subtitle' => 'Escolha o ritmo certo para crescer',
            'description' => 'Comece no teste grátis e evolua conforme sua operação escala.',
            'order' => 6,
        ],
        'faq' => [
            'title' => 'Perguntas frequentes',
            'subtitle' => 'Respostas objetivas antes de começar',
            'description' => null,
            'order' => 7,
        ],
        'cta' => [
            'title' => 'Pronto para vender com mais clareza?',
            'subtitle' => 'Comece o teste grátis em minutos.',
            'description' => 'Sem cartão para explorar. Configure sua empresa e leve o time para um processo comercial único.',
            'button_text' => 'Começar agora',
            'button_url' => '/cadastro',
            'order' => 8,
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
            'answer' => 'Sim. O CMS da plataforma permite ajustar textos, imagens, FAQ e depoimentos. Sem configuração, a landing já vem completa com conteúdo padrão.',
            'order' => 4,
            'active' => true,
        ],
    ],
];
