<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Campaigns\Enums\CampaignStatus;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Campaigns\Services\CampaignService;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Repositories\OnboardingRepository;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Sales\Territory\Services\TerritoryService;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Services\VisitService;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class GenerateDemoDataAction
{
    public function __construct(
        protected OnboardingRepository $repository,
        protected TerritoryService $territory,
        protected PropertyService $properties,
        protected ResidentService $residents,
        protected CampaignService $campaigns,
        protected VisitService $visits,
        protected TenantContext $tenant,
        protected CompleteStepAction $completeStep,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Company $company, ?User $actor = null): array
    {
        $run = $this->repository->runForCompany($company);

        // Idempotente: se já gerou, devolve o resultado anterior sem recriar.
        if ($run?->demo_generated) {
            $existing = is_array($run->metadata['demo'] ?? null) ? $run->metadata['demo'] : [];

            return $existing !== [] ? $existing : [
                'already_generated' => true,
                'company_id' => $company->id,
            ];
        }

        $template = $this->repository->demoTemplate();
        $payload = $template?->payload ?? [];

        $previousCompany = $this->tenant->company();
        $previousUser = $this->tenant->user();
        $this->tenant->set($company, $actor);

        try {
            $result = DB::transaction(function () use ($company, $actor, $payload) {
                $cityData = $payload['city'] ?? ['name' => 'Demoópolis', 'state' => 'SP'];
                $city = $this->territory->upsertCity([
                    'company_id' => $company->id,
                    'name' => $cityData['name'],
                    'state' => $cityData['state'],
                    'active' => true,
                ]);

                $sectors = [];
                foreach (($payload['sectors'] ?? ['Centro', 'Jardins']) as $sectorName) {
                    $sectors[] = $this->territory->upsertSector([
                        'company_id' => $company->id,
                        'city_id' => $city->id,
                        'name' => $sectorName,
                        'description' => 'Setor demonstrativo',
                        'active' => true,
                    ]);
                }

                $products = [];
                foreach (($payload['products'] ?? [['name' => '[Demo] Plano Básico', 'price' => 99.9]]) as $product) {
                    $products[] = Product::query()->withoutGlobalScopes()->updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'name' => $product['name'],
                            'is_demo' => true,
                        ],
                        [
                            'description' => 'Produto fictício de demonstração',
                            'price' => $product['price'] ?? 0,
                            'commission_amount' => $product['commission_amount'] ?? 25,
                            'stock_control' => (bool) ($product['stock_control'] ?? false),
                            'stock_quantity' => (int) ($product['stock_quantity'] ?? 0),
                            'minimum_stock' => (int) ($product['minimum_stock'] ?? 0),
                            'status' => Product::STATUS_ACTIVE,
                        ]
                    );
                }

                $sellerRoleId = Role::query()->where('slug', Role::SELLER)->value('id');
                $sellerEmail = 'demo.seller.'.$company->id.'@geosales.demo';
                $seller = User::query()->withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'email' => $sellerEmail,
                    ],
                    [
                        'role_id' => $sellerRoleId,
                        'name' => 'Vendedor Demo',
                        'password' => 'password',
                        'status' => User::STATUS_ACTIVE,
                        'is_platform_admin' => false,
                    ]
                );

                $campaignPayload = $payload['campaign'] ?? ['name' => '[Demo] Campanha Inicial', 'goal_visits' => 20];
                $campaignName = (string) ($campaignPayload['name'] ?? '[Demo] Campanha Inicial');

                $campaign = Campaign::query()->withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $campaignName,
                    ],
                    [
                        'description' => 'Campanha fictícia — não misturar com operação real.',
                        'city_id' => $city->id,
                        'status' => CampaignStatus::ACTIVE,
                        'start_date' => now()->toDateString(),
                        'goal_visits' => (int) ($campaignPayload['goal_visits'] ?? 20),
                        'created_by' => $actor?->id ?? $seller->id,
                    ]
                );

                $this->campaigns->update($campaign, [
                    'name' => $campaign->name,
                    'description' => $campaign->description,
                    'city_id' => $city->id,
                    'start_date' => $campaign->start_date?->toDateString() ?? now()->toDateString(),
                    'goal_visits' => (int) $campaign->goal_visits,
                    'sector_ids' => collect($sectors)->pluck('id')->all(),
                    'user_ids' => [$seller->id],
                ]);

                $propertyCount = (int) ($payload['properties'] ?? 5);
                $existingDemoProperties = Property::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('notes', 'like', '[Demo]%')
                    ->count();

                $properties = Property::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('notes', 'like', '[Demo]%')
                    ->orderBy('id')
                    ->get()
                    ->all();

                if ($existingDemoProperties === 0) {
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
                            'email' => 'morador'.$i.'.'.$company->id.'@geosales.demo',
                        ]);

                        $properties[] = $property;
                    }
                }

                $visitCount = min((int) ($payload['visits'] ?? 3), count($properties));
                $existingVisits = Visit::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('campaign_id', $campaign->id)
                    ->where('notes', 'like', '[Demo]%')
                    ->count();

                $visits = [];
                if ($existingVisits === 0) {
                    for ($i = 0; $i < $visitCount; $i++) {
                        $visits[] = $this->visits->register($campaign, [
                            'property_id' => $properties[$i]->id,
                            'user_id' => $seller->id,
                            'status' => VisitStatus::INTERESTED->value,
                            'notes' => '[Demo] Visita fictícia',
                            'visited_at' => now()->subHours($i + 1),
                        ], $actor ?? $seller);
                    }
                } else {
                    $visits = Visit::query()
                        ->withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->where('campaign_id', $campaign->id)
                        ->where('notes', 'like', '[Demo]%')
                        ->get()
                        ->all();
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
