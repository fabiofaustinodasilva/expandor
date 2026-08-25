<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Platform\Actions\DeletePlatformPlanAction;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PlatformPlanDeletionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_unused_plan_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'name' => 'Teste Errado',
            'slug' => 'teste-errado-'.Str::random(4),
            'is_legacy' => false,
            'status' => Plan::STATUS_ACTIVE,
        ]);

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.plan.deleted')
                ->get()
                ->contains(fn (AuditLog $log) => (int) data_get($log->new_values, 'plan_id') === (int) $plan->id)
        );
    }

    public function test_inactive_unused_plan_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'name' => 'Inativo Sem Uso',
            'slug' => 'inativo-sem-uso-'.Str::random(4),
            'is_legacy' => false,
            'status' => Plan::STATUS_INACTIVE,
            'active' => false,
        ]);

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_plan_with_subscription_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'slug' => 'com-sub-'.Str::random(4),
            'is_legacy' => false,
        ]);
        $company = \App\Domains\Company\Models\Company::factory()->create(['name' => 'Cliente Com Sub']);
        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertStringContainsString(
            'histórico de uso',
            session('errors')->first('plan')
        );
    }

    public function test_plan_with_invoice_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'slug' => 'com-invoice-'.Str::random(4),
            'is_legacy' => false,
        ]);
        $company = $this->makeCompanyWithPlan('Empresa Invoice');

        Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'number' => 'INV-PLAN-DEL-1',
            'billing_period_key' => '2099-02',
            'status' => InvoiceStatus::Open,
            'amount_due' => 10,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->addDays(5),
        ]);

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_plan_with_checkout_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'slug' => 'com-checkout-'.Str::random(4),
            'is_legacy' => false,
            'allows_checkout' => true,
            'is_public' => true,
            'price' => 99,
            'status' => Plan::STATUS_ACTIVE,
        ]);

        CheckoutSession::factory()->create([
            'plan_id' => $plan->id,
            'status' => CheckoutStatus::Pending,
            'amount' => 99,
        ]);

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_legacy_protected_plan_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::FREE)->firstOrFail();
        $this->assertTrue($plan->isLegacyPlan());

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertStringContainsString(
            'estrutural/legado',
            session('errors')->first('plan')
        );
    }

    public function test_structural_catalog_slug_cannot_be_deleted_even_if_unused(): void
    {
        $owner = $this->makePlatformAdmin();
        $target = Plan::query()->where('slug', CommercialPlanCatalog::START)->first();
        if ($target === null) {
            $target = Plan::factory()->create([
                'name' => 'Start',
                'slug' => CommercialPlanCatalog::START,
                'is_legacy' => false,
            ]);
        }

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $target))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $target->id]);
    }

    public function test_delete_button_only_shown_for_eligible_plans(): void
    {
        $owner = $this->makePlatformAdmin();
        $eligible = Plan::factory()->create([
            'name' => 'Elegivel Delete',
            'slug' => 'elegivel-delete-'.Str::random(4),
            'is_legacy' => false,
        ]);
        $legacy = Plan::query()->where('slug', CommercialPlanCatalog::FREE)->firstOrFail();

        $html = $this->actingAs($owner)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            'data-plan-action="'.route('platform.plans.destroy', $eligible).'"',
            $html
        );
        $this->assertStringNotContainsString(
            'data-plan-action="'.route('platform.plans.destroy', $legacy).'"',
            $html
        );
    }

    public function test_catalog_still_lists_after_deleting_unused_plan(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'slug' => 'temp-catalog-'.Str::random(4),
            'is_legacy' => false,
        ]);
        $remaining = Plan::query()->count();

        $this->actingAs($owner)->delete(route('platform.plans.destroy', $plan));

        $this->assertSame($remaining - 1, Plan::query()->count());
        $this->actingAs($owner)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->assertSee('Planos SaaS');
    }

    public function test_action_rejection_does_not_throw_500(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::PROFESSIONAL)->firstOrFail();

        $response = $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan));

        $response->assertStatus(302);
        $this->assertNotEquals(500, $response->status());
        $this->assertTrue(app(DeletePlatformPlanAction::class)->isStructurallyProtected($plan));
    }
}
