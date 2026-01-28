<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ProxyPool
{
    public function select(?string $strategy = null): ?string
    {
        $pool = array_values(array_filter(config('screenshot.proxy.pool', [])));
        if (!$pool) {
            return null;
        }

        $strategy = $strategy ?: config('screenshot.proxy.strategy', 'random');

        if ($strategy === 'round_robin') {
            $key = (string) config('screenshot.proxy.cache_key', 'screenshot:proxy:rr');
            try {
                $index = Cache::increment($key);
            } catch (\Throwable) {
                $index = random_int(1, PHP_INT_MAX);
            }

            $slot = ($index - 1) % count($pool);
            return $pool[$slot];
        }

        return $pool[array_rand($pool)];
    }
}
