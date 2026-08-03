<?php

namespace App\Domains\Onboarding\Services;

use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Domains\Onboarding\Actions\CompleteStepAction;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Territory\Services\TerritoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WizardService
{
    /**
     * @return array<int, string>
     */
    public function wizardOrder(): array
    {
        return [
            'welcome',
            'company',
            'branding',
            'team',
            'cities',
            'sectors',
            'products',
            'campaigns',
            'sellers',
            'finish',
        ];
    }

    public function __construct(
        protected OnboardingRepository $repository,
        protected CompleteStepAction $completeStep,
        protected UserService $users,
        protected BrandingService $branding,
        protected TerritoryService $territory,
    ) {}

    public function nextKey(?string $current): ?string
    {
        $order = $this->wizardOrder();
        if ($current === null) {
            return $order[0];
        }

        $index = array_search($current, $order, true);
        if ($index === false) {
            return $order[0];
        }

        return $order[$index + 1] ?? null;
    }

    public function previousKey(?string $current): ?string
    {
        $order = $this->wizardOrder();
        $index = array_search($current, $order, true);

        if ($index === false || $index === 0) {
            return null;
        }

        return $order[$index - 1];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveStep(Company $company, string $wizardKey, array $data, ?User $actor = null): void
    {
        DB::transaction(function () use ($company, $wizardKey, $data, $actor): void {
            match ($wizardKey) {
                'welcome' => null,
                'company' => $this->saveCompany($company, $data),
                'branding' => $this->saveBranding($company, $data),
                'team' => $this->saveTeam($company, $data),
                'cities' => $this->saveCity($data),
                'sectors' => $this->saveSector($data),
                'products' => $this->saveProduct($company, $data),
                'campaigns' => null,
                'sellers' => $this->saveTeam($company, array_merge($data, ['role' => Role::SELLER])),
                'finish' => null,
                default => throw ValidationException::withMessages([
                    'step' => ['Etapa do wizard inválida.'],
                ]),
            };

            $stepKey = $wizardKey === 'welcome' ? null : $wizardKey;
            if ($stepKey !== null && $this->repository->findStepByKey($stepKey)) {
                $this->completeStep->execute($company, $stepKey, $actor, ['wizard' => true]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveCompany(Company $company, array $data): void
    {
        $this->users->updateCompany($company, [
            'name' => $data['name'] ?? $company->name,
            'legal_name' => $data['legal_name'] ?? $company->legal_name,
            'document' => $data['document'] ?? $company->document,
            'email' => $data['email'] ?? $company->email,
            'phone' => $data['phone'] ?? $company->phone,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveBranding(Company $company, array $data): void
    {
        $this->branding->update($company, [
            'system_name' => $data['system_name'] ?? config('app.name'),
            'display_name' => $data['display_name'] ?? $company->name,
            'theme' => $data['theme'] ?? 'dark',
            'colors' => $data['colors'] ?? [],
            'fonts' => $data['fonts'] ?? [],
            'support_email' => $data['support_email'] ?? $company->email,
            'support_phone' => $data['support_phone'] ?? $company->phone,
            'socials' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveTeam(Company $company, array $data): void
    {
        if (empty($data['name']) || empty($data['email'])) {
            return;
        }

        $roleId = Role::query()->where('slug', $data['role'] ?? Role::MANAGER)->value('id');
        $email = (string) $data['email'];

        $existing = User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', $email)
            ->first();

        if ($existing !== null) {
            $this->users->update($existing, [
                'role_id' => $roleId,
                'name' => $data['name'],
                'email' => $email,
                'password' => $data['password'] ?? null,
                'status' => User::STATUS_ACTIVE,
            ]);

            return;
        }

        $this->users->create([
            'role_id' => $roleId,
            'name' => $data['name'],
            'email' => $email,
            'password' => $data['password'] ?? 'password',
            'status' => User::STATUS_ACTIVE,
        ], $company);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveCity(array $data): void
    {
        if (empty($data['name']) || empty($data['state'])) {
            return;
        }

        $this->territory->createCity([
            'name' => $data['name'],
            'state' => $data['state'],
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveSector(array $data): void
    {
        if (empty($data['name']) || empty($data['city_id'])) {
            return;
        }

        $this->territory->createSector([
            'city_id' => $data['city_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function saveProduct(Company $company, array $data): void
    {
        if (empty($data['name'])) {
            return;
        }

        Product::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $company->id,
                'name' => $data['name'],
            ],
            [
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? 0,
                'commission_amount' => $data['commission_amount'] ?? 0,
                'stock_control' => (bool) ($data['stock_control'] ?? false),
                'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
                'minimum_stock' => (int) ($data['minimum_stock'] ?? 0),
                'status' => Product::STATUS_ACTIVE,
                'is_demo' => false,
            ]
        );
    }
}
