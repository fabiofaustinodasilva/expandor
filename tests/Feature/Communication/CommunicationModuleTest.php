<?php

namespace Tests\Feature\Communication;

use App\Domains\Communication\Enums\MessageDirection;
use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Models\Message;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Residents\Models\Resident;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class CommunicationModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_cannot_access_messages_from_another_company(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Comm A');
        $companyB = $this->makeCompanyWithPlan('Empresa Comm B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@comm.test',
        ]);

        $residentA = Resident::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Morador Alpha Comm',
        ]);

        $residentB = Resident::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Morador Beta Comm',
        ]);

        Message::factory()->create([
            'company_id' => $companyA->id,
            'resident_id' => $residentA->id,
            'user_id' => $adminA->id,
            'message' => 'Mensagem exclusiva Alpha',
        ]);

        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@comm.test',
        ]);

        Message::factory()->create([
            'company_id' => $companyB->id,
            'resident_id' => $residentB->id,
            'user_id' => $adminB->id,
            'message' => 'Mensagem exclusiva Beta',
        ]);

        $this->actingAs($adminA)
            ->get(route('communication.messages.index'))
            ->assertOk()
            ->assertSee('Mensagem exclusiva Alpha')
            ->assertDontSee('Mensagem exclusiva Beta');
    }

    public function test_history_belongs_to_correct_resident(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Hist Comm');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-hist@comm.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $residentA = Resident::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cliente Histórico A',
        ]);

        $residentB = Resident::factory()->create([
            'company_id' => $company->id,
            'name' => 'Cliente Histórico B',
        ]);

        Queue::fake();

        $this->actingAs($admin)->post(route('communication.messages.store'), [
            'resident_id' => $residentA->id,
            'message' => 'Olá, retorno agendado para amanhã.',
            'queue_send' => 1,
        ])->assertRedirect(route('communication.messages.index'));

        Message::factory()->create([
            'company_id' => $company->id,
            'resident_id' => $residentB->id,
            'user_id' => $admin->id,
            'message' => 'Mensagem do cliente B',
            'direction' => MessageDirection::OUTBOUND,
            'status' => MessageStatus::PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('communication.messages.history', $residentA))
            ->assertOk()
            ->assertSee('Olá, retorno agendado para amanhã.')
            ->assertDontSee('Mensagem do cliente B')
            ->assertSee('Cliente Histórico A');

        $this->assertDatabaseHas('messages', [
            'company_id' => $company->id,
            'resident_id' => $residentA->id,
            'message' => 'Olá, retorno agendado para amanhã.',
            'direction' => MessageDirection::OUTBOUND->value,
            'status' => MessageStatus::QUEUED->value,
        ]);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer Comm');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@comm.test',
        ]);

        $this->actingAs($viewer)
            ->get(route('communication.messages.index'))
            ->assertForbidden();
    }
}
