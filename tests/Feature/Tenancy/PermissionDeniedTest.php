<?php

namespace Tests\Feature\Tenancy;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionDeniedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_permission_receives_access_denied(): void
    {
        $company = Company::factory()->create();
        $viewerRole = Role::query()->where('slug', Role::VIEWER)->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $company->id,
            'role_id' => $viewerRole->id,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Access denied.');
    }
}
