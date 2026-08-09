<?php

namespace Database\Seeders;

use App\Domains\Platform\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['key' => 'ai.enabled', 'name' => 'IA assistiva', 'description' => 'Habilita o módulo Expandor AI.', 'default_enabled' => true],
            ['key' => 'whatsapp.enabled', 'name' => 'WhatsApp', 'description' => 'Habilita comunicação WhatsApp.', 'default_enabled' => true],
            ['key' => 'mobile.enabled', 'name' => 'App Mobile', 'description' => 'Libera API mobile para a empresa.', 'default_enabled' => true],
            ['key' => 'branding.custom_css', 'name' => 'CSS personalizado', 'description' => 'Permite CSS white-label avançado.', 'default_enabled' => false],
            ['key' => 'billing.self_serve', 'name' => 'Self-serve billing', 'description' => 'Permite upgrade/downgrade pelo cliente.', 'default_enabled' => true],
            ['key' => 'onboarding.required', 'name' => 'Onboarding obrigatório', 'description' => 'Força wizard de implantação.', 'default_enabled' => true],
            ['key' => 'integrations.google_maps', 'name' => 'Google Maps (integração)', 'description' => 'Permite conectar Google Maps por empresa quando o plano incluir a feature.', 'default_enabled' => true],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::query()->updateOrCreate(
                ['key' => $flag['key']],
                [...$flag, 'is_active' => true]
            );
        }
    }
}
