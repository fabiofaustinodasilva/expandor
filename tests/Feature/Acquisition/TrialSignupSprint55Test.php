<?php

namespace Tests\Feature\Acquisition;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Payments\Services\BillingAutomationService;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class TrialSignupSprint55Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_guest_can_open_trial_form_from_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Começar agora', false)
            ->assertSee('/cadastro', false);

        $this->get(route('signup.create'))
            ->assertOk()
            ->assertSee('data-trial-signup="1"', false)
            ->assertSee('Criar minha conta grátis', false)
            ->assertSee('Professional', false)
            ->assertSee('name="company_name"', false);

        $this->get(route('trial.create'))
            ->assertOk()
            ->assertSee('Criar ambiente demonstração', false)
            ->assertSee('Professional', false);
    }

    public function test_trial_signup_creates_tenant_professional_subscription_and_admin(): void
    {
        $response = $this->post(route('trial.store'), $this->validPayload([
            'with_demo_data' => '0',
        ]));

        $response->assertRedirect(route('setup.show'));
        $this->assertAuthenticated();

        $user = Auth::user();
        $this->assertInstanceOf(User::class, $user);
        $user->load('role');
        $this->assertFalse($user->is_platform_admin);
        $this->assertSame(Role::ADMINISTRATOR, $user->role?->slug);

        $company = Company::query()->findOrFail($user->company_id);
        $this->assertSame('Trial Demo LTDA', $company->name);
        $this->assertSame(CompanySegment::INTERNET->value, $company->segment);
        $this->assertSame('11988887777', $company->whatsapp);
        $this->assertSame(Company::STATUS_ACTIVE, $company->status);

        $subscription = Subscription::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($subscription);
        $this->assertSame(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertSame('professional', $subscription->plan?->slug);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->between(now()->addDays(1), now()->addDays(3)));

        $this->assertDatabaseHas('brands', ['company_id' => $company->id]);
        $this->assertNotNull(OnboardingRun::query()->where('company_id', $company->id)->first());
    }

    public function test_demo_data_checkbox_seeds_examples_when_enabled(): void
    {
        $this->post(route('trial.store'), $this->validPayload([
            'admin_email' => 'demo-on@trial.test',
            'with_demo_data' => '1',
        ]))->assertRedirect(route('setup.show'));

        $companyId = Auth::user()->company_id;

        $this->assertTrue(
            Product::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertTrue(
            Property::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertTrue(
            OnboardingRun::query()->where('company_id', $companyId)->where('demo_generated', true)->exists()
        );
    }

    public function test_demo_data_off_keeps_clean_environment(): void
    {
        $this->post(route('trial.store'), $this->validPayload([
            'admin_email' => 'demo-off@trial.test',
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

    public function test_duplicate_email_is_rejected_globally(): void
    {
        $company = $this->makeCompanyWithPlan('Existente');
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'mesmo@trial.test',
        ]);

        $this->from(route('trial.create'))
            ->post(route('trial.store'), $this->validPayload([
                'admin_email' => 'mesmo@trial.test',
            ]))
            ->assertRedirect(route('trial.create'))
            ->assertSessionHasErrors('admin_email');
    }

    public function test_trial_tenants_are_isolated(): void
    {
        $this->post(route('trial.store'), $this->validPayload([
            'company_name' => 'Empresa A Trial',
            'admin_email' => 'a@trial.test',
            'with_demo_data' => '1',
        ]));
        $companyA = Auth::user()->company_id;
        Auth::logout();

        $this->post(route('trial.store'), $this->validPayload([
            'company_name' => 'Empresa B Trial',
            'admin_email' => 'b@trial.test',
            'with_demo_data' => '1',
        ]));
        $companyB = Auth::user()->company_id;

        $this->assertNotSame($companyA, $companyB);
        $this->assertSame(
            Brand::query()->withoutGlobalScopes()->where('company_id', $companyA)->count(),
            1
        );
        $this->assertSame(
            Brand::query()->withoutGlobalScopes()->where('company_id', $companyB)->count(),
            1
        );

        $productsA = Product::query()->withoutGlobalScopes()->where('company_id', $companyA)->pluck('id');
        $productsB = Product::query()->withoutGlobalScopes()->where('company_id', $companyB)->pluck('id');
        $this->assertTrue($productsA->intersect($productsB)->isEmpty());
    }

    public function test_expired_trial_suspends_company_access(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute([
            'company_name' => 'Expira Já',
            'segment' => CompanySegment::SOLAR->value,
            'admin_name' => 'Owner Trial',
            'admin_email' => 'expira@trial.test',
            'admin_whatsapp' => '11977776666',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ]);

        $result->subscription->forceFill([
            'trial_ends_at' => now()->subMinute(),
        ])->save();

        app(BillingAutomationService::class)->expireTrials();

        $this->assertSame(
            Subscription::STATUS_PAST_DUE,
            $result->subscription->fresh()->status
        );
        $this->assertSame(
            Company::STATUS_SUSPENDED,
            $result->company->fresh()->status
        );

        $this->actingAs($result->administrator)
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Company is suspended and cannot operate.');
    }

    public function test_owner_can_convert_trial_to_active_client(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute([
            'company_name' => 'Converter LTDA',
            'segment' => CompanySegment::SECURITY->value,
            'admin_name' => 'Resp',
            'admin_email' => 'convert@trial.test',
            'admin_whatsapp' => '11966665555',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ]);

        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->post(route('platform.companies.convert-trial', $result->company))
            ->assertRedirect();

        $subscription = $result->subscription->fresh();
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertSame(Company::STATUS_ACTIVE, $result->company->fresh()->status);
    }

    public function test_administrator_role_has_operational_permissions(): void
    {
        $this->post(route('trial.store'), $this->validPayload([
            'admin_email' => 'perms@trial.test',
            'with_demo_data' => '0',
        ]));

        /** @var User $user */
        $user = Auth::user();
        $this->assertTrue($user->hasPermission('maps.view'));
        $this->assertTrue($user->hasPermission('visits.manage'));
        $this->assertTrue($user->hasPermission('commissions.manage'));
        $this->assertTrue($user->hasPermission('onboarding.manage'));
        $this->assertTrue($user->hasPermission('branding.manage'));
        $this->assertFalse($user->hasPermission('platform.access'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Trial Demo LTDA',
            'segment' => CompanySegment::INTERNET->value,
            'document' => '',
            'admin_name' => 'Responsável Trial',
            'admin_whatsapp' => '11988887777',
            'admin_email' => 'novo@trial.test',
            'admin_password' => 'Password123!',
            'admin_password_confirmation' => 'Password123!',
            'with_demo_data' => '1',
            'terms_accepted' => '1',
        ], $overrides);
    }
}
