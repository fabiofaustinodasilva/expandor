<?php

namespace App\Http\Controllers\Web\CRM;

use App\Domains\CRM\Actions\ConvertLeadAction;
use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Repositories\LeadRepository;
use App\Domains\CRM\Requests\ConvertLeadRequest;
use App\Domains\CRM\Requests\StoreLeadRequest;
use App\Domains\CRM\Requests\UpdateLeadRequest;
use App\Domains\CRM\Services\LeadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leads,
        protected LeadRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        return view('crm.leads.index', [
            'leads' => $this->repository->paginate(
                status: $request->query('status'),
                assignedTo: $request->integer('assigned_to') ?: null,
            ),
            'statuses' => LeadStatus::options(),
            'filters' => [
                'status' => $request->query('status'),
                'assigned_to' => $request->query('assigned_to'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Lead::class);

        return view('crm.leads.create', $this->formData());
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $this->leads->create($request->validated(), $request->user());

        return redirect()
            ->route('crm.leads.index')
            ->with('success', 'Lead criado com sucesso.');
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('crm.leads.edit', array_merge($this->formData(), [
            'lead' => $lead,
        ]));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $this->leads->update($lead, $request->validated(), $request->user());

        return redirect()
            ->route('crm.leads.index')
            ->with('success', 'Lead atualizado.');
    }

    public function convert(ConvertLeadRequest $request, Lead $lead, ConvertLeadAction $action): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $lead = $action->execute($lead, $request->validated(), $request->user());

        return redirect()
            ->route('crm.opportunities.kanban')
            ->with('success', 'Lead convertido em oportunidade #'.$lead->converted_opportunity_id.'.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'statuses' => LeadStatus::options(),
            'sources' => LeadSource::options(),
            'sellers' => $this->repository->sellerOptions(),
            'campaigns' => $this->repository->campaignOptions(),
        ];
    }
}
