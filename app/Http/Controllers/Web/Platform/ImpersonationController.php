<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Company\Models\User;
use App\Domains\Platform\Requests\StartImpersonationRequest;
use App\Domains\Platform\Services\ImpersonationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        protected ImpersonationService $impersonation,
    ) {}

    public function store(StartImpersonationRequest $request): RedirectResponse
    {
        $target = User::query()->withoutGlobalScopes()->findOrFail($request->validated('user_id'));

        $this->impersonation->start(
            $request->user(),
            $target,
            $request->validated('reason'),
            $request,
        );

        return redirect()
            ->route('dashboard')
            ->with('success', 'Impersonação iniciada com segurança.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->impersonation->end($request);

        return redirect()
            ->route('platform.dashboard')
            ->with('success', 'Impersonação encerrada.');
    }
}
