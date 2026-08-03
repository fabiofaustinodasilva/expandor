<?php

namespace App\Http\Controllers\Api\Mobile\V1;

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

        $status = $this->onboarding->status($company);
        $checklist = $status->checklist;

        return response()->json([
            'success' => true,
            'message' => 'Onboarding mobile.',
            'data' => [
                'status' => $status->status,
                'percent' => $status->percent,
                'is_completed' => $status->isCompleted,
                'checklist' => $checklist->toArray(),
                'next_steps' => collect($checklist->steps)
                    ->filter(fn ($step) => ! in_array($step->status, ['completed', 'skipped'], true))
                    ->map(fn ($step) => $step->toArray())
                    ->values()
                    ->all(),
                'alerts' => $status->alerts,
            ],
        ]);
    }
}
