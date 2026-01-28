<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (App\Exceptions\ScreenshotException $e) {
            return response()->json([
                'error' => true,
                'code' => $e->errorCode->value,
                'message' => $e->getMessage(),
            ], $e->errorCode->httpStatus());
        });

        $exceptions->render(function (Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => true,
                'code' => App\Enums\ErrorCode::ValidationError->value,
                'message' => $e->getMessage(),
                'details' => $e->errors(),
            ], App\Enums\ErrorCode::ValidationError->httpStatus());
        });

        $exceptions->render(function (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => true,
                'code' => App\Enums\ErrorCode::NotFound->value,
                'message' => 'Not found',
            ], App\Enums\ErrorCode::NotFound->httpStatus());
        });
    })->create();
