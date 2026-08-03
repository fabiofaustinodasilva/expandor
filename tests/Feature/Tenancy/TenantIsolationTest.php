<?php

namespace Tests\Feature\Tenancy;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_cannot_list_users_from_another_company(): void
    {
        $companyA = Company::factory()->create(['name' => 'Company A']);
        $companyB = Company::factory()->create(['name' => 'Company B']);

        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();

        $userA = User::factory()->create([
            'company_id' => $companyA->id,
            'role_id' => $adminRole->id,
            'email' => 'admin-a@example.com',
        ]);

        User::factory()->create([
            'company_id' => $companyB->id,
            'role_id' => $adminRole->id,
            'email' => 'admin-b@example.com',
            'name' => 'Admin Company B',
        ]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $emails = collect($response->json('data'))->pluck('email');

        $this->assertTrue($emails->contains('admin-a@example.com'));
        $this->assertFalse($emails->contains('admin-b@example.com'));
    }
}
