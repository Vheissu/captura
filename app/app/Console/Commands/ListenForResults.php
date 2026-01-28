<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\HandleScreenshotResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ListenForResults extends Command
{
    protected $signature = 'screenshot:listen';
    protected $description = 'Listen for screenshot results from Node worker';

    public function handle(): int
    {
        $this->info('Listening for screenshot results...');
        $queue = config('screenshot.queue.result_queue');

        while (true) {
            $result = Redis::blpop($queue, 5);
            if ($result) {
                $data = json_decode($result[1], true);
                if (is_array($data)) {
                    HandleScreenshotResult::dispatch($data);
                }
            }
        }

        return self::SUCCESS;
    }
}
