<?php

use App\Support\ApiRequestPath;
use App\Support\CorsApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // OPTIONS preflight dulu (hindari 500 di subpath / produksi); lalu CORS resmi + JSON.
        $middleware->api(prepend: [
            \App\Http\Middleware\EarlyApiPreflightResponse::class,
            HandleCors::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        // Role middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'super_admin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'member_approved' => \App\Http\Middleware\EnsureMemberApprovedForApi::class,
        ]);

        // Disable CSRF protection for API routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON for API routes
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return ApiRequestPath::matches($request);
        });

        // Handle validation exceptions
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (ApiRequestPath::matches($request)) {
                return CorsApiResponse::wrap(response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                ], 422), $request);
            }
        });

        // Handle authentication exceptions (salah email/password, token, dll.)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (ApiRequestPath::matches($request)) {
                $msg = $e->getMessage() ?: 'Unauthenticated.';

                return CorsApiResponse::wrap(response()->json([
                    'message' => $msg,
                    'errors' => [
                        'email' => [$msg],
                    ],
                ], 401), $request);
            }
        });

        // Handle not found exceptions
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (ApiRequestPath::matches($request)) {
                return CorsApiResponse::wrap(response()->json([
                    'message' => 'Resource not found.',
                ], 404), $request);
            }
        });

        // Handle general exceptions
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (ApiRequestPath::matches($request)) {
                $statusCode = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                return CorsApiResponse::wrap(response()->json([
                    'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
                    'error' => config('app.debug') ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ] : null,
                ], $statusCode >= 100 && $statusCode < 600 ? $statusCode : 500), $request);
            }
        });
    })->create();
