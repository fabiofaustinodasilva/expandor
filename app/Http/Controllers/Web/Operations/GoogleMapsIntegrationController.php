<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Services\GoogleMapsIntegrationService;
use App\Domains\Integrations\Services\IntegrationEntitlementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoogleMapsIntegrationController extends Controller
{
    public function edit(
        Request $request,
        IntegrationEntitlementService $entitlements,
        GoogleMapsIntegrationService $service,
    ): View {
        $user = $request->user();
        abort_unless($user?->hasPermission('integrations.view') ?? false, 403);

        $company = $user->company;
        abort_unless($company !== null, 403);

        $entitled = $entitlements->allowsGoogleMaps($company);
        $integration = $service->find($company);

        return view('operations.integrations.google-maps', [
            'entitled' => $entitled,
            'integration' => $integration,
            'maskedKey' => $integration?->maskedBrowserApiKey(),
            'canManage' => $user->hasPermission('integrations.manage'),
            'billingNotice' => 'Serviços externos podem possuir cobrança própria. A cobrança e os limites do Google Maps são administrados na conta Google Cloud da sua empresa. O plano Expandor libera o direito de usar a integração; o consumo da API é cobrado pela Google na conta da sua empresa.',
        ]);
    }

    public function update(Request $request, GoogleMapsIntegrationService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('integrations.manage') ?? false, 403);

        $company = $user->company;
        abort_unless($company !== null, 403);

        $data = $request->validate([
            'browser_api_key' => ['required', 'string', 'min:20', 'max:512'],
        ]);

        try {
            $result = $service->connect($company, $user, $data['browser_api_key']);
        } catch (ValidationException $e) {
            return redirect()
                ->route('operations.integrations.google-maps.edit')
                ->withErrors($e->errors())
                ->withInput($request->except('browser_api_key'));
        }

        return redirect()
            ->route('operations.integrations.google-maps.edit')
            ->with('success', $result['test']['message']);
    }

    public function test(Request $request, GoogleMapsIntegrationService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('integrations.manage') ?? false, 403);

        $company = $user->company;
        abort_unless($company !== null, 403);

        $data = $request->validate([
            'browser_api_key' => ['nullable', 'string', 'min:20', 'max:512'],
        ]);

        try {
            $service->assertEntitled($company);
        } catch (ValidationException $e) {
            return redirect()
                ->route('operations.integrations.google-maps.edit')
                ->withErrors($e->errors());
        }

        $result = $service->test(
            $company,
            $user,
            filled($data['browser_api_key'] ?? null) ? $data['browser_api_key'] : null,
        );

        if ($result['ok']) {
            return redirect()
                ->route('operations.integrations.google-maps.edit')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('operations.integrations.google-maps.edit')
            ->with('error', $result['message'])
            ->withInput($request->except('browser_api_key'));
    }

    public function disconnect(Request $request, GoogleMapsIntegrationService $service): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasPermission('integrations.manage') ?? false, 403);

        $company = $user->company;
        abort_unless($company !== null, 403);

        $service->disconnect($company, $user);

        return redirect()
            ->route('operations.integrations.google-maps.edit')
            ->with('success', 'Google Maps desconectado. O mapa padrão Expandor continua ativo.');
    }
}
