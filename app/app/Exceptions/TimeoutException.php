<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;

class TimeoutException extends ScreenshotException
{
    public function __construct(string $message = 'Screenshot timed out')
    {
        parent::__construct(ErrorCode::RenderTimeout, $message);
    }
}
