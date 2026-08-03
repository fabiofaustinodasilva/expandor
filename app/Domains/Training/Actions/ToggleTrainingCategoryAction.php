<?php

namespace App\Domains\Training\Actions;

use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Services\TrainingService;

class ToggleTrainingCategoryAction
{
    public function __construct(
        protected TrainingService $training
    ) {}

    public function execute(TrainingCategory $category): TrainingCategory
    {
        return $this->training->toggleCategory($category);
    }
}
