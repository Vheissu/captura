<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptionalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('screenshot.security.api_key');
        if (!$apiKey) {
            return $next($request);
        }

        $provided = $request->bearerToken()
            ?? $request->header('X-API-Key')
            ?? $request->query('api_key');

        if (!$provided || $provided !== $apiKey) {
            return response()->json([
                'error' => true,
                'code' => ErrorCode::Unauthorized->value,
                'message' => 'Unauthorized',
            ], ErrorCode::Unauthorized->httpStatus());
        }

        return $next($request);
    }
}
