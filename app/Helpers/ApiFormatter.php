<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiFormatter
{
    public static function success(mixed $data = null, string $message = 'Success.', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message = 'Error.', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    public static function validationError(array $errors, string $message = 'Validation failed.'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    public static function filterSensitiveData(array $data): array
    {
        return self::filterValue($data);
    }

    private static function filterValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && self::isSensitiveKey($key)) {
            return '[FILTERED]';
        }

        if (! is_array($value)) {
            return $value;
        }

        $filtered = [];

        foreach ($value as $childKey => $childValue) {
            $filtered[$childKey] = self::filterValue(
                $childValue,
                is_string($childKey) ? $childKey : null,
            );
        }

        return $filtered;
    }

    private static function isSensitiveKey(string $key): bool
    {
        return in_array(strtolower($key), [
            'authorization',
            'cookie',
            'current_password',
            'new_password',
            'password',
            'password_confirmation',
            'remember_token',
            'set-cookie',
            'token',
            'access_token',
            'refresh_token',
            'x-csrf-token',
            'x-xsrf-token',
        ], true);
    }
}
