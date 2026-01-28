<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Screenshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class CleanupOldScreenshots implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $hours = (int) config('screenshot.cleanup.after_hours', 0);
        if ($hours <= 0) {
            return;
        }

        $cutoff = now()->subHours($hours);
        $disk = config('screenshot.storage.disk');

        Screenshot::query()
            ->whereNotNull('completed_at')
            ->where(function ($query) use ($cutoff) {
                $query->where('completed_at', '<', $cutoff)
                    ->orWhereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            })
            ->chunkById(200, function ($screenshots) use ($disk) {
                foreach ($screenshots as $screenshot) {
                    if ($screenshot->file_path) {
                        Storage::disk($disk)->delete($screenshot->file_path);
                    }
                    $screenshot->delete();
                }
            });
    }
}
