<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\CaptureScreenshotRequest;
use Tests\TestCase;

class CaptureScreenshotRequestTest extends TestCase
{
    public function test_boolean_strings_are_normalized(): void
    {
        $request = new CaptureScreenshotRequest();
        $request->merge([
            'full_page' => 'true',
            'block_ads' => 'false',
            'block_cookies' => 'true',
            'dark_mode' => 'false',
            'cache' => 'true',
            'stealth' => 'true',
            'proxy_pool' => 'true',
        ]);

        $method = new \ReflectionMethod(CaptureScreenshotRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $data = $request->all();

        $this->assertSame(1, $data['full_page']);
        $this->assertSame(0, $data['block_ads']);
        $this->assertSame(1, $data['block_cookies']);
        $this->assertSame(0, $data['dark_mode']);
        $this->assertSame(1, $data['cache']);
        $this->assertSame(1, $data['stealth']);
        $this->assertSame(1, $data['proxy_pool']);
    }
}
