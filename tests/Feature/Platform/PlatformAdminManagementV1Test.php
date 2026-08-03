<?php

namespace Tests\Feature\Platform;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class PlatformAdminManagementV1Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_middleware_blocks_user_without_is_platform_admin_flag(): void
    {
        $company = $this->makeCompanyWithPlan('Cliente Sem Flag');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@nofag.test',
            'is_platform_admin' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.profile.edit'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('platform.companies.index'))
            ->assertForbidden();
    }

    public function test_owner_can_update_profile_name_email_and_password(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.profile.update'), [
                'name' => 'Owner Atualizado',
                'email' => 'owner-atualizado@geosales.local',
                'password' => 'NovaSenha123!',
                'password_confirmation' => 'NovaSenha123!',
            ])
            ->assertRedirect(route('platform.profile.edit'));

        $owner->refresh();
        $this->assertSame('Owner Atualizado', $owner->name);
        $this->assertSame('owner-atualizado@geosales.local', $owner->email);
        $this->assertTrue(Hash::check('NovaSenha123!', $owner->password));

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.owner.profile_updated')
                ->where('user_id', $owner->id)
                ->exists()
        );
    }

    public function test_owner_can_upload_and_remove_profile_photo(): void
    {
        Storage::fake('public');
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->put(route('platform.profile.update'), [
                'name' => $owner->name,
                'email' => $owner->email,
                'photo' => UploadedFile::fake()->image('owner.jpg', 400, 400),
            ])
            ->assertRedirect(route('platform.profile.edit'));

        $owner->refresh();
        $this->assertNotNull($owner->photo);
        Storage::disk('public')->assertExists($owner->photo);

        $this->actingAs($owner)
            ->put(route('platform.profile.update'), [
                'name' => $owner->name,
                'email' => $owner->email,
                'remove_photo' => true,
            ])
            ->assertRedirect(route('platform.profile.edit'));

        $owner->refresh();
        $this->assertNull($owner->photo);
    }

    public function test_owner_can_edit_company_and_audit_is_recorded(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Editável');

        $this->actingAs($owner)
            ->get(route('platform.companies.edit', $company))
            ->assertOk()
            ->assertSee('Editar empresa');

        $this->actingAs($owner)
            ->put(route('platform.companies.update', $company), [
                'name' => 'Empresa Editada',
                'legal_name' => 'Empresa Editada LTDA',
                'email' => 'contato@editada.test',
                'phone' => '62999999999',
                'whatsapp' => '62999999999',
                'document' => '11.111.111/0001-11',
                'address' => 'Rua A, 100',
                'segment' => 'telecom',
            ])
            ->assertRedirect(route('platform.companies.show', $company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Empresa Editada',
            'email' => 'contato@editada.test',
        ]);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.company.updated')
                ->where('company_id', $company->id)
                ->exists()
        );
    }

    public function test_owner_can_soft_delete_and_restore_company(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Arquivável');

        $this->actingAs($owner)
            ->delete(route('platform.companies.destroy', $company), [
                'reason' => 'Cliente encerrou',
            ])
            ->assertRedirect(route('platform.companies.index'));

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => Company::STATUS_CANCELLED,
            'deletion_reason' => 'Cliente encerrou',
            'deleted_by' => $owner->id,
        ]);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.company.soft_deleted')
                ->where('company_id', $company->id)
                ->exists()
        );

        $this->actingAs($owner)
            ->get(route('platform.companies.index', ['archived' => '1']))
            ->assertOk()
            ->assertSee('Empresa Arquivável');

        $this->actingAs($owner)
            ->post(route('platform.companies.restore', $company))
            ->assertRedirect(route('platform.companies.show', $company));

        $company->refresh();
        $this->assertFalse($company->trashed());
        $this->assertSame(Company::STATUS_SUSPENDED, $company->status);
        $this->assertNull($company->deletion_reason);

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.company.restored')
                ->where('company_id', $company->id)
                ->exists()
        );
    }

    public function test_owner_can_reset_company_admin_password(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Reset Senha');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@reset.test',
            'password' => 'password',
        ]);

        $this->actingAs($owner)
            ->post(route('platform.companies.reset-admin-password', $company), [
                'user_id' => $admin->id,
                'password' => 'SenhaResetada123!',
                'password_confirmation' => 'SenhaResetada123!',
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertTrue(Hash::check('SenhaResetada123!', $admin->password));

        $this->assertTrue(
            AuditLog::query()->withoutGlobalScopes()
                ->where('action', 'platform.company.admin_password_reset')
                ->where('company_id', $company->id)
                ->exists()
        );
    }

    public function test_company_show_lists_subscription_and_users(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Show');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin@show.test',
            'name' => 'Admin Show',
        ]);

        $this->actingAs($owner)
            ->get(route('platform.companies.show', $company))
            ->assertOk()
            ->assertSee('Assinatura')
            ->assertSee('Usuários')
            ->assertSee('Admin Show')
            ->assertSee($admin->email)
            ->assertSee('Professional');
    }

    public function test_suspend_and_activate_still_work_with_audit(): void
    {
        $owner = $this->makePlatformAdmin();
        $company = $this->makeCompanyWithPlan('Empresa Suspender');

        $this->actingAs($owner)
            ->post(route('platform.companies.suspend', $company), [
                'reason' => 'Inadimplência',
            ])
            ->assertRedirect();

        $this->assertSame(Company::STATUS_SUSPENDED, $company->fresh()->status);

        $this->actingAs($owner)
            ->post(route('platform.companies.activate', $company))
            ->assertRedirect();

        $this->assertSame(Company::STATUS_ACTIVE, $company->fresh()->status);
    }

    public function test_system_company_cannot_be_soft_deleted(): void
    {
        $owner = $this->makePlatformAdmin();
        $system = Company::query()->where('is_system', true)->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('platform.companies.destroy', $system))
            ->assertNotFound();
    }
}
