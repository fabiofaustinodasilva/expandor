<?php

namespace App\Http\Controllers\Web\AI;

use App\Domains\AI\Enums\AIContextType;
use App\Domains\AI\Models\AIConversation;
use App\Domains\AI\Repositories\AIConversationRepository;
use App\Domains\AI\Requests\StoreAIConversationRequest;
use App\Domains\AI\Services\AIService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AIConversationController extends Controller
{
    public function __construct(
        protected AIService $ai,
        protected AIConversationRepository $repository,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', AIConversation::class);

        return view('ai.conversations.index', [
            'conversations' => $this->repository->paginate(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AIConversation::class);

        return view('ai.conversations.create', [
            'contextTypes' => AIContextType::cases(),
        ]);
    }

    public function store(StoreAIConversationRequest $request): RedirectResponse
    {
        $this->authorize('create', AIConversation::class);

        $conversation = $this->ai->ask(
            question: $request->validated('question'),
            contextType: $request->validated('context_type'),
            user: $request->user(),
        );

        return redirect()
            ->route('ai.conversations.show', $conversation)
            ->with('success', 'Sugestão gerada. A IA não altera dados automaticamente.');
    }

    public function show(AIConversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('ai.conversations.show', [
            'conversation' => $conversation->loadMissing('user:id,name'),
        ]);
    }
}
