# Captura App

This directory contains Captura's Laravel API. The repository-level README has
the product overview, Docker setup, API examples, and public parameter list.

## Local Checks

Run PHP checks from this directory:

```bash
./vendor/bin/pint --dirty --test
php artisan test --testsuite=Unit,Feature
```

The Puppeteer worker lives in `screenshot-worker/`:

```bash
cd screenshot-worker
npm run build
```

Local PHP 8.5 may print Laravel vendor deprecation warnings for
`PDO::MYSQL_ATTR_SSL_CA`; treat those as noise when the test exit code is
successful.
