# Contributing to Captura

Thanks for contributing! This project is intentionally small and focused.

## Development setup

```bash
docker compose build
docker compose up -d

docker compose run --rm app php artisan migrate
```

## Code style

- PHP: PSR‑12 (use Laravel Pint)
- TypeScript: Prettier + ESLint

Run linters:
```bash
make lint
```

## Tests

```bash
make test
```

## Pull requests

- Keep changes focused and small.
- Update docs/tests if behavior changes.
- Run the test suite before opening a PR.
