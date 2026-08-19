<?php

namespace Tests\Feature\Acquisition;

use App\Domains\Acquisition\Actions\ProvisionTrialCompanyAction;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Cadastro público deixou de ofertar trial/legado. Provisionamento interno permanece.
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

    public function test_cadastro_page_redirects_public_visitors_to_demo(): void
    {
        $this->get(route('signup.create'))
            ->assertRedirect(route('marketplace.home').'#demo');
    }

    public function test_public_cadastro_does_not_create_a_company(): void
    {
        $this->post(route('signup.store'), $this->payload())
            ->assertRedirect(route('marketplace.home').'#demo');

        $this->assertGuest();
        $this->assertNull(Company::query()->where('name', 'Nova Empresa Trial')->first());
    }

    public function test_internal_provision_creates_company_admin_and_trial_subscription(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload());

        Auth::login($result->administrator);

        $user = $result->administrator->load('role');
        $this->assertSame(Role::ADMINISTRATOR, $user->role?->slug);
        $this->assertFalse((bool) $user->is_platform_admin);

        $this->assertSame('Nova Empresa Trial', $result->company->name);
        $this->assertSame(CompanySegment::INTERNET->value, $result->company->segment);

        $subscription = $result->subscription;
        $this->assertSame(Subscription::STATUS_TRIAL, $subscription->status);
        $this->assertSame('professional', $subscription->plan?->slug);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue(
            $subscription->trial_ends_at->between(now()->addDays(1)->startOfDay(), now()->addDays(3)->endOfDay())
        );
    }

    public function test_trial_tenants_are_isolated(): void
    {
        $companyA = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'company_name' => 'Tenant A',
            'admin_email' => 'a@cadastro.test',
            'with_demo_data' => true,
        ]))->company->id;

        $companyB = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'company_name' => 'Tenant B',
            'admin_email' => 'b@cadastro.test',
            'admin_whatsapp' => '11977776666',
            'with_demo_data' => true,
        ]))->company->id;

        $this->assertNotSame($companyA, $companyB);

        $productsA = Product::query()->withoutGlobalScopes()->where('company_id', $companyA)->pluck('id');
        $productsB = Product::query()->withoutGlobalScopes()->where('company_id', $companyB)->pluck('id');
        $this->assertTrue($productsA->intersect($productsB)->isEmpty());
        $this->assertTrue($productsA->isNotEmpty());
        $this->assertTrue($productsB->isNotEmpty());
    }

    public function test_duplicate_email_is_blocked(): void
    {
        app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'admin_email' => 'unico@cadastro.test',
            'with_demo_data' => false,
        ]));

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'company_name' => 'Outra Empresa',
            'admin_email' => 'unico@cadastro.test',
            'with_demo_data' => false,
        ]));
    }

    public function test_admin_permissions_are_correct(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'admin_email' => 'perms@cadastro.test',
            'with_demo_data' => false,
        ]));

        Auth::login($result->administrator);
        $user = $result->administrator;

        $this->assertTrue($user->hasPermission('maps.view'));
        $this->assertTrue($user->hasPermission('visits.manage'));
        $this->assertTrue($user->hasPermission('onboarding.manage'));
        $this->assertTrue($user->hasPermission('branding.manage'));
        $this->assertFalse($user->hasPermission('platform.access'));
    }

    public function test_demo_yes_creates_sample_data(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'admin_email' => 'demo-on@cadastro.test',
            'with_demo_data' => true,
        ]));

        $companyId = $result->company->id;
        $this->assertTrue(
            Product::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertTrue(
            Property::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
    }

    public function test_demo_no_keeps_clean_environment(): void
    {
        $result = app(ProvisionTrialCompanyAction::class)->execute($this->actionPayload([
            'admin_email' => 'demo-off@cadastro.test',
            'with_demo_data' => false,
        ]));

        $companyId = $result->company->id;
        $this->assertFalse(
            Product::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
        $this->assertFalse(
            Property::query()->withoutGlobalScopes()->where('company_id', $companyId)->exists()
        );
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function actionPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Nova Empresa Trial',
            'segment' => CompanySegment::INTERNET->value,
            'admin_name' => 'Admin Trial',
            'admin_whatsapp' => '11988887777',
            'admin_email' => 'novo@cadastro.test',
            'admin_password' => 'Password123!',
            'with_demo_data' => false,
        ], $overrides);
    }
}
