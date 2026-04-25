<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ScreenshotParams;

class CaptureScreenshotRequest extends ScreenshotRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace($this->normalizeScreenshotBooleans($this->all()));
    }

    public function rules(): array
    {
        return $this->screenshotRules(includeResponse: true);
    }

    public function toParams(): ScreenshotParams
    {
        return ScreenshotParams::fromRequest($this->validated());
    }
}
