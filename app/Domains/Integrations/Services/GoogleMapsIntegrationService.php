<?php

namespace App\Domains\Integrations\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoogleMapsIntegrationService
{
    public function __construct(
        private readonly IntegrationEntitlementService $entitlements,
        private readonly GoogleMapsConnectionTester $tester,
        private readonly MapIntegrationResolver $resolver,
        private readonly SecurityService $security,
    ) {}

    public function find(Company $company): ?CompanyIntegration
    {
        return CompanyIntegration::query()
            ->where('company_id', $company->id)
            ->where('provider', IntegrationProviders::GOOGLE_MAPS)
            ->first();
    }

    /**
     * Test + save/activate only when validation succeeds.
     * Failed tests store encrypted credentials with status=error (so user can retry) when $persistOnFailure.
     *
     * @return array{integration: CompanyIntegration, test: array{ok: bool, code: string, message: string}}
     */
    public function connect(Company $company, User $actor, string $browserApiKey, bool $persistOnFailure = true): array
    {
        $this->assertEntitled($company);

        $browserApiKey = trim($browserApiKey);
        $test = $this->tester->test($browserApiKey);

        if (! $test['ok'] && ! $persistOnFailure) {
            throw ValidationException::withMessages([
                'browser_api_key' => $test['message'],
            ]);
        }

        $payload = DB::transaction(function () use ($company, $actor, $browserApiKey, $test): array {
            $existing = $this->find($company);
            $wasNew = $existing === null;

            $integration = $existing ?? new CompanyIntegration([
                'company_id' => $company->id,
                'provider' => IntegrationProviders::GOOGLE_MAPS,
                'category' => IntegrationProviders::CATEGORY_MAPS,
                'created_by' => $actor->id,
            ]);

            $integration->fill([
                'category' => IntegrationProviders::CATEGORY_MAPS,
                'credentials' => ['browser_api_key' => $browserApiKey],
                'configuration' => array_merge($integration->configuration ?? [], [
                    'key_type' => 'browser_web',
                ]),
                'enabled' => $test['ok'],
                'status' => $test['ok']
                    ? CompanyIntegrationStatus::Connected
                    : CompanyIntegrationStatus::Error,
                'last_tested_at' => now(),
                'last_error' => $test['ok'] ? null : $test['message'],
                'updated_by' => $actor->id,
            ]);
            $integration->save();

            $this->resolver->forget($company);

            $this->security->recordAudit(
                action: $wasNew ? 'integration.google_maps.created' : 'integration.google_maps.updated',
                user: $actor,
                auditable: $integration,
                oldValues: null,
                newValues: [
                    'provider' => IntegrationProviders::GOOGLE_MAPS,
                    'status' => $integration->status->value,
                    'enabled' => $integration->enabled,
                    'test_code' => $test['code'],
                    'masked_key' => $integration->maskedBrowserApiKey(),
                ],
                companyId: (int) $company->id,
            );

            $this->security->recordAudit(
                action: 'integration.google_maps.tested',
                user: $actor,
                auditable: $integration,
                oldValues: null,
                newValues: [
                    'ok' => $test['ok'],
                    'code' => $test['code'],
                    'message' => $test['message'],
                ],
                companyId: (int) $company->id,
            );

            if ($test['ok']) {
                $this->security->recordAudit(
                    action: 'integration.google_maps.enabled',
                    user: $actor,
                    auditable: $integration,
                    oldValues: null,
                    newValues: ['enabled' => true, 'status' => 'connected'],
                    companyId: (int) $company->id,
                );
            }

            return [
                'integration' => $integration->fresh(),
                'test' => $test,
            ];
        });

        if (! $test['ok']) {
            throw ValidationException::withMessages([
                'browser_api_key' => $test['message'],
            ]);
        }

        return $payload;
    }

    /**
     * @return array{ok: bool, code: string, message: string, integration: ?CompanyIntegration}
     */
    public function test(Company $company, User $actor, ?string $browserApiKey = null): array
    {
        $this->assertEntitled($company);

        $integration = $this->find($company);
        $key = trim((string) ($browserApiKey ?: $integration?->browserApiKey()));

        if ($key === '') {
            return [
                'ok' => false,
                'code' => 'empty_key',
                'message' => 'Informe a API Key Web para testar.',
                'integration' => $integration,
            ];
        }

        $test = $this->tester->test($key);

        if ($integration instanceof CompanyIntegration) {
            if (filled($browserApiKey)) {
                $integration->credentials = ['browser_api_key' => $key];
            }

            $integration->forceFill([
                'last_tested_at' => now(),
                'last_error' => $test['ok'] ? null : $test['message'],
                'status' => $test['ok']
                    ? CompanyIntegrationStatus::Connected
                    : CompanyIntegrationStatus::Error,
                'enabled' => $test['ok'],
                'updated_by' => $actor->id,
            ])->save();

            $this->resolver->forget($company);
        }

        $this->security->recordAudit(
            action: 'integration.google_maps.tested',
            user: $actor,
            auditable: $integration,
            oldValues: null,
            newValues: [
                'ok' => $test['ok'],
                'code' => $test['code'],
                'message' => $test['message'],
                'persisted' => $integration instanceof CompanyIntegration,
            ],
            companyId: (int) $company->id,
        );

        return [
            'ok' => $test['ok'],
            'code' => $test['code'],
            'message' => $test['message'],
            'integration' => $integration?->fresh(),
        ];
    }

    public function disconnect(Company $company, User $actor): void
    {
        $integration = $this->find($company);
        if (! $integration instanceof CompanyIntegration) {
            return;
        }

        $integration->forceFill([
            'enabled' => false,
            'status' => CompanyIntegrationStatus::Disconnected,
            'credentials' => null,
            'configuration' => $integration->configuration,
            'last_error' => null,
            'updated_by' => $actor->id,
        ])->save();

        $this->resolver->forget($company);

        $this->security->recordAudit(
            action: 'integration.google_maps.disconnected',
            user: $actor,
            auditable: $integration,
            oldValues: ['enabled' => true],
            newValues: [
                'enabled' => false,
                'status' => CompanyIntegrationStatus::Disconnected->value,
                'credentials_cleared' => true,
            ],
            companyId: (int) $company->id,
        );

        $this->security->recordAudit(
            action: 'integration.google_maps.disabled',
            user: $actor,
            auditable: $integration,
            oldValues: null,
            newValues: ['enabled' => false],
            companyId: (int) $company->id,
        );
    }

    public function assertEntitled(Company $company): void
    {
        if (! $this->entitlements->allowsGoogleMaps($company)) {
            throw ValidationException::withMessages([
                'provider' => 'Google Maps não está disponível no plano atual da empresa.',
            ]);
        }
    }

    public function assertEntitledOrAbort(Company $company): void
    {
        if (! $this->entitlements->allowsGoogleMaps($company)) {
            abort(403, 'Google Maps não está disponível no plano atual da empresa.');
        }
    }
}
