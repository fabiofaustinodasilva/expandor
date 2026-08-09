<?php

namespace App\Domains\Geo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoState extends Model
{
    protected $fillable = [
        'ibge_id',
        'uf',
        'name',
    ];

    public function municipalities(): HasMany
    {
        return $this->hasMany(GeoMunicipality::class);
    }
}
