# Captura Agent Guide

Captura is a self-hosted screenshot API. The Laravel 11 API lives in `app/`;
the Node/Puppeteer renderer lives in `app/screenshot-worker/`; Docker and Nginx
wire the local stack together from the repo root.

## Canonical Guidance

- This file is the shared source of truth for agent instructions.
- Keep `CLAUDE.md` as a thin wrapper that points back here. Do not duplicate
  the same policy in both files.
- If a new repo rule is learned during implementation, add it here first.

## Working Style

- Inspect the real owning path before changing behavior. For screenshot
  parameters the path is: request validation -> `ScreenshotParams` -> queued job
  payload -> worker TypeScript type -> renderer -> README/docs UI -> tests.
- Prefer native Laravel, Redis, and Puppeteer APIs over home-grown browser or
  parsing logic. Check the official docs when adding renderer behavior:
  `https://pptr.dev/api/` and `https://laravel.com/docs/11.x/`.
- Keep API additions small, explicit, validated, and documented. Every public
  parameter should have validation, DTO storage, worker support where relevant,
  README coverage, docs-page coverage, and at least focused unit coverage.
- Preserve user work in this repo. Do not revert untracked files or unrelated
  edits unless explicitly asked.

## Screenshot API Rules

- Keep SSRF protections intact. Do not weaken `UrlValidator`, localhost rules,
  blocked hosts, private-IP checks, or allowlist behavior to make a test pass.
- Treat CSS and JS injection as intentional user-facing features, but keep size
  limits and validation in the request layer.
- Use Puppeteer options directly where possible: viewport options for DPR,
  mobile, touch, and orientation; `setJavaScriptEnabled` before navigation;
  screenshot/PDF options for transparency and PDF output.
- Do not make the PHP API guess at browser behavior that the worker can express
  directly. The PHP side validates and serializes; the worker renders.
- Keep cache hashes sensitive to every render-affecting option, but ignore
  delivery-only fields such as webhook URL and the cache flag itself.

## Verification

- For PHP changes, run from `app/`: `./vendor/bin/pint --dirty --test` and
  `php artisan test --testsuite=Unit,Feature`.
- The test suite should run from the host without Docker by using SQLite
  in-memory in `phpunit.xml`.
- Local PHP 8.5 may still report Laravel vendor deprecations around
  `PDO::MYSQL_ATTR_SSL_CA`; do not treat those as app failures unless the exit
  code fails or Captura code is the trigger.
- For worker changes, run from `app/screenshot-worker/`: `npm run build`.
- For renderer behavior or Docker wiring changes, prefer an end-to-end Docker
  capture check after unit/build checks.

## Docs UI

- The docs page at `app/resources/views/docs.blade.php` is a real product
  surface, not throwaway demo code.
- Keep it usable and parameter-complete when adding API features.
- Avoid adding visible implementation trivia to the UI. Show controls and
  outcomes; leave internal mechanics to README or this file.
