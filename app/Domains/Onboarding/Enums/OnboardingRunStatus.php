<?php

namespace App\Domains\Onboarding\Enums;

enum OnboardingRunStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
