<?php

namespace Tests\Feature\Communication;

use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Enums\WhatsAppConnectionStatus;
use App\Domains\Communication\Jobs\SendWhatsAppMessageJob;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\WhatsAppConnection;
use App\Domains\Communication\Providers\ProviderFactory;
use App\Domains\Communication\Providers\WppConnectProvider;
use App\Domains\Communication\Services\MessageService;
use App\Domains\Company\Models\Role;
use App\Domains\Sales\Residents\Models\Resident;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Support\CreatesTenantUsers;
use Tests\Support\FakeWhatsAppProvider;
use Tests\TestCase;

class WhatsAppProviderIntegrationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_send_uses_configured_provider(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Provider');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-provider@comm.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        $connection = WhatsAppConnection::query()->create([
            'company_id' => $company->id,
            'provider' => 'wppconnect',
            'phone' => '5511999999999',
            'status' => WhatsAppConnectionStatus::CONNECTED,
            'credentials' => [
                'token' => 'test-token',
                'session' => 'geosales',
                'base_url' => 'https://wpp.example.test',
            ],
        ]);

        $provider = app(ProviderFactory::class)->make($connection);

        $this->assertInstanceOf(WppConnectProvider::class, $provider);

        Http::fake([
            'wpp.example.test/*' => Http::response([
                'status' => 'success',
                'response' => ['id' => 'wpp-123'],
            ], 200),
        ]);

        $result = $provider->sendMessage('11988887777', 'Olá do GeoSales');

        $this->assertTrue($result->success);
        $this->assertSame('wpp-123', $result->providerMessageId);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/geosales/send-message')
                && $request['phone'] === '11988887777'
                && $request['message'] === 'Olá do GeoSales';
        });
    }

    public function test_message_is_registered_before_send(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Ordem Envio');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-order@comm.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        WhatsAppConnection::query()->create([
            'company_id' => $company->id,
            'provider' => 'wppconnect',
            'phone' => '5511888888888',
            'status' => WhatsAppConnectionStatus::CONNECTED,
            'credentials' => [
                'token' => 'token',
                'session' => 'geosales',
                'base_url' => 'https://wpp.order.test',
            ],
        ]);

        $resident = Resident::factory()->create([
            'company_id' => $company->id,
            'phone' => '11977776666',
        ]);

        Queue::fake();

        $fake = new FakeWhatsAppProvider;
        $fake->driverName = 'wppconnect';

        $factory = Mockery::mock(ProviderFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($fake);
        $this->app->instance(ProviderFactory::class, $factory);

        $message = app(MessageService::class)->queueOutbound([
            'resident_id' => $resident->id,
            'message' => 'Mensagem registrada antes do envio',
        ], $admin);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'resident_id' => $resident->id,
            'message' => 'Mensagem registrada antes do envio',
            'status' => MessageStatus::QUEUED->value,
        ]);
        $this->assertSame([], $fake->sentMessages);

        (new SendWhatsAppMessageJob($message->id))->handle(app(ProviderFactory::class));

        $this->assertCount(1, $fake->sentMessages);
        $this->assertSame('Mensagem registrada antes do envio', $fake->sentMessages[0]['body']);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => MessageStatus::SENT->value,
        ]);
    }

    public function test_provider_failure_does_not_break_history(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Falha Provider');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-fail@comm.test',
        ]);

        app(TenantContext::class)->set($company, $admin);

        WhatsAppConnection::query()->create([
            'company_id' => $company->id,
            'provider' => 'wppconnect',
            'phone' => '5511777777777',
            'status' => WhatsAppConnectionStatus::CONNECTED,
            'credentials' => [
                'token' => 'token',
                'session' => 'geosales',
                'base_url' => 'https://wpp.fail.test',
            ],
        ]);

        $resident = Resident::factory()->create([
            'company_id' => $company->id,
            'phone' => '11966665555',
        ]);

        $fake = new FakeWhatsAppProvider;
        $fake->shouldThrow = true;

        $factory = Mockery::mock(ProviderFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($fake);
        $this->app->instance(ProviderFactory::class, $factory);

        $message = app(MessageService::class)->register([
            'resident_id' => $resident->id,
            'message' => 'Deve permanecer no histórico',
            'status' => MessageStatus::QUEUED->value,
        ], $admin);

        (new SendWhatsAppMessageJob($message->id))->handle(app(ProviderFactory::class));

        $this->assertSame(1, Message::query()->where('id', $message->id)->count());
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message' => 'Deve permanecer no histórico',
            'status' => MessageStatus::FAILED->value,
        ]);
    }
}
