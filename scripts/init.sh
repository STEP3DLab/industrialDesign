#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
command -v openssl >/dev/null || { echo 'Install openssl first.' >&2; exit 1; }
umask 077
mkdir -p secrets
for name in db_password admin_password upgrade_key; do
  if [ ! -s "secrets/$name" ]; then
    { printf 'Aa1!'; openssl rand -hex 32; } > "secrets/$name"
  fi
  # Docker Compose file secrets are bind mounts. Container www-data needs read access;
  # the containing host directory remains mode 0700.
  chmod 0444 "secrets/$name"
done
chmod 0700 secrets
[ -f .env ] || cp .env.example .env
printf '%s\n' 'Configuration prepared. Set .env, then run: bash scripts/start.sh'
