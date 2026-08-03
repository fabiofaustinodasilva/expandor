<?php

namespace App\Domains\Training\Models;

use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\TrainingCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCategory extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): TrainingCategoryFactory
    {
        return TrainingCategoryFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(TrainingContent::class, 'category_id');
    }
}
