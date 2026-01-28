<?php

declare(strict_types=1);

namespace App\Enums;

enum ErrorCode: string
{
    case ValidationError = 'VALIDATION_ERROR';
    case InvalidUrl = 'INVALID_URL';
    case Unauthorized = 'UNAUTHORIZED';
    case RateLimited = 'RATE_LIMITED';
    case RenderTimeout = 'RENDER_TIMEOUT';
    case RenderFailed = 'RENDER_FAILED';
    case SelectorNotFound = 'SELECTOR_NOT_FOUND';
    case NotFound = 'NOT_FOUND';
    case InternalError = 'INTERNAL_ERROR';

    public function httpStatus(): int
    {
        return match ($this) {
            self::ValidationError, self::InvalidUrl => 400,
            self::Unauthorized => 401,
            self::NotFound => 404,
            self::RateLimited => 429,
            self::RenderTimeout => 504,
            default => 500,
        };
    }
}
