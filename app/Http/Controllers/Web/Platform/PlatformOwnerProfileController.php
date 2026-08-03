<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Platform\Requests\UpdatePlatformOwnerProfileRequest;
use App\Domains\Platform\Services\PlatformOwnerProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformOwnerProfileController extends Controller
{
    public function __construct(
        protected PlatformOwnerProfileService $profiles,
    ) {}

    public function edit(): View
    {
        $this->authorize('platform.access');

        return view('platform.profile.edit', [
            'user' => request()->user(),
        ]);
    }

    public function update(UpdatePlatformOwnerProfileRequest $request): RedirectResponse
    {
        $this->authorize('platform.access');

        $this->profiles->update(
            $request->user(),
            $request->validated(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        $message = 'Perfil atualizado.';
        if (! empty($request->validated('password'))) {
            $message = 'Perfil e senha atualizados.';
        }

        return redirect()
            ->route('platform.profile.edit')
            ->with('success', $message);
    }
}
