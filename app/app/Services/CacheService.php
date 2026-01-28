<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ScreenshotStatus;
use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Builder;

class CacheService
{
    public function find(string $hash): ?Screenshot
    {
        return Screenshot::query()
            ->where('params_hash', $hash)
            ->where('status', ScreenshotStatus::Completed->value)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('completed_at')
            ->first();
    }
}
