<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Services\CampaignService;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Services\VisitService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GenerateDemoDataAction
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected TerritoryService $territory,
        protected PropertyService $properties,
        protected ResidentService $residents,
        protected CampaignService $campaigns,
        protected VisitService $visits,
        protected UserService $users,
        protected TenantContext $tenant,
        protected CompleteStepAction $completeStep,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Company $company, ?User $actor = null): array
    {
        $run = $this->repository->runForCompany($company);

        if ($run?->demo_generated) {
            throw ValidationException::withMessages([
                'demo' => ['Os dados de demonstração já foram gerados para esta empresa.'],
            ]);
        }

        $template = $this->repository->demoTemplate();
        $payload = $template?->payload ?? [];

        $previousCompany = $this->tenant->company();
        $previousUser = $this->tenant->user();
        $this->tenant->set($company, $actor);

        try {
            $result = DB::transaction(function () use ($company, $actor, $payload) {
                $cityData = $payload['city'] ?? ['name' => 'Demoópolis', 'state' => 'SP'];
                $city = $this->territory->createCity([
                    'name' => $cityData['name'],
                    'state' => $cityData['state'],
                    'active' => true,
                ]);

                $sectors = [];
                foreach (($payload['sectors'] ?? ['Centro', 'Jardins']) as $sectorName) {
                    $sectors[] = $this->territory->createSector([
                        'city_id' => $city->id,
                        'name' => $sectorName,
                        'description' => 'Setor demonstrativo',
                        'active' => true,
                    ]);
                }

                $products = [];
                foreach (($payload['products'] ?? [['name' => '[Demo] Plano Básico', 'price' => 99.9]]) as $product) {
                    $products[] = Product::query()->create([
                        'company_id' => $company->id,
                        'name' => $product['name'],
                        'description' => 'Produto fictício de demonstração',
                        'price' => $product['price'] ?? 0,
                        'commission_amount' => $product['commission_amount'] ?? 25,
                        'stock_control' => (bool) ($product['stock_control'] ?? false),
                        'stock_quantity' => (int) ($product['stock_quantity'] ?? 0),
                        'minimum_stock' => (int) ($product['minimum_stock'] ?? 0),
                        'status' => Product::STATUS_ACTIVE,
                        'is_demo' => true,
                    ]);
                }

                $sellerRoleId = Role::query()->where('slug', Role::SELLER)->value('id');
                $seller = $this->users->create([
                    'role_id' => $sellerRoleId,
                    'name' => 'Vendedor Demo',
                    'email' => 'demo.seller.'.Str::lower(Str::random(6)).'@geosales.demo',
                    'password' => 'password',
                    'status' => User::STATUS_ACTIVE,
                ], $company);

                $campaignPayload = $payload['campaign'] ?? ['name' => '[Demo] Campanha Inicial', 'goal_visits' => 20];
                $campaign = $this->campaigns->create([
                    'name' => $campaignPayload['name'],
                    'description' => 'Campanha fictícia — não misturar com operação real.',
                    'city_id' => $city->id,
                    'status' => CampaignStatus::ACTIVE->value,
                    'start_date' => now()->toDateString(),
                    'goal_visits' => (int) ($campaignPayload['goal_visits'] ?? 20),
                    'sector_ids' => collect($sectors)->pluck('id')->all(),
                    'user_ids' => [$seller->id],
                ]);

                $propertyCount = (int) ($payload['properties'] ?? 5);
                $properties = [];
                for ($i = 1; $i <= $propertyCount; $i++) {
                    $address = $this->properties->createAddress([
                        'city_id' => $city->id,
                        'sector_id' => $sectors[array_rand($sectors)]->id,
                        'street' => 'Rua Demo '.$i,
                        'number' => (string) (100 + $i),
                        'neighborhood' => 'Bairro Demo',
                        'zipcode' => '01000-00'.$i,
                        'latitude' => -23.55 + ($i * 0.001),
                        'longitude' => -46.63 + ($i * 0.001),
                    ]);

                    $property = $this->properties->createProperty([
                        'address_id' => $address->id,
                        'type' => 'house',
                        'notes' => '[Demo] Imóvel fictício',
                        'latitude' => $address->latitude,
                        'longitude' => $address->longitude,
                    ], $actor);

                    $this->residents->create($property, [
                        'name' => 'Morador Demo '.$i,
                        'phone' => '1199999000'.$i,
                        'email' => 'morador'.$i.'@geosales.demo',
                    ]);

                    $properties[] = $property;
                }

                $visitCount = min((int) ($payload['visits'] ?? 3), count($properties));
                $visits = [];
                for ($i = 0; $i < $visitCount; $i++) {
                    $visits[] = $this->visits->register($campaign, [
                        'property_id' => $properties[$i]->id,
                        'user_id' => $seller->id,
                        'status' => VisitStatus::INTERESTED->value,
                        'notes' => '[Demo] Visita fictícia',
                        'visited_at' => now()->subHours($i + 1),
                    ], $actor ?? $seller);
                }

                return [
                    'city_id' => $city->id,
                    'sectors' => count($sectors),
                    'products' => count($products),
                    'campaign_id' => $campaign->id,
                    'properties' => count($properties),
                    'visits' => count($visits),
                    'seller_id' => $seller->id,
                ];
            });

            $run = $this->repository->runForCompany($company);
            $run?->forceFill([
                'demo_generated' => true,
                'metadata' => array_merge($run->metadata ?? [], ['demo' => $result]),
            ])->save();

            foreach (['cities', 'sectors', 'products', 'campaigns', 'sellers', 'property', 'visit'] as $stepKey) {
                $this->completeStep->execute($company, $stepKey, $actor, ['from_demo' => true]);
            }

            return $result;
        } finally {
            if ($previousCompany !== null) {
                $this->tenant->set($previousCompany, $previousUser);
            } else {
                $this->tenant->clear();
            }
        }
    }
}
