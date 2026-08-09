<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoMunicipality extends Model
{
    protected $fillable = [
        'geo_state_id',
        'ibge_code',
        'name',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(GeoState::class, 'geo_state_id');
    }
}
