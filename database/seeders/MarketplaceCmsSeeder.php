<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MarketplaceCmsSeeder extends Seeder
{
    public function run(): void
    {
        if (! MarketplaceSetting::query()->exists()) {
            MarketplaceSetting::query()->create([
                'title' => 'Transforme sua empresa em uma operação inteligente',
                'subtitle' => 'Controle clientes, vendas, equipes e processos em um único sistema.',
                'description' => 'Expandor é o CRM de campo e gestão comercial para equipes que precisam de velocidade, clareza e resultado.',
                'primary_color' => '#3B82F6',
                'secondary_color' => '#0F172A',
                'background_color' => '#0B1220',
                'button_color' => '#F59E0B',
                'whatsapp_enabled' => false,
                'whatsapp_message' => 'Olá! Quero saber mais sobre o Expandor.',
                'instagram_enabled' => false,
                'seo_title' => 'Expandor — CRM inteligente para vendas',
                'seo_description' => 'Controle clientes, vendas, equipes e processos em um único sistema. Teste grátis.',
                'seo_keywords' => 'crm, vendas, saas, expandor, gestão comercial',
            ]);
        }

        if (! MarketplaceSection::query()->exists()) {
            $defaults = [
                [
                    'type' => MarketplaceSectionType::Hero->value,
                    'title' => 'Transforme sua empresa em uma operação inteligente',
                    'subtitle' => 'Controle clientes, vendas, equipes e processos em um único sistema.',
                    'description' => null,
                    'button_text' => 'Teste grátis',
                    'button_url' => '/cadastro',
                    'order' => 1,
                ],
                [
                    'type' => MarketplaceSectionType::About->value,
                    'title' => 'Quem Somos',
                    'subtitle' => 'Feito para operação comercial real',
                    'description' => 'O Expandor nasceu para unir CRM, território, visitas e comissões em uma experiência SaaS clara — do primeiro cliente ao time em escala.',
                    'order' => 2,
                ],
                [
                    'type' => MarketplaceSectionType::Features->value,
                    'title' => 'Recursos',
                    'subtitle' => 'Tudo que sua operação precisa',
                    'description' => json_encode([
                        ['title' => 'CRM', 'description' => 'Gerencie seus clientes e oportunidades.'],
                        ['title' => 'Vendas', 'description' => 'Acompanhe todo processo comercial.'],
                        ['title' => 'Equipe', 'description' => 'Controle usuários e permissões.'],
                        ['title' => 'Dashboard', 'description' => 'Tenha visão completa do negócio.'],
                    ], JSON_UNESCAPED_UNICODE),
                    'order' => 3,
                ],
                [
                    'type' => MarketplaceSectionType::Video->value,
                    'title' => 'Demonstração do CRM',
                    'subtitle' => 'Veja o sistema funcionando.',
                    'description' => 'Uma visão rápida da jornada comercial no Expandor.',
                    'order' => 4,
                ],
                [
                    'type' => MarketplaceSectionType::Gallery->value,
                    'title' => 'Galeria',
                    'subtitle' => 'Telas e momentos do produto',
                    'order' => 5,
                ],
                [
                    'type' => MarketplaceSectionType::Testimonials->value,
                    'title' => 'Depoimentos',
                    'subtitle' => 'Quem já opera com Expandor',
                    'order' => 6,
                ],
                [
                    'type' => MarketplaceSectionType::Plans->value,
                    'title' => 'Planos',
                    'subtitle' => 'Escolha o ritmo certo para crescer',
                    'order' => 7,
                ],
                [
                    'type' => MarketplaceSectionType::Faq->value,
                    'title' => 'Perguntas frequentes',
                    'subtitle' => 'Respostas objetivas antes de começar',
                    'order' => 8,
                ],
                [
                    'type' => MarketplaceSectionType::Cta->value,
                    'title' => 'Pronto para vender com mais clareza?',
                    'subtitle' => 'Comece o teste grátis em minutos.',
                    'button_text' => 'Começar agora',
                    'button_url' => '/cadastro',
                    'order' => 9,
                ],
            ];

            foreach ($defaults as $row) {
                MarketplaceSection::query()->create(array_merge($row, ['active' => true]));
            }
        }

        if (! MarketplaceTestimonial::query()->exists()) {
            MarketplaceTestimonial::query()->create([
                'name' => 'Ana Ribeiro',
                'company' => 'Campo Norte',
                'text' => 'Em uma semana organizamos o funil e a equipe passou a seguir o mesmo processo.',
                'rating' => 5,
                'active' => true,
                'order' => 1,
            ]);
            MarketplaceTestimonial::query()->create([
                'name' => 'Lucas Mendes',
                'company' => 'VendaMais',
                'text' => 'Mapa, visitas e comissões no mesmo lugar — finalmente paramos de improvisar planilhas.',
                'rating' => 5,
                'active' => true,
                'order' => 2,
            ]);
        }

        if (! MarketplaceFaq::query()->exists()) {
            MarketplaceFaq::query()->create([
                'question' => 'Posso testar sem cartão?',
                'answer' => 'Sim. O teste grátis permite explorar o workspace antes de assinar um plano pago.',
                'order' => 1,
                'active' => true,
            ]);
            MarketplaceFaq::query()->create([
                'question' => 'O Expandor serve para equipes de campo?',
                'answer' => 'Sim. O produto foi pensado para operação comercial com território, visitas e acompanhamento em tempo real.',
                'order' => 2,
                'active' => true,
            ]);
            MarketplaceFaq::query()->create([
                'question' => 'Como funciona a assinatura?',
                'answer' => 'Escolha um plano, conclua o checkout e o workspace é provisionado automaticamente após a confirmação.',
                'order' => 3,
                'active' => true,
            ]);
        }

        Cache::forget(MarketplaceSettingsRepository::CACHE_KEY);
        Cache::forget(MarketplaceContentRepository::CACHE_KEY);
    }
}
