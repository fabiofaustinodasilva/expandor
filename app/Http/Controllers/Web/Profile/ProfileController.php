<?php

namespace App\Http\Controllers\Web\Profile;

use App\Domains\Company\Services\UserService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $users,
    ) {}

    public function edit(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $this->users->updateOwnProfile(
            $user,
            $request->validated(),
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()
            ->route('profile.edit')
            ->with('success', $this->successMessage($request));
    }

    protected function successMessage(UpdateProfileRequest $request): string
    {
        if ($request->hasFile('photo')) {
            return 'Upload concluído. Foto de perfil atualizada.';
        }

        if ($request->boolean('remove_photo')) {
            return 'Remoção concluída. Foto de perfil removida.';
        }

        return 'Perfil atualizado com sucesso.';
    }
}
