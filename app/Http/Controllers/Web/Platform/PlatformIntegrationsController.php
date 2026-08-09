<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Company\Models\Plan;
use App\Domains\Payments\Models\PaymentGatewaySetting;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlatformIntegrationsController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('platform.managePlans');

        $googleMeta = IntegrationProviders::googleMaps();
        $mpMeta = IntegrationProviders::mercadoPago();

        $connectedCompanies = CompanyIntegration::query()
            ->withoutGlobalScopes()
            ->where('provider', IntegrationProviders::GOOGLE_MAPS)
            ->where('status', CompanyIntegrationStatus::Connected)
            ->where('enabled', true)
            ->count();

        $plansWithGoogle = Plan::query()
            ->where('status', Plan::STATUS_ACTIVE)
            ->get()
            ->filter(fn (Plan $plan) => $plan->hasCatalogFeature('google_maps'))
            ->map(fn (Plan $plan) => $plan->name)
            ->values()
            ->all();

        $mp = PaymentGatewaySetting::forProvider(PaymentGatewaySetting::PROVIDER_MERCADOPAGO);

        return view('platform.integrations.index', [
            'integrations' => [
                [
                    'name' => $googleMeta['name'],
                    'category' => 'Mapas',
                    'management' => 'Tenant-managed',
                    'description' => $googleMeta['description'],
                    'connected_companies' => $connectedCompanies,
                    'plans' => $plansWithGoogle,
                    'platform_status' => 'Disponível',
                    'notes' => 'Credenciais pertencem a cada empresa. API keys não são exibidas aqui.',
                    'manage_route' => null,
                ],
                [
                    'name' => $mpMeta['name'],
                    'category' => 'Pagamentos',
                    'management' => 'Platform-managed',
                    'description' => $mpMeta['description'],
                    'connected_companies' => null,
                    'plans' => [],
                    'platform_status' => $mp->active ? 'Ativo ('.$mp->mode.')' : 'Inativo',
                    'notes' => 'Usa PaymentGatewaySetting / .env. Sem alteração nesta sprint.',
                    'manage_route' => route('platform.marketplace.mercadopago.edit'),
                ],
            ],
        ]);
    }
}
