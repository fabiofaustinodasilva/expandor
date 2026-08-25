<?php

namespace Tests\Feature\Platform;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Enums\CheckoutStatus;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class LegacyCatalogCleanupTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_plan_seeder_only_creates_start_pro_scale(): void
    {
        $slugs = Plan::query()->orderBy('slug')->pluck('slug')->all();
        $this->assertSame(
            [CommercialPlanCatalog::PRO, CommercialPlanCatalog::SCALE, CommercialPlanCatalog::START],
            $slugs
        );

        foreach (CommercialPlanCatalog::legacySlugs() as $legacy) {
            $this->assertDatabaseMissing('plans', ['slug' => $legacy]);
        }
    }

    public function test_acquisition_defaults_to_start(): void
    {
        $this->assertSame('start', config('acquisition.plan_slug'));
    }

    public function test_platform_seeder_does_not_create_system_subscription(): void
    {
        $this->seed(PlatformSeeder::class);
        $system = Company::query()->withoutGlobalScopes()->where('is_system', true)->firstOrFail();
        $this->assertSame(0, Subscription::query()->withoutGlobalScopes()->where('company_id', $system->id)->count());
        $this->assertNotNull(
            \App\Domains\Company\Models\User::query()->withoutGlobalScopes()
                ->where('email', 'owner@geosales.local')
                ->where('is_platform_admin', true)
                ->first()
        );
    }

    public function test_system_subscription_purge_dry_run_and_execute(): void
    {
        $this->seed(PlatformSeeder::class);
        $system = Company::query()->withoutGlobalScopes()->where('is_system', true)->firstOrFail();
        $plan = Plan::query()->where('slug', CommercialPlanCatalog::SCALE)->firstOrFail();
        $sub = Subscription::query()->withoutGlobalScopes()->create([
            'company_id' => $system->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => now(),
        ]);

        $this->artisan('expandor:platform:purge-system-subscriptions')
            ->assertSuccessful();
        $this->assertDatabaseHas('subscriptions', ['id' => $sub->id]);

        $this->artisan('expandor:platform:purge-system-subscriptions', [
            '--execute' => true,
            '--confirm' => 'PURGE-SYSTEM-SUBSCRIPTION',
        ])->assertSuccessful();

        $this->assertDatabaseMissing('subscriptions', ['id' => $sub->id]);
    }

    public function test_checkout_purge_is_selective(): void
    {
        $this->seed(PlatformSeeder::class);

        $plan = Plan::query()->where('slug', CommercialPlanCatalog::START)->firstOrFail();
        $keep = CheckoutSession::factory()->create([
            'plan_id' => $plan->id,
            'status' => CheckoutStatus::Pending,
        ]);
        $remove = CheckoutSession::factory()->create([
            'plan_id' => $plan->id,
            'status' => CheckoutStatus::Pending,
            'gateway' => 'fake',
        ]);

        $this->artisan('expandor:production-reset', [
            '--checkout' => [$remove->id],
        ])->assertSuccessful();
        $this->assertDatabaseHas('checkout_sessions', ['id' => $remove->id]);

        $this->artisan('expandor:production-reset', [
            '--checkout' => [$remove->id],
            '--execute' => true,
            '--confirm' => 'RESET-PRODUCTION-DATA',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertSuccessful();

        $this->assertDatabaseMissing('checkout_sessions', ['id' => $remove->id]);
        $this->assertDatabaseHas('checkout_sessions', ['id' => $keep->id]);
    }

    public function test_paid_checkout_cannot_be_purged(): void
    {
        $this->seed(PlatformSeeder::class);

        $plan = Plan::query()->where('slug', CommercialPlanCatalog::PRO)->firstOrFail();
        $paid = CheckoutSession::factory()->create([
            'plan_id' => $plan->id,
            'status' => CheckoutStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->artisan('expandor:production-reset', [
            '--checkout' => [$paid->id],
            '--execute' => true,
            '--confirm' => 'RESET-PRODUCTION-DATA',
            '--skip-backup' => true,
            '--confirm-backup-exists' => 'YES',
        ])->assertFailed();

        $this->assertDatabaseHas('checkout_sessions', ['id' => $paid->id]);
    }

    public function test_official_prices_unchanged(): void
    {
        $this->assertEqualsWithDelta(349.00, (float) Plan::query()->where('slug', 'start')->value('price'), 0.01);
        $this->assertEqualsWithDelta(449.00, (float) Plan::query()->where('slug', 'pro')->value('price'), 0.01);
        $this->assertEqualsWithDelta(649.00, (float) Plan::query()->where('slug', 'scale')->value('price'), 0.01);
    }
}
