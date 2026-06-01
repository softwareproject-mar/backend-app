<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        // Deploy subdirektori: $request->path() sering "obormas/api/...", bukan "api/..."
        'obormas/api/*',
        // Variasi prefix / proxy — cocokkan apa pun yang mengandung segmen api
        '*api*',
        'sanctum/csrf-cookie',
        'obormas/sanctum/csrf-cookie',
        /*
         * Fallback: HandleCors hanya jalan jika salah satu pattern di atas cocok dengan $request->is().
         * Beberapa setup Nginx / subdirectory mengirim path lain (mis. prefix tambahan), sehingga
         * preflight OPTIONS tidak tertangani → request jatuh ke router dan bisa berujung 500/405.
         * '*' memastikan preflight dari browser (localhost:5173 → API production) selalu dijawab 204 + header CORS.
         */
        '*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost',
        'https://localhost',
        'capacitor://localhost',
        'ionic://localhost',
        'http://localhost:8080',
        'https://localhost:8080',
        'null',
        'http://103.253.212.105',
        'https://103.253.212.105',
    ],

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
        '#^capacitor://#',
        '#^ionic://#',
        '#^https?://103\.253\.212\.105(:\d+)?$#',
        '#^http://10\.0\.2\.2(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
