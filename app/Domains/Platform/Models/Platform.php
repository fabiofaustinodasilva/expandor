<?php

namespace App\Domains\Platform\Models;

/**
 * Marker namespace for the Platform domain.
 * Client companies, plans and users reuse Company/Plan/User models;
 * platform access is gated by users.is_platform_admin + platform.* permissions.
 */
final class Platform
{
    public const SYSTEM_COMPANY_EMAIL = 'platform@geosales.local';

    public const OWNER_EMAIL = 'owner@geosales.local';
}
