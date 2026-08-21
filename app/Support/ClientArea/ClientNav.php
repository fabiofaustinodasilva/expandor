<?php

namespace App\Support\ClientArea;

use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;

/**
 * Sprint 8.2.2 — Estrutura de navegação da área do cliente.
 *
 * Centraliza os itens de navegação (rail primário, seções secundárias) para
 * que layouts/partials fiquem simples: eles apenas iteram o array já
 * filtrado por permissão/flag/plano (via NavVisibility).
 */
final class ClientNav
{
    /**
     * Itens do rail primário (ícones), já filtrados para o usuário logado.
     *
     * @return list<array{module: string, label: string, icon: string, route: string, params: array, patterns: list<string>}>
     */
    public static function railItems(User $user): array
    {
        $items = self::isFieldSeller($user) ? self::sellerRail() : self::adminRail();

        return self::visible($user, $items);
    }

    /**
     * Seções de navegação secundária (usadas no app-nav e na página "Mais").
     *
     * @return list<array{key: string, label: string, icon: string, items: list<array<string, mixed>>}>
     */
    public static function sections(User $user): array
    {
        $sections = [];

        foreach (self::rawSections($user) as $section) {
            $items = self::visible($user, $section['items']);

            if (self::isFieldSeller($user)) {
                $items = self::filterSellerMaisItems($items);
            }

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'key' => $section['key'],
                'label' => $section['label'],
                'icon' => $section['icon'],
                'items' => $items,
            ];
        }

