<?php

namespace App\Http\Controllers\Web\Branding;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Requests\StoreBrandRequest;
use App\Domains\Branding\Requests\UpdateBrandRequest;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Branding\Services\ThemeService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function __construct(
        protected BrandingService $branding,
        protected ThemeService $themes,
        protected TenantContext $tenant,
    ) {}

    public function edit(Request $request): View
    {
        $company = $this->tenant->company() ?? $request->user()?->company;

        abort_if($company === null, 404);

        $this->authorize('create', Brand::class);

        $brand = $this->branding->modelForCompany($company);
        $payload = $this->branding->forCompany($company);

        return view('branding.edit', [
            'company' => $company,
            'brand' => $brand,
            'payload' => $payload,
            'cssVariables' => $this->themes->cssVariables($payload),
            'fontFamily' => $this->themes->fontFamily($payload),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;

        abort_if($company === null, 404);

        $this->branding->store($company, $request->validated(), [
            'logo' => $request->file('logo'),
            'logo_mark' => $request->file('logo_mark'),
            'favicon' => $request->file('favicon'),
            'login_image' => $request->file('login_image'),
        ], [
            'logo' => $request->boolean('remove_logo'),
            'logo_mark' => $request->boolean('remove_logo_mark'),
            'favicon' => $request->boolean('remove_favicon'),
            'login_image' => $request->boolean('remove_login_image'),
        ]);

        return redirect()
            ->route('company.branding.edit')
            ->with('success', $this->successMessage($request, created: true));
    }

    public function update(UpdateBrandRequest $request): RedirectResponse
    {
        $company = $this->tenant->company() ?? $request->user()?->company;

        abort_if($company === null, 404);

        $this->branding->update($company, $request->validated(), [
            'logo' => $request->file('logo'),
            'logo_mark' => $request->file('logo_mark'),
            'favicon' => $request->file('favicon'),
            'login_image' => $request->file('login_image'),
        ], [
            'logo' => $request->boolean('remove_logo'),
            'logo_mark' => $request->boolean('remove_logo_mark'),
            'favicon' => $request->boolean('remove_favicon'),
            'login_image' => $request->boolean('remove_login_image'),
        ]);

        return redirect()
            ->route('company.branding.edit')
            ->with('success', $this->successMessage($request, created: false));
    }

    protected function successMessage(Request $request, bool $created): string
    {
        $uploaded = $request->hasFile('logo')
            || $request->hasFile('logo_mark')
            || $request->hasFile('favicon')
            || $request->hasFile('login_image');

        $removed = $request->boolean('remove_logo')
            || $request->boolean('remove_logo_mark')
            || $request->boolean('remove_favicon')
            || $request->boolean('remove_login_image');

        if ($uploaded) {
            return 'Upload concluído. Identidade visual '.($created ? 'criada' : 'atualizada').' com sucesso.';
        }

        if ($removed) {
            return 'Remoção concluída. A imagem foi removida da identidade visual.';
        }

        return $created
            ? 'Identidade visual criada com sucesso.'
            : 'Identidade visual atualizada com sucesso.';
    }
}
