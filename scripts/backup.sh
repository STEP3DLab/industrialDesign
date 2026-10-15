#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
backup_dir="backups/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"
docker compose exec -T --user www-data web php admin/cli/maintenance.php --enable
docker compose stop cron
cleanup() {
  docker compose exec -T --user www-data web php admin/cli/maintenance.php --disable
  docker compose start cron
}
trap cleanup EXIT
# Allow in-flight requests to finish before the database/file snapshot.
sleep 10
docker compose exec -T db pg_dump -U moodle -d moodle -Fc > "$backup_dir/database.dump"
docker compose exec -T web tar -czf - -C /var/moodledata . > "$backup_dir/moodledata.tar.gz"
git rev-parse HEAD > "$backup_dir/source-commit.txt"
cp .env "$backup_dir/environment.env"
printf '%s\n' "Backup saved to $backup_dir. Store a protected off-server copy, including secrets separately."
