<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Onboarding\Events\OnboardingCompanyCompleted;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Sales\Territory\Services\TerritoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveOnboardingCompanyAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected TerritoryService $territory,
        protected MediaUploadService $media,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Company $company, array $data, ?User $actor = null, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($company, $data, $actor, $logo) {
            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? $company->phone,
                'whatsapp' => $data['whatsapp'] ?? $company->whatsapp,
                'address' => $data['address'] ?? $company->address,
            ];

            if ($logo instanceof UploadedFile) {
                $result = $this->media->store(
                    $logo,
                    (int) $company->id,
                    MediaCategory::Branding,
                    MediaPurpose::Logo,
                    $company->logo,
                    null,
                    'logo',
                );
                $payload['logo'] = $result->path;
            }

            $company->update($payload);

            $cityName = trim((string) ($data['city'] ?? ''));
            $state = strtoupper(trim((string) ($data['state'] ?? '')));
            if ($cityName !== '' && $state !== '') {
                $this->territory->upsertCity([
                    'company_id' => $company->id,
                    'name' => $cityName,
                    'state' => $state,
                    'active' => true,
                ]);
            }

            $company = $this->saasOnboarding->advanceTo($company, SaasOnboardingService::STEP_TEAM);
            OnboardingCompanyCompleted::dispatch($company, $actor);

            return $company;
        });
    }
}
