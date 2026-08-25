<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Self-serve trial (Sprint 5.5)
    |--------------------------------------------------------------------------
    */
    'trial_days' => (int) env('ACQUISITION_TRIAL_DAYS', 2),

    'plan_slug' => env('ACQUISITION_TRIAL_PLAN', 'start'),

    'signup' => [
        'per_minute' => (int) env('ACQUISITION_SIGNUP_PER_MINUTE', 3),
        'per_hour' => (int) env('ACQUISITION_SIGNUP_PER_HOUR', 5),
    ],
];
