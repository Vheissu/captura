<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\StorageService;
use Tests\TestCase;

class StorageServiceTest extends TestCase
{
    public function test_generate_path_uses_date_shard_id_and_format(): void
    {
        $this->travelTo(now()->setDate(2026, 5, 8)->setTime(12, 0));

        $path = (new StorageService)->generatePath('abcdef12-3456-7890-abcd-ef1234567890', 'webp');

        $this->assertSame('2026/05/08/ab/abcdef12-3456-7890-abcd-ef1234567890.webp', $path);
    }
}
