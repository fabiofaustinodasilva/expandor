<?php

namespace App\Http\Controllers\Web\Payments;

use App\Http\Controllers\Controller;
use App\Domains\Payments\Providers\ProviderFactory;
use App\Domains\Payments\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $webhooks,
        protected ProviderFactory $providers,
    ) {}

    public function asaas(Request $request): JsonResponse
    {
        return $this->handle('asaas', $request);
    }

    public function handle(string $provider, Request $request): JsonResponse
    {
        $result = $this->webhooks->handle($provider, $request);

        return response()->json([
            'success' => true,
            'duplicate' => $result['duplicate'],
            'provisioned' => $result['provisioned'],
            'webhook_id' => $result['webhook']->id,
        ]);
    }
}
