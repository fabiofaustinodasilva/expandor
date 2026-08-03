<?php

namespace App\Domains\AI\Models;

use App\Domains\AI\Enums\AIContextType;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\AIConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIConversation extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'ai_conversations';

    protected $fillable = [
        'company_id',
        'user_id',
        'context_type',
        'question',
        'answer',
        'provider',
        'tokens_used',
    ];

    protected function casts(): array
    {
        return [
            'context_type' => AIContextType::class,
            'tokens_used' => 'integer',
        ];
    }

    protected static function newFactory(): AIConversationFactory
    {
        return AIConversationFactory::new();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
