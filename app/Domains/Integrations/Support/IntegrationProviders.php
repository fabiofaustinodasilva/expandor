<?php

namespace App\Domains\Integrations\Support;

final class IntegrationProviders
{
    public const GOOGLE_MAPS = 'google_maps';

    public const MERCADOPAGO = 'mercadopago';

    public const LEAFLET_OSM = 'leaflet_osm';

    public const CATEGORY_MAPS = 'maps';

    public const CATEGORY_PAYMENTS = 'payments';

    public const CATEGORY_COMMUNICATION = 'communication';

    public const MANAGEMENT_TENANT = 'tenant';

    public const MANAGEMENT_PLATFORM = 'platform';

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     category: string,
     *     management: string,
     *     description: string,
     *     plan_feature: ?string,
     *     feature_flag: ?string
     * }
     */
    public static function googleMaps(): array
    {
        return [
            'key' => self::GOOGLE_MAPS,
            'name' => 'Google Maps',
            'category' => self::CATEGORY_MAPS,
            'management' => self::MANAGEMENT_TENANT,
            'description' => 'Mapas premium com credenciais da própria empresa (GCP).',
            'plan_feature' => 'google_maps',
            'feature_flag' => 'integrations.google_maps',
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     category: string,
     *     management: string,
     *     description: string,
     *     plan_feature: ?string,
     *     feature_flag: ?string
     * }
     */
    public static function mercadoPago(): array
    {
        return [
            'key' => self::MERCADOPAGO,
            'name' => 'Mercado Pago',
            'category' => self::CATEGORY_PAYMENTS,
            'management' => self::MANAGEMENT_PLATFORM,
            'description' => 'Checkout SaaS da plataforma Expandor (credenciais globais).',
            'plan_feature' => null,
            'feature_flag' => null,
        ];
    }

    /**
     * @return list<array{key: string, name: string, category: string, management: string, description: string, plan_feature: ?string, feature_flag: ?string}>
     */
    public static function platformCatalog(): array
    {
        return [
            self::googleMaps(),
            self::mercadoPago(),
        ];
    }
}
