<?php

namespace App\Domains\Company\Support\FieldOps;

/**
 * Chaves em company_settings (e, no futuro, overrides por campanha).
 */
final class FieldOpsKeys
{
    public const POINTS_VISIBILITY = 'field.points_visibility';

    public const POINTS_DISPLAY = 'field.points_display';

    public const POINTS_EDIT_OTHERS = 'field.points_edit_others';

    public const POINTS_DELETE = 'field.points_delete';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::POINTS_VISIBILITY,
            self::POINTS_DISPLAY,
            self::POINTS_EDIT_OTHERS,
            self::POINTS_DELETE,
        ];
    }
}
