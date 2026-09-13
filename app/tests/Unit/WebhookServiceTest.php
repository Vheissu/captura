<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use App\Models\Screenshot;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeScreenshot(): Screenshot
    {
        Config::set('screenshot.storage.public_url', 'http://localhost/screenshots');

        return Screenshot::create([
            'url' => 'https://example.com',
            'params_hash' => hash('sha256', 'webhook'),
            'params' => ['url' => 'https://example.com'],
            'status' => ScreenshotStatus::Completed,
            'file_path' => 'shots/a.png',
            'file_type' => FileType::Png,
            'file_size' => 10,
            'width' => 800,
            'height' => 600,
            'render_time_ms' => 100,
            'webhook_url' => 'https://hooks.example.com/done',
            'completed_at' => now(),
        ]);
    }

    public function test_sends_signed_payload_when_secret_configured(): void
    {
        Config::set('screenshot.security.webhook_secret', 'topsecret');
        Http::fake();

        (new WebhookService)->send($this->makeScreenshot());

        Http::assertSent(function (Request $request) {
            if ($request->url() !== 'https://hooks.example.com/done') {
                return false;
            }

            $signature = $request->header('X-Captura-Signature')[0] ?? '';
            $expected = 'sha256='.hash_hmac('sha256', $request->body(), 'topsecret');

            return hash_equals($expected, $signature)
                && $request->hasHeader('X-Captura-Event', 'screenshot.completed')
                && $request['status'] === 'completed'
                && $request['file_url'] === 'http://localhost/screenshots/shots/a.png';
        });
    }

    public function test_omits_signature_when_no_secret_configured(): void
    {
        Config::set('screenshot.security.webhook_secret', null);
        Http::fake();

        (new WebhookService)->send($this->makeScreenshot());

        Http::assertSent(
            fn (Request $request) => ! $request->hasHeader('X-Captura-Signature')
        );
    }

    public function test_throws_on_error_response(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);
        (new WebhookService)->send($this->makeScreenshot());
    }

    public function test_noop_without_webhook_url(): void
    {
        Http::fake();
        $screenshot = $this->makeScreenshot();
        $screenshot->webhook_url = null;

        (new WebhookService)->send($screenshot);

        Http::assertNothingSent();
    }
}
