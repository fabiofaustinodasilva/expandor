<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Sales\SaleFields\SaleFieldKeys;
use App\Domains\Sales\SaleFields\SaleFieldsPolicyResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleSettingsController extends Controller
{
    public function __construct(
        protected SaleFieldsPolicyResolver $saleFields,
    ) {}

    public function edit(): View
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('company.manage')
                || $user?->hasPermission('users.manage')
                || $user?->hasPermission('commissions.manage'),
            403,
            'Access denied.'
        );

        $company = $user->company;
        abort_unless($company !== null, 404);

        $policy = $this->saleFields->resolve((int) $company->id);

        return view('operations.sale-settings', [
            'company' => $company,
            'policy' => $policy,
            'fieldLabels' => SaleFieldKeys::labels(),
            'fieldKeys' => SaleFieldKeys::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('company.manage')
                || $user?->hasPermission('users.manage')
                || $user?->hasPermission('commissions.manage'),
            403,
            'Access denied.'
        );

        $company = $user->company;
        abort_unless($company !== null, 404);

        $data = $request->validate([
            'required_fields' => ['nullable', 'array'],
            'required_fields.*' => ['string', Rule::in(SaleFieldKeys::all())],
        ]);

        $this->saleFields->saveForCompany($company, $data['required_fields'] ?? []);

        return redirect()
            ->route('operations.settings.sale')
            ->with('success', 'Campos obrigatórios da venda atualizados.');
    }
}
