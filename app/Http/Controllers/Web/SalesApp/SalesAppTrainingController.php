<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\Training\Actions\CompleteTrainingProgressAction;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Repositories\TrainingRepository;
use App\Domains\Training\Services\TrainingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppTrainingController extends Controller
{
    public function __construct(
        protected TrainingRepository $repository,
        protected TrainingService $training
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeSalesApp($request);
        $this->authorize('viewAny', TrainingContent::class);

        /** @var \App\Domains\Company\Models\User $user */
        $user = $request->user();

        return view('sales-app.training.index', [
            'categories' => $this->repository->activeCategoriesWithContents(),
            'progress' => $this->repository->progressMapForUser($user),
        ]);
    }

    public function show(Request $request, TrainingContent $content): View
    {
        $this->authorizeSalesApp($request);
        $this->authorize('view', $content);

        /** @var \App\Domains\Company\Models\User $user */
        $user = $request->user();

        $content = $this->repository->findActiveContent($content->id);
        $this->training->startProgress($user, $content);

        return view('sales-app.training.show', [
            'content' => $content,
            'progress' => $this->repository->progressForUser($user, $content->id),
        ]);
    }

    public function complete(
        Request $request,
        TrainingContent $content,
        CompleteTrainingProgressAction $action
    ): RedirectResponse {
        $this->authorizeSalesApp($request);
        $this->authorize('complete', $content);

        /** @var \App\Domains\Company\Models\User $user */
        $user = $request->user();
        $content = $this->repository->findActiveContent($content->id);
        $action->execute($user, $content);

        return redirect()
            ->route('sales-app.training.show', $content)
            ->with('success', 'Conteúdo marcado como concluído.');
    }

    protected function authorizeSalesApp(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('sales_app.access') ?? false,
            403,
            'Access denied.'
        );
    }
}
