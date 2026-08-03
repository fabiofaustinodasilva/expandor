<?php

namespace App\Http\Controllers\Web\Communication;

use App\Domains\Communication\Enums\MessageTemplateEvent;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Communication\Repositories\MessageRepository;
use App\Domains\Communication\Requests\StoreMessageTemplateRequest;
use App\Domains\Communication\Requests\UpdateMessageTemplateRequest;
use App\Domains\Communication\Services\MessageService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    public function __construct(
        protected MessageService $messages,
        protected MessageRepository $repository
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', MessageTemplate::class);

        return view('communication.templates.index', [
            'templates' => $this->repository->paginateTemplates(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', MessageTemplate::class);

        return view('communication.templates.create', [
            'events' => MessageTemplateEvent::options(),
        ]);
    }

    public function store(StoreMessageTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', MessageTemplate::class);

        $this->messages->createTemplate($request->validated());

        return redirect()
            ->route('communication.templates.index')
            ->with('success', 'Template criado com sucesso.');
    }

    public function edit(MessageTemplate $template): View
    {
        $this->authorize('update', $template);

        return view('communication.templates.edit', [
            'template' => $template,
            'events' => MessageTemplateEvent::options(),
        ]);
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $this->messages->updateTemplate($template, $request->validated());

        return redirect()
            ->route('communication.templates.index')
            ->with('success', 'Template atualizado com sucesso.');
    }
}
