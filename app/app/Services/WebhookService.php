<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Screenshot;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    public function send(Screenshot $screenshot): void
    {
        if (!$screenshot->webhook_url) {
            return;
        }

        Http::timeout(5)->post($screenshot->webhook_url, [
            'id' => $screenshot->id,
            'status' => $screenshot->status->value,
            'file_url' => $screenshot->file_url,
            'file_type' => $screenshot->file_type?->value,
            'file_size' => $screenshot->file_size,
            'width' => $screenshot->width,
            'height' => $screenshot->height,
            'render_time_ms' => $screenshot->render_time_ms,
            'error_code' => $screenshot->error_code,
            'error_message' => $screenshot->error_message,
        ]);
    }
}
