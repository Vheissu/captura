<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use App\Models\Screenshot;
use App\Services\ScreenshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScreenshotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_capture_returns_json_response(): void
    {
        Config::set('screenshot.storage.public_url', 'http://localhost/screenshots');

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'json'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/json.png',
            'file_type' => FileType::Png,
            'file_size' => 123,
            'width' => 1280,
            'height' => 800,
            'render_time_ms' => 250,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
            $mock->shouldReceive('waitForCompletion')->andReturn($screenshot);
        });

        $response = $this->getJson('/api/screenshot?url=https://example.com&response=json');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $screenshot->id,
                'status' => 'completed',
                'format' => 'png',
                'width' => 1280,
                'height' => 800,
                'file_size' => 123,
            ])
            ->assertJsonPath('file_url', 'http://localhost/screenshots/shots/json.png');
    }

    public function test_capture_accepts_output_alias_for_json_response(): void
    {
        Config::set('screenshot.storage.public_url', 'http://localhost/screenshots');

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'output-json'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/output-json.png',
            'file_type' => FileType::Png,
            'file_size' => 123,
            'width' => 1280,
            'height' => 800,
            'render_time_ms' => 250,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
            $mock->shouldReceive('waitForCompletion')->andReturn($screenshot);
        });

        $response = $this->getJson('/api/screenshot?url=https://example.com&output=json');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $screenshot->id,
                'status' => 'completed',
            ]);
    }

    public function test_capture_returns_image_payload(): void
    {
        Storage::fake('screenshots');

        $payload = 'PNG';
        Storage::disk('screenshots')->put('shots/image.png', $payload);

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'image'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/image.png',
            'file_type' => FileType::Png,
            'file_size' => strlen($payload),
            'width' => 800,
            'height' => 600,
            'render_time_ms' => 150,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
            $mock->shouldReceive('waitForCompletion')->andReturn($screenshot);
        });

        $response = $this->get('/api/screenshot?url=https://example.com');

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Length', (string) strlen($payload))
            ->assertHeader('X-Screenshot-Id', $screenshot->id)
            ->assertSee($payload, false);
    }

    public function test_async_returns_job_metadata(): void
    {
        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'async'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Pending,
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
        });

        $response = $this->postJson('/api/screenshot/async', [
            'url' => 'https://example.com',
        ]);

        $response->assertStatus(202)
            ->assertJsonFragment([
                'id' => $screenshot->id,
                'status' => 'pending',
            ])
            ->assertJsonStructure([
                'poll_url',
                'estimated_seconds',
            ]);
    }

    public function test_bulk_returns_items(): void
    {
        $first = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'bulk-1'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Pending,
        ]);

        $second = Screenshot::create([
            'url' => 'https://example.org',
            'params_hash' => hash('sha256', 'bulk-2'),
            'params' => ['url' => 'https://example.org'],
            'status' => ScreenshotStatus::Pending,
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($first, $second) {
            $mock->shouldReceive('capture')->andReturn($first, $second);
        });

        $response = $this->postJson('/api/screenshot/bulk', [
            'items' => [
                ['url' => 'https://example.com'],
                ['url' => 'https://example.org'],
            ],
        ]);

        $response->assertStatus(202)
            ->assertJsonCount(2, 'items')
            ->assertJsonFragment([
                'id' => $first->id,
                'status' => 'pending',
            ])
            ->assertJsonFragment([
                'id' => $second->id,
                'status' => 'pending',
            ]);
    }

    public function test_show_returns_resource(): void
    {
        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'show'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/show.png',
            'file_type' => FileType::Png,
            'file_size' => 10,
            'width' => 800,
            'height' => 600,
            'render_time_ms' => 100,
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/screenshot/{$screenshot->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $screenshot->id,
                'status' => 'completed',
                'format' => 'png',
                'width' => 800,
                'height' => 600,
            ]);
    }

    public function test_capture_returns_error_payload_when_render_fails(): void
    {
        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'failed'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Failed,
            'error_code' => 'RENDER_FAILED',
            'error_message' => 'Render exploded',
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
            $mock->shouldReceive('waitForCompletion')->andReturn($screenshot);
        });

        $response = $this->getJson('/api/screenshot?url=https://example.com');

        $response->assertStatus(500)
            ->assertJson([
                'error' => true,
                'code' => 'RENDER_FAILED',
                'message' => 'Render exploded',
            ]);
    }

    public function test_capture_returns_404_when_file_is_missing(): void
    {
        Storage::fake('screenshots');
        Config::set('screenshot.storage.disk', 'screenshots');

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'gone'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/missing.png',
            'file_type' => FileType::Png,
            'file_size' => 10,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
            $mock->shouldReceive('waitForCompletion')->andReturn($screenshot);
        });

        $response = $this->getJson('/api/screenshot?url=https://example.com');

        $response->assertNotFound()
            ->assertJson([
                'error' => true,
                'code' => 'NOT_FOUND',
            ]);
    }

    public function test_show_includes_error_details_for_failed_screenshots(): void
    {
        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'failed-show'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Failed,
            'error_code' => 'RENDER_TIMEOUT',
            'error_message' => 'Navigation timed out',
            'completed_at' => now(),
        ]);

        $response = $this->getJson("/api/screenshot/{$screenshot->id}");

        $response->assertOk()
            ->assertJson([
                'status' => 'failed',
                'error_code' => 'RENDER_TIMEOUT',
                'error_message' => 'Navigation timed out',
            ]);
    }

    public function test_show_returns_404_for_unknown_id(): void
    {
        $this->getJson('/api/screenshot/'.(string) \Illuminate\Support\Str::uuid())
            ->assertNotFound()
            ->assertJson([
                'error' => true,
                'code' => 'NOT_FOUND',
            ]);
    }

    public function test_requests_are_rejected_when_api_key_is_configured(): void
    {
        Config::set('screenshot.security.api_key', 'test-key');

        $this->getJson('/api/screenshot?url=https://example.com')
            ->assertUnauthorized()
            ->assertJson([
                'error' => true,
                'code' => 'UNAUTHORIZED',
            ]);
    }

    public function test_valid_api_key_passes_middleware(): void
    {
        Config::set('screenshot.security.api_key', 'test-key');

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'auth'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'from_cache' => true,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
        });

        $this->getJson('/api/screenshot?url=https://example.com&response=json', [
            'Authorization' => 'Bearer test-key',
        ])->assertOk();
    }

    public function test_rate_limit_rejects_excess_requests(): void
    {
        Config::set('screenshot.security.rate_limit', 1);

        $screenshot = Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'rl'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'from_cache' => true,
            'completed_at' => now(),
        ]);

        $this->mock(ScreenshotService::class, function ($mock) use ($screenshot) {
            $mock->shouldReceive('capture')->andReturn($screenshot);
        });

        $this->getJson('/api/screenshot?url=https://example.com&response=json')->assertOk();
        $this->getJson('/api/screenshot?url=https://example.com&response=json')
            ->assertStatus(429)
            ->assertJson([
                'error' => true,
                'code' => 'RATE_LIMITED',
            ]);
    }
}
