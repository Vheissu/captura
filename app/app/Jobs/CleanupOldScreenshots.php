<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ScreenshotStatus;
use App\Models\Screenshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
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
        $this->failStuckJobs();
        $this->deleteExpiredScreenshots();
    }

    private function deleteExpiredScreenshots(): void
    {
        $hours = (int) config('screenshot.cleanup.after_hours', 0);
        if ($hours <= 0) {
            return;
        }

        $cutoff = now()->subHours($hours);
        $disk = config('screenshot.storage.disk');

        Screenshot::query()
            ->whereNotNull('completed_at')
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('completed_at', '<', $cutoff)
                    ->orWhere(function (Builder $query) {
                        $query->whereNotNull('expires_at')
                            ->where('expires_at', '<', now());
                    });
            })
            ->chunkById(200, function ($screenshots) use ($disk) {
                foreach ($screenshots as $screenshot) {
                    if ($screenshot->file_path && ! $this->fileIsShared($screenshot)) {
                        Storage::disk($disk)->delete($screenshot->file_path);
                    }
                    $screenshot->delete();
                }
            });
    }

    /**
     * Cached copies share the original capture's file path, so a file may only
     * be removed once no remaining row points at it.
     */
    private function fileIsShared(Screenshot $screenshot): bool
    {
        return Screenshot::query()
            ->where('file_path', $screenshot->file_path)
            ->whereKeyNot($screenshot->id)
            ->exists();
    }

    private function failStuckJobs(): void
    {
        $minutes = (int) config('screenshot.cleanup.stuck_after_minutes', 0);
        if ($minutes <= 0) {
            return;
        }

        Screenshot::query()
            ->whereIn('status', [
                ScreenshotStatus::Pending->value,
                ScreenshotStatus::Processing->value,
            ])
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => ScreenshotStatus::Failed->value,
                'error_code' => 'STALE_JOB',
                'error_message' => 'Screenshot job expired before completion',
                'completed_at' => now(),
            ]);
    }
}
