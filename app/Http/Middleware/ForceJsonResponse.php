<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Preflight CORS: jangan paksa Accept: application/json (mengurangi risiko 500 di beberapa stack).
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // Unduhan biner (Excel/PDF): jangan timpa Accept — client mengirim */* atau mime spesifik.
        $path = $request->path();
        $isExportPath = str_contains($path, '/export/');

        // Android stabilization: izinkan auth_token di query string khusus endpoint export.
        // Ini dipakai hanya untuk membuka URL export via browser eksternal di native app.
        if ($isExportPath && ! $request->headers->has('Authorization')) {
            $queryToken = (string) $request->query('auth_token', '');
            if ($queryToken !== '') {
                $request->headers->set('Authorization', 'Bearer '.$queryToken);
            }
        }

        if (! $isExportPath) {
            $request->headers->set('Accept', 'application/json');
        }

        return $next($request);
    }
}
