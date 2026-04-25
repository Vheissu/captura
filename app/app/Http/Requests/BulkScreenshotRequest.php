<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ScreenshotParams;

class BulkScreenshotRequest extends ScreenshotRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();
        if (! isset($data['items']) || ! is_array($data['items'])) {
            return;
        }

        foreach ($data['items'] as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $data['items'][$index] = $this->normalizeScreenshotBooleans($item);
        }

        $this->replace($data);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            ...$this->screenshotRules(prefix: 'items.*.', includeWebhook: true),
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
}
