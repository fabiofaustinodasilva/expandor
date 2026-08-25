<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
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
        // Evita bloquear exclusão dos legados pelo default acquisition.plan_slug=professional
        config(['acquisition.plan_slug' => 'pro']);
    }

    public function test_unused_legacy_free_slug_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::FREE)->firstOrFail();
        $this->assertTrue($plan->isLegacyPlan());
        $this->assertSame(0, Subscription::query()->where('plan_id', $plan->id)->count());

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_unused_professional_slug_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::PROFESSIONAL)->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_unused_enterprise_slug_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::ENTERPRISE)->first();
        if ($plan === null) {
            $plan = Plan::factory()->create([
                'name' => 'Enterprise',
                'slug' => CommercialPlanCatalog::ENTERPRISE,
                'is_legacy' => false,
            ]);
        }

        // Isola o teste: remove vínculos de QA sem tocar em outros planos.
        Subscription::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->delete();
        Invoice::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->delete();
        CheckoutSession::query()->withoutGlobalScopes()->where('plan_id', $plan->id)->delete();

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_unused_enterprise_legacy_can_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::ENTERPRISE_LEGACY)->firstOrFail();
        $this->assertTrue($plan->isLegacyPlan());

        $this->actingAs($owner)
            ->delete(route('platform.plans.destroy', $plan))
            ->assertRedirect(route('platform.plans.index'));

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_plan_with_subscription_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'com-sub-'.Str::random(4)]);
        $company = Company::factory()->create(['name' => 'Cliente Com Sub']);
        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertStringContainsString('assinatura', session('errors')->first('plan'));
    }

    public function test_plan_with_invoice_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'com-invoice-'.Str::random(4)]);
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
        $this->assertStringContainsString('fatura', session('errors')->first('plan'));
    }

    public function test_plan_with_checkout_cannot_be_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create([
            'slug' => 'com-checkout-'.Str::random(4),
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
        $this->assertStringContainsString('checkout', session('errors')->first('plan'));
    }

    public function test_acquisition_trial_plan_config_blocks_deletion(): void
    {
        $owner = $this->makePlatformAdmin();
        config(['acquisition.plan_slug' => CommercialPlanCatalog::PROFESSIONAL]);
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::PROFESSIONAL)->firstOrFail();

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertStringContainsString('acquisition.plan_slug', session('errors')->first('plan'));
    }

    public function test_delete_records_audit_log(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'audit-del-'.Str::random(4)]);

        $this->actingAs($owner)->delete(route('platform.plans.destroy', $plan));

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.plan.deleted')
                ->get()
                ->contains(fn (AuditLog $log) => (int) data_get($log->new_values, 'plan_id') === (int) $plan->id)
        );
    }

    public function test_index_shows_block_reason_and_hides_delete_when_blocked(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'blocked-ui-'.Str::random(4)]);
        $company = Company::factory()->create();
        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $html = $this->actingAs($owner)
            ->get(route('platform.plans.index'))
            ->assertOk()
            ->assertSee('Não pode ser excluído: possui')
            ->getContent();

        $this->assertStringNotContainsString(
            'data-plan-action="'.route('platform.plans.destroy', $plan).'"',
            $html
        );
    }

    public function test_catalog_still_works_after_deleting_unused_plan(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'temp-catalog-'.Str::random(4)]);
        $remaining = Plan::query()->count();

        $this->actingAs($owner)->delete(route('platform.plans.destroy', $plan));

        $this->assertSame($remaining - 1, Plan::query()->count());
        $this->actingAs($owner)->get(route('platform.plans.index'))->assertOk()->assertSee('Planos SaaS');
    }

    public function test_deletion_audit_command_is_read_only(): void
    {
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::FREE)->firstOrFail();
        $before = Plan::query()->count();

        $this->artisan('expandor:plans:deletion-audit', [
            '--plan' => [$plan->id],
        ])->assertSuccessful();

        $this->assertSame($before, Plan::query()->count());
        $this->assertTrue(app(DeletePlatformPlanAction::class)->canDelete($plan));
    }

    public function test_rejection_does_not_return_500(): void
    {
        $owner = $this->makePlatformAdmin();
        $plan = Plan::factory()->create(['slug' => 'no-500-'.Str::random(4)]);
        CheckoutSession::factory()->create(['plan_id' => $plan->id]);

        $this->actingAs($owner)
            ->from(route('platform.plans.index'))
            ->delete(route('platform.plans.destroy', $plan))
            ->assertStatus(302)
            ->assertSessionHasErrors('plan');
    }
}
