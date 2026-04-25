<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\CaptureScreenshotRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CaptureScreenshotRequestTest extends TestCase
{
    public function test_boolean_strings_are_normalized(): void
    {
        $request = new CaptureScreenshotRequest;
        $request->merge([
            'full_page' => 'true',
            'block_ads' => 'FALSE',
            'block_cookies' => 'yes',
            'dark_mode' => 'off',
            'cache' => '1',
            'stealth' => 'on',
            'proxy_pool' => 'no',
            'mobile' => 'yes',
            'touch' => 'true',
            'landscape' => 'off',
            'transparent' => '1',
            'disable_js' => '0',
            'prefer_css_page_size' => 'false',
        ]);

        $method = new \ReflectionMethod(CaptureScreenshotRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $data = $request->all();

        $this->assertTrue($data['full_page']);
        $this->assertFalse($data['block_ads']);
        $this->assertTrue($data['block_cookies']);
        $this->assertFalse($data['dark_mode']);
        $this->assertTrue($data['cache']);
        $this->assertTrue($data['stealth']);
        $this->assertFalse($data['proxy_pool']);
        $this->assertTrue($data['mobile']);
        $this->assertTrue($data['touch']);
        $this->assertFalse($data['landscape']);
        $this->assertTrue($data['transparent']);
        $this->assertFalse($data['disable_js']);
        $this->assertFalse($data['prefer_css_page_size']);
    }

    public function test_wait_for_selector_is_valid_input(): void
    {
        Config::set('screenshot.limits', [
            'max_width' => 3840,
            'max_height' => 2160,
            'timeout' => 30,
            'max_delay' => 10000,
            'max_css_length' => 50000,
            'max_js_length' => 50000,
        ]);

        $request = new CaptureScreenshotRequest;
        $validator = Validator::make([
            'url' => 'https://example.com',
            'wait_for_selector' => '#app-ready',
            'device_scale_factor' => 2,
            'pdf_format' => 'letter',
            'pdf_scale' => 0.75,
        ], $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame('#app-ready', $validator->validated()['wait_for_selector']);
        $this->assertSame(2, $validator->validated()['device_scale_factor']);
        $this->assertSame('letter', $validator->validated()['pdf_format']);
        $this->assertSame(0.75, $validator->validated()['pdf_scale']);
    }
}
