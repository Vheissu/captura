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
}
