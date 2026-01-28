<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Screenshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ProcessScreenshot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public Screenshot $screenshot) {}

    public function handle(): void
    {
        $this->screenshot->markAsProcessing();

        $payload = [
            'id' => $this->screenshot->id,
            'params' => $this->screenshot->params,
            'storage_path' => $this->generateStoragePath(),
        ];

        Redis::rpush(config('screenshot.queue.name'), json_encode($payload));
    }

    public function failed(Throwable $e): void
    {
        $this->screenshot->markAsFailed('DISPATCH_FAILED', $e->getMessage());
    }

    private function generateStoragePath(): string
    {
        $ext = $this->screenshot->params['format'] ?? 'png';

        return sprintf(
            '%s/%s/%s.%s',
            now()->format('Y/m/d'),
            substr($this->screenshot->id, 0, 2),
            $this->screenshot->id,
            $ext
        );
    }
}
