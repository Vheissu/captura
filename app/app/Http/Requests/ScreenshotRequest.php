<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ScreenshotRequest extends FormRequest
{
    private const BOOLEAN_FIELDS = [
        'full_page',
        'block_ads',
        'block_cookies',
        'dark_mode',
        'cache',
        'stealth',
        'proxy_pool',
        'mobile',
        'touch',
        'landscape',
        'transparent',
        'disable_js',
        'prefer_css_page_size',
    ];

    private const UA_PRESETS = [
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
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function normalizeScreenshotBooleans(array $data): array
    {
        foreach (self::BOOLEAN_FIELDS as $key) {
            if (! array_key_exists($key, $data) || ! is_string($data[$key])) {
                continue;
            }

            $value = filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    protected function screenshotRules(
        string $prefix = '',
        bool $includeResponse = false,
        bool $includeWebhook = false,
    ): array {
        $limits = config('screenshot.limits');
        $field = static fn (string $name): string => $prefix.$name;

        $rules = [
            $field('url') => ['required', 'url', 'max:2048'],
            $field('format') => ['sometimes', Rule::in(['png', 'jpg', 'jpeg', 'webp', 'pdf'])],
            $field('quality') => ['sometimes', 'integer', 'min:1', 'max:100'],
            $field('width') => ['sometimes', 'integer', 'min:100', 'max:'.$limits['max_width']],
            $field('height') => ['sometimes', 'integer', 'min:100', 'max:'.$limits['max_height']],
            $field('device_scale_factor') => ['sometimes', 'numeric', 'min:0.1', 'max:4'],
            $field('mobile') => ['sometimes', 'boolean'],
            $field('touch') => ['sometimes', 'boolean'],
            $field('landscape') => ['sometimes', 'boolean'],
            $field('full_page') => ['sometimes', 'boolean'],
            $field('selector') => ['sometimes', 'string', 'max:500'],
            $field('wait_for_selector') => ['sometimes', 'string', 'max:500'],
            $field('delay') => ['sometimes', 'integer', 'min:0', 'max:'.$limits['max_delay']],
            $field('wait_until') => ['sometimes', Rule::in(['load', 'domcontentloaded', 'networkidle'])],
            $field('timeout') => ['sometimes', 'integer', 'min:1', 'max:'.$limits['timeout']],
            $field('block_ads') => ['sometimes', 'boolean'],
            $field('block_cookies') => ['sometimes', 'boolean'],
            $field('dark_mode') => ['sometimes', 'boolean'],
            $field('transparent') => ['sometimes', 'boolean'],
            $field('disable_js') => ['sometimes', 'boolean'],
            $field('css') => ['sometimes', 'string', 'max:'.$limits['max_css_length']],
            $field('js') => ['sometimes', 'string', 'max:'.$limits['max_js_length']],
            $field('hide_selectors') => ['sometimes', 'string', 'max:1000'],
            $field('pdf_format') => ['sometimes', Rule::in([
                'letter',
                'legal',
                'tabloid',
                'ledger',
                'a0',
                'a1',
                'a2',
                'a3',
                'a4',
                'a5',
                'a6',
            ])],
            $field('pdf_scale') => ['sometimes', 'numeric', 'min:0.1', 'max:2'],
            $field('prefer_css_page_size') => ['sometimes', 'boolean'],
            $field('ua_preset') => ['sometimes', Rule::in(self::UA_PRESETS)],
            $field('user_agent') => ['sometimes', 'string', 'max:500'],
            $field('headers') => ['sometimes', 'json'],
            $field('stealth') => ['sometimes', 'boolean'],
            $field('proxy') => ['sometimes', 'string', 'max:2048'],
            $field('proxy_pool') => ['sometimes', 'boolean'],
            $field('proxy_strategy') => ['sometimes', Rule::in(['random', 'round_robin'])],
            $field('locale') => ['sometimes', 'string', 'max:50'],
            $field('timezone') => ['sometimes', 'string', 'max:100'],
            $field('cache') => ['sometimes', 'boolean'],
        ];

        if ($includeResponse) {
            $rules[$field('response')] = ['sometimes', Rule::in(['image', 'json'])];
        }

        if ($includeWebhook) {
            $rules[$field('webhook_url')] = ['sometimes', 'url', 'max:2048'];
        }

        return $rules;
    }
}
