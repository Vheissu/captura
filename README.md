# Captura

Captura is a free, open-source, self-hosted screenshot API inspired by screenshotapi.net. It lets you capture screenshots of any public URL via a simple HTTP API without recurring SaaS fees.

![Captura captured itself](screenshot.png)

*Captura captured itself.*


## Highlights

- Sync, async, and bulk screenshot capture
- PNG, JPG, WebP, and PDF output
- Full-page, viewport, element, or crop-rectangle capture
- HiDPI, mobile, touch, landscape, and transparent-background rendering
- Screen/print CSS media and reduced-motion emulation for steadier captures
- Custom CSS/JS injection, external CSS/JS URLs, and request cookies
- Lazy-load scrolling, scroll-to-element, click-before-capture, and selector redaction/removal
- Cookie banner hiding, ad/tracker/chat blocking, and request/resource blocking
- Built-in caching and webhook notifications
- Stealth mode, proxy support, and region-aware identity presets

## Quick start

Prerequisites: Docker + Docker Compose.

```bash
docker compose build
docker compose up -d

docker compose run --rm app php artisan migrate
```

Open the docs page:
- http://localhost/
- http://localhost/docs

## API endpoints

- `GET /health`
- `GET|POST /api/screenshot`
- `POST /api/screenshot/async`
- `POST /api/screenshot/bulk`
- `GET /api/screenshot/{id}`

## Example usage

```bash
# Sync screenshot (image response)
curl "http://localhost/api/screenshot?url=https://example.com" --output example.png

# JSON response
curl "http://localhost/api/screenshot?url=https://example.com&response=json"

# Async screenshot
curl -X POST http://localhost/api/screenshot/async \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com","full_page":true}'

# Bulk screenshot
curl -X POST http://localhost/api/screenshot/bulk \
  -H "Content-Type: application/json" \
  -d '{"items":[{"url":"https://example.com"},{"url":"https://example.com","full_page":true}]}'

# Stealth + rotate identity + US region bundle
curl "http://localhost/api/screenshot?url=https://example.com&stealth=1&ua_preset=rotate&locale=en-US&timezone=America/New_York"

# Mobile HiDPI capture with touch emulation
curl "http://localhost/api/screenshot?url=https://example.com&width=390&height=844&device_scale_factor=3&mobile=1&touch=1&ua_preset=iphone" --output mobile.png

# Crop the top-left 1200x630 pixels for a share image
curl "http://localhost/api/screenshot?url=https://example.com&clip_x=0&clip_y=0&clip_width=1200&clip_height=630" --output crop.png

# Capture with print CSS and reduced motion
curl "http://localhost/api/screenshot?url=https://example.com&media=print&reduced_motion=1" --output print-css.png

# Trigger lazy-loaded content, click a tab, and blur private text
curl "http://localhost/api/screenshot?url=https://example.com&lazy_load=1&selector_to_click=.pricing-tab&blur_selector=.email" --output clean.png

# ScreenshotAPI-compatible aliases are accepted for easier migration
curl "http://localhost/api/screenshot?url=https://example.com&file_type=jpg&image_quality=90&retina=1&wait_for_event=networkidle&fresh=1" --output migrated.jpg

# Proxy pool round robin
curl "http://localhost/api/screenshot?url=https://example.com&proxy_pool=1&proxy_strategy=round_robin"
```

## Parameters

