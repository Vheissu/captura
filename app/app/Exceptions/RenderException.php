<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ErrorCode;

class RenderException extends ScreenshotException
{
    public function __construct(string $message = 'Render failed')
    {
        parent::__construct(ErrorCode::RenderFailed, $message);
    }
}
