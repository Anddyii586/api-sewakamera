<?php

use App\Helpers\ApiFormatter;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\LogAPI;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => JwtMiddleware::class,
            'log.api' => LogAPI::class,
        ]);

        $middleware->api(prepend: [
            LogAPI::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiFormatter::validationError($e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiFormatter::error('Unauthenticated.', 401);
            }

            if ($e instanceof AuthorizationException) {
                return ApiFormatter::error('Forbidden.', 403);
            }

            if ($e instanceof ModelNotFoundException || $e->getPrevious() instanceof ModelNotFoundException) {
                return ApiFormatter::error('Resource not found.', 404);
            }

            if ($e instanceof NotFoundHttpException) {
                return ApiFormatter::error('Route not found.', 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return ApiFormatter::error('Method not allowed.', 405);
            }

            return null;
        });
    })->create();
