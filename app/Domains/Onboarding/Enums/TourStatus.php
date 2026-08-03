<?php

namespace App\Domains\Onboarding\Enums;

enum TourStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
}
