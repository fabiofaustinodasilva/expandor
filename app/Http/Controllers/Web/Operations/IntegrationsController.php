<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Services\IntegrationEntitlementService;
use App\Domains\Integrations\Services\MapIntegrationResolver;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function __invoke(
        Request $request,
        IntegrationEntitlementService $entitlements,
        MapIntegrationResolver $resolver,
    ): View {
        $user = $request->user();
        abort_unless($user?->hasPermission('integrations.view') ?? false, 403, 'Access denied.');

        $this->authorize('viewAny', CompanyIntegration::class);

        $company = $user->company;
        abort_unless($company !== null, 403);

        $google = IntegrationProviders::googleMaps();
        $entitled = $entitlements->allowsGoogleMaps($company);
        $integration = CompanyIntegration::query()
            ->where('company_id', $company->id)
            ->where('provider', IntegrationProviders::GOOGLE_MAPS)
            ->first();

        $googleCard = $this->googleCardState($entitled, $integration);
        $decision = $resolver->resolve($company);

        return view('operations.integrations', [
            'canManage' => $user->hasPermission('integrations.manage'),
            'billingNotice' => 'Serviços externos podem possuir cobrança própria. A cobrança e os limites do Google Maps são administrados na conta Google Cloud da sua empresa.',
            'categories' => [
                [
                    'key' => 'maps',
                    'title' => 'Mapas e localização',
                    'items' => [
                        [
                            'key' => IntegrationProviders::GOOGLE_MAPS,
                            'name' => $google['name'],
                            'description' => $google['description'],
                            'icon' => 'map',
                            'state' => $googleCard['state'],
                            'status_label' => $googleCard['label'],
                            'cta' => $googleCard['cta'],
                            'cta_route' => $googleCard['route'],
                            'cta_disabled' => $googleCard['disabled'],
                            'secondary' => $googleCard['secondary'] ?? null,
                        ],
                        [
                            'key' => IntegrationProviders::LEAFLET_OSM,
                            'name' => 'Mapa padrão Expandor',
                            'description' => 'Leaflet + OpenStreetMap / satélite provisório. Sempre disponível como fallback.',
                            'icon' => 'globe',
                            'state' => 'fallback',
                            'status_label' => 'Ativo (fallback)',
                            'cta' => null,
                            'cta_route' => null,
                            'cta_disabled' => true,
                            'secondary' => 'Provider atual do mapa: '.$decision->visualProvider(),
                        ],
                    ],
                ],
                [
                    'key' => 'communication',
                    'title' => 'Comunicação',
                    'items' => [
                        [
                            'key' => 'whatsapp',
                            'name' => 'WhatsApp',
                            'description' => 'Envio e histórico de mensagens por empresa.',
                            'icon' => 'message-circle',
                            'state' => 'soon',
                            'status_label' => 'Em breve',
                            'cta' => null,
                            'cta_route' => null,
                            'cta_disabled' => true,
                            'secondary' => null,
                        ],
                    ],
                ],
                [
                    'key' => 'payments',
                    'title' => 'Pagamentos',
                    'items' => [
                        [
                            'key' => IntegrationProviders::MERCADOPAGO,
                            'name' => 'Mercado Pago',
                            'description' => 'Checkout da assinatura Expandor é gerenciado pela plataforma (não configurável aqui).',
                            'icon' => 'credit-card',
                            'state' => 'platform',
                            'status_label' => 'Plataforma',
                            'cta' => null,
                            'cta_route' => null,
                            'cta_disabled' => true,
                            'secondary' => null,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @return array{state: string, label: string, cta: ?string, route: ?string, disabled: bool, secondary?: ?string}
     */
    private function googleCardState(bool $entitled, ?CompanyIntegration $integration): array
    {
        if (! $entitled) {
            return [
                'state' => 'not_available',
                'label' => 'Disponível em plano superior',
                'cta' => 'Ver planos',
                                'route' => route('company.plan.show'),
                                'disabled' => false,
                                'secondary' => null,
                            ];
                        }

                        if ($integration === null || $integration->status === CompanyIntegrationStatus::Disconnected || ! $integration->hasBrowserApiKey()) {
            return [
                'state' => 'not_configured',
                'label' => 'Não conectado',
                'cta' => 'Configurar',
                'route' => route('operations.integrations.google-maps.edit'),
                'disabled' => false,
            ];
        }

        if ($integration->status === CompanyIntegrationStatus::Error) {
            return [
                'state' => 'error',
                'label' => 'Erro na conexão',
                'cta' => 'Gerenciar',
                'route' => route('operations.integrations.google-maps.edit'),
                'disabled' => false,
                'secondary' => $integration->last_error,
            ];
        }

        if ($integration->isUsable()) {
            return [
                'state' => 'connected',
                'label' => 'Conectado',
                'cta' => 'Gerenciar',
                'route' => route('operations.integrations.google-maps.edit'),
                'disabled' => false,
            ];
        }

        return [
            'state' => 'not_configured',
            'label' => 'Não conectado',
            'cta' => 'Configurar',
            'route' => route('operations.integrations.google-maps.edit'),
            'disabled' => false,
        ];
    }
}
