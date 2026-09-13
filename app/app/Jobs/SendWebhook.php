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
use Throwable;

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
        if (! $screenshot || ! $screenshot->webhook_url) {
            return;
        }

        $webhookService->send($screenshot);
        $screenshot->update(['webhook_status' => 'sent']);
    }

    public function failed(Throwable $e): void
    {
        Screenshot::whereKey($this->screenshotId)
            ->update(['webhook_status' => 'failed']);

        Log::warning('Webhook delivery failed', [
            'screenshot_id' => $this->screenshotId,
            'error' => $e->getMessage(),
        ]);
    }
}
