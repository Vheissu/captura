<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Screenshot;
use App\Jobs\SendWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleScreenshotResult implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public array $result) {}

    public function handle(): void
    {
        $screenshot = Screenshot::find($this->result['id'] ?? null);

        if (!$screenshot) {
            return;
        }

        if (!empty($this->result['success'])) {
            $screenshot->markAsCompleted([
                'file_path' => $this->result['file_path'] ?? null,
                'file_type' => $this->result['file_type'] ?? null,
                'file_size' => $this->result['file_size'] ?? null,
                'width' => $this->result['width'] ?? null,
                'height' => $this->result['height'] ?? null,
                'render_time_ms' => $this->result['render_time_ms'] ?? null,
            ]);
        } else {
            $screenshot->markAsFailed(
                $this->result['error_code'] ?? 'RENDER_FAILED',
                $this->result['error_message'] ?? 'Render failed'
            );
        }

        if ($screenshot->webhook_url) {
            SendWebhook::dispatch($screenshot->id);
        }
    }
}
