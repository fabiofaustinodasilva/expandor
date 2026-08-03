<?php

namespace App\Http\Controllers\Web\Training;

use App\Domains\Training\Actions\ToggleTrainingCategoryAction;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Repositories\TrainingRepository;
use App\Domains\Training\Requests\StoreTrainingCategoryRequest;
use App\Domains\Training\Requests\UpdateTrainingCategoryRequest;
use App\Domains\Training\Services\TrainingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingCategoryController extends Controller
{
    public function __construct(
        protected TrainingService $training,
        protected TrainingRepository $repository
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', TrainingCategory::class);

        return view('training.categories.index', [
            'categories' => $this->repository->paginateCategories(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TrainingCategory::class);

        return view('training.categories.create');
    }

    public function store(StoreTrainingCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingCategory::class);

        $this->training->createCategory($request->validated());

        return redirect()
            ->route('training.categories.index')
            ->with('success', 'Categoria criada com sucesso.');
    }

    public function edit(TrainingCategory $category): View
    {
        $this->authorize('update', $category);

        return view('training.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateTrainingCategoryRequest $request, TrainingCategory $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $this->training->updateCategory($category, $request->validated());

        return redirect()
            ->route('training.categories.index')
            ->with('success', 'Categoria atualizada com sucesso.');
    }

    public function toggleStatus(
        TrainingCategory $category,
        ToggleTrainingCategoryAction $action
    ): RedirectResponse {
        $this->authorize('update', $category);

        $action->execute($category);

        return redirect()
            ->route('training.categories.index')
            ->with('success', 'Status da categoria atualizado.');
    }
}
