<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'role' => 'user',
        ]);

        return ApiFormatter::success($user, 'User registered successfully.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiFormatter::validationError($validator->errors()->toArray());
        }

        $user = User::query()->where('email', $request->string('email'))->first();

        if (! $user) {
            return ApiFormatter::error('User not found.', 404);
        }

        if (! Hash::check($request->string('password'), $user->password)) {
            return ApiFormatter::error('Invalid password.', 401);
        }

        return $this->tokenResponse(JWTAuth::fromUser($user), $user, 'Login successful.');
    }

    public function me(): JsonResponse
    {
        return ApiFormatter::success(JWTAuth::parseToken()->authenticate(), 'Authenticated user retrieved.');
    }

    public function refresh(): JsonResponse
    {
        $token = JWTAuth::parseToken()->refresh();
        $user = JWTAuth::setToken($token)->authenticate();

        return $this->tokenResponse($token, $user, 'Token refreshed.');
    }

    public function logout(): JsonResponse
    {
        JWTAuth::parseToken()->invalidate();

        return ApiFormatter::success(null, 'Logout successful.');
    }

    private function tokenResponse(string $token, User $user, string $message): JsonResponse
    {
        return ApiFormatter::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'user' => $user,
        ], $message);
    }
}
