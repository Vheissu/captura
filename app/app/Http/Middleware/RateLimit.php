<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = (int) config('screenshot.security.rate_limit', 0);
        if ($limit <= 0) {
            return $next($request);
        }

        $key = 'screenshot-rate:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'error' => true,
                'code' => ErrorCode::RateLimited->value,
                'message' => 'Too many requests',
            ], ErrorCode::RateLimited->httpStatus());
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
