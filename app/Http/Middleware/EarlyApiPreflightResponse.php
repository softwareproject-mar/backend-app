<?php

namespace App\Http\Middleware;

use Closure;
use Fruitcake\Cors\CorsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjawab OPTIONS (CORS preflight) untuk jalur API sebelum HandleCors::hasMatchingPath
 * gagal (sering di deploy subpath obormas/api/...). Kalau Fruitcake error → fallback 204
 * supaya browser tidak melihat preflight 500 + "CORS error" / Failed to fetch.
 */
class EarlyApiPreflightResponse
{
    public function __construct(
        private CorsService $cors
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() !== 'OPTIONS') {
            return $next($request);
        }

        if (! preg_match('#(^|/)api(/|$)#', $request->path())) {
            return $next($request);
        }

        try {
            $this->cors->setOptions(config('cors', []));

            if ($this->cors->isPreflightRequest($request)) {
                $response = $this->cors->handlePreflightRequest($request);
                $this->cors->varyHeader($response, 'Access-Control-Request-Method');

                // Fruitcake bisa mengembalikan 4xx/5xx pada konfigurasi anomali — jangan biarkan preflight 500.
                if ($response->getStatusCode() >= 400) {
                    return $this->fallbackPreflightNoContent($request);
                }

                return $response;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->fallbackPreflightNoContent($request);
    }

    private function fallbackPreflightNoContent(Request $request): Response
    {
        $origin = $request->headers->get('Origin');
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => $request->headers->get('Access-Control-Request-Headers')
                ?: 'Authorization, Content-Type, Accept, X-Requested-With, X-CSRF-TOKEN',
            'Access-Control-Max-Age' => (string) config('cors.max_age', 86400),
        ];

        if ($this->originAllowed($origin)) {
            $headers['Access-Control-Allow-Origin'] = $origin ?? '';
            if (config('cors.supports_credentials')) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }
        } else {
            $headers['Access-Control-Allow-Origin'] = '*';
        }

        return response('', 204, $headers);
    }

    private function originAllowed(?string $origin): bool
    {
        if ($origin === null || $origin === '') {
            return false;
        }

        foreach (config('cors.allowed_origins', []) as $allowed) {
            if ($allowed === $origin || $allowed === '*') {
                return true;
            }
        }

        foreach (config('cors.allowed_origins_patterns', []) as $pattern) {
            if ($pattern !== '' && @preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }
}
