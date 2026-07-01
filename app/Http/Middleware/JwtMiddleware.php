<?php

namespace App\Http\Middleware;

use App\Helpers\ApiFormatter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return ApiFormatter::error('Authorization header is missing.', 401);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return ApiFormatter::error('Token is invalid.', 401);
            }

            $request->setUserResolver(fn () => $user);
        } catch (TokenExpiredException) {
            return ApiFormatter::error('Token has expired.', 401);
        } catch (TokenBlacklistedException) {
            return ApiFormatter::error('Token has been blacklisted.', 401);
        } catch (TokenInvalidException) {
            return ApiFormatter::error('Token is invalid.', 401);
        } catch (JWTException) {
            return ApiFormatter::error('Token is invalid.', 401);
        }

        return $next($request);
    }
}
