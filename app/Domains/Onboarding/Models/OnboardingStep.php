<?php

namespace App\Domains\Onboarding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'description',
        'sort_order',
        'wizard_key',
        'training_keywords',
        'is_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'training_keywords' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory()
    {
        return \Database\Factories\OnboardingStepFactory::new();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(OnboardingProgress::class);
    }
}
