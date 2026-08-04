<?php

namespace Database\Seeders;

use App\Domains\Marketplace\Growth\Models\MarketplaceCampaign;
use App\Domains\Marketplace\Growth\Models\MarketplaceCase;
use App\Domains\Marketplace\Growth\Models\MarketplaceSegmentPage;
use Illuminate\Database\Seeder;

class MarketplaceGrowthSeeder extends Seeder
{
    public function run(): void
    {
        if (! MarketplaceSegmentPage::query()->exists()) {
            $segments = [
                [
                    'slug' => 'provedor-internet',
                    'title' => 'CRM para provedores de internet',
                    'subtitle' => 'Vendas técnicas com previsibilidade',
                    'description' => 'Organize leads, instalações e renovações com visão por território e equipe de campo.',
                    'features' => [
                        ['title' => 'Território por bairro', 'description' => 'Distribua oportunidades por região e vendedor.'],
                        ['title' => 'Funil de instalação', 'description' => 'Acompanhe do lead à ativação do cliente.'],
                        ['title' => 'Metas por equipe', 'description' => 'Dashboards claros para gestores comerciais.'],
                    ],
                    'cta_text' => 'Solicitar demonstração',
                    'cta_url' => '#demo',
                ],
                [
                    'slug' => 'energia-solar',
                    'title' => 'CRM para energia solar',
                    'subtitle' => 'Do primeiro contato à instalação',
                    'description' => 'Controle propostas, visitas técnicas e pós-venda em um fluxo único para equipes de campo.',
                    'features' => [
                        ['title' => 'Propostas e follow-up', 'description' => 'Nunca perca um retorno após a visita técnica.'],
                        ['title' => 'Mapa de oportunidades', 'description' => 'Visualize leads e clientes por região.'],
                        ['title' => 'Comissões automáticas', 'description' => 'Regras claras para representantes e closers.'],
                    ],
                    'cta_text' => 'Solicitar demonstração',
                    'cta_url' => '#demo',
                ],
                [
                    'slug' => 'imobiliaria',
                    'title' => 'CRM para imobiliárias',
                    'subtitle' => 'Mais visitas, menos leads perdidos',
                    'description' => 'Centralize corretores, imóveis e negociações com histórico completo de cada cliente.',
                    'features' => [
                        ['title' => 'Carteira por corretor', 'description' => 'Distribua leads e imóveis com regras claras.'],
                        ['title' => 'Agenda de visitas', 'description' => 'Confirme, registre e acompanhe cada visita.'],
                        ['title' => 'Pipeline de negócios', 'description' => 'Proposta, documentação e fechamento visíveis.'],
                    ],
                    'cta_text' => 'Solicitar demonstração',
                    'cta_url' => '#demo',
                ],
                [
                    'slug' => 'representante-comercial',
                    'title' => 'CRM para representantes comerciais',
                    'subtitle' => 'Portfólio, rotas e metas',
                    'description' => 'Ideal para representantes autônomos e equipes externas que precisam de mobilidade e controle.',
                    'features' => [
                        ['title' => 'Roteiro de visitas', 'description' => 'Planeje o dia com clientes e prioridades.'],
                        ['title' => 'Pedidos e histórico', 'description' => 'Tudo registrado por cliente e região.'],
                        ['title' => 'Metas e comissões', 'description' => 'Acompanhe resultado e ganhos em tempo real.'],
                    ],
                    'cta_text' => 'Solicitar demonstração',
                    'cta_url' => '#demo',
                ],
            ];

            foreach ($segments as $row) {
                MarketplaceSegmentPage::query()->create(array_merge($row, ['active' => true]));
            }
        }

        if (! MarketplaceCase::query()->exists()) {
            MarketplaceCase::query()->create([
                'company_name' => 'NetConnect Telecom',
                'segment' => 'provedor-internet',
                'challenge' => 'Leads espalhados em planilhas e WhatsApp, sem visão por bairro.',
                'solution' => 'Implementação do Expandor com territórios e funil de instalação.',
                'result' => '+32% de conversão em 90 dias e redução de 40% no tempo de follow-up.',
                'active' => true,
                'order' => 1,
            ]);

            MarketplaceCase::query()->create([
                'company_name' => 'Sol Energia Ltda',
                'segment' => 'energia-solar',
                'challenge' => 'Propostas enviadas sem retorno estruturado após visita técnica.',
                'solution' => 'CRM com follow-ups automáticos e mapa de oportunidades por região.',
                'result' => 'Equipe dobrou visitas qualificadas e fechou 18% mais contratos no trimestre.',
                'active' => true,
                'order' => 2,
            ]);
        }

        if (! MarketplaceCampaign::query()->exists()) {
            MarketplaceCampaign::query()->create([
                'name' => 'Google Ads — CRM Brasil',
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'crm-brasil',
                'active' => true,
            ]);
        }
    }
}
