<?php

namespace Tests\Feature\Acquisition;

use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 5.5.3 — cadastro Trial público em /cadastro.
 */
class CadastroTrialTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_cadastro_page_shows_public_signup_form(): void
    {
        $this->get(route('signup.create'))
            ->assertOk()
            ->assertSee('data-trial-signup="1"', false)
            ->assertSee('Criar minha conta grátis', false)
            ->assertSee('Professional', false)
            ->assertSee('name="company_name"', false)
            ->assertSee('name="terms_accepted"', false)
            ->assertSee('Começar vazio', false)
            ->assertSee('Criar ambiente demonstração', false)
            ->assertDontSee('data-signup-placeholder="1"', false);
    }

    public function test_creates_company_admin_and_trial_subscription(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'with_demo_data' => '0',
        ]))
            ->assertRedirect(route('setup.show'));

        $this->assertAuthenticated();

        /** @var User $user */
        $user = Auth::user();
        $user->load('role');

        $this->assertSame(Role::ADMINISTRATOR, $user->role?->slug);
        $this->assertFalse((bool) $user->is_platform_admin);

        $company = Company::query()->findOrFail($user->company_id);
        $this->assertSame('Nova Empresa Trial', $company->name);
        $this->assertSame(CompanySegment::INTERNET->value, $company->segment);

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertSame(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertSame('professional', $subscription->plan?->slug);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue(
            $subscription->trial_ends_at->between(now()->addDays(1)->startOfDay(), now()->addDays(3)->endOfDay())
        );
        $this->assertEqualsWithDelta(now()->addDays(2)->timestamp, $subscription->trial_ends_at->timestamp, 120);
    }

    public function test_trial_tenants_are_isolated(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'company_name' => 'Tenant A',
            'admin_email' => 'a@cadastro.test',
            'with_demo_data' => '1',
        ]))->assertRedirect(route('setup.show'));

        $companyA = Auth::user()->company_id;
        Auth::logout();

        $this->post(route('signup.store'), $this->payload([
            'company_name' => 'Tenant B',
            'admin_email' => 'b@cadastro.test',
            'admin_whatsapp' => '11977776666',
            'with_demo_data' => '1',
        ]))->assertRedirect(route('setup.show'));

        $companyB = Auth::user()->company_id;
        $this->assertNotSame($companyA, $companyB);

        $productsA = Product::query()->withoutGlobalScopes()->where('company_id', $companyA)->pluck('id');
        $productsB = Product::query()->withoutGlobalScopes()->where('company_id', $companyB)->pluck('id');
        $this->assertTrue($productsA->intersect($productsB)->isEmpty());
        $this->assertTrue($productsA->isNotEmpty());
        $this->assertTrue($productsB->isNotEmpty());
    }

    public function test_duplicate_email_is_blocked(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'admin_email' => 'unico@cadastro.test',
            'with_demo_data' => '0',
        ]))->assertRedirect(route('setup.show'));

        $this->post(route('logout'));

        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->payload([
                'company_name' => 'Outra Empresa',
                'admin_email' => 'unico@cadastro.test',
                'with_demo_data' => '0',
            ]))
            ->assertRedirect(route('signup.create'))
            ->assertSessionHasErrors('admin_email');

        $this->assertSame(
            1,
            User::query()->withoutGlobalScopes()->where('email', 'unico@cadastro.test')->count()
        );
    }

    public function test_admin_permissions_are_correct(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'admin_email' => 'perms@cadastro.test',
            'with_demo_data' => '0',
        ]));

        /** @var User $user */
        $user = Auth::user();
        $this->assertTrue($user->hasPermission('maps.view'));
        $this->assertTrue($user->hasPermission('visits.manage'));
        $this->assertTrue($user->hasPermission('onboarding.manage'));
        $this->assertTrue($user->hasPermission('branding.manage'));
        $this->assertFalse($user->hasPermission('platform.access'));
    }

    public function test_demo_yes_creates_sample_data(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'admin_email' => 'demo-on@cadastro.test',
            'with_demo_data' => '1',
        ]))->assertRedirect(route('setup.show'));

        $companyId = Auth::user()->company_id;

        $this->assertTrue(
            Product::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertTrue(
            Property::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
    }

    public function test_demo_no_keeps_clean_environment(): void
    {
        $this->post(route('signup.store'), $this->payload([
            'admin_email' => 'demo-off@cadastro.test',
            'with_demo_data' => '0',
        ]))->assertRedirect(route('setup.show'));

        $companyId = Auth::user()->company_id;

        $this->assertFalse(
            Product::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertFalse(
            Property::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
    }

    public function test_terms_and_honeypot_are_enforced(): void
    {
        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->payload([
                'terms_accepted' => '0',
                'with_demo_data' => '0',
            ]))
            ->assertRedirect(route('signup.create'))
            ->assertSessionHasErrors('terms_accepted');

        $this->from(route('signup.create'))
            ->post(route('signup.store'), $this->payload([
                'website' => 'http://spam.bot',
                'with_demo_data' => '0',
            ]))
            ->assertRedirect(route('signup.create'))
            ->assertSessionHasErrors('website');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Nova Empresa Trial',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin Trial',
            'admin_whatsapp' => '11988887777',
            'admin_email' => 'novo@cadastro.test',
            'admin_password' => 'Password123!',
            'admin_password_confirmation' => 'Password123!',
            'with_demo_data' => '1',
            'terms_accepted' => '1',
        ], $overrides);
    }
}
