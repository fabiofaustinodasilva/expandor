<?php

namespace Tests\Feature\Marketplace;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Company\Services\UserService;
use App\Domains\Payments\Services\CheckoutService;
use App\Domains\Platform\Support\CommercialPlanCatalog;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SprintCommercialCatalogAlignmentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_commercial_catalog_prices_and_seller_limits(): void
    {
        $start = Plan::query()->where('slug', CommercialPlanCatalog::START)->firstOrFail();
        $pro = Plan::query()->where('slug', CommercialPlanCatalog::PRO)->firstOrFail();
        $scale = Plan::query()->where('slug', CommercialPlanCatalog::SCALE)->firstOrFail();

        $this->assertEqualsWithDelta(349.00, (float) $start->price, 0.01);
        $this->assertSame(2, (int) $start->getAttributes()['max_sellers']);
        $this->assertTrue($start->allowsPublicCheckout());

        $this->assertEqualsWithDelta(449.00, (float) $pro->price, 0.01);
        $this->assertSame(5, (int) $pro->getAttributes()['max_sellers']);
        $this->assertTrue($pro->is_featured);
        $this->assertTrue($pro->allowsPublicCheckout());

        $this->assertEqualsWithDelta(649.00, (float) $scale->price, 0.01);
        $this->assertNull($scale->getAttributes()['max_sellers']);
        $this->assertTrue($scale->allowsPublicCheckout());

        $this->assertNull(Plan::query()->where('slug', CommercialPlanCatalog::ENTERPRISE)->first());
    }

    public function test_legacy_plans_are_not_seeded(): void
    {
        foreach (CommercialPlanCatalog::legacySlugs() as $slug) {
            $this->assertNull(Plan::query()->where('slug', $slug)->first(), "legacy {$slug} should not be seeded");
        }
    }

    public function test_checkout_rejects_non_checkoutable_plans(): void
    {
        $checkout = app(CheckoutService::class);
        $blocked = Plan::factory()->create([
            'slug' => 'bloqueado-qa',
            'allows_checkout' => false,
            'is_public' => false,
            'status' => Plan::STATUS_ACTIVE,
            'price' => 10,
        ]);

        try {
            $checkout->start($this->checkoutPayload($blocked->id, 'bloqueado@checkout.test'));
            $this->fail('Expected checkout to reject non-checkoutable plan.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('plan_id', $exception->errors());
        }
    }

    public function test_checkout_accepts_start_pro_and_scale(): void
    {
        foreach (['start', 'pro', 'scale'] as $slug) {
            $plan = Plan::query()->where('slug', $slug)->firstOrFail();
            $result = app(CheckoutService::class)->start(
                $this->checkoutPayload($plan->id, $slug.'@sellable.test', $slug)
            );
            $this->assertEqualsWithDelta((float) $plan->price, (float) $result->session->amount, 0.01);
        }
    }

    public function test_assinar_redirects_to_demo_and_hides_legacy_catalog(): void
    {
        $html = $this->get(route('marketplace.subscribe'))
            ->assertRedirect(route('marketplace.home').'#demo')
            ->getTargetUrl();

        $this->assertStringContainsString('#demo', $html);

        $page = $this->get(route('marketplace.home'))->assertOk()->getContent();
        $this->assertStringNotContainsString('R$ 199,90', $page);
        $this->assertStringNotContainsString('R$ 499,90', $page);
        $this->assertStringNotContainsString('>Free<', $page);
        $this->assertStringNotContainsString('Professional', $page);
    }

    public function test_public_plans_index_redirects_to_commercial_planos(): void
    {
        $this->get(route('plans.index'))
            ->assertRedirect(route('marketplace.plans'));

        $this->get(route('marketplace.plans'))
            ->assertOk()
            ->assertSee('R$ 349', false)
            ->assertSee('R$ 449', false)
            ->assertSee('R$ 649', false)
            ->assertDontSee('R$ 199,90', false)
            ->assertDontSee('Professional', false);
    }

    public function test_cadastro_does_not_allow_public_legacy_signup(): void
    {
        $this->get(route('signup.create', ['plan' => 'free']))
            ->assertRedirect(route('marketplace.home').'#demo');

        $this->get(route('signup.create', ['plan' => 'professional']))
            ->assertRedirect(route('marketplace.home').'#demo');

        $this->post(route('signup.store'), [
            'company_name' => 'Tentativa Pública',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin',
            'admin_whatsapp' => '11988887777',
            'admin_email' => 'publico@cadastro.test',
            'admin_password' => 'Password123!',
            'admin_password_confirmation' => 'Password123!',
            'terms_accepted' => '1',
        ])->assertRedirect(route('marketplace.home').'#demo');

        $this->assertNull(Company::query()->where('name', 'Tentativa Pública')->first());
        $this->assertNull(User::query()->withoutGlobalScopes()->where('email', 'publico@cadastro.test')->first());
    }

    public function test_internal_trial_provision_uses_start(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute([
            'company_name' => 'Trial Interno',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Owner Trial',
            'admin_email' => 'interno@trial.test',
            'admin_whatsapp' => '11977776666',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ]);

        $this->assertSame('start', $result->subscription->plan?->slug);
        $this->assertSame(Subscription::STATUS_TRIAL, $result->subscription->status);
        $this->assertSame(Role::ADMINISTRATOR, $result->administrator->role?->slug);
    }

    public function test_start_limits_two_sellers_and_ignores_admin_manager(): void
    {
        $company = $this->makeCompanyWithPlan('Start Seats', 'start');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin@start.test']);
        $this->makeUser($company, Role::MANAGER, ['email' => 'manager@start.test']);
        app(TenantContext::class)->set($company, $admin);

        $this->createSeller($company, 'seller1@start.test');
        $this->createSeller($company, 'seller2@start.test');

        try {
            $this->createSeller($company, 'seller3@start.test');
            $this->fail('Expected the third seller to be blocked on Start.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role_id', $exception->errors());
            $this->assertStringContainsString('Seu plano permite até 2 vendedores.', $exception->errors()['role_id'][0]);
            $this->assertStringContainsString('Conheça o plano Pro.', $exception->errors()['role_id'][0]);
        }

        $this->assertSame(2, $this->activeSellerCount($company));
        $this->assertNull(User::query()->withoutGlobalScopes()->where('email', 'seller3@start.test')->first());
    }

    public function test_pro_limits_five_sellers(): void
    {
        $company = $this->makeCompanyWithPlan('Pro Seats', 'pro');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin@pro.test']);
        app(TenantContext::class)->set($company, $admin);

        for ($i = 1; $i <= 5; $i++) {
            $this->createSeller($company, "seller{$i}@pro.test");
        }

        try {
            $this->createSeller($company, 'seller6@pro.test');
            $this->fail('Expected the sixth seller to be blocked on Pro.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Seu plano permite até 5 vendedores.', $exception->errors()['role_id'][0]);
            $this->assertStringContainsString('Conheça o plano Scale.', $exception->errors()['role_id'][0]);
        }
    }

    public function test_scale_allows_more_than_five_sellers(): void
    {
        $company = $this->makeCompanyWithPlan('Scale Seats', 'scale');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin@scale.test']);
        app(TenantContext::class)->set($company, $admin);

        for ($i = 1; $i <= 6; $i++) {
            $this->createSeller($company, "seller{$i}@scale.test");
        }

        $this->assertSame(6, $this->activeSellerCount($company));
    }

    public function test_seller_limits_are_isolated_per_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Start A', 'start');
        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, ['email' => 'admin@start-a.test']);
        $companyB = $this->makeCompanyWithPlan('Start B', 'start');
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, ['email' => 'admin@start-b.test']);

        app(TenantContext::class)->set($companyA, $adminA);
        $this->createSeller($companyA, 'a1@start.test');
        $this->createSeller($companyA, 'a2@start.test');

        app(TenantContext::class)->set($companyB, $adminB);
        $this->createSeller($companyB, 'b1@start.test');
        $this->createSeller($companyB, 'b2@start.test');

        $this->assertSame(2, $this->activeSellerCount($companyA));
        $this->assertSame(2, $this->activeSellerCount($companyB));
    }

    public function test_legacy_professional_subscription_still_works_without_seller_cap(): void
    {
        $company = $this->makeCompanyWithPlan('Legacy Co', 'professional');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'admin@legacy.test']);
        app(TenantContext::class)->set($company, $admin);

        $this->createSeller($company, 's1@legacy.test');
        $this->createSeller($company, 's2@legacy.test');
        $this->createSeller($company, 's3@legacy.test');

        $this->assertSame('professional', $company->subscriptions()->first()?->plan?->slug);
        $this->assertSame(3, $this->activeSellerCount($company));
        $this->assertSame(Subscription::STATUS_ACTIVE, $company->subscriptions()->first()?->status);
    }

    public function test_team_hub_blocks_third_start_seller_with_human_message(): void
    {
        $company = $this->makeCompanyWithPlan('Start Hub', 'start');
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'mgr@start-hub.test']);
        $this->makeUser($company, Role::SELLER, ['email' => 's1@start-hub.test']);
        $this->makeUser($company, Role::SELLER, ['email' => 's2@start-hub.test']);
        $sellerRole = Role::query()->where('slug', Role::SELLER)->firstOrFail();

        $this->actingAs($manager)
            ->from(route('operations.team'))
            ->post(route('operations.team.store'), [
                'name' => 'Terceiro',
                'email' => 's3@start-hub.test',
                'password' => 'password123',
                'role_id' => $sellerRole->id,
            ])
            ->assertRedirect(route('operations.team'))
            ->assertSessionHasErrors('role_id');

        $this->actingAs($manager)
            ->followingRedirects()
            ->from(route('operations.team'))
            ->post(route('operations.team.store'), [
                'name' => 'Terceiro',
                'email' => 's3@start-hub.test',
                'password' => 'password123',
                'role_id' => $sellerRole->id,
            ])
            ->assertSee('Seu plano permite até 2 vendedores.', false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkoutPayload(int $planId, string $email, string $suffix = 'base'): array
    {
        $documents = [
            'base' => '12345678909',
            'start' => '39053344705',
            'pro' => '52998224725',
            'scale' => '15350946056',
        ];

        return [
            'plan_id' => $planId,
            'company_name' => 'Empresa '.$email,
            'buyer_name' => 'Comprador',
            'buyer_email' => $email,
            'buyer_document' => $documents[$suffix] ?? '12345678909',
            'buyer_phone' => '11988887777',
            'billing_cycle' => 'monthly',
            'payment_method' => 'PIX',
        ];
    }

    protected function createSeller(Company $company, string $email): User
    {
        $roleId = (int) Role::query()->where('slug', Role::SELLER)->value('id');

        return app(UserService::class)->create([
            'role_id' => $roleId,
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'status' => User::STATUS_ACTIVE,
        ], $company);
    }

    protected function activeSellerCount(Company $company): int
    {
        return User::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', User::STATUS_ACTIVE)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::SELLER))
            ->count();
    }
}
