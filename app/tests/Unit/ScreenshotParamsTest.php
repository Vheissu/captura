<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\ScreenshotParams;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ScreenshotParamsTest extends TestCase
{
    public function test_defaults_are_applied(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
        ]);

        $this->assertSame(1280, $params->width);
        $this->assertSame(800, $params->height);
        $this->assertSame('png', $params->format);
        $this->assertSame(80, $params->quality);
        $this->assertFalse($params->fullPage);
    }

    public function test_jpeg_is_normalized_to_jpg(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'format' => 'jpeg',
        ]);

        $this->assertSame('jpg', $params->format);
    }

    public function test_cache_hash_ignores_webhook_and_cache_flag(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $base = [
            'url' => 'https://example.com',
            'cache' => true,
            'webhook_url' => 'https://hook.example.com',
        ];

        $hashA = ScreenshotParams::fromRequest($base)->getCacheHash();
        $hashB = ScreenshotParams::fromRequest(array_merge($base, [
            'cache' => false,
            'webhook_url' => 'https://other.example.com',
        ]))->getCacheHash();

        $this->assertSame($hashA, $hashB);
    }

    public function test_headers_and_hide_selectors_are_normalized(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'user_agent' => 'UA-DEFAULT',
            'headers' => [
                'Accept-Language' => 'en-US',
            ],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'headers' => '{"X-Test":"1","X-Env":"captura"}',
            'hide_selectors' => ' .ads , #cookie ,  ',
            'cache' => 0,
        ]);

        $this->assertSame([
            'Accept-Language' => 'en-US',
            'X-Test' => '1',
            'X-Env' => 'captura',
        ], $params->headers);
        $this->assertSame(['.ads', '#cookie'], $params->hideSelectors);
        $this->assertFalse($params->cache);
    }

    public function test_default_user_agent_and_headers_apply(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'user_agent' => 'UA-DEFAULT',
            'headers' => [
                'Accept-Language' => 'en-US',
                'Accept' => 'text/html',
            ],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
        ]);

        $this->assertSame('UA-DEFAULT', $params->userAgent);
        $this->assertSame([
            'Accept-Language' => 'en-US',
            'Accept' => 'text/html',
        ], $params->headers);

        $override = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'user_agent' => 'UA-CUSTOM',
            'headers' => '{"Accept-Language":"fr","X-Test":"1"}',
        ]);

        $this->assertSame('UA-CUSTOM', $override->userAgent);
        $this->assertSame([
            'Accept-Language' => 'fr',
            'Accept' => 'text/html',
            'X-Test' => '1',
        ], $override->headers);
    }

    public function test_ua_preset_applies_when_user_agent_missing(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'user_agent' => 'UA-DEFAULT',
            'headers' => [
                'Accept-Language' => 'en-US',
            ],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ]);
        Config::set('screenshot.user_agent_presets', [
            'chrome-mac' => [
                'user_agent' => 'UA-CHROME',
                'headers' => [
                    'Accept' => 'text/html',
                ],
                'locale' => 'en-GB',
                'timezone' => 'Europe/London',
            ],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'ua_preset' => 'chrome-mac',
        ]);

        $this->assertSame('UA-CHROME', $params->userAgent);
        $this->assertSame([
            'Accept-Language' => 'en-US',
            'Accept' => 'text/html',
        ], $params->headers);
        $this->assertSame('en-GB', $params->locale);
        $this->assertSame('Europe/London', $params->timezone);
    }

    public function test_ua_preset_rotate_uses_available_presets(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'user_agent' => 'UA-DEFAULT',
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ]);
        Config::set('screenshot.user_agent_presets', [
            'only' => [
                'user_agent' => 'UA-ROTATE',
                'headers' => [
                    'Accept' => 'text/html',
                ],
                'locale' => 'fr-FR',
                'timezone' => 'Europe/Paris',
            ],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'ua_preset' => 'rotate',
        ]);

        $this->assertSame('UA-ROTATE', $params->userAgent);
        $this->assertSame(['Accept' => 'text/html'], $params->headers);
        $this->assertSame('fr-FR', $params->locale);
        $this->assertSame('Europe/Paris', $params->timezone);
    }

    public function test_locale_and_timezone_defaults_apply(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'user_agent' => 'UA-DEFAULT',
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/New_York',
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
        ]);

        $this->assertSame('en-US', $params->locale);
        $this->assertSame('America/New_York', $params->timezone);
    }
}