        return $sections;
    }

    public static function isFieldSeller(User $user): bool
    {
        return $user->role?->slug === Role::SELLER;
    }

    /**
     * Sprint 8.2.16 — esconde superfícies administrativas/duplicadas do Mais seller.
     * Rotas/backend permanecem; só a superfície de navegação é filtrada.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function filterSellerMaisItems(array $items): array
    {
        $denyRoutes = [
            'crm.dashboard',
            'crm.commissions.index',
            'properties.index',
            'cities.index',
            'sectors.index',
            'sales-app.dashboard',
            'reports.index',
            'operations.team',
            'operations.settings',
            'operations.integrations',
            'company.branding.edit',
            'company.plan.show',
            'company.subscription.show',
            'company.finance.index',
            'company.audit.index',
            'commissions.products.index',
        ];

        return array_values(array_filter(
            $items,
            fn (array $item): bool => ! in_array($item['route'] ?? '', $denyRoutes, true)
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private static function visible(User $user, array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $item): bool => NavVisibility::can($user, $item['module'])
        ));
    }

    /**
     * @return list<array{module: string, label: string, icon: string, route: string, params: array, patterns: list<string>}>
     */
    private static function adminRail(): array
    {
        return [
            ['module' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'gauge', 'route' => 'dashboard', 'params' => [], 'patterns' => ['dashboard']],
            ['module' => 'map', 'label' => 'Mapa', 'icon' => 'map-pinned', 'route' => 'map.index', 'params' => [], 'patterns' => ['map.*']],
            ['module' => 'campaigns', 'label' => 'Campanhas', 'icon' => 'target', 'route' => 'campaigns.index', 'params' => [], 'patterns' => ['campaigns.*']],
            ['module' => 'customers', 'label' => 'Clientes', 'icon' => 'contact', 'route' => 'customers.index', 'params' => [], 'patterns' => ['customers.*']],
            ['module' => 'team', 'label' => 'Equipe', 'icon' => 'users', 'route' => 'operations.team', 'params' => [], 'patterns' => ['operations.team']],
            ['module' => 'stock', 'label' => 'Produtos', 'icon' => 'package', 'route' => 'commissions.products.index', 'params' => [], 'patterns' => ['commissions.products.*']],
            ['module' => 'commissions', 'label' => 'Financeiro', 'icon' => 'wallet', 'route' => 'commissions.index', 'params' => [], 'patterns' => ['commissions.index']],
        ];
    }

    /**
     * @return list<array{module: string, label: string, icon: string, route: string, params: array, patterns: list<string>}>
     */
    private static function sellerRail(): array
    {
        return [
            ['module' => 'map', 'label' => 'Mapa', 'icon' => 'map-pinned', 'route' => 'map.index', 'params' => [], 'patterns' => ['map.*']],
            ['module' => 'visits', 'label' => 'Agenda', 'icon' => 'calendar-clock', 'route' => 'follow-ups.index', 'params' => [], 'patterns' => ['follow-ups.*']],
            ['module' => 'customers', 'label' => 'Clientes', 'icon' => 'contact', 'route' => 'customers.index', 'params' => [], 'patterns' => ['customers.*']],
            ['module' => 'dashboard', 'label' => 'Resultado', 'icon' => 'bar-chart-3', 'route' => 'dashboard', 'params' => [], 'patterns' => ['dashboard']],
            ['module' => 'commissions', 'label' => 'Comissão', 'icon' => 'wallet', 'route' => 'commissions.index', 'params' => [], 'patterns' => ['commissions.index']],
            ['module' => 'sales_app', 'label' => 'Apresentar', 'icon' => 'package', 'route' => 'sales-app.products.present', 'params' => [], 'patterns' => ['sales-app.products.*']],
        ];
    }

    /**
     * @return list<array{key: string, label: string, icon: string, items: list<array<string, mixed>>}>
     */
    private static function rawSections(User $user): array
    {
        return [
            [
                'key' => 'inicio',
                'label' => 'Início',
                'icon' => 'home',
                'items' => [
                    ['module' => 'dashboard', 'label' => 'Painel', 'route' => 'dashboard', 'params' => [], 'patterns' => ['dashboard']],
                    ['module' => 'map', 'label' => 'Mapa', 'route' => 'map.index', 'params' => [], 'patterns' => ['map.*']],
                    ['module' => 'reports', 'label' => 'Atalhos de análise', 'route' => 'reports.index', 'params' => [], 'patterns' => ['reports.index']],
                    ['module' => 'sales_app', 'label' => 'App de campo', 'route' => 'sales-app.dashboard', 'params' => [], 'patterns' => ['sales-app.*']],
                ],
            ],
            [
                'key' => 'comercial',
                'label' => 'Comercial',
                'icon' => 'briefcase',
                'items' => [
                    ['module' => 'crm', 'label' => 'Clientes e oportunidades', 'route' => 'crm.dashboard', 'params' => [], 'patterns' => ['crm.dashboard', 'crm.opportunities.*', 'crm.leads.*']],
                    ['module' => 'campaigns', 'label' => 'Campanhas', 'route' => 'campaigns.index', 'params' => [], 'patterns' => ['campaigns.*']],
                    ['module' => 'visits', 'label' => 'Agenda', 'route' => 'follow-ups.index', 'params' => [], 'patterns' => ['follow-ups.*', 'visits.*']],
                    ['module' => 'customers', 'label' => 'Clientes', 'route' => 'customers.index', 'params' => [], 'patterns' => ['customers.*']],
                    ['module' => 'points', 'label' => 'Pontos', 'route' => 'properties.index', 'params' => [], 'patterns' => ['properties.*', 'residents.*']],
                    ['module' => 'territory', 'label' => 'Cidades', 'route' => 'cities.index', 'params' => [], 'patterns' => ['cities.*']],
                    ['module' => 'territory', 'label' => 'Setores', 'route' => 'sectors.index', 'params' => [], 'patterns' => ['sectors.*']],
                    ['module' => 'commissions', 'label' => 'Comissões', 'route' => 'commissions.index', 'params' => [], 'patterns' => ['commissions.index']],
                    ['module' => 'stock', 'label' => 'Produtos', 'route' => 'commissions.products.index', 'params' => [], 'patterns' => ['commissions.products.*']],
                    ['module' => 'commission_rules', 'label' => 'Regras de comissão', 'route' => 'crm.commissions.index', 'params' => [], 'patterns' => ['crm.commissions.*']],
                ],
            ],
            [
                'key' => 'empresa',
                'label' => 'Empresa',
                'icon' => 'building-2',
                'items' => [
                    ['module' => 'team', 'label' => 'Equipe', 'route' => 'operations.team', 'params' => [], 'patterns' => ['operations.team']],
                    ['module' => 'settings', 'label' => 'Configurações', 'route' => 'operations.settings', 'params' => [], 'patterns' => ['operations.settings', 'operations.settings.*']],
                    // Produtos fica no rail principal + seção Comercial (evitar duplicata Empresa).
                    ['module' => 'branding', 'label' => 'Identidade visual', 'route' => 'company.branding.edit', 'params' => [], 'patterns' => ['company.branding.*']],
                    ['module' => 'integrations', 'label' => 'Integrações', 'route' => 'operations.integrations', 'params' => [], 'patterns' => ['operations.integrations']],
                    ['module' => 'billing', 'label' => 'Financeiro', 'route' => 'company.finance.index', 'params' => [], 'patterns' => ['company.finance.*']],
                    ['module' => 'billing', 'label' => 'Plano e uso', 'route' => 'company.plan.show', 'params' => [], 'patterns' => ['company.plan.*']],
                    ['module' => 'billing', 'label' => 'Minha assinatura', 'route' => 'company.subscription.show', 'params' => [], 'patterns' => ['company.subscription.*']],
                ],
            ],
            [
                'key' => 'sistema',
                'label' => 'Sistema',
                'icon' => 'shield',
                'items' => [
                    ['module' => 'audit', 'label' => 'Auditoria', 'route' => 'company.audit.index', 'params' => [], 'patterns' => ['company.audit.*']],
                    ['module' => 'privacy', 'label' => 'Privacidade', 'route' => 'company.privacy.index', 'params' => [], 'patterns' => ['company.privacy.*']],
                    ['module' => 'whatsapp', 'label' => 'WhatsApp', 'route' => 'communication.messages.index', 'params' => [], 'patterns' => ['communication.*']],
                    ['module' => 'ai', 'label' => 'Assistente Expandor', 'route' => 'ai.conversations.index', 'params' => [], 'patterns' => ['ai.*']],
                    ['module' => 'training', 'label' => 'Academia', 'route' => 'training.categories.index', 'params' => [], 'patterns' => ['training.categories.*', 'training.contents.*']],
                ],
            ],
        ];
    }
}
