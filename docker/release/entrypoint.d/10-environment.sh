#!/bin/sh

set -eu

source_file="${APP_ENV_FILE:-/run/secrets/sinterklaas.env}"
target_file=/var/www/html/.env
temporary_file="${target_file}.tmp"

if [ ! -f "$source_file" ]; then
    echo "Missing runtime environment file: $source_file" >&2
    exit 1
fi

if ! grep -q '^APP_KEY=.' "$source_file"; then
    echo "Runtime environment file is missing APP_KEY: $source_file" >&2
    exit 1
fi

cp "$source_file" "$temporary_file"
chown www-data:www-data "$temporary_file"
chmod 640 "$temporary_file"
mv -f "$temporary_file" "$target_file"
