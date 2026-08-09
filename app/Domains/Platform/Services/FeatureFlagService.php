<?php

namespace App\Domains\Platform\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Actions\ToggleCompanyFeatureFlagAction;
use App\Domains\Platform\DTOs\FeatureFlagStateDTO;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\FeatureFlag;
use App\Domains\Platform\Repositories\PlatformConsoleRepository;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    public function __construct(
        protected PlatformConsoleRepository $repository,
        protected ToggleCompanyFeatureFlagAction $toggle,
    ) {}

    /**
     * @return Collection<int, FeatureFlagStateDTO>
     */
    public function statesForCompany(Company $company): Collection
    {
        $overrides = $this->repository->overridesForCompany($company)->keyBy('feature_flag_id');

        return $this->repository->activeFlags()->map(function (FeatureFlag $flag) use ($overrides) {
            /** @var CompanyFeatureFlag|null $override */
            $override = $overrides->get($flag->id);

            return new FeatureFlagStateDTO(
                flagId: $flag->id,
                key: $flag->key,
                name: $flag->name,
                description: $flag->description,
                defaultEnabled: $flag->default_enabled,
                enabled: $override?->enabled ?? $flag->default_enabled,
                hasOverride: $override !== null,
            );
        });
    }

    public function isEnabled(Company $company, string $key): bool
    {
        $flag = $this->repository->findFlagByKey($key);

        if ($flag === null) {
            return false;
        }

        $override = $this->repository->companyOverride($company, $flag);

        return $override?->enabled ?? $flag->default_enabled;
    }

    /**
     * Whether an active feature-flag row exists for the key (platform kill-switch catalog).
     */
    public function exists(string $key): bool
    {
        return $this->repository->findFlagByKey($key) !== null;
    }

    public function toggle(Company $company, string $key, bool $enabled, User $actor): CompanyFeatureFlag
    {
        $flag = $this->repository->findFlagByKey($key);

        abort_if($flag === null, 404);

        return $this->toggle->execute($company, $flag, $enabled, $actor);
    }

    /**
     * @return Collection<int, FeatureFlag>
     */
    public function catalog(): Collection
    {
        return $this->repository->activeFlags();
    }
}
