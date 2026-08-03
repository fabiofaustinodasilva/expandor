<?php

namespace Tests\Feature\Operations;

use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Team;
use App\Domains\Company\Support\FieldOps\FieldOpsKeys;
use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\Company\Support\FieldOps\PointsDisplay;
use App\Domains\Company\Support\FieldOps\PointsVisibility;
use App\Domains\Maps\DTOs\MapFiltersDTO;
use App\Domains\Maps\Services\MapQueryService;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class FieldOperationsSettingsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_manager_can_save_field_operations_settings(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Ops');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr-field@ops.test']);

        $this->actingAs($manager)
            ->get(route('operations.settings.field'))
            ->assertOk()
            ->assertSee('Visibilidade dos Pontos')
            ->assertSee('Todos os pontos da empresa')
            ->assertSee('Apenas meus pontos')
            ->assertSee('Exibição dos Pontos')
            ->assertSee('Apenas pendentes')
            ->assertSee('Editar pontos de outro vendedor')
            ->assertSee('Excluir pontos');

        $this->actingAs($manager)
            ->put(route('operations.settings.field.update'), [
                'points_visibility' => PointsVisibility::Own->value,
                'points_display' => PointsDisplay::CustomersOnly->value,
                'points_edit_others' => 'manager',
                'points_delete' => 'creator',
            ])
            ->assertRedirect(route('operations.settings.field'));

        $this->assertDatabaseHas('company_settings', [
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_VISIBILITY,
            'value' => 'own',
        ]);
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_DISPLAY,
            'value' => 'customers_only',
        ]);
    }

    public function test_seller_cannot_access_field_operations_settings(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Seller');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-field@ops.test']);

        $this->actingAs($seller)
            ->get(route('operations.settings.field'))
            ->assertForbidden();
    }

    public function test_map_markers_respect_own_visibility_and_display(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Map');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'seller-a@map.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'seller-b@map.test']);

        CompanySetting::query()->create([
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_VISIBILITY,
            'value' => PointsVisibility::Own->value,
        ]);
        CompanySetting::query()->create([
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_DISPLAY,
            'value' => PointsDisplay::All->value,
        ]);

        Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerA->id,
            'status' => PropertyStatus::NEW,
            'latitude' => -23.5,
            'longitude' => -46.6,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerB->id,
            'status' => PropertyStatus::NEW,
            'latitude' => -23.51,
            'longitude' => -46.61,
        ]);

        Sanctum::actingAs($sellerA);
        $response = $this->getJson('/api/v1/maps/markers');
        $response->assertOk();
        $markers = $response->json('data.markers');
        $this->assertCount(1, $markers);
        $this->assertSame($sellerA->id, (int) ($markers[0]['owner_user_id'] ?? 0));
    }

    public function test_team_visibility_includes_teammates(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Team');
        $sellerA = $this->makeUser($company, Role::SELLER, ['email' => 'ta@map.test']);
        $sellerB = $this->makeUser($company, Role::SELLER, ['email' => 'tb@map.test']);
        $outsider = $this->makeUser($company, Role::SELLER, ['email' => 'out@map.test']);

        $team = Team::query()->create([
            'company_id' => $company->id,
            'name' => 'Time Norte',
            'status' => Team::STATUS_ACTIVE,
        ]);
        $team->users()->sync([$sellerA->id, $sellerB->id]);

        CompanySetting::query()->create([
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_VISIBILITY,
            'value' => PointsVisibility::Team->value,
        ]);

        Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerA->id,
            'status' => PropertyStatus::NEW,
            'latitude' => -23.1,
            'longitude' => -46.1,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $sellerB->id,
            'status' => PropertyStatus::INTERESTED,
            'latitude' => -23.2,
            'longitude' => -46.2,
        ]);
        Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $outsider->id,
            'status' => PropertyStatus::CUSTOMER,
            'latitude' => -23.3,
            'longitude' => -46.3,
        ]);

        $service = app(MapQueryService::class);
        $filters = $service->constrainForUser(new MapFiltersDTO, $sellerA);
        $markers = $service->markers($filters);
        $owners = collect($markers)->pluck('owner_user_id')->all();

        $this->assertContains($sellerA->id, $owners);
        $this->assertContains($sellerB->id, $owners);
        $this->assertNotContains($outsider->id, $owners);
    }

    public function test_resolver_accepts_campaign_id_for_future_scope_without_changing_company_policy(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Field Future');
        CompanySetting::query()->create([
            'company_id' => $company->id,
            'key' => FieldOpsKeys::POINTS_VISIBILITY,
            'value' => PointsVisibility::Team->value,
        ]);

        $resolver = app(FieldOpsPolicyResolver::class);
        $policy = $resolver->resolve((int) $company->id, campaignId: 999);

        $this->assertSame('company', $policy->scope);
        $this->assertNull($policy->campaignId);
        $this->assertSame(PointsVisibility::Team, $policy->visibility);
    }
}
