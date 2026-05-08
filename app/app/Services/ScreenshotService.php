<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\ScreenshotParams;
use App\Enums\ScreenshotStatus;
use App\Exceptions\TimeoutException;
use App\Jobs\ProcessScreenshot;
use App\Models\Screenshot;

class ScreenshotService
{
    public function __construct(
        private UrlValidator $urlValidator,
        private CacheService $cacheService,
        private ProxyPool $proxyPool,
    ) {}

    public function capture(ScreenshotParams $params, ?string $ipAddress = null): Screenshot
    {
        $this->urlValidator->validate($params->url);
        $params = $this->applyProxyPool($params);

        if (config('screenshot.cache.enabled') && $params->cache) {
            $cached = $this->cacheService->find($params->getCacheHash());
            if ($cached) {
                return $this->createCachedCopy($cached, $params, $ipAddress);
            }
        }

        $expiresAt = null;
        if (config('screenshot.cache.enabled')) {
            $expiresAt = now()->addSeconds(config('screenshot.cache.ttl'));
        }

        $screenshot = Screenshot::create([
            'url' => $params->url,
            'params_hash' => $params->getCacheHash(),
            'params' => $params->toArray(),
            'status' => ScreenshotStatus::Pending,
            'ip_address' => $ipAddress,
            'webhook_url' => $params->webhookUrl,
            'webhook_status' => $params->webhookUrl ? 'pending' : null,
            'expires_at' => $expiresAt,
        ]);

        ProcessScreenshot::dispatch($screenshot);

        return $screenshot;
    }

    public function waitForCompletion(Screenshot $screenshot, int $timeout = 30): Screenshot
    {
        $deadline = now()->addSeconds($timeout);

        while (now()->lt($deadline)) {
            $screenshot->refresh();

            if ($screenshot->status->isTerminal()) {
                return $screenshot;
            }

            usleep(200_000);
        }

        throw new TimeoutException('Screenshot timed out');
    }

    private function createCachedCopy(Screenshot $cached, ScreenshotParams $params, ?string $ipAddress): Screenshot
    {
        return Screenshot::create([
            'url' => $params->url,
            'params_hash' => $cached->params_hash,
            'params' => $params->toArray(),
            'status' => ScreenshotStatus::Completed,
            'file_path' => $cached->file_path,
            'file_type' => $cached->file_type,
            'file_size' => $cached->file_size,
            'width' => $cached->width,
            'height' => $cached->height,
            'render_time_ms' => $cached->render_time_ms,
            'ip_address' => $ipAddress,
            'from_cache' => true,
            'completed_at' => now(),
            'expires_at' => $cached->expires_at,
        ]);
    }

    private function applyProxyPool(ScreenshotParams $params): ScreenshotParams
    {
        if ($params->proxy !== null) {
            return $params;
        }

        $poolEnabled = (bool) config('screenshot.proxy.enabled', false);
        if (! $poolEnabled && ! $params->proxyPool) {
            return $params;
        }

        $proxy = $this->proxyPool->select($params->proxyStrategy);
        if (! $proxy) {
            return $params;
        }

        return $params->withProxy($proxy);
    }
}
