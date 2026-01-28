<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProxyPool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProxyPoolTest extends TestCase
{
    public function test_round_robin_cycles_pool(): void
    {
        Config::set('screenshot.proxy.pool', ['http://proxy-a:8080', 'http://proxy-b:8080']);
        Config::set('screenshot.proxy.strategy', 'round_robin');
        Config::set('screenshot.proxy.cache_key', 'test:proxy:rr');

        Cache::shouldReceive('increment')
            ->twice()
            ->with('test:proxy:rr')
            ->andReturn(1, 2);

        $pool = new ProxyPool();

        $this->assertSame('http://proxy-a:8080', $pool->select());
        $this->assertSame('http://proxy-b:8080', $pool->select());
    }
}
