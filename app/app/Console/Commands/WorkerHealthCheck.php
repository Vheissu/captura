<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class WorkerHealthCheck extends Command
{
    protected $signature = 'screenshot:worker-health';
    protected $description = 'Check Node screenshot worker heartbeat';

    public function handle(): int
    {
        $key = config('screenshot.worker.heartbeat_key');
        $ttl = (int) config('screenshot.worker.heartbeat_ttl', 30);
        $timestamp = Redis::get($key);

        if (!$timestamp) {
            $this->error('Worker heartbeat missing');
            return self::FAILURE;
        }

        $age = now()->getTimestamp() - (int) ($timestamp / 1000);
        if ($age > $ttl) {
            $this->error('Worker heartbeat stale');
            return self::FAILURE;
        }

        $this->info('Worker heartbeat ok');
        return self::SUCCESS;
    }
}
