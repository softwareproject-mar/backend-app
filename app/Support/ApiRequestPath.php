<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Deteksi path API saat aplikasi di-deploy di subdirektori (mis. /obormas/api/...).
 * Laravel default $request->is('api/*') hanya cocok jika segmen pertama persis "api".
 */
final class ApiRequestPath
{
    public static function matches(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return str_starts_with($path, 'api/')
            || str_contains($path, '/api/');
    }
}
