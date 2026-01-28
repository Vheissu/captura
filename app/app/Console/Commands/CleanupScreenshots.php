<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CleanupOldScreenshots;
use Illuminate\Console\Command;

class CleanupScreenshots extends Command
{
    protected $signature = 'screenshot:cleanup';
    protected $description = 'Delete expired screenshots and cleanup storage';

    public function handle(): int
    {
        (new CleanupOldScreenshots())->handle();
        $this->info('Cleanup complete');

        return self::SUCCESS;
    }
}
