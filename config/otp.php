<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry Duration
    |--------------------------------------------------------------------------
    |
    | This value determines how long (in minutes) an OTP code remains valid
    | before it expires. Default is 5 minutes.
    |
    */

    'expiry_minutes' => env('OTP_EXPIRY_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Maximum OTP Verification Attempts
    |--------------------------------------------------------------------------
    |
    | This value determines how many times a user can attempt to verify
    | an OTP code before it is invalidated. Default is 5 attempts.
    |
    */

    'max_attempts' => env('OTP_MAX_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | OTP Request Rate Limit
    |--------------------------------------------------------------------------
    |
    | This value determines how many OTP requests can be made within
    | a 10-minute window for a single email. Default is 3 requests.
    |
    */

    'rate_limit' => env('OTP_RATE_LIMIT', 10),

];
