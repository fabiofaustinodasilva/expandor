<?php

namespace App\Http\Controllers\Web\Auth;

use App\Domains\Company\Models\User;
use App\Domains\Security\Services\SecurityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        $neutral = 'Se existir uma conta com esse e-mail, enviaremos as instruções de recuperação.';

        try {
            /** @var User|null $user */
            $user = User::query()
                ->withoutGlobalScopes()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($user !== null && $user->status === User::STATUS_ACTIVE) {
                $status = Password::broker()->sendResetLink(['email' => $user->email]);

                if ($status === Password::RESET_LINK_SENT) {
                    $this->security->recordAudit(
                        action: 'auth.password_reset_requested',
                        user: $user,
                        auditable: $user,
                        newValues: [
                            'channel' => 'email',
                        ],
                        request: $request,
                        companyId: $user->company_id,
                    );
                } else {
                    Log::warning('password_reset.link_not_sent', [
                        'status' => $status,
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Não enumera conta; não vaza SMTP/host/senha.
            Log::error('password_reset.request_failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return back()
            ->withInput(['email' => $email])
            ->with('status', $neutral);
    }
}
