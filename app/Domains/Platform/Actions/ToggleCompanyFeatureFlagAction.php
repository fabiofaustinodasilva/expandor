<?php

namespace App\Domains\Platform\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\FeatureFlag;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Validation\ValidationException;

class ToggleCompanyFeatureFlagAction
{
    public function __construct(
        protected PlatformConsoleRepository $repository,
        protected SecurityService $security,
    ) {}

    public function execute(Company $company, FeatureFlag $flag, bool $enabled, User $actor): CompanyFeatureFlag
    {
        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company_id' => ['Não é permitido alterar flags da empresa sistema.'],
            ]);
        }

        $existing = $this->repository->companyOverride($company, $flag);
        $old = $existing?->enabled;

        $override = CompanyFeatureFlag::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'feature_flag_id' => $flag->id,
            ],
            [
                'enabled' => $enabled,
                'updated_by' => $actor->id,
            ]
        );

        $this->security->recordAudit(
            action: 'platform.feature_flag.toggled',
            user: $actor,
            auditable: $company,
            oldValues: ['flag' => $flag->key, 'enabled' => $old],
            newValues: ['flag' => $flag->key, 'enabled' => $enabled],
            companyId: $company->id,
        );

        return $override->fresh('featureFlag');
    }
}
