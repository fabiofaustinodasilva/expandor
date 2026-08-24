<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Empresas permanentemente protegidas
    |--------------------------------------------------------------------------
    | Além de is_system=true e empresas que hospedam Platform Owner.
    */
    'protected_company_ids' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('PRODUCTION_RESET_PROTECTED_COMPANY_IDS', '1'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Confirmação obrigatória para --execute
    |--------------------------------------------------------------------------
    */
    'confirm_phrase' => 'RESET-PRODUCTION-DATA',

    /*
    |--------------------------------------------------------------------------
    | Frase interativa adicional em APP_ENV=production
    |--------------------------------------------------------------------------
    */
    'production_typed_phrase' => 'RESET EXPANDOR PRODUCTION DATA',

    /*
    |--------------------------------------------------------------------------
    | Backup
    |--------------------------------------------------------------------------
    */
    'backup_disk_path' => 'backups/production-reset',
    'require_backup_on_execute' => true,
];
