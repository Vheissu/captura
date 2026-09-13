<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreenshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'status' => $this->status?->value,
            'file_url' => $this->file_url,
            'format' => $this->file_type?->value,
            'width' => $this->width,
            'height' => $this->height,
            'file_size' => $this->file_size,
            'render_time_ms' => $this->render_time_ms,
            'cached' => $this->from_cache,
            'error_code' => $this->whenNotNull($this->error_code),
            'error_message' => $this->whenNotNull($this->error_message),
            'extracted_html' => $this->whenNotNull($this->extracted_html),
            'extracted_text' => $this->whenNotNull($this->extracted_text),
            'created_at' => optional($this->created_at)->toISOString(),
            'completed_at' => optional($this->completed_at)->toISOString(),
            'expires_at' => optional($this->expires_at)->toISOString(),
        ];
    }
}
