<?php

namespace App\Domains\Platform\Support;

/**
 * Catálogo comercial oficial do Expandor (site + SaaS).
 * Planos legados permanecem no banco para assinaturas existentes, sem checkout público.
 */
final class CommercialPlanCatalog
{
    public const START = 'start';

    public const PRO = 'pro';

    public const SCALE = 'scale';

    public const ENTERPRISE = 'enterprise';

    public const FREE = 'free';

    public const PROFESSIONAL = 'professional';

    public const ENTERPRISE_LEGACY = 'enterprise-legacy';

    /**
     * @return list<string>
     */
    public static function checkoutSlugs(): array
    {
        return [self::START, self::PRO, self::SCALE];
    }

    /**
     * @return list<string>
     */
    public static function publicSlugs(): array
    {
        return [self::START, self::PRO, self::SCALE, self::ENTERPRISE];
    }

    /**
     * @return list<string>
     */
    public static function legacySlugs(): array
    {
        return [self::FREE, self::PROFESSIONAL, self::ENTERPRISE_LEGACY];
    }

    /**
     * Planos estruturais / comerciais oficiais — nunca excluir pelo painel.
     *
     * @return list<string>
     */
    public static function protectedSlugs(): array
    {
        return array_values(array_unique(array_merge(
            self::publicSlugs(),
            self::legacySlugs(),
        )));
    }

    public static function nextPlanHint(?string $slug): ?string
    {
        return match ($slug) {
            self::START => 'Conheça o plano Pro.',
            self::PRO => 'Conheça o plano Scale.',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function commercialFeatures(): array
    {
        return PlanCatalog::normalizeFeatures([
            'crm' => true,
            'ai' => false,
            'whatsapp' => true,
            'stock' => true,
            'finance' => true,
            'api' => false,
            'white_label' => false,
            'google_maps' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function enterpriseFeatures(): array
    {
        return PlanCatalog::normalizeFeatures([
            'crm' => true,
            'ai' => true,
            'whatsapp' => true,
            'stock' => true,
            'finance' => true,
            'api' => true,
            'white_label' => true,
            'google_maps' => true,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function seedRows(): array
    {
        $features = self::commercialFeatures();

        return [
            [
                'name' => 'Start',
                'slug' => self::START,
                'description' => 'Todo o poder do Expandor para equipes de até 2 vendedores.',
                'price' => 349.00,
                'price_yearly' => 3769.20,
                'trial_days' => null,
                'max_users' => null,
                'max_sellers' => 2,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 10,
                'is_featured' => false,
                'is_public' => true,
                'is_legacy' => false,
                'allows_checkout' => true,
                'features' => $features,
            ],
            [
                'name' => 'Pro',
                'slug' => self::PRO,
                'description' => 'Todo o poder do Expandor para equipes de até 5 vendedores.',
                'price' => 449.00,
                'price_yearly' => 4849.20,
                'trial_days' => null,
                'max_users' => null,
                'max_sellers' => 5,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 20,
                'is_featured' => true,
                'is_public' => true,
                'is_legacy' => false,
                'allows_checkout' => true,
                'features' => $features,
            ],
            [
                'name' => 'Scale',
                'slug' => self::SCALE,
                'description' => 'Todo o poder do Expandor para operações com vendedores ilimitados. Sujeito à política de uso justo.',
                'price' => 649.00,
                'price_yearly' => 7009.20,
                'trial_days' => null,
                'max_users' => null,
                'max_sellers' => null,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 30,
                'is_featured' => false,
                'is_public' => true,
                'is_legacy' => false,
                'allows_checkout' => true,
                'features' => $features,
            ],
            [
                'name' => 'Enterprise',
                'slug' => self::ENTERPRISE,
                'description' => 'Operações maiores, múltiplas necessidades ou integrações especiais. Sob consulta.',
                'price' => 0,
                'price_yearly' => 0,
                'trial_days' => null,
                'max_users' => null,
                'max_sellers' => null,
                'max_properties' => null,
                'max_campaigns' => null,
                'max_teams' => null,
                'max_products' => null,
                'max_storage_mb' => null,
                'max_visits' => null,
                'display_order' => 40,
                'is_featured' => false,
                'is_public' => true,
                'is_legacy' => false,
                'allows_checkout' => false,
                'features' => self::enterpriseFeatures(),
            ],
        ];
    }
}
