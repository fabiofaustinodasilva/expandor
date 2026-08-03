<?php

namespace App\Domains\Training\Actions;

use App\Domains\Company\Models\User;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use App\Domains\Training\Services\TrainingService;

class CompleteTrainingProgressAction
{
    public function __construct(
        protected TrainingService $training
    ) {}

    public function execute(User $user, TrainingContent $content): TrainingProgress
    {
        return $this->training->completeProgress($user, $content);
    }
}
