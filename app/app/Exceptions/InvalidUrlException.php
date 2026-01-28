<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;

class InvalidUrlException extends ScreenshotException
{
    public function __construct(string $message = 'Invalid URL')
    {
        parent::__construct(ErrorCode::InvalidUrl, $message);
    }
}
