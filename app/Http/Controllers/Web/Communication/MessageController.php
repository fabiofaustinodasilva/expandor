<?php

namespace App\Http\Controllers\Web\Communication;

use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Repositories\MessageRepository;
use App\Domains\Communication\Requests\StoreMessageRequest;
use App\Domains\Communication\Services\MessageService;
use App\Domains\Sales\Residents\Models\Resident;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messages,
        protected MessageRepository $repository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Message::class);

        $residentId = $request->integer('resident_id') ?: null;

        return view('communication.messages.index', [
            'messages' => $this->repository->paginate($residentId),
            'residents' => Resident::query()->orderBy('name')->limit(200)->get(['id', 'name', 'phone']),
            'selectedResidentId' => $residentId,
            'connection' => $this->repository->connectionForCompany(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Message::class);

        return view('communication.messages.create', [
            'residents' => Resident::query()->orderBy('name')->limit(200)->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(StoreMessageRequest $request): RedirectResponse
    {
        $this->authorize('create', Message::class);

        $data = $request->validated();

        if (! empty($data['queue_send'])) {
            $this->messages->queueOutbound($data, $request->user());
        } else {
            $this->messages->register($data, $request->user());
        }

        return redirect()
            ->route('communication.messages.index')
            ->with('success', 'Mensagem registrada com sucesso.');
    }

    public function residentHistory(Resident $resident): View
    {
        $this->authorize('viewAny', Message::class);

        return view('communication.messages.history', [
            'resident' => $resident,
            'messages' => $this->repository->historyForResident($resident),
        ]);
    }
}