These are valid for `GET /api/screenshot` and `POST /api/screenshot` unless noted.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `url` | string | required | Target URL |
| `format` | string | `png` | `png`, `jpg`, `webp`, `pdf` |
| `quality` | int | `80` | JPG/WebP quality 1-100 |
| `width` | int | `1280` | Viewport width |
| `height` | int | `800` | Viewport height |
| `device_scale_factor` | float | `1` | Device pixel ratio / HiDPI scale, 0.1-4 |
| `retina` | bool | `false` | Alias that uses a 2x device scale unless `device_scale_factor` is set |
| `mobile` | bool | `false` | Emulate mobile viewport behavior |
| `touch` | bool | `false` | Enable touch-capable viewport emulation |
| `landscape` | bool | `false` | Emulate landscape orientation; also prints PDFs landscape |
| `clip_x` | float | `0` | Left edge of an image crop rectangle |
| `clip_y` | float | `0` | Top edge of an image crop rectangle |
| `clip_width` | float | `null` | Crop rectangle width for image formats |
| `clip_height` | float | `null` | Crop rectangle height for image formats |
| `full_page` | bool | `false` | Capture full page |
| `selector` | string | `null` | Capture a specific element |
| `wait_for_selector` | string | `null` | Wait for a selector before capture |
| `scroll_to_element` | string | `null` | Scroll a selector into view before capture |
| `adjust_top` | int | `null` | Scroll the viewport to a vertical offset before capture |
| `lazy_load` | bool | `false` | Scroll through the page to trigger lazy-loaded content before capture |
| `scroll_delay` | int | `250` | Delay between lazy-load scroll steps, in milliseconds |
| `selector_to_click` | string | `null` | Click an element before capture |
| `click_recursion` | int | `1` | Number of times to click `selector_to_click`, 1-10 |
| `delay` | int | `0` | Delay before capture (ms) |
| `wait_until` | string | `load` | `load`, `domcontentloaded`, `networkidle` |
| `timeout` | int | `30` | Timeout in seconds |
| `block_ads` | bool | `false` | Block ads |
| `block_cookies` | bool | `false` | Hide cookie banners |
| `block_tracking` | bool | `false` | Block common analytics/tracking requests |
| `block_chat_widgets` | bool | `false` | Block common chat widget scripts |
| `block_resources` | string | `null` | Comma-separated resource types to block, such as `image,font,script` |
| `block_specific_requests` | string | `null` | Comma-separated URL fragments to block before capture |
| `dark_mode` | bool | `false` | Prefer dark theme |
| `grayscale` | int | `0` | Apply grayscale filter intensity, 0-100 |
| `transparent` | bool | `false` | Preserve transparency instead of forcing a white background |
| `disable_js` | bool | `false` | Disable JavaScript before navigation |
| `media` | string | `null` | Emulate CSS media: `screen` or `print` |
| `reduced_motion` | bool | `false` | Request reduced-motion CSS behavior for steadier captures |
| `css` | string | `null` | Custom CSS to inject |
| `css_url` | string | `null` | External stylesheet URL to inject after navigation |
| `js` | string | `null` | Custom JS to execute |
| `js_url` | string | `null` | External script URL to execute after navigation |
| `hide_selectors` | string | `null` | Comma-separated selectors to hide |
| `remove_selectors` | string | `null` | Comma-separated selectors to remove from the DOM |
| `blur_selectors` | string | `null` | Comma-separated selectors to blur for redaction |
| `pdf_format` | string | `a4` | PDF paper format: `letter`, `legal`, `tabloid`, `ledger`, `a0`-`a6` |
| `pdf_scale` | float | `1` | PDF render scale, 0.1-2 |
| `prefer_css_page_size` | bool | `false` | Let CSS `@page` size override PDF paper format |
| `ua_preset` | string | `null` | See Identity presets below |
| `user_agent` | string | `null` | Custom UA string |
| `headers` | string | `null` | JSON object of headers |
| `cookies` | string | `null` | Cookie header string or JSON object/array to set before navigation |
| `accept_languages` | string | `null` | Alias for setting locale and `Accept-Language` |
| `locale` | string | `en-US` | Locale override (e.g. `en-US`) |
| `timezone` | string | `America/Los_Angeles` | IANA timezone |
| `stealth` | bool | `false` | Enable stealth mode (harder to detect) |
| `proxy` | string | `null` | Proxy URL (http[s]://user:pass@host:port) |
| `proxy_pool` | bool | `false` | Use configured proxy pool |
| `proxy_strategy` | string | `random` | `random` or `round_robin` |
| `response` | string | `image` | `image` or `json` |
| `cache` | bool | `true` | Use cached screenshot if available |
| `fresh` | bool | `false` | Bypass cache lookup and render a new capture |
| `webhook_url` | string | `null` | Async + bulk only: webhook to notify |

Migration aliases accepted by the API:
- `file_type` -> `format`
- `image_quality` -> `quality`
- `wait_for_event` -> `wait_until`
- `output` -> `response`
- `block_js` -> `disable_js`
- `no_cookie_banners` -> `block_cookies`
- `omit_background` -> `transparent`
- `remove_selector` -> `remove_selectors`
- `blur_selector` -> `blur_selectors`
- `enable_caching` -> `cache`

## Identity presets

Use `ua_preset` to set a realistic UA + headers + locale + timezone bundle. You can also pass `locale`/`timezone` explicitly to override.
The `iphone` preset also enables mobile viewport, touch input, and a HiDPI scale unless you override those fields.

- `chrome-mac`, `chrome-win`, `safari-mac`, `iphone`, `firefox-win`
- Region bundles: `region-us`, `region-uk`, `region-eu`, `region-au`, `region-jp`
- `rotate` or `random` to pick one of the configured presets

## Configuration

All configuration is handled via `app/.env`.

Defaults
- `SCREENSHOT_DEFAULT_WIDTH`, `SCREENSHOT_DEFAULT_HEIGHT`, `SCREENSHOT_DEFAULT_FORMAT`, `SCREENSHOT_DEFAULT_QUALITY`
- `SCREENSHOT_DEFAULT_STEALTH`, `SCREENSHOT_DEFAULT_LOCALE`, `SCREENSHOT_DEFAULT_TIMEZONE`
- `SCREENSHOT_DEFAULT_USER_AGENT` (empty by default to match the Chromium UA), `SCREENSHOT_DEFAULT_ACCEPT_LANGUAGE`, `SCREENSHOT_DEFAULT_ACCEPT` (leave empty unless you know you need it)

Limits
- `SCREENSHOT_MAX_WIDTH`, `SCREENSHOT_MAX_HEIGHT`, `SCREENSHOT_TIMEOUT`

Caching
- `SCREENSHOT_CACHE_ENABLED`, `SCREENSHOT_CACHE_TTL`

Storage
- `SCREENSHOT_STORAGE_PATH`, `SCREENSHOT_PUBLIC_URL`

Security
- `SCREENSHOT_RATE_LIMIT`
- `SCREENSHOT_API_KEY`
- `SCREENSHOT_BLOCKED_HOSTS`
- `SCREENSHOT_ALLOW_LOCALHOST`
- `SCREENSHOT_ALLOWED_HOSTS` (comma-separated allowlist; overrides blocklist/private IP checks)

When `SCREENSHOT_ALLOW_LOCALHOST=true`, the validator also allows `localhost`, `127.0.0.1`, `host.docker.internal`, and `nginx` (for self-capture inside Docker).

Queues
- `SCREENSHOT_QUEUE_NAME`, `SCREENSHOT_RESULT_QUEUE`

Proxy pool
- `SCREENSHOT_PROXY_POOL_ENABLED`
- `SCREENSHOT_PROXY_POOL` (comma-separated list)
- `SCREENSHOT_PROXY_STRATEGY` (`random` or `round_robin`)
- `SCREENSHOT_PROXY_CACHE_KEY`

Cleanup
- `SCREENSHOT_CLEANUP_AFTER`

## Notes on stealth and proxies

Some sites aggressively detect automation. Stealth mode and region-aware identity bundles reduce friction, but they are not guaranteed to bypass every detection system. For hard targets, use a residential proxy and align `locale`, `timezone`, and `ua_preset`.

## Services

`docker compose up` starts:
- Nginx (reverse proxy)
- Laravel (API)
- Redis (queues/cache)
- PostgreSQL (database)
- Node screenshot worker (Puppeteer)

## Project structure

- `app/` Laravel application
- `app/screenshot-worker/` Node.js worker (TypeScript + Puppeteer)
- `docker/` Dockerfiles and configs
- `docker-compose.yml` Local stack

## License

MIT
