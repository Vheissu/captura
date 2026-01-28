<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ScreenshotParams;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkScreenshotRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        if (!isset($data['items']) || !is_array($data['items'])) {
            return;
        }

        foreach ($data['items'] as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $data['items'][$index] = $this->normalizeBooleans(
                $item,
                ['full_page', 'block_ads', 'block_cookies', 'dark_mode', 'cache', 'stealth', 'proxy_pool']
            );
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $limits = config('screenshot.limits');

        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.url' => ['required', 'url', 'max:2048'],
            'items.*.format' => ['sometimes', Rule::in(['png', 'jpg', 'jpeg', 'webp', 'pdf'])],
            'items.*.quality' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'items.*.width' => ['sometimes', 'integer', 'min:100', 'max:' . $limits['max_width']],
            'items.*.height' => ['sometimes', 'integer', 'min:100', 'max:' . $limits['max_height']],
            'items.*.full_page' => ['sometimes', 'boolean'],
            'items.*.selector' => ['sometimes', 'string', 'max:500'],
            'items.*.delay' => ['sometimes', 'integer', 'min:0', 'max:' . $limits['max_delay']],
            'items.*.wait_until' => ['sometimes', Rule::in(['load', 'domcontentloaded', 'networkidle'])],
            'items.*.timeout' => ['sometimes', 'integer', 'min:1', 'max:' . $limits['timeout']],
            'items.*.block_ads' => ['sometimes', 'boolean'],
            'items.*.block_cookies' => ['sometimes', 'boolean'],
            'items.*.dark_mode' => ['sometimes', 'boolean'],
            'items.*.css' => ['sometimes', 'string', 'max:' . $limits['max_css_length']],
            'items.*.js' => ['sometimes', 'string', 'max:' . $limits['max_js_length']],
            'items.*.hide_selectors' => ['sometimes', 'string', 'max:1000'],
            'items.*.ua_preset' => ['sometimes', Rule::in([
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
            'items.*.user_agent' => ['sometimes', 'string', 'max:500'],
            'items.*.headers' => ['sometimes', 'json'],
            'items.*.stealth' => ['sometimes', 'boolean'],
            'items.*.proxy' => ['sometimes', 'string', 'max:2048'],
            'items.*.proxy_pool' => ['sometimes', 'boolean'],
            'items.*.proxy_strategy' => ['sometimes', Rule::in(['random', 'round_robin'])],
            'items.*.locale' => ['sometimes', 'string', 'max:50'],
            'items.*.timezone' => ['sometimes', 'string', 'max:100'],
            'items.*.cache' => ['sometimes', 'boolean'],
            'items.*.webhook_url' => ['sometimes', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array<int, ScreenshotParams>
     */
    public function toParamsArray(): array
    {
        $items = $this->validated('items', []);

        return array_map(static fn (array $item) => ScreenshotParams::fromRequest($item), $items);
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
