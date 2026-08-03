<?php

namespace App\Domains\Platform\Support;

final class PlanCatalog
{
    /**
     * @return list<string>
     */
    public static function featureKeys(): array
    {
        return [
            'crm',
            'ai',
            'whatsapp',
            'stock',
            'finance',
            'api',
            'white_label',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function featureLabels(): array
    {
        return [
            'crm' => 'CRM',
            'ai' => 'IA',
            'whatsapp' => 'WhatsApp',
            'stock' => 'Estoque',
            'finance' => 'Financeiro',
            'api' => 'API',
            'white_label' => 'White Label',
        ];
    }

    /**
     * Normaliza features legadas (lista de strings) para mapa booleano.
     *
     * @param  array<int|string, mixed>|null  $features
     * @return array<string, bool>
     */
    public static function normalizeFeatures(?array $features): array
    {
        $map = array_fill_keys(self::featureKeys(), false);

        if ($features === null) {
            return $map;
        }

        $isList = array_is_list($features);

        if ($isList) {
            foreach ($features as $item) {
                $key = match ((string) $item) {
                    'ai' => 'ai',
                    'whatsapp' => 'whatsapp',
                    'api' => 'api',
                    'crm' => 'crm',
                    'stock', 'estoque' => 'stock',
                    'finance', 'financeiro' => 'finance',
                    'white_label', 'whitelabel' => 'white_label',
                    default => null,
                };
                if ($key !== null) {
                    $map[$key] = true;
                }
            }

            return $map;
        }

        foreach (self::featureKeys() as $key) {
            $map[$key] = (bool) ($features[$key] ?? false);
        }

        return $map;
    }
}
