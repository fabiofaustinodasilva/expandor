<?php

namespace Tests\Feature;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeosalesInstallCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_geosales_install_prepares_demo_environment(): void
    {
        $this->artisan('geosales:install')
            ->assertSuccessful();

        $company = Company::query()
            ->where('name', 'Única Network Demo')
            ->first();

        $this->assertNotNull($company);

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'admin@unicanetwork.demo',
        ]);

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'manager@unicanetwork.demo',
        ]);

        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'seller@unicanetwork.demo',
        ]);

        $admin = User::query()
            ->withoutGlobalScopes()
            ->where('email', 'admin@unicanetwork.demo')
            ->firstOrFail();

        $this->assertSame(Role::ADMINISTRATOR, $admin->role->slug);
        $this->assertTrue($admin->hasPermission('company.manage'));
        $this->assertTrue($admin->hasPermission('users.manage'));

        $this->assertTrue(
            CompanySetting::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('key', 'timezone')
                ->where('value', 'America/Sao_Paulo')
                ->exists()
        );
    }
}
