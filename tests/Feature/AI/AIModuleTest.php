<?php

namespace Tests\Feature\AI;

use App\Domains\AI\Context\SalesContext;
use App\Domains\AI\Enums\AIContextType;
use App\Domains\AI\Models\AIConversation;
use App\Domains\AI\Providers\ProviderFactory;
use App\Domains\AI\Services\AIService;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Properties\Models\Property;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\FakeAIProvider;
use Tests\TestCase;

class AIModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_cannot_access_conversation_from_another_company(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa AI A');
        $companyB = $this->makeCompanyWithPlan('Empresa AI B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@ai.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'admin-b@ai.test',
        ]);

        AIConversation::factory()->create([
            'company_id' => $companyA->id,
            'user_id' => $adminA->id,
            'question' => 'Pergunta exclusiva Alpha AI',
            'answer' => 'Resposta Alpha',
        ]);

        AIConversation::factory()->create([
            'company_id' => $companyB->id,
            'user_id' => $adminB->id,
            'question' => 'Pergunta exclusiva Beta AI',
            'answer' => 'Resposta Beta',
        ]);

        $this->actingAs($adminA)
            ->get(route('ai.conversations.index'))
            ->assertOk()
            ->assertSee('Pergunta exclusiva Alpha AI')
            ->assertDontSee('Pergunta exclusiva Beta AI');
    }

    public function test_context_respects_tenant(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Contexto A');
        $companyB = $this->makeCompanyWithPlan('Empresa Contexto B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-ctx-a@ai.test',
        ]);

        Property::factory()->create([
            'company_id' => $companyA->id,
        ]);
        Property::factory()->count(3)->create([
            'company_id' => $companyB->id,
        ]);

        app(TenantContext::class)->set($companyA, $adminA);

        $payload = app(SalesContext::class)->build();

        $this->assertSame($companyA->id, $payload->companyId);
        $this->assertStringContainsString('Company ID: '.$companyA->id, $payload->body);
        $this->assertStringContainsString('Imóveis: 1', $payload->body);
        $this->assertStringNotContainsString('Company ID: '.$companyB->id, $payload->body);
        $this->assertStringNotContainsString('Imóveis: 3', $payload->body);
    }

    public function test_provider_can_be_replaced(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Provider AI');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-provider@ai.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $fake = new FakeAIProvider;
        $fake->chatResponse = 'Sugestão do provider substituído';

        $factory = Mockery::mock(ProviderFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($fake);
        $this->app->instance(ProviderFactory::class, $factory);

        $conversation = app(AIService::class)->ask(
            question: 'Como priorizar visitas?',
            contextType: AIContextType::SALES,
            user: $admin,
        );

        $this->assertSame('fake', $conversation->provider);
        $this->assertSame('Sugestão do provider substituído', $conversation->answer);
        $this->assertCount(1, $fake->chatCalls);
        $this->assertStringContainsString('Como priorizar visitas?', $fake->chatCalls[0]['user']);
        $this->assertStringContainsString('apenas sugestões', strtolower($fake->chatCalls[0]['system']));

        $this->assertDatabaseHas('ai_conversations', [
            'id' => $conversation->id,
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'provider' => 'fake',
            'question' => 'Como priorizar visitas?',
        ]);
    }

    public function test_user_without_permission_receives_forbidden(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Viewer AI');
        $viewer = $this->makeUser($company, Role::VIEWER, [
            'email' => 'viewer@ai.test',
        ]);

        $this->actingAs($viewer)
            ->get(route('ai.conversations.index'))
            ->assertForbidden();
    }
}
