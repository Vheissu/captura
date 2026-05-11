<?php

declare(strict_types=1);

return [
    'defaults' => [
        'width' => (int) env('SCREENSHOT_DEFAULT_WIDTH', 1280),
        'height' => (int) env('SCREENSHOT_DEFAULT_HEIGHT', 800),
        'format' => env('SCREENSHOT_DEFAULT_FORMAT', 'png'),
        'quality' => (int) env('SCREENSHOT_DEFAULT_QUALITY', 80),
        'full_page' => false,
        'stealth' => (bool) env('SCREENSHOT_DEFAULT_STEALTH', false),
        'locale' => env('SCREENSHOT_DEFAULT_LOCALE', 'en-US'),
        'timezone' => env('SCREENSHOT_DEFAULT_TIMEZONE', 'America/Los_Angeles'),
        'user_agent' => env('SCREENSHOT_DEFAULT_USER_AGENT', ''),
        'headers' => array_filter([
            'Accept-Language' => env('SCREENSHOT_DEFAULT_ACCEPT_LANGUAGE', 'en-US,en;q=0.9'),
            'Accept' => env('SCREENSHOT_DEFAULT_ACCEPT', ''),
            'Upgrade-Insecure-Requests' => '1',
        ], static fn (?string $value) => $value !== null && $value !== ''),
    ],

    'user_agent_presets' => [
        'chrome-mac' => [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ],
        'chrome-win' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ],
        'safari-mac' => [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'headers' => [],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ],
        'iphone' => [
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'headers' => [
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
            'device_scale_factor' => 3,
            'mobile' => true,
            'touch' => true,
        ],
        'firefox-win' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            ],
            'locale' => 'en-US',
            'timezone' => 'America/Los_Angeles',
        ],
        'region-us' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
            'locale' => 'en-US',
            'timezone' => 'America/New_York',
        ],
        'region-uk' => [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
            'headers' => [
                'Accept-Language' => 'en-GB,en;q=0.9',
            ],
            'locale' => 'en-GB',
            'timezone' => 'Europe/London',
        ],
        'region-eu' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
            ],
            'locale' => 'de-DE',
            'timezone' => 'Europe/Berlin',
        ],
        'region-au' => [
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept-Language' => 'en-AU,en;q=0.9',
            ],
            'locale' => 'en-AU',
            'timezone' => 'Australia/Sydney',
        ],
        'region-jp' => [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers' => [
                'Accept-Language' => 'ja-JP,ja;q=0.9,en;q=0.8',
            ],
            'locale' => 'ja-JP',
            'timezone' => 'Asia/Tokyo',
        ],
    ],

    'limits' => [
        'max_width' => (int) env('SCREENSHOT_MAX_WIDTH', 3840),
        'max_height' => (int) env('SCREENSHOT_MAX_HEIGHT', 2160),
        'timeout' => (int) env('SCREENSHOT_TIMEOUT', 30),
        'max_delay' => 10000,
        'max_scroll_offset' => 200000,
        'max_css_length' => 50000,
        'max_js_length' => 50000,
    ],

    'cache' => [
        'enabled' => (bool) env('SCREENSHOT_CACHE_ENABLED', true),
        'ttl' => (int) env('SCREENSHOT_CACHE_TTL', 3600),
    ],

    'storage' => [
        'disk' => 'screenshots',
        'path' => env('SCREENSHOT_STORAGE_PATH', 'screenshots'),
        'public_url' => env('SCREENSHOT_PUBLIC_URL'),
    ],

    'queue' => [
        'name' => env('SCREENSHOT_QUEUE_NAME', 'screenshots'),
        'result_queue' => env('SCREENSHOT_RESULT_QUEUE', 'screenshot-results'),
    ],

    'proxy' => [
        'enabled' => (bool) env('SCREENSHOT_PROXY_POOL_ENABLED', false),
        'pool' => array_filter(array_map('trim', explode(',', env('SCREENSHOT_PROXY_POOL', '')))),
        'strategy' => env('SCREENSHOT_PROXY_STRATEGY', 'random'),
        'cache_key' => env('SCREENSHOT_PROXY_CACHE_KEY', 'screenshot:proxy:rr'),
    ],

    'worker' => [
        'heartbeat_key' => env('SCREENSHOT_WORKER_HEARTBEAT_KEY', 'screenshot-worker:heartbeat'),
        'heartbeat_ttl' => (int) env('SCREENSHOT_WORKER_HEARTBEAT_TTL', 30),
    ],

    'security' => [
        'api_key' => env('SCREENSHOT_API_KEY'),
        'rate_limit' => (int) env('SCREENSHOT_RATE_LIMIT', 60),
        'allow_localhost' => (bool) env('SCREENSHOT_ALLOW_LOCALHOST', false),
        'allowed_hosts' => array_filter(
            explode(',', env('SCREENSHOT_ALLOWED_HOSTS', ''))
        ),
        'blocked_hosts' => array_filter(
            explode(',', env('SCREENSHOT_BLOCKED_HOSTS', 'localhost,127.0.0.1'))
        ),
    ],

    'cleanup' => [
        'after_hours' => (int) env('SCREENSHOT_CLEANUP_AFTER', 24),
    ],
];
