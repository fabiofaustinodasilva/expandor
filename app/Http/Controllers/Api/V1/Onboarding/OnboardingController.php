<?php

namespace App\Http\Controllers\Api\V1\Onboarding;

use App\Domains\Onboarding\Requests\CompleteOnboardingStepRequest;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        protected OnboardingService $onboarding,
        protected TenantContext $tenant,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.view', $company);

        return response()->json([
            'success' => true,
            'message' => 'Status do onboarding.',
            'data' => $this->onboarding->status($company)->toArray(),
        ]);
    }

    public function completeStep(CompleteOnboardingStepRequest $request): JsonResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        $this->onboarding->completeStep(
            $request->validated('step'),
            $company,
            $request->user(),
            $request->validated('metadata') ?? [],
        );

        return response()->json([
            'success' => true,
            'message' => 'Etapa concluída.',
            'data' => $this->onboarding->status($company)->toArray(),
        ]);
    }

    public function demoData(Request $request): JsonResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.manage', $company);

        $result = $this->onboarding->generateDemo($company, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dados de demonstração gerados.',
            'data' => [
                'demo' => $result,
                'onboarding' => $this->onboarding->status($company)->toArray(),
            ],
        ]);
    }

    public function finish(Request $request): JsonResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);
        $this->authorize('onboarding.manage', $company);

        $this->onboarding->finish($company, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Onboarding finalizado.',
            'data' => $this->onboarding->status($company)->toArray(),
        ]);
    }
}
