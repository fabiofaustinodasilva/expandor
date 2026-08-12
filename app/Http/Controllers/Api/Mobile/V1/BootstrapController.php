<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Domains\Company\Models\User;
use App\Domains\Mobile\Services\MobileSellerOpsService;
use App\Domains\Mobile\Support\MobileAuthResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __construct(
        protected MobileSellerOpsService $ops,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return MobileAuthResponse::ok('Bootstrap do app.', $this->ops->bootstrap($user));
    }
}
