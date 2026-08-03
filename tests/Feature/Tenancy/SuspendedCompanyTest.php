<?php

namespace Tests\Feature\Tenancy;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuspendedCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_suspended_company_cannot_operate(): void
    {
        $company = Company::factory()->suspended()->create();
        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role_id' => $adminRole->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Company is suspended and cannot operate.');
    }
}
