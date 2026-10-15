#!/bin/sh
set -eu
trap 'exit 0' TERM INT
while :; do
  php /var/www/html/admin/cli/cron.php || true
  sleep 60 & wait $!
done
