<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use App\Jobs\CleanupOldScreenshots;
use App\Models\Screenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupOldScreenshotsTest extends TestCase
{
    use RefreshDatabase;

    private function makeScreenshot(array $overrides = []): Screenshot
    {
        return Screenshot::create(array_merge([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', (string) random_int(0, PHP_INT_MAX)),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'completed_at' => now(),
        ], $overrides));
    }

    public function test_expired_screenshots_are_deleted_with_their_files(): void
    {
        Storage::fake('screenshots');
        Config::set('screenshot.cleanup.after_hours', 24);
        Config::set('screenshot.cleanup.stuck_after_minutes', 0);
        Config::set('screenshot.storage.disk', 'screenshots');

        Storage::disk('screenshots')->put('shots/old.png', 'PNG');

        $old = $this->makeScreenshot([
            'file_path' => 'shots/old.png',
            'file_type' => FileType::Png,
            'completed_at' => now()->subDays(2),
        ]);

        $fresh = $this->makeScreenshot([
            'file_path' => 'shots/fresh.png',
            'file_type' => FileType::Png,
            'completed_at' => now(),
        ]);

        (new CleanupOldScreenshots)->handle();

        $this->assertDatabaseMissing('screenshots', ['id' => $old->id]);
        $this->assertDatabaseHas('screenshots', ['id' => $fresh->id]);
        Storage::disk('screenshots')->assertMissing('shots/old.png');
    }

    public function test_expired_screenshots_via_expires_at_are_deleted(): void
    {
        Storage::fake('screenshots');
        Config::set('screenshot.cleanup.after_hours', 24);
        Config::set('screenshot.cleanup.stuck_after_minutes', 0);
        Config::set('screenshot.storage.disk', 'screenshots');

        $expired = $this->makeScreenshot([
            'completed_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
        ]);

        (new CleanupOldScreenshots)->handle();

        $this->assertDatabaseMissing('screenshots', ['id' => $expired->id]);
    }

    public function test_shared_file_is_kept_while_another_row_references_it(): void
    {
        Storage::fake('screenshots');
        Config::set('screenshot.cleanup.after_hours', 24);
        Config::set('screenshot.cleanup.stuck_after_minutes', 0);
        Config::set('screenshot.storage.disk', 'screenshots');

        Storage::disk('screenshots')->put('shots/shared.png', 'PNG');

        $old = $this->makeScreenshot([
            'file_path' => 'shots/shared.png',
            'completed_at' => now()->subDays(2),
        ]);

        $cachedCopy = $this->makeScreenshot([
            'file_path' => 'shots/shared.png',
            'completed_at' => now(),
            'from_cache' => true,
        ]);

        (new CleanupOldScreenshots)->handle();

        $this->assertDatabaseMissing('screenshots', ['id' => $old->id]);
        $this->assertDatabaseHas('screenshots', ['id' => $cachedCopy->id]);
        Storage::disk('screenshots')->assertExists('shots/shared.png');
    }

    public function test_shared_file_is_deleted_once_last_reference_expires(): void
    {
        Storage::fake('screenshots');
        Config::set('screenshot.cleanup.after_hours', 24);
        Config::set('screenshot.cleanup.stuck_after_minutes', 0);
        Config::set('screenshot.storage.disk', 'screenshots');

        Storage::disk('screenshots')->put('shots/shared.png', 'PNG');

        $this->makeScreenshot([
            'file_path' => 'shots/shared.png',
            'completed_at' => now()->subDays(3),
        ]);
        $this->makeScreenshot([
            'file_path' => 'shots/shared.png',
            'completed_at' => now()->subDays(2),
            'from_cache' => true,
        ]);

        (new CleanupOldScreenshots)->handle();

        $this->assertSame(0, Screenshot::count());
        Storage::disk('screenshots')->assertMissing('shots/shared.png');
    }

    public function test_stuck_jobs_are_marked_failed(): void
    {
        Config::set('screenshot.cleanup.after_hours', 24);
        Config::set('screenshot.cleanup.stuck_after_minutes', 60);

        $stuck = $this->makeScreenshot([
            'status' => ScreenshotStatus::Processing,
            'completed_at' => null,
        ]);
        $stuck->created_at = now()->subHours(2);
        $stuck->save();

        $recent = $this->makeScreenshot([
            'status' => ScreenshotStatus::Processing,
            'completed_at' => null,
        ]);

        (new CleanupOldScreenshots)->handle();

        $stuck->refresh();
        $recent->refresh();

        $this->assertSame(ScreenshotStatus::Failed, $stuck->status);
        $this->assertSame('STALE_JOB', $stuck->error_code);
        $this->assertNotNull($stuck->completed_at);
        $this->assertSame(ScreenshotStatus::Processing, $recent->status);
    }
}
