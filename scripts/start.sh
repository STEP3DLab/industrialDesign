#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose config --quiet
docker compose build
docker compose up -d db
docker compose --profile setup run --rm setup
docker compose run --rm --user www-data web php /opt/lab/scripts/seed.php
docker compose up -d web cron
printf '%s\n' 'Moodle installed. The admin password is in secrets/admin_password (never commit it).'
