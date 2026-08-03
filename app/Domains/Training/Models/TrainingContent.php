<?php

namespace App\Domains\Training\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Training\Enums\TrainingContentType;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\TrainingContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingContent extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'category_id',
        'title',
        'description',
        'type',
        'content',
        'url',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TrainingContentType::class,
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): TrainingContentFactory
    {
        return TrainingContentFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'training_content_id');
    }
}
