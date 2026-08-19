<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Actions\UpdateMarketplaceSettingsAction;
use App\Domains\Marketplace\Growth\Models\MarketplaceLeadNotification;
use App\Domains\Marketplace\Growth\Services\CommercialWhatsAppGateway;
use App\Domains\Marketplace\Growth\Support\BrazilianPhone;
use App\Domains\Marketplace\Requests\UpdateMarketplaceSettingsRequest;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Database\Seeders\MarketplaceDefaultSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketplaceSettingsController extends Controller
{
    public function edit(MarketplaceSettingsService $settings): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.settings', [
            'settings' => $settings->current(),
        ]);
    }

    public function update(
        UpdateMarketplaceSettingsRequest $request,
        UpdateMarketplaceSettingsAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->validated(),
            [
                'logo' => $request->file('logo'),
                'favicon' => $request->file('favicon'),
                'hero_image' => $request->file('hero_image'),
                'og_image' => $request->file('og_image'),
            ],
            [
                'logo' => $request->boolean('remove_logo'),
                'favicon' => $request->boolean('remove_favicon'),
                'hero_image' => $request->boolean('remove_hero_image'),
                'og_image' => $request->boolean('remove_og_image'),
            ],
        );

        return redirect()
            ->route('platform.marketplace.settings.edit')
            ->with('success', 'Configuração do site salva.');
    }

    public function restoreDefaults(): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        (new MarketplaceDefaultSeeder)->run(force: true);

        return redirect()
            ->route('platform.marketplace.settings.edit')
            ->with('success', 'Conteúdo padrão do Marketplace restaurado.');
    }

    public function preview(MarketplacePublicPageService $page): View
    {
        $this->authorize('marketplace.manage');

        $data = $page->assemble(bypassCache: true);
        $data['preview'] = true;

        return view('marketplace.landing', $data);
    }

    public function sendCommercialAlertTest(
        MarketplaceSettingsService $settings,
        CommercialWhatsAppGateway $gateway,
    ): RedirectResponse {
        $this->authorize('marketplace.manage');

        $row = $settings->current();
        $to = BrazilianPhone::normalize((string) $row->commercial_alert_whatsapp);
        if ($to === null) {
            return back()->withErrors([
                'commercial_alert_whatsapp' => 'Informe o WhatsApp que deve receber os alertas.',
            ]);
        }

        $body = "🧪 Teste de alerta comercial Expandor.\nSe você recebeu esta mensagem, a notificação de novos pedidos de demonstração está funcionando.";
        $result = $gateway->send($to, $body);

        MarketplaceLeadNotification::query()->create([
            'lead_id' => null,
            'type' => MarketplaceLeadNotification::TYPE_TEST,
            'channel' => 'whatsapp',
            'status' => $result->success
                ? MarketplaceLeadNotification::STATUS_SENT
                : MarketplaceLeadNotification::STATUS_FAILED,
            'to_phone' => $to,
            'body' => $body,
            'error' => $result->error,
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => $result->success ? now() : null,
        ]);

        if (! $result->success) {
            return back()->withErrors([
                'commercial_alert_whatsapp' => 'Não foi possível enviar o teste: '.($result->error ?: 'provedor indisponível'),
            ]);
        }

        return back()->with('success', 'Mensagem de teste enviada para o WhatsApp comercial.');
    }
}
