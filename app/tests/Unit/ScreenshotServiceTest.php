<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTOs\ScreenshotParams;
use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use App\Exceptions\TimeoutException;
use App\Models\Screenshot;
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

    public function test_cache_hit_returns_copy_with_shared_file_and_extraction(): void
    {
        Queue::fake();
        Config::set('screenshot.cache.enabled', true);
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

        // UrlValidator runs real DNS; use a literal public IP host instead.
        $params = ScreenshotParams::fromRequest([
            'url' => 'https://93.184.216.34',
            'extract_html' => true,
        ]);

        Screenshot::create([
            'url' => 'https://93.184.216.34',
            'params_hash' => $params->getCacheHash(),
            'params' => $params->toArray(),
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/original.png',
            'file_type' => FileType::Png,
            'file_size' => 42,
            'width' => 1280,
            'height' => 800,
            'render_time_ms' => 200,
            'extracted_html' => '<html>cached</html>',
            'expires_at' => now()->addHour(),
            'completed_at' => now(),
        ]);

        $service = new ScreenshotService(new UrlValidator, new CacheService, new ProxyPool);
        $result = $service->capture($params, '203.0.113.5');

        $this->assertTrue($result->from_cache);
        $this->assertSame(ScreenshotStatus::Completed, $result->status);
        $this->assertSame('shots/original.png', $result->file_path);
        $this->assertSame('<html>cached</html>', $result->extracted_html);
        $this->assertSame('203.0.113.5', $result->ip_address);
    }

    public function test_wait_for_completion_marks_failed_on_timeout(): void
    {
        Config::set('screenshot.cache.enabled', false);

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'timeout'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Processing,
        ]);

        $service = new ScreenshotService(new UrlValidator, new CacheService, new ProxyPool);

        try {
            $service->waitForCompletion($screenshot, 0);
            $this->fail('Expected TimeoutException');
        } catch (TimeoutException) {
            // expected
        }

        $screenshot->refresh();
        $this->assertSame(ScreenshotStatus::Failed, $screenshot->status);
        $this->assertSame('RENDER_TIMEOUT', $screenshot->error_code);
    }
}
