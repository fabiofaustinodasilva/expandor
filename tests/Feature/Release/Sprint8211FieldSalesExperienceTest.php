<?php

namespace Tests\Feature\Release;

use App\Domains\Company\Models\Role;
use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.11 — Field sales experience + product presentation.
 */
class Sprint8211FieldSalesExperienceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_map_keeps_single_minha_localizacao_button(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8211 GPS');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-gps@sprint8211.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="btn-recenter-location"', $html);
        $this->assertStringContainsString('Minha localização', $html);
        $this->assertStringNotContainsString('id="btn-new-point"', $html);
        $this->assertStringNotContainsString('id="btn-new-point-side"', $html);
        $this->assertStringNotContainsString('id="btn-empty-add-point"', $html);
        $this->assertStringNotContainsString('>Meu Local<', $html);
        $this->assertStringNotContainsString('Próxima casa', $html);
    }

    public function test_locate_flow_has_lock_loading_and_specific_errors(): void
    {
        $js = file_get_contents(public_path('js/operational-map.js'));
        $this->assertIsString($js);

        $this->assertStringContainsString('let locateInFlight = false', $js);
        $this->assertStringContainsString("label.textContent = 'Localizando...'", $js);
        $this->assertStringContainsString('if (locateInFlight)', $js);
        $this->assertStringContainsString('Permissão de localização negada', $js);
        $this->assertStringContainsString('Tempo esgotado ao obter a localização', $js);
        $this->assertStringContainsString('Posição indisponível no momento', $js);
        $this->assertStringContainsString('Este navegador não oferece localização.', $js);
        $this->assertStringContainsString('function locateMyPosition(', $js);
        $this->assertStringContainsString('openCreateAtMapTap', $js);
        $this->assertStringNotContainsString("getElementById('btn-new-point')", $js);
    }

    public function test_form_status_colors_match_map_marker_color(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8211 Colors');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-color@sprint8211.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-status-color="'.MapMarkerColor::forStatus(PropertyStatus::INTERESTED)->value.'"', $html);
        $this->assertStringContainsString('data-status-color="'.MapMarkerColor::forStatus(PropertyStatus::RETURN_LATER)->value.'"', $html);
        $this->assertStringContainsString('data-status-color="'.MapMarkerColor::forStatus(PropertyStatus::NO_INTEREST)->value.'"', $html);
        $this->assertStringContainsString('data-status-color="'.MapMarkerColor::forStatus(PropertyStatus::INSTALLATION_REQUESTED)->value.'"', $html);
        $this->assertStringContainsString('--status-color', $html);
        $this->assertSame('#3b82f6', MapMarkerColor::forStatus(PropertyStatus::INTERESTED)->value);
        $this->assertSame('#22c55e', MapMarkerColor::forStatus(PropertyStatus::CUSTOMER)->value);
        $this->assertSame('#f97316', MapMarkerColor::forStatus(PropertyStatus::RETURN_LATER)->value);
        $this->assertSame('#64748b', MapMarkerColor::forStatus(PropertyStatus::NO_INTEREST)->value);
    }

    public function test_commission_shows_resident_name_or_ponto_sem_cliente(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8211 Comm');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-comm@sprint8211.test']);
        $product = Product::factory()->create(['company_id' => $company->id, 'name' => 'Plano 8211']);

        $withClient = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $seller->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'street' => 'Local GPS',
                'number' => 'S/N',
            ])->id,
        ]);
        Resident::factory()->create([
            'company_id' => $company->id,
            'property_id' => $withClient->id,
            'name' => 'João da Silva',
            'is_primary_contact' => true,
        ]);
        $visitWith = Visit::factory()->create([
            'company_id' => $company->id,
            'property_id' => $withClient->id,
            'user_id' => $seller->id,
        ]);
        SalesCommission::factory()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'visit_id' => $visitWith->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'status' => SalesCommissionStatus::PENDING,
            'earned_at' => now(),
        ]);

        $without = Property::factory()->create([
            'company_id' => $company->id,
            'created_by' => $seller->id,
            'address_id' => Address::factory()->create([
                'company_id' => $company->id,
                'street' => 'Local GPS',
                'number' => '10',
            ])->id,
        ]);
        $visitWithout = Visit::factory()->create([
            'company_id' => $company->id,
            'property_id' => $without->id,
            'user_id' => $seller->id,
        ]);
        SalesCommission::factory()->create([
            'company_id' => $company->id,
            'user_id' => $seller->id,
            'visit_id' => $visitWithout->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'status' => SalesCommissionStatus::PENDING,
            'earned_at' => now(),
        ]);

        $this->assertSame('João da Silva', VisitHistoryPresenter::commissionClientLabel($visitWith->fresh(['property.residents', 'sale.resident'])));
        $this->assertSame('Ponto sem cliente', VisitHistoryPresenter::commissionClientLabel($visitWithout->fresh(['property.residents', 'sale.resident'])));

        $html = $this->actingAs($seller)->get(route('commissions.index'))->assertOk()->getContent();
        $this->assertStringContainsString('João da Silva', $html);
        $this->assertStringContainsString('Ponto sem cliente', $html);
        $this->assertStringNotContainsString('>Local GPS', $html);
    }

    public function test_company_can_manage_product_presentation_fields(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8211 Prod Admin');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin-prod@sprint8211.test']);

        $this->actingAs($admin)
            ->post(route('commissions.products.store'), [
                'name' => 'ÚNICA MÓVEL',
                'category' => 'Móvel',
                'description' => 'Plano móvel completo',
                'benefits' => "5 GB\nLigações ilimitadas",
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'price' => 79.9,
                'commission_amount' => 20,
                'sort_order' => 1,
                'status' => Product::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('commissions.products.index'));

        $product = Product::query()->where('name', 'ÚNICA MÓVEL')->first();
        $this->assertNotNull($product);
        $this->assertSame('Móvel', $product->category);
        $this->assertSame(['5 GB', 'Ligações ilimitadas'], $product->benefitList());
        $this->assertSame(1, $product->sort_order);
        $this->assertStringContainsString('youtube.com/embed/', (string) $product->embeddableVideoUrl());
    }

    public function test_seller_sees_active_products_and_cannot_edit(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa 8211 A');
        $companyB = $this->makeCompanyWithPlan('Empresa 8211 B');
        $seller = $this->makeUser($companyA, Role::SELLER, ['email' => 'seller-prod@sprint8211.test']);

        $active = Product::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'ÚNICA MÓVEL',
            'category' => 'Móvel',
            'benefits' => ['5 GB'],
            'video_url' => 'https://example.com/demo.mp4',
            'status' => Product::STATUS_ACTIVE,
            'sort_order' => 1,
        ]);
        $inactive = Product::factory()->inactive()->create([
            'company_id' => $companyA->id,
            'name' => 'Plano Inativo',
        ]);
        $other = Product::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Produto Outra Empresa',
        ]);

        $list = $this->actingAs($seller)->get(route('sales-app.products.index'))->assertOk()->getContent();
        $this->assertStringContainsString('ÚNICA MÓVEL', $list);
        $this->assertStringContainsString('Ver apresentação', $list);
        $this->assertStringNotContainsString('Plano Inativo', $list);
        $this->assertStringNotContainsString('Produto Outra Empresa', $list);

        $show = $this->actingAs($seller)->get(route('sales-app.products.show', $active))->assertRedirect(route('sales-app.products.present', ['product' => $active->id]));
        $deck = $this->actingAs($seller)->get(route('sales-app.products.present', ['product' => $active->id]))->assertOk()->getContent();
        $this->assertStringContainsString('ÚNICA MÓVEL', $deck);
        $this->assertStringContainsString('5 GB', $deck);
        $this->assertStringContainsString('Voltar ao mapa', $deck);
        $this->assertStringContainsString('playsinline', $deck);

        $this->actingAs($seller)->get(route('sales-app.products.show', $inactive))->assertForbidden();
        $this->actingAs($seller)->get(route('sales-app.products.show', $other))->assertNotFound();
        $this->actingAs($seller)->get(route('commissions.products.edit', $active))->assertForbidden();
    }

    public function test_cache_bust_operational_map_v47(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8211 Cache');
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 'seller-cache@sprint8211.test']);

        $html = $this->actingAs($seller)->get(route('map.index'))->assertOk()->getContent();
        $this->assertStringContainsString('operational-map.js?v=50', $html);
    }
}
