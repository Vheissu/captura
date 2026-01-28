#!/bin/sh
set -e

mkdir -p /app/storage/app/screenshots
chown -R pptruser:pptruser /app/storage/app

exec gosu pptruser "$@"
