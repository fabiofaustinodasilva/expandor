<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Requests\ToggleFeatureFlagRequest;
use App\Domains\Platform\Services\FeatureFlagService;
use App\Domains\Platform\Services\PlatformCompanyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeatureFlagController extends Controller
{
    public function __construct(
        protected FeatureFlagService $flags,
        protected PlatformCompanyService $platform,
    ) {}

    public function index(): View
    {
        $this->authorize('platform.manageFeatureFlags');

        return view('platform.flags.index', [
            'flags' => $this->flags->catalog(),
        ]);
    }

    public function update(ToggleFeatureFlagRequest $request, int $company): RedirectResponse
    {
        $model = $this->platform->findClient($company);

        $this->flags->toggle(
            $model,
            $request->validated('key'),
            $request->boolean('enabled'),
            $request->user(),
        );

        return back()->with('success', 'Feature flag atualizada.');
    }
}
