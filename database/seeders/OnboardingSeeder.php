<?php

namespace Database\Seeders;

use App\Domains\Onboarding\Models\OnboardingStep;
use App\Domains\Onboarding\Models\SetupTemplate;
use Illuminate\Database\Seeder;

class OnboardingSeeder extends Seeder
{
    public function run(): void
    {
        $steps = [
            ['key' => 'company', 'title' => 'Dados da empresa', 'description' => 'Confirme os dados cadastrais da empresa.', 'wizard_key' => 'company', 'training_keywords' => ['empresa', 'cadastro'], 'sort_order' => 1],
            ['key' => 'branding', 'title' => 'Branding', 'description' => 'Personalize logo, cores e identidade visual.', 'wizard_key' => 'branding', 'training_keywords' => ['branding', 'marca'], 'sort_order' => 2],
            ['key' => 'team', 'title' => 'Equipe', 'description' => 'Cadastre gestores e usuários administrativos.', 'wizard_key' => 'team', 'training_keywords' => ['equipe', 'usuários'], 'sort_order' => 3],
            ['key' => 'cities', 'title' => 'Cidades', 'description' => 'Defina as cidades de atuação.', 'wizard_key' => 'cities', 'training_keywords' => ['cidade', 'território'], 'sort_order' => 4],
            ['key' => 'sectors', 'title' => 'Setores', 'description' => 'Organize setores dentro das cidades.', 'wizard_key' => 'sectors', 'training_keywords' => ['setor', 'território'], 'sort_order' => 5],
            ['key' => 'products', 'title' => 'Produtos', 'description' => 'Cadastre os produtos oferecidos pela equipe.', 'wizard_key' => 'products', 'training_keywords' => ['produto', 'oferta'], 'sort_order' => 6],
            ['key' => 'campaigns', 'title' => 'Campanhas', 'description' => 'Crie a primeira campanha comercial.', 'wizard_key' => 'campaigns', 'training_keywords' => ['campanha'], 'sort_order' => 7],
            ['key' => 'sellers', 'title' => 'Vendedores', 'description' => 'Adicione vendedores e vincule às campanhas.', 'wizard_key' => 'sellers', 'training_keywords' => ['vendedor', 'equipe'], 'sort_order' => 8],
            ['key' => 'property', 'title' => 'Primeiro imóvel', 'description' => 'Cadastre o primeiro imóvel no território.', 'wizard_key' => 'property', 'training_keywords' => ['imóvel', 'propriedade'], 'sort_order' => 9],
            ['key' => 'visit', 'title' => 'Primeira visita', 'description' => 'Registre a primeira visita em campo.', 'wizard_key' => 'visit', 'training_keywords' => ['visita', 'campo'], 'sort_order' => 10],
            ['key' => 'training', 'title' => 'Academia', 'description' => 'Explore conteúdos de treinamento.', 'wizard_key' => 'training', 'training_keywords' => ['academia', 'treinamento'], 'sort_order' => 11],
            ['key' => 'finish', 'title' => 'Concluir implantação', 'description' => 'Finalize o onboarding e libere o uso completo.', 'wizard_key' => 'finish', 'training_keywords' => ['onboarding', 'implantação'], 'sort_order' => 12],
        ];

        foreach ($steps as $step) {
            OnboardingStep::query()->updateOrCreate(
                ['key' => $step['key']],
                [
                    ...$step,
                    'is_required' => true,
                    'is_active' => true,
                ]
            );
        }

        SetupTemplate::query()->updateOrCreate(
            ['key' => 'demo_starter'],
            [
                'name' => 'Demonstração inicial',
                'description' => 'Gera cidade, setores, produtos, campanha, imóveis, moradores e visitas fictícios.',
                'is_active' => true,
                'payload' => [
                    'city' => ['name' => 'Demoópolis', 'state' => 'SP'],
                    'sectors' => ['Centro', 'Jardins', 'Industrial'],
                    'products' => [
                        ['name' => '[Demo] Internet 100MB', 'price' => 99.90, 'commission_amount' => 30, 'stock_control' => false],
                        ['name' => '[Demo] Internet 300MB', 'price' => 149.90, 'commission_amount' => 45, 'stock_control' => false],
                        ['name' => '[Demo] Roteador Wi-Fi', 'price' => 249.90, 'commission_amount' => 40, 'stock_control' => true, 'stock_quantity' => 25, 'minimum_stock' => 5],
                    ],
                    'campaign' => ['name' => '[Demo] Campanha Inicial', 'goal_visits' => 20],
                    'properties' => 5,
                    'visits' => 3,
                ],
            ]
        );
    }
}
