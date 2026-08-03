<?php

namespace App\Domains\Security\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Security\Enums\ConsentType;
use App\Domains\Security\Enums\PrivacyRequestStatus;
use App\Domains\Security\Models\AnonymizationRequest;
use App\Domains\Security\Models\Consent;
use App\Domains\Security\Models\DataExportRequest;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PrivacyService
{
    public function __construct(
        protected SecurityService $security,
        protected TenantContext $tenant,
    ) {}

    public function paginateExports(?Company $company = null, int $perPage = 20): LengthAwarePaginator
    {
        return DataExportRequest::query()
            ->when($company, fn ($q) => $q->withoutGlobalScopes()->where('company_id', $company->id))
            ->with('requester:id,name,email')
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateAnonymizations(?Company $company = null, int $perPage = 20): LengthAwarePaginator
    {
        return AnonymizationRequest::query()
            ->when($company, fn ($q) => $q->withoutGlobalScopes()->where('company_id', $company->id))
            ->with(['requester:id,name,email', 'processor:id,name'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateConsents(?Company $company = null, int $perPage = 20): LengthAwarePaginator
    {
        return Consent::query()
            ->when($company, fn ($q) => $q->withoutGlobalScopes()->where('company_id', $company->id))
            ->latest('id')
            ->paginate($perPage);
    }

    public function requestCompanyExport(Company $company, User $actor, string $format = 'json'): DataExportRequest
    {
        $this->assertSameCompany($company, $actor);

        $export = DataExportRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $actor->id,
            'status' => PrivacyRequestStatus::PROCESSING,
            'format' => $format,
            'requested_at' => now(),
        ]);

        try {
            $payload = $this->buildCompanyExportPayload($company);
            $relativePath = trim((string) config('security.export.path'), '/')."/company-{$company->id}-{$export->id}.json";
            Storage::disk((string) config('security.export.disk', 'local'))
                ->put($relativePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $export->update([
                'status' => PrivacyRequestStatus::READY,
                'file_path' => $relativePath,
                'completed_at' => now(),
                'expires_at' => now()->addHours((int) config('security.export.expires_hours', 24)),
            ]);
        } catch (\Throwable $exception) {
            $export->update([
                'status' => PrivacyRequestStatus::FAILED,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        $this->security->recordAudit(
            action: 'privacy.export.requested',
            user: $actor,
            auditable: $export,
            newValues: ['format' => $format, 'company_id' => $company->id],
            companyId: $company->id,
        );

        return $export->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportPayloadForCompany(Company $company, User $actor): array
    {
        $this->assertSameCompany($company, $actor);

        return $this->buildCompanyExportPayload($company);
    }

    public function requestAnonymization(
        Company $company,
        User $actor,
        Model $subject,
        ?string $reason = null,
    ): AnonymizationRequest {
        $this->assertSameCompany($company, $actor);

        if ((int) ($subject->getAttribute('company_id') ?? 0) !== (int) $company->id) {
            throw ValidationException::withMessages([
                'subject' => ['O registro não pertence à empresa atual.'],
            ]);
        }

        $request = AnonymizationRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $actor->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'status' => PrivacyRequestStatus::PENDING,
            'reason' => $reason,
            'requested_at' => now(),
        ]);

        $this->security->recordAudit(
            action: 'privacy.anonymization.requested',
            user: $actor,
            auditable: $request,
            newValues: [
                'subject_type' => $request->subject_type,
                'subject_id' => $request->subject_id,
                'reason' => $reason,
            ],
            companyId: $company->id,
        );

        return $request;
    }

    public function processAnonymization(AnonymizationRequest $request, User $actor): AnonymizationRequest
    {
        $company = Company::query()->findOrFail($request->company_id);
        $this->assertSameCompany($company, $actor);

        return DB::transaction(function () use ($request, $actor, $company) {
            $request->refresh();

            if ($request->status !== PrivacyRequestStatus::PENDING) {
                throw ValidationException::withMessages([
                    'status' => ['Somente solicitações pendentes podem ser processadas.'],
                ]);
            }

            /** @var class-string<Model>|null $type */
            $type = $request->subject_type;
            $subject = $type
                ? $type::query()->withoutGlobalScopes()->find($request->subject_id)
                : null;

            $oldValues = null;
            if ($subject instanceof Resident) {
                $oldValues = $subject->only(['name', 'email', 'phone', 'document', 'notes']);
                $subject->update([
                    'name' => 'Titular Anonimizado',
                    'email' => null,
                    'phone' => null,
                    'document' => null,
                    'notes' => 'Dados anonimizados conforme solicitação LGPD #'.$request->id,
                ]);
            } elseif ($subject instanceof User) {
                $oldValues = $subject->only(['name', 'email', 'phone']);
                $subject->update([
                    'name' => 'Usuário Anonimizado',
                    'email' => 'anonimizado+'.$subject->id.'@privacy.invalid',
                    'phone' => null,
                    'status' => User::STATUS_INACTIVE,
                ]);
            } else {
                throw ValidationException::withMessages([
                    'subject' => ['Tipo de registro não suportado para anonimização.'],
                ]);
            }

            $request->update([
                'status' => PrivacyRequestStatus::COMPLETED,
                'processed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $this->security->recordAudit(
                action: 'privacy.anonymization.completed',
                user: $actor,
                auditable: $subject,
                oldValues: $oldValues,
                newValues: ['anonymization_request_id' => $request->id],
                companyId: $company->id,
            );

            return $request->refresh();
        });
    }

    public function recordConsent(
        Company $company,
        Model $subject,
        ConsentType|string $type,
        bool $granted = true,
        ?string $source = 'manual',
        ?User $actor = null,
        ?array $meta = null,
    ): Consent {
        $type = $type instanceof ConsentType ? $type : ConsentType::from($type);

        if ((int) ($subject->getAttribute('company_id') ?? 0) !== (int) $company->id) {
            throw ValidationException::withMessages([
                'subject' => ['O registro não pertence à empresa atual.'],
            ]);
        }

        $consent = Consent::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'consent_type' => $type->value,
            ],
            [
                'granted' => $granted,
                'source' => $source,
                'captured_at' => now(),
                'revoked_at' => $granted ? null : now(),
                'meta' => $meta,
            ]
        );

        $this->security->recordAudit(
            action: $granted ? 'privacy.consent.granted' : 'privacy.consent.revoked',
            user: $actor,
            auditable: $consent,
            newValues: [
                'consent_type' => $type->value,
                'granted' => $granted,
                'subject_type' => $consent->subject_type,
                'subject_id' => $consent->subject_id,
            ],
            companyId: $company->id,
        );

        return $consent->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCompanyExportPayload(Company $company): array
    {
        $users = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']);

        $residents = Resident::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get(['id', 'name', 'email', 'phone', 'document', 'status', 'created_at']);

        $properties = Property::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get(['id', 'type', 'status', 'latitude', 'longitude', 'created_at']);

        $consents = Consent::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        return [
            'exported_at' => now()->toIso8601String(),
            'company' => $company->only(['id', 'name', 'legal_name', 'document', 'email', 'phone', 'status']),
            'users' => $users->toArray(),
            'residents' => $residents->toArray(),
            'properties' => $properties->toArray(),
            'consents' => $consents->toArray(),
        ];
    }

    protected function assertSameCompany(Company $company, User $actor): void
    {
        if ((int) $actor->company_id !== (int) $company->id) {
            throw new RuntimeException('Usuário não pertence à empresa solicitada.');
        }
    }
}
