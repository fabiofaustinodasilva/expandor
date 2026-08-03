<?php

namespace App\Http\Controllers\Api\V1\Branding;

use App\Domains\Branding\Services\BrandingService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    public function __construct(
        protected BrandingService $branding,
        protected TenantContext $tenant,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if($user?->isPlatformAdmin(), 403);

        $company = $this->tenant->company() ?? $user?->company;

        abort_if($company === null || $company->isSystem(), 404);

        $payload = $this->branding->forCompany($company);

        return response()->json([
            'success' => true,
            'message' => 'Branding da empresa.',
            'data' => $payload->toArray(),
        ]);
    }
}
