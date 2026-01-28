<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class StorageService
{
    public function generatePath(string $id, string $format): string
    {
        $ext = $format;
        return sprintf(
            '%s/%s/%s.%s',
            now()->format('Y/m/d'),
            substr($id, 0, 2),
            $id,
            $ext
        );
    }
}
