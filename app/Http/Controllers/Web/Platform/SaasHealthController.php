<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Services\ActivationIntelligenceService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SaasHealthController extends Controller
{
    public function __construct(
        protected ActivationIntelligenceService $activation,
    ) {}

    public function __invoke(): View
    {
        $this->authorize('platform.access');

        return view('platform.activation.index', [
            'health' => $this->activation->healthDashboard(),
        ]);
    }
}
