<?php

namespace App\Domains\Communication\Models;

use App\Domains\Communication\Enums\MessageTemplateEvent;
use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\MessageTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'event',
        'content',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'event' => MessageTemplateEvent::class,
            'active' => 'boolean',
        ];
    }

    protected static function newFactory(): MessageTemplateFactory
    {
        return MessageTemplateFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
