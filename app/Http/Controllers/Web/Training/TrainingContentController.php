<?php

namespace App\Http\Controllers\Web\Training;

use App\Domains\Training\Enums\TrainingContentType;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Repositories\TrainingRepository;
use App\Domains\Training\Requests\StoreTrainingContentRequest;
use App\Domains\Training\Requests\UpdateTrainingContentRequest;
use App\Domains\Training\Services\TrainingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingContentController extends Controller
{
    public function __construct(
        protected TrainingService $training,
        protected TrainingRepository $repository
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', TrainingContent::class);

        $categoryId = $request->integer('category_id') ?: null;

        return view('training.contents.index', [
            'contents' => $this->repository->paginateContents($categoryId),
            'categories' => $this->repository->categoryOptions(),
            'selectedCategoryId' => $categoryId,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TrainingContent::class);

        return view('training.contents.create', [
            'categories' => $this->repository->categoryOptions(),
            'types' => TrainingContentType::options(),
        ]);
    }

    public function store(StoreTrainingContentRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingContent::class);

        $this->training->createContent($request->validated());

        return redirect()
            ->route('training.contents.index')
            ->with('success', 'Conteúdo cadastrado com sucesso.');
    }

    public function edit(TrainingContent $content): View
    {
        $this->authorize('update', $content);

        return view('training.contents.edit', [
            'content' => $content,
            'categories' => $this->repository->categoryOptions(),
            'types' => TrainingContentType::options(),
        ]);
    }

    public function update(UpdateTrainingContentRequest $request, TrainingContent $content): RedirectResponse
    {
        $this->authorize('update', $content);

        $this->training->updateContent($content, $request->validated());

        return redirect()
            ->route('training.contents.index')
            ->with('success', 'Conteúdo atualizado com sucesso.');
    }
}
