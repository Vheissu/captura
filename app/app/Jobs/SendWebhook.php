<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Screenshot;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public string $screenshotId) {}

    public function handle(WebhookService $webhookService): void
    {
        $screenshot = Screenshot::find($this->screenshotId);
        if (!$screenshot || !$screenshot->webhook_url) {
            return;
        }

        try {
            $webhookService->send($screenshot);
            $screenshot->update(['webhook_status' => 'sent']);
        } catch (\Throwable $e) {
            $screenshot->update(['webhook_status' => 'failed']);
            Log::warning('Webhook delivery failed', [
                'screenshot_id' => $screenshot->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
