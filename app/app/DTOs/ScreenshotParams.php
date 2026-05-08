<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Arr;

final readonly class ScreenshotParams
{
    public function __construct(
        public string $url,
        public string $format,
        public int $quality,
        public int $width,
        public int $height,
        public float $deviceScaleFactor,
        public bool $mobile,
        public bool $touch,
        public bool $landscape,
        public ?float $clipX,
        public ?float $clipY,
        public ?float $clipWidth,
        public ?float $clipHeight,
        public bool $fullPage,
        public ?string $selector,
        public ?string $waitForSelector,
        public int $delay,
        public string $waitUntil,
        public int $timeout,
        public bool $blockAds,
        public bool $blockCookies,
        public bool $darkMode,
        public bool $transparent,
        public bool $disableJs,
        public ?string $media,
        public bool $reducedMotion,
        public ?string $css,
        public ?string $js,
        public array $hideSelectors,
        public string $pdfFormat,
        public float $pdfScale,
        public bool $preferCssPageSize,
        public ?string $userAgent,
        public array $headers,
        public bool $stealth,
        public ?string $proxy,
        public bool $proxyPool,
        public ?string $proxyStrategy,
        public ?string $locale,
        public ?string $timezone,
        public ?string $webhookUrl,
        public bool $cache,
    ) {}

    public static function fromRequest(array $validated): self
    {
        $defaults = config('screenshot.defaults');
        $limits = config('screenshot.limits');
        $presets = config('screenshot.user_agent_presets', []);
        $appliedPreset = null;

        $format = strtolower((string) ($validated['format'] ?? $defaults['format']));
        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $headers = self::parseHeaders($defaults['headers'] ?? null);

        $hideSelectors = [];
        if (! empty($validated['hide_selectors'])) {
            $hideSelectors = array_filter(array_map('trim', explode(',', (string) $validated['hide_selectors'])));
        }

        $locale = $validated['locale'] ?? null;
        if (is_string($locale) && trim($locale) === '') {
            $locale = null;
        }

        $timezone = $validated['timezone'] ?? null;
        if (is_string($timezone) && trim($timezone) === '') {
            $timezone = null;
        }

        $userAgent = $validated['user_agent'] ?? null;
        if (is_string($userAgent) && trim($userAgent) === '') {
            $userAgent = null;
        }

        $uaPreset = $validated['ua_preset'] ?? null;
        if ($userAgent === null && $uaPreset) {
            $presetKey = $uaPreset;
            if (in_array($uaPreset, ['rotate', 'random'], true)) {
                $keys = array_keys($presets);
                $presetKey = $keys ? $keys[array_rand($keys)] : null;
            }

            if ($presetKey && isset($presets[$presetKey])) {
                $preset = $presets[$presetKey];
                $appliedPreset = $preset;
                if (! empty($preset['user_agent'])) {
                    $userAgent = $preset['user_agent'];
                }
                if (! empty($preset['headers']) && is_array($preset['headers'])) {
                    $headers = array_merge($headers, $preset['headers']);
                }
                if ($locale === null && ! empty($preset['locale'])) {
                    $locale = (string) $preset['locale'];
                }
                if ($timezone === null && ! empty($preset['timezone'])) {
                    $timezone = (string) $preset['timezone'];
                }
            }
        }

        if ($userAgent === null) {
            $userAgent = $defaults['user_agent'] ?? null;
        }
        if ($locale === null) {
            $locale = $defaults['locale'] ?? null;
        }
        if ($timezone === null) {
            $timezone = $defaults['timezone'] ?? null;
        }

        if (! empty($validated['headers'])) {
            $customHeaders = self::parseHeaders($validated['headers']);
            if ($customHeaders) {
                $headers = array_merge($headers, $customHeaders);
            }
        }

        $clipWidth = self::nullableFloat($validated, 'clip_width');
        $clipHeight = self::nullableFloat($validated, 'clip_height');
        $hasClip = $clipWidth !== null && $clipHeight !== null;

        return new self(
            url: (string) $validated['url'],
            format: $format,
            quality: (int) ($validated['quality'] ?? $defaults['quality']),
            width: (int) ($validated['width'] ?? $defaults['width']),
            height: (int) ($validated['height'] ?? $defaults['height']),
            deviceScaleFactor: (float) ($validated['device_scale_factor'] ?? ($appliedPreset['device_scale_factor'] ?? 1)),
            mobile: (bool) ($validated['mobile'] ?? ($appliedPreset['mobile'] ?? false)),
            touch: (bool) ($validated['touch'] ?? ($appliedPreset['touch'] ?? false)),
            landscape: (bool) ($validated['landscape'] ?? false),
            clipX: $hasClip ? (self::nullableFloat($validated, 'clip_x') ?? 0.0) : null,
            clipY: $hasClip ? (self::nullableFloat($validated, 'clip_y') ?? 0.0) : null,
            clipWidth: $hasClip ? $clipWidth : null,
            clipHeight: $hasClip ? $clipHeight : null,
            fullPage: (bool) ($validated['full_page'] ?? $defaults['full_page']),
            selector: $validated['selector'] ?? null,
            waitForSelector: $validated['wait_for_selector'] ?? null,
            delay: (int) ($validated['delay'] ?? 0),
            waitUntil: (string) ($validated['wait_until'] ?? 'load'),
            timeout: (int) ($validated['timeout'] ?? $limits['timeout']),
            blockAds: (bool) ($validated['block_ads'] ?? false),
            blockCookies: (bool) ($validated['block_cookies'] ?? false),
            darkMode: (bool) ($validated['dark_mode'] ?? false),
            transparent: (bool) ($validated['transparent'] ?? false),
            disableJs: (bool) ($validated['disable_js'] ?? false),
            media: $validated['media'] ?? null,
            reducedMotion: (bool) ($validated['reduced_motion'] ?? false),
            css: $validated['css'] ?? null,
            js: $validated['js'] ?? null,
            hideSelectors: $hideSelectors,
            pdfFormat: (string) ($validated['pdf_format'] ?? 'a4'),
            pdfScale: (float) ($validated['pdf_scale'] ?? 1),
            preferCssPageSize: (bool) ($validated['prefer_css_page_size'] ?? false),
            userAgent: $userAgent,
            headers: $headers,
            stealth: (bool) ($validated['stealth'] ?? ($defaults['stealth'] ?? false)),
            proxy: $validated['proxy'] ?? null,
            proxyPool: (bool) ($validated['proxy_pool'] ?? false),
            proxyStrategy: $validated['proxy_strategy'] ?? null,
            locale: $locale,
            timezone: $timezone,
            webhookUrl: $validated['webhook_url'] ?? null,
            cache: (bool) ($validated['cache'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'format' => $this->format,
            'quality' => $this->quality,
            'width' => $this->width,
            'height' => $this->height,
            'device_scale_factor' => $this->deviceScaleFactor,
            'mobile' => $this->mobile,
            'touch' => $this->touch,
            'landscape' => $this->landscape,
            'clip_x' => $this->clipX,
            'clip_y' => $this->clipY,
            'clip_width' => $this->clipWidth,
            'clip_height' => $this->clipHeight,
            'full_page' => $this->fullPage,
            'selector' => $this->selector,
            'wait_for_selector' => $this->waitForSelector,
            'delay' => $this->delay,
            'wait_until' => $this->waitUntil,
            'timeout' => $this->timeout,
            'block_ads' => $this->blockAds,
            'block_cookies' => $this->blockCookies,
            'dark_mode' => $this->darkMode,
            'transparent' => $this->transparent,
            'disable_js' => $this->disableJs,
            'media' => $this->media,
            'reduced_motion' => $this->reducedMotion,
            'css' => $this->css,
            'js' => $this->js,
            'hide_selectors' => $this->hideSelectors,
            'pdf_format' => $this->pdfFormat,
            'pdf_scale' => $this->pdfScale,
            'prefer_css_page_size' => $this->preferCssPageSize,
            'user_agent' => $this->userAgent,
            'headers' => $this->headers,
            'stealth' => $this->stealth,
            'proxy' => $this->proxy,
            'proxy_pool' => $this->proxyPool,
            'proxy_strategy' => $this->proxyStrategy,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'webhook_url' => $this->webhookUrl,
            'cache' => $this->cache,
        ];
    }

    public function withProxy(?string $proxy, ?bool $stealth = null): self
    {
        return new self(
            url: $this->url,
            format: $this->format,
            quality: $this->quality,
            width: $this->width,
            height: $this->height,
            deviceScaleFactor: $this->deviceScaleFactor,
            mobile: $this->mobile,
            touch: $this->touch,
            landscape: $this->landscape,
            clipX: $this->clipX,
            clipY: $this->clipY,
            clipWidth: $this->clipWidth,
            clipHeight: $this->clipHeight,
            fullPage: $this->fullPage,
            selector: $this->selector,
            waitForSelector: $this->waitForSelector,
            delay: $this->delay,
            waitUntil: $this->waitUntil,
            timeout: $this->timeout,
            blockAds: $this->blockAds,
            blockCookies: $this->blockCookies,
            darkMode: $this->darkMode,
            transparent: $this->transparent,
            disableJs: $this->disableJs,
            media: $this->media,
            reducedMotion: $this->reducedMotion,
            css: $this->css,
            js: $this->js,
            hideSelectors: $this->hideSelectors,
            pdfFormat: $this->pdfFormat,
            pdfScale: $this->pdfScale,
            preferCssPageSize: $this->preferCssPageSize,
            userAgent: $this->userAgent,
            headers: $this->headers,
            stealth: $stealth ?? $this->stealth,
            proxy: $proxy,
            proxyPool: $this->proxyPool,
            proxyStrategy: $this->proxyStrategy,
            locale: $this->locale,
            timezone: $this->timezone,
            webhookUrl: $this->webhookUrl,
            cache: $this->cache,
        );
    }

    public function getCacheHash(): string
    {
        $cacheable = Arr::except($this->toArray(), [
            'webhook_url',
            'cache',
        ]);

        return hash('sha256', json_encode($cacheable));
    }

    private static function parseHeaders(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function nullableFloat(array $values, string $key): ?float
    {
        if (! array_key_exists($key, $values) || $values[$key] === null || $values[$key] === '') {
            return null;
        }

        return (float) $values[$key];
    }
}
