<?php

namespace App\Domains\Training\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\TrainingProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgress extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'training_progress';

    protected $fillable = [
        'company_id',
        'user_id',
        'training_content_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TrainingProgressFactory
    {
        return TrainingProgressFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(TrainingContent::class, 'training_content_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
