<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\ScreenshotParams;
use App\Services\CacheService;
use App\Services\ProxyPool;
use App\Services\ScreenshotService;
use App\Services\UrlValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ScreenshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_injection_urls_use_the_same_url_validator(): void
    {
        Queue::fake();
        Config::set('screenshot.cache.enabled', false);
        Config::set('screenshot.proxy.enabled', false);
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
            'css_url' => 'https://cdn.example.com/capture.css',
            'js_url' => 'https://cdn.example.com/capture.js',
        ]);

        $validator = Mockery::mock(UrlValidator::class);
        $validator->shouldReceive('validate')->once()->with('https://example.com');
        $validator->shouldReceive('validate')->once()->with('https://cdn.example.com/capture.css');
        $validator->shouldReceive('validate')->once()->with('https://cdn.example.com/capture.js');

        $service = new ScreenshotService($validator, new CacheService, new ProxyPool);
        $service->capture($params);

        $this->assertDatabaseHas('screenshots', [
            'url' => 'https://example.com',
        ]);
    }
}
