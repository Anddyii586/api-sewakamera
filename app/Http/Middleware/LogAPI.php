<?php

namespace App\Http\Middleware;

use App\Helpers\ApiFormatter;
use App\Models\LogModel;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class LogAPI
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $response = $this->exceptionResponse($e, $request);
        }

        $this->writeLog($request, $response);

        return $response;
    }

    private function writeLog(Request $request, Response $response): void
    {
        try {
            LogModel::create([
                'user_id' => $this->resolveUserId($request),
                'log_method' => $request->method(),
                'log_url' => $request->fullUrl(),
                'log_ip' => $request->ip(),
                'log_request' => $this->encode($this->requestPayload($request)),
                'log_response' => $this->encode($this->responsePayload($response)),
            ]);
        } catch (Throwable) {
            //
        }
    }

    private function requestPayload(Request $request): array
    {
        return ApiFormatter::filterSensitiveData([
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);
    }

    private function responsePayload(Response $response): array
    {
        if ($response instanceof JsonResponse) {
            return ApiFormatter::filterSensitiveData($response->getData(true) ?: []);
        }

        $decoded = json_decode($response->getContent(), true);

        if (is_array($decoded)) {
            return ApiFormatter::filterSensitiveData($decoded);
        }

        return [
            'status_code' => $response->getStatusCode(),
            'body' => $response->getContent(),
        ];
    }

    private function resolveUserId(Request $request): ?int
    {
        if (! $request->bearerToken()) {
            return null;
        }

        try {
            return JWTAuth::setToken($request->bearerToken())->authenticate()?->getKey();
        } catch (Throwable) {
            return null;
        }
    }

    private function exceptionResponse(Throwable $e, Request $request): JsonResponse
    {
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

        return ApiFormatter::error('Server error.', 500);
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }
}
