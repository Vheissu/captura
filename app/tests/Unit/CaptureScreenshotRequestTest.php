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
            'no_cookie_banners' => 'true',
            'block_tracking' => 'yes',
            'block_chat_widgets' => 'on',
            'dark_mode' => 'off',
            'cache' => '1',
            'fresh' => '0',
            'enable_caching' => 'yes',
            'stealth' => 'on',
            'proxy_pool' => 'no',
            'mobile' => 'yes',
            'touch' => 'true',
            'landscape' => 'off',
            'transparent' => '1',
            'omit_background' => 'false',
            'disable_js' => '0',
            'block_js' => 'no',
            'prefer_css_page_size' => 'false',
            'reduced_motion' => 'yes',
            'retina' => 'true',
            'lazy_load' => 'on',
        ]);

        $method = new \ReflectionMethod(CaptureScreenshotRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $data = $request->all();

        $this->assertTrue($data['full_page']);
        $this->assertFalse($data['block_ads']);
        $this->assertTrue($data['block_cookies']);
        $this->assertTrue($data['no_cookie_banners']);
        $this->assertTrue($data['block_tracking']);
        $this->assertTrue($data['block_chat_widgets']);
        $this->assertFalse($data['dark_mode']);
        $this->assertTrue($data['cache']);
        $this->assertFalse($data['fresh']);
        $this->assertTrue($data['enable_caching']);
        $this->assertTrue($data['stealth']);
        $this->assertFalse($data['proxy_pool']);
        $this->assertTrue($data['mobile']);
        $this->assertTrue($data['touch']);
        $this->assertFalse($data['landscape']);
        $this->assertTrue($data['transparent']);
        $this->assertFalse($data['omit_background']);
        $this->assertFalse($data['disable_js']);
        $this->assertFalse($data['block_js']);
        $this->assertFalse($data['prefer_css_page_size']);
        $this->assertTrue($data['reduced_motion']);
        $this->assertTrue($data['retina']);
        $this->assertTrue($data['lazy_load']);
    }

    public function test_rendering_controls_are_valid_input(): void
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
            'clip_x' => 10,
            'clip_y' => 20,
            'clip_width' => 640,
            'clip_height' => 360,
            'scroll_to_element' => '#details',
            'selector_to_click' => '#open',
            'click_recursion' => 2,
            'adjust_top' => 500,
            'lazy_load' => true,
            'scroll_delay' => 50,
            'media' => 'print',
            'reduced_motion' => true,
            'css_url' => 'https://cdn.example.com/capture.css',
            'js_url' => 'https://cdn.example.com/capture.js',
            'remove_selector' => '.remove-me',
            'blur_selector' => '.secret',
            'block_resources' => 'image,font',
            'block_specific_requests' => 'analytics.js,chat.js',
            'cookies' => 'session=abc',
            'accept_languages' => 'en-AU,en;q=0.9',
            'grayscale' => 60,
        ], $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertSame('#app-ready', $validator->validated()['wait_for_selector']);
        $this->assertSame(2, $validator->validated()['device_scale_factor']);
        $this->assertSame('letter', $validator->validated()['pdf_format']);
        $this->assertSame(0.75, $validator->validated()['pdf_scale']);
        $this->assertSame(10, $validator->validated()['clip_x']);
        $this->assertSame(20, $validator->validated()['clip_y']);
        $this->assertSame(640, $validator->validated()['clip_width']);
        $this->assertSame(360, $validator->validated()['clip_height']);
        $this->assertSame('#details', $validator->validated()['scroll_to_element']);
        $this->assertSame('#open', $validator->validated()['selector_to_click']);
        $this->assertSame(2, $validator->validated()['click_recursion']);
        $this->assertSame(500, $validator->validated()['adjust_top']);
        $this->assertTrue($validator->validated()['lazy_load']);
        $this->assertSame(50, $validator->validated()['scroll_delay']);
        $this->assertSame('print', $validator->validated()['media']);
        $this->assertTrue($validator->validated()['reduced_motion']);
        $this->assertSame('https://cdn.example.com/capture.css', $validator->validated()['css_url']);
        $this->assertSame('https://cdn.example.com/capture.js', $validator->validated()['js_url']);
        $this->assertSame('.remove-me', $validator->validated()['remove_selector']);
        $this->assertSame('.secret', $validator->validated()['blur_selector']);
        $this->assertSame('image,font', $validator->validated()['block_resources']);
        $this->assertSame('analytics.js,chat.js', $validator->validated()['block_specific_requests']);
        $this->assertSame('session=abc', $validator->validated()['cookies']);
        $this->assertSame('en-AU,en;q=0.9', $validator->validated()['accept_languages']);
        $this->assertSame(60, $validator->validated()['grayscale']);
    }

    public function test_clip_coordinates_require_clip_dimensions(): void
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
            'clip_x' => 10,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clip_width', $validator->errors()->messages());
        $this->assertArrayHasKey('clip_height', $validator->errors()->messages());
    }
}
