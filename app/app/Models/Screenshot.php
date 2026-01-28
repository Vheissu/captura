<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FileType;
use App\Enums\ScreenshotStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Screenshot extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'url',
        'params_hash',
        'params',
        'status',
        'error_code',
        'error_message',
        'file_path',
        'file_type',
        'file_size',
        'width',
        'height',
        'render_time_ms',
        'extracted_html',
        'extracted_text',
        'webhook_url',
        'webhook_status',
        'ip_address',
        'from_cache',
        'processing_started_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'params' => 'array',
        'status' => ScreenshotStatus::class,
        'file_type' => FileType::class,
        'from_cache' => 'boolean',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => ScreenshotStatus::Processing,
            'processing_started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $data): void
    {
        $this->update([
            'status' => ScreenshotStatus::Completed,
            'file_path' => $data['file_path'] ?? null,
            'file_type' => $data['file_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'render_time_ms' => $data['render_time_ms'] ?? null,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $code, ?string $message): void
    {
        $this->update([
            'status' => ScreenshotStatus::Failed,
            'error_code' => $code,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $disk = config('screenshot.storage.disk');
        $url = config('screenshot.storage.public_url');

        if ($url) {
            return rtrim($url, '/') . '/' . ltrim($this->file_path, '/');
        }

        return Storage::disk($disk)->url($this->file_path);
    }
}
