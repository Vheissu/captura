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

    public function test_screenshotapi_compatible_aliases_are_normalized(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'file_type' => 'jpeg',
            'image_quality' => 95,
            'retina' => true,
            'wait_for_event' => 'networkidle',
            'no_cookie_banners' => true,
            'block_js' => true,
            'omit_background' => true,
            'accept_languages' => 'en-AU,en;q=0.9',
            'fresh' => true,
        ]);

        $this->assertSame('jpg', $params->format);
        $this->assertSame(95, $params->quality);
        $this->assertSame(2.0, $params->deviceScaleFactor);
        $this->assertSame('networkidle', $params->waitUntil);
        $this->assertTrue($params->blockCookies);
        $this->assertTrue($params->disableJs);
        $this->assertTrue($params->transparent);
        $this->assertSame('en-AU,en;q=0.9', $params->locale);
        $this->assertFalse($params->cache);
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
                'device_scale_factor' => 2,
                'mobile' => true,
                'touch' => true,
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
        $this->assertSame(2.0, $params->deviceScaleFactor);
        $this->assertTrue($params->mobile);
        $this->assertTrue($params->touch);
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

    public function test_wait_for_selector_is_preserved(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'headers' => [],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'wait_for_selector' => '#app-ready',
        ]);

        $this->assertSame('#app-ready', $params->waitForSelector);
        $this->assertSame('#app-ready', $params->toArray()['wait_for_selector']);
    }

    public function test_rendering_controls_are_preserved(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'headers' => [],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'device_scale_factor' => 3,
            'mobile' => true,
            'touch' => true,
            'landscape' => true,
            'clip_x' => 10,
            'clip_y' => 20,
            'clip_width' => 640,
            'clip_height' => 360,
            'transparent' => true,
            'disable_js' => true,
            'media' => 'print',
            'reduced_motion' => true,
            'pdf_format' => 'letter',
            'pdf_scale' => 0.75,
            'prefer_css_page_size' => true,
            'scroll_to_element' => '#section',
            'adjust_top' => 240,
            'lazy_load' => true,
            'scroll_delay' => 25,
            'selector_to_click' => '#open',
            'click_recursion' => 3,
            'css_url' => 'https://cdn.example.com/capture.css',
            'js_url' => 'https://cdn.example.com/capture.js',
            'remove_selectors' => '.remove-me,#toast',
            'blur_selector' => '.secret',
            'block_tracking' => true,
            'block_chat_widgets' => true,
            'block_resources' => 'image,font',
            'block_specific_requests' => 'analytics.js,chat.js',
            'cookies' => 'session=abc; theme=dark',
            'grayscale' => 40,
        ]);

        $this->assertSame(3.0, $params->deviceScaleFactor);
        $this->assertTrue($params->mobile);
        $this->assertTrue($params->touch);
        $this->assertTrue($params->landscape);
        $this->assertSame(10.0, $params->clipX);
        $this->assertSame(20.0, $params->clipY);
        $this->assertSame(640.0, $params->clipWidth);
        $this->assertSame(360.0, $params->clipHeight);
        $this->assertTrue($params->transparent);
        $this->assertTrue($params->disableJs);
        $this->assertSame('print', $params->media);
        $this->assertTrue($params->reducedMotion);
        $this->assertSame('letter', $params->pdfFormat);
        $this->assertSame(0.75, $params->pdfScale);
        $this->assertTrue($params->preferCssPageSize);
        $this->assertSame('#section', $params->scrollToElement);
        $this->assertSame(240, $params->adjustTop);
        $this->assertTrue($params->lazyLoad);
        $this->assertSame(25, $params->scrollDelay);
        $this->assertSame('#open', $params->selectorToClick);
        $this->assertSame(3, $params->clickRecursion);
        $this->assertSame('https://cdn.example.com/capture.css', $params->cssUrl);
        $this->assertSame('https://cdn.example.com/capture.js', $params->jsUrl);
        $this->assertSame(['.remove-me', '#toast'], $params->removeSelectors);
        $this->assertSame(['.secret'], $params->blurSelectors);
        $this->assertTrue($params->blockTracking);
        $this->assertTrue($params->blockChatWidgets);
        $this->assertSame(['image', 'font'], $params->blockResources);
        $this->assertSame(['analytics.js', 'chat.js'], $params->blockSpecificRequests);
        $this->assertSame('session=abc; theme=dark', $params->cookies);
        $this->assertSame(40, $params->grayscale);
        $this->assertSame(640.0, $params->toArray()['clip_width']);
        $this->assertSame('print', $params->toArray()['media']);
        $this->assertTrue($params->toArray()['prefer_css_page_size']);
        $this->assertSame('#section', $params->toArray()['scroll_to_element']);
        $this->assertSame(['.secret'], $params->toArray()['blur_selectors']);
    }

    public function test_clip_coordinates_default_to_origin_when_dimensions_are_present(): void
    {
        Config::set('screenshot.defaults', [
            'width' => 1280,
            'height' => 800,
            'format' => 'png',
            'quality' => 80,
            'full_page' => false,
            'headers' => [],
        ]);
        Config::set('screenshot.limits', ['timeout' => 30]);

        $params = ScreenshotParams::fromRequest([
            'url' => 'https://example.com',
            'clip_width' => 1200,
            'clip_height' => 630,
        ]);

        $this->assertSame(0.0, $params->clipX);
        $this->assertSame(0.0, $params->clipY);
        $this->assertSame(1200.0, $params->clipWidth);
        $this->assertSame(630.0, $params->clipHeight);
    }
}
