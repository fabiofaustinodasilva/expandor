<?php

namespace App\Http\Controllers\Web\Security;

use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Security\Actions\RecordConsentAction;
use App\Domains\Security\Actions\RequestAnonymizationAction;
use App\Domains\Security\Actions\RequestDataExportAction;
use App\Domains\Security\Models\AnonymizationRequest;
use App\Domains\Security\Requests\StoreAnonymizationRequest;
use App\Domains\Security\Requests\StoreConsentRequest;
use App\Domains\Security\Services\PrivacyService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function __construct(
        protected PrivacyService $privacy,
        protected RequestDataExportAction $exportAction,
        protected RequestAnonymizationAction $anonymizationAction,
        protected RecordConsentAction $consentAction,
        protected TenantContext $tenant,
    ) {}

    public function index(): View
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.view', $company);

        return view('security.privacy.index', [
            'company' => $company,
            'exports' => $this->privacy->paginateExports($company, 10),
            'anonymizations' => $this->privacy->paginateAnonymizations($company, 10),
            'consents' => $this->privacy->paginateConsents($company, 10),
            'residents' => Resident::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email', 'phone']),
        ]);
    }

    public function export(Request $request): RedirectResponse
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.manage', $company);

        $this->exportAction->execute($company, $request->user());

        return redirect()
            ->route('company.privacy.index')
            ->with('success', 'Exportação de dados gerada com sucesso.');
    }

    public function downloadExport(int $export): StreamedResponse|RedirectResponse
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.view', $company);

        $record = \App\Domains\Security\Models\DataExportRequest::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('id', $export)
            ->firstOrFail();

        if ($record->file_path === null || ! Storage::disk((string) config('security.export.disk'))->exists($record->file_path)) {
            return redirect()
                ->route('company.privacy.index')
                ->withErrors(['export' => 'Arquivo de exportação indisponível.']);
        }

        return Storage::disk((string) config('security.export.disk'))
            ->download($record->file_path, "export-empresa-{$company->id}.json");
    }

    public function storeAnonymization(StoreAnonymizationRequest $request): RedirectResponse
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.manage', $company);

        $resident = Resident::query()->findOrFail($request->validated('resident_id'));

        $this->anonymizationAction->execute(
            $company,
            $request->user(),
            $resident,
            $request->validated('reason'),
        );

        return redirect()
            ->route('company.privacy.index')
            ->with('success', 'Solicitação de anonimização registrada.');
    }

    public function processAnonymization(AnonymizationRequest $anonymization): RedirectResponse
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.manage', $company);

        abort_unless((int) $anonymization->company_id === (int) $company->id, 404);

        $this->privacy->processAnonymization($anonymization, request()->user());

        return redirect()
            ->route('company.privacy.index')
            ->with('success', 'Anonimização concluída.');
    }

    public function storeConsent(StoreConsentRequest $request): RedirectResponse
    {
        $company = $this->tenant->company();
        abort_if($company === null, 404);

        $this->authorize('privacy.manage', $company);

        $resident = Resident::query()->findOrFail($request->validated('resident_id'));

        $this->consentAction->execute(
            company: $company,
            subject: $resident,
            type: $request->validated('consent_type'),
            granted: (bool) $request->validated('granted'),
            source: $request->validated('source') ?? 'web',
            actor: $request->user(),
        );

        return redirect()
            ->route('company.privacy.index')
            ->with('success', 'Consentimento registrado.');
    }
}
