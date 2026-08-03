<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Company\Support\FieldOps\FieldOpsPolicyResolver;
use App\Domains\Company\Support\FieldOps\PointDeletePolicy;
use App\Domains\Company\Support\FieldOps\PointEditOthersPolicy;
use App\Domains\Company\Support\FieldOps\PointsDisplay;
use App\Domains\Company\Support\FieldOps\PointsVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FieldOperationsSettingsController extends Controller
{
    public function __construct(
        protected FieldOpsPolicyResolver $fieldOps,
    ) {}

    public function edit(): View
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('company.manage')
                || $user?->hasPermission('users.manage'),
            403,
            'Access denied.'
        );

        $company = $user->company;
        abort_unless($company !== null, 404);

        // campaignId reservado para overrides futuros por campanha
        $policy = $this->fieldOps->resolve((int) $company->id, null);

        return view('operations.field-operations', [
            'company' => $company,
            'policy' => $policy,
            'visibilityOptions' => PointsVisibility::casesOrdered(),
            'displayOptions' => PointsDisplay::casesOrdered(),
            'editOthersOptions' => PointEditOthersPolicy::casesOrdered(),
            'deleteOptions' => PointDeletePolicy::casesOrdered(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('company.manage')
                || $user?->hasPermission('users.manage'),
            403,
            'Access denied.'
        );

        $company = $user->company;
        abort_unless($company !== null, 404);

        $data = $request->validate([
            'points_visibility' => ['required', Rule::enum(PointsVisibility::class)],
            'points_display' => ['required', Rule::enum(PointsDisplay::class)],
            'points_edit_others' => ['required', Rule::enum(PointEditOthersPolicy::class)],
            'points_delete' => ['required', Rule::enum(PointDeletePolicy::class)],
        ]);

        $this->fieldOps->saveForCompany($company, [
            'points_visibility' => $data['points_visibility'] instanceof \BackedEnum
                ? $data['points_visibility']->value
                : (string) $data['points_visibility'],
            'points_display' => $data['points_display'] instanceof \BackedEnum
                ? $data['points_display']->value
                : (string) $data['points_display'],
            'points_edit_others' => $data['points_edit_others'] instanceof \BackedEnum
                ? $data['points_edit_others']->value
                : (string) $data['points_edit_others'],
            'points_delete' => $data['points_delete'] instanceof \BackedEnum
                ? $data['points_delete']->value
                : (string) $data['points_delete'],
        ], $user);

        return redirect()
            ->route('operations.settings.field')
            ->with('success', 'Operação de campo atualizada.');
    }
}
