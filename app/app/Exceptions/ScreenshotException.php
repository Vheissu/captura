<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;
use RuntimeException;

class ScreenshotException extends RuntimeException
{
    public function __construct(
        public readonly ErrorCode $errorCode,
        string $message = ''
    ) {
        parent::__construct($message);
    }
}
