<?php

namespace App\Domains\Communication\Models;

use App\Domains\Communication\Enums\WhatsAppConnectionStatus;
use App\Domains\Company\Models\Company;
use App\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConnection extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_connections';

    protected $fillable = [
        'company_id',
        'provider',
        'phone',
        'status',
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppConnectionStatus::class,
            'credentials' => 'encrypted:array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
