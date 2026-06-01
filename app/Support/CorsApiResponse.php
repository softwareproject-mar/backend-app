<?php

namespace App\Support;

use Fruitcake\Cors\CorsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Respons JSON dari exception handler tidak melewati HandleCors::addActualRequestHeaders,
 * sehingga error lintas origin tidak berisi Access-Control-Allow-Origin → browser mem-blokir
 * dan JavaScript melihat "Failed to fetch" (termasuk POST /api/auth/login).
 */
final class CorsApiResponse
{
    public static function wrap(Response $response, Request $request): Response
    {
        try {
            /** @var CorsService $cors */
            $cors = app(CorsService::class);
            $cors->setOptions(config('cors', []));

            return $cors->addActualRequestHeaders($response, $request);
        } catch (\Throwable) {
            // Jangan biarkan header CORS memicu exception baru di tengah exception handling.
            return $response;
        }
    }
}
