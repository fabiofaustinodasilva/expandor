<?php

namespace App\Support\ClientArea;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Services\FeatureFlagService;
use Illuminate\Support\Collection;

/**
 * Sprint 8.2.2 — Camada única de visibilidade de navegação da área do cliente.
 *
 * Regra: permissão AND (flag opcional) AND (feature de plano opcional).
 *
 * Tenants legados (sem assinatura, ou com plano cujo featureMap() é
 * totalmente falso/vazio) NÃO devem ter módulos escondidos por causa do
 * catálogo de features — nesse caso a feature de plano é ignorada e a
 * visibilidade depende apenas da permissão. Só passamos a exigir a feature
 * do catálogo quando o plano já tem ao menos uma feature configurada como
 * verdadeira (ou seja, a empresa já passou por um processo de configuração
 * de features do plano).
 */
final class NavVisibility
{
    /**
     * Mapa de módulos → regras de visibilidade.
     *
     * @var array<string, array{permissions: list<string>, flag?: string, plan_feature?: string}>
     */
    private const MODULES = [
        'dashboard' => ['permissions' => ['dashboard.view']],
        'map' => ['permissions' => ['maps.view']],
        'campaigns' => ['permissions' => ['campaigns.view']],
        'points' => ['permissions' => ['properties.view']],
        'visits' => ['permissions' => ['visits.view']],
        'sales' => ['permissions' => ['visits.contract', 'visits.view']],
        'customers' => ['permissions' => ['customers.view']],
        'team' => ['permissions' => ['users.view', 'users.manage']],
        'crm' => ['permissions' => ['crm.view'], 'plan_feature' => 'crm'],
        'commissions' => ['permissions' => ['commissions.manage', 'commissions.view_self']],
        'commission_rules' => ['permissions' => ['crm.view']],
        'stock' => ['permissions' => ['commissions.manage'], 'plan_feature' => 'stock'],
        'reports' => ['permissions' => ['reports.view']],
        'branding' => ['permissions' => ['branding.manage']],
        'integrations' => ['permissions' => ['integrations.view']],
        'whatsapp' => ['permissions' => ['communication.view'], 'flag' => 'whatsapp.enabled', 'plan_feature' => 'whatsapp'],
        'ai' => ['permissions' => ['ai.access'], 'flag' => 'ai.enabled', 'plan_feature' => 'ai'],
        'training' => ['permissions' => ['training.view', 'training.manage']],
        'billing' => ['permissions' => ['billing.view']],
        'sales_app' => ['permissions' => ['sales_app.access'], 'flag' => 'mobile.enabled'],
        'audit' => ['permissions' => ['audit.view']],
        'privacy' => ['permissions' => ['privacy.view']],
        'territory' => ['permissions' => ['cities.view', 'sectors.view']],
    ];

    /**
     * @return list<string>
     */
    public static function modules(): array
    {
        return array_keys(self::MODULES);
    }

    public static function can(?User $user, string $module): bool
    {
        if ($user === null) {
            return false;
        }

        $config = self::MODULES[$module] ?? null;

        if ($config === null) {
            return false;
        }

        if (! self::hasAnyPermission($user, $config['permissions'])) {
            return false;
        }

        $company = $user->company;

        if (isset($config['flag'])) {
            if (! $company instanceof Company) {
                return false;
            }

            if (! app(FeatureFlagService::class)->isEnabled($company, $config['flag'])) {
                return false;
            }
        }

        if (isset($config['plan_feature']) && ! self::passesPlanFeature($user, $config['plan_feature'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  list<string>  $permissions
     */
    private static function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    private static function passesPlanFeature(User $user, string $key): bool
    {
        $plan = self::planFor($user);

        if (! $plan instanceof Plan) {
            // Empresa sem plano/assinatura — tenant legado, não bloqueia por feature.
            return true;
        }

        $featureMap = Collection::make($plan->featureMap());

        if (! $featureMap->contains(true)) {
            // Plano com catálogo de features vazio/todo falso — não restringe (legado).
            return true;
        }

        return (bool) ($featureMap->get($key) ?? false);
    }

    private static function planFor(User $user): ?Plan
    {
        $company = $user->company;

        if (! $company instanceof Company) {
            return null;
        }

        return $company->latestSubscription()?->plan;
    }
}
