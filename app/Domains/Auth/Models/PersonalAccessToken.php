<?php

namespace App\Domains\Auth\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'device_id',
        'device_name',
        'platform',
        'app_version',
        'session_version',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'session_version' => 'integer',
        ]);
    }

    public function isDeviceBound(): bool
    {
        return filled($this->device_id);
    }
}
