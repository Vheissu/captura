<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use App\Jobs\HandleScreenshotResult;
use App\Jobs\SendWebhook;
use App\Models\Screenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class HandleScreenshotResultTest extends TestCase
{
    use RefreshDatabase;

    private function makeScreenshot(array $overrides = []): Screenshot
    {
        return Screenshot::create(array_merge([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', (string) random_int(0, PHP_INT_MAX)),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Processing,
        ], $overrides));
    }

    public function test_success_result_marks_screenshot_completed(): void
    {
        Bus::fake();
        $screenshot = $this->makeScreenshot();

        (new HandleScreenshotResult([
            'id' => $screenshot->id,
            'success' => true,
            'file_path' => 'shots/a.png',
            'file_type' => 'png',
            'file_size' => 1234,
            'width' => 1280,
            'height' => 800,
            'render_time_ms' => 420,
            'extracted_html' => '<html><body>hi</body></html>',
            'extracted_text' => 'hi',
        ]))->handle();

        $screenshot->refresh();

        $this->assertSame(ScreenshotStatus::Completed, $screenshot->status);
        $this->assertSame('shots/a.png', $screenshot->file_path);
        $this->assertSame(FileType::Png, $screenshot->file_type);
        $this->assertSame(1234, $screenshot->file_size);
        $this->assertSame(420, $screenshot->render_time_ms);
        $this->assertSame('<html><body>hi</body></html>', $screenshot->extracted_html);
        $this->assertSame('hi', $screenshot->extracted_text);
        $this->assertNotNull($screenshot->completed_at);
        Bus::assertNotDispatched(SendWebhook::class);
    }

    public function test_failure_result_marks_screenshot_failed(): void
    {
        Bus::fake();
        $screenshot = $this->makeScreenshot();

        (new HandleScreenshotResult([
            'id' => $screenshot->id,
            'success' => false,
            'error_code' => 'RENDER_TIMEOUT',
            'error_message' => 'Navigation timed out',
        ]))->handle();

        $screenshot->refresh();

        $this->assertSame(ScreenshotStatus::Failed, $screenshot->status);
        $this->assertSame('RENDER_TIMEOUT', $screenshot->error_code);
        $this->assertSame('Navigation timed out', $screenshot->error_message);
    }

    public function test_webhook_is_dispatched_when_webhook_url_present(): void
    {
        Bus::fake();
        $screenshot = $this->makeScreenshot([
            'webhook_url' => 'https://hooks.example.com/done',
            'webhook_status' => 'pending',
        ]);

        (new HandleScreenshotResult([
            'id' => $screenshot->id,
            'success' => true,
            'file_path' => 'shots/a.png',
            'file_type' => 'png',
        ]))->handle();

        Bus::assertDispatched(SendWebhook::class, fn (SendWebhook $job) => $job->screenshotId === $screenshot->id);
    }

    public function test_unknown_screenshot_is_ignored(): void
    {
        Bus::fake();

        (new HandleScreenshotResult([
            'id' => 'missing-id',
            'success' => true,
        ]))->handle();

        $this->assertSame(0, Screenshot::count());
        Bus::assertNotDispatched(SendWebhook::class);
    }
}
