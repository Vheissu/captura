<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ScreenshotParams;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsyncScreenshotRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->replace($this->normalizeBooleans(
            $this->all(),
            ['full_page', 'block_ads', 'block_cookies', 'dark_mode', 'cache', 'stealth', 'proxy_pool']
        ));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $limits = config('screenshot.limits');

        return [
            'url' => ['required', 'url', 'max:2048'],
            'format' => ['sometimes', Rule::in(['png', 'jpg', 'jpeg', 'webp', 'pdf'])],
            'quality' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'width' => ['sometimes', 'integer', 'min:100', 'max:' . $limits['max_width']],
            'height' => ['sometimes', 'integer', 'min:100', 'max:' . $limits['max_height']],
            'full_page' => ['sometimes', 'boolean'],
            'selector' => ['sometimes', 'string', 'max:500'],
            'delay' => ['sometimes', 'integer', 'min:0', 'max:' . $limits['max_delay']],
            'wait_until' => ['sometimes', Rule::in(['load', 'domcontentloaded', 'networkidle'])],
            'timeout' => ['sometimes', 'integer', 'min:1', 'max:' . $limits['timeout']],
            'block_ads' => ['sometimes', 'boolean'],
            'block_cookies' => ['sometimes', 'boolean'],
            'dark_mode' => ['sometimes', 'boolean'],
            'css' => ['sometimes', 'string', 'max:' . $limits['max_css_length']],
            'js' => ['sometimes', 'string', 'max:' . $limits['max_js_length']],
            'hide_selectors' => ['sometimes', 'string', 'max:1000'],
            'ua_preset' => ['sometimes', Rule::in([
                'chrome-mac',
                'chrome-win',
                'safari-mac',
                'iphone',
                'firefox-win',
                'region-us',
                'region-uk',
                'region-eu',
                'region-au',
                'region-jp',
                'rotate',
                'random',
                'custom',
            ])],
            'user_agent' => ['sometimes', 'string', 'max:500'],
            'headers' => ['sometimes', 'json'],
            'stealth' => ['sometimes', 'boolean'],
            'proxy' => ['sometimes', 'string', 'max:2048'],
            'proxy_pool' => ['sometimes', 'boolean'],
            'proxy_strategy' => ['sometimes', Rule::in(['random', 'round_robin'])],
            'locale' => ['sometimes', 'string', 'max:50'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'cache' => ['sometimes', 'boolean'],
            'webhook_url' => ['sometimes', 'url', 'max:2048'],
        ];
    }

    public function toParams(): ScreenshotParams
    {
        return ScreenshotParams::fromRequest($this->validated());
    }

    private function normalizeBooleans(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            if ($data[$key] === 'true') {
                $data[$key] = 1;
            } elseif ($data[$key] === 'false') {
                $data[$key] = 0;
            }
        }

        return $data;
    }
}
