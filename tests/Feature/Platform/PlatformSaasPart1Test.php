<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\SubscriptionEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PlatformSaasPart1Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_dashboard_shows_saas_kpis(): void
    {
        $owner = $this->makePlatformAdmin();
        $this->makeCompanyWithPlan('Cliente KPI');

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Empresas totais')
            ->assertSee('Churn')
            ->assertSee('Conversão de teste');
    }

    public function test_owner_can_crud_plans(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->post(route('platform.plans.store'), [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Plano intermediário',
                'price' => 99.9,
                'price_yearly' => 999,
                'trial_days' => 7,
                'max_users' => 10,
                'max_properties' => 1000,
                'max_campaigns' => 20,
                'max_teams' => 3,
                'max_products' => 100,
                'max_storage_mb' => 2048,
                'features' => [
                    'crm' => '1',
                    'ai' => '0',
                    'whatsapp' => '1',
                    'stock' => '0',
                    'finance' => '0',
                    'api' => '0',
                    'white_label' => '0',
                ],
                'status' => Plan::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('platform.plans.index'));

        $plan = Plan::query()->where('slug', 'growth')->firstOrFail();
        $this->assertSame(7, $plan->trial_days);
        $this->assertTrue($plan->hasCatalogFeature('crm'));
        $this->assertTrue($plan->hasCatalogFeature('whatsapp'));
        $this->assertFalse($plan->hasCatalogFeature('ai'));

        $this->actingAs($owner)
            ->put(route('platform.plans.update', $plan), [
                'name' => 'Growth Plus',
                'slug' => 'growth',
                'description' => 'Atualizado',
                'price' => 129.9,
                'price_yearly' => 1299,
                'trial_days' => 10,
                'max_users' => 15,
                'features' => [
                    'crm' => true,
                    'ai' => true,
                    'whatsapp' => true,
                    'stock' => false,
                    'finance' => false,
                    'api' => false,
                    'white_label' => false,
                ],
                'status' => Plan::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('platform.plans.edit', $plan));

        $this->assertSame('Growth Plus', $plan->fresh()->name);
        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()->where('action', 'platform.plan.created')->exists()
        );
    }

    public function test_company_show_includes_operational_metrics(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Ops');
        $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@ops.test',
            'last_login_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('platform.companies.show', $company))
            ->assertOk()
            ->assertSee('Métricas operacionais')
            ->assertSee('Assinatura')
            ->assertSee('Gestão do administrador');
    }

    public function test_subscription_renew_change_plan_cancel_reactivate(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Sub');
        $subscription = Subscription::query()->withoutGlobalScopes()->where('company_id', $company->id)->firstOrFail();
        $subscription->forceFill([
            'status' => Subscription::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(1),
        ])->save();

        $scale = Plan::query()->where('slug', 'scale')->firstOrFail();

        $this->actingAs($owner)
            ->post(route('platform.companies.subscription.renew-trial', $company), ['days' => 5])
            ->assertRedirect();

        $this->assertTrue(
            SubscriptionEvent::query()->where('company_id', $company->id)->where('event', 'trial.renewed')->exists()
        );

        $this->actingAs($owner)
            ->post(route('platform.companies.subscription.change-plan', $company), [
                'plan_id' => $scale->id,
            ])
            ->assertRedirect();

        $this->assertSame($scale->id, $subscription->fresh()->plan_id);

        $this->actingAs($owner)
            ->post(route('platform.companies.subscription.cancel', $company), [
                'reason' => 'Teste cancelamento',
            ])
            ->assertRedirect();

        $this->assertSame(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);

        $this->actingAs($owner)
            ->post(route('platform.companies.subscription.reactivate', $company))
            ->assertRedirect();

        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    public function test_admin_contact_block_unblock_and_change_administrator(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Admin Mgmt');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@mgmt.test',
            'phone' => '62999990000',
        ]);
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@mgmt.test',
        ]);

        $this->actingAs($owner)
            ->put(route('platform.companies.admin-contact', $company), [
                'user_id' => $admin->id,
                'email' => 'novo-admin@mgmt.test',
                'phone' => '62988887777',
                'whatsapp' => '62988887777',
            ])
            ->assertRedirect();

        $this->assertSame('novo-admin@mgmt.test', $admin->fresh()->email);

        $this->actingAs($owner)
            ->post(route('platform.companies.admin-block', $company), ['user_id' => $admin->id])
            ->assertRedirect();
        $this->assertSame(User::STATUS_BLOCKED, $admin->fresh()->status);

        $this->actingAs($owner)
            ->post(route('platform.companies.admin-unblock', $company), ['user_id' => $admin->id])
            ->assertRedirect();
        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);

        $this->actingAs($owner)
            ->post(route('platform.companies.admin-force-logout', $company), ['user_id' => $admin->id])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('platform.companies.change-administrator', $company), [
                'user_id' => $seller->id,
            ])
            ->assertRedirect();

        $this->assertSame(Role::ADMINISTRATOR, $seller->fresh()->role->slug);
        $this->assertSame(Role::MANAGER, $admin->fresh()->role->slug);
    }

    public function test_owner_profile_shows_two_factor_structure(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.profile.edit'))
            ->assertOk()
            ->assertSee('Autenticação em dois fatores')
            ->assertSee('Estrutura preparada');
    }

    public function test_tenant_admin_cannot_manage_plans(): void
    {
        $company = $this->makeCompanyWithPlan('Sem Planos');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@semplanos.test',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.plans.index'))
            ->assertForbidden();
    }
}
