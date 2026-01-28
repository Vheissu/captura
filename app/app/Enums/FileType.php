<?php

declare(strict_types=1);

namespace App\Enums;

enum FileType: string
{
    case Png = 'png';
    case Jpg = 'jpg';
    case Jpeg = 'jpeg';
    case Webp = 'webp';
    case Pdf = 'pdf';

    public function mimeType(): string
    {
        return match ($this) {
            self::Png => 'image/png',
            self::Jpg, self::Jpeg => 'image/jpeg',
            self::Webp => 'image/webp',
            self::Pdf => 'application/pdf',
        };
    }

    public function normalized(): self
    {
        return $this === self::Jpeg ? self::Jpg : $this;
    }
}
