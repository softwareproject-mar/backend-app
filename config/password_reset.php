<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Password Reset Token TTL (minutes)
    |--------------------------------------------------------------------------
    |
    | How long a password reset token remains valid.
    |
    */
    'ttl_minutes' => env('PASSWORD_RESET_TTL_MINUTES', 60),
];
