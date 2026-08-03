#!/usr/bin/env bash
# Expandor — atualização sem apagar uploads (storage/)
# Uso:
#   cd /var/www/expandor && sudo bash deploy/update.sh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WEB_USER="${WEB_USER:-www-data}"
BRANCH="${BRANCH:-}"

log()  { echo -e "\n==> $*"; }
ok()   { echo "  [OK] $*"; }
die()  { echo "  [ERROR] $*" >&2; exit 1; }

cd "${APP_DIR}"
[[ -f artisan ]] || die "artisan não encontrado em ${APP_DIR}"

log "Modo manutenção"
php artisan down --retry=60 || true

# Preservar storage e .env — nunca remover essas pastas/arquivos
[[ -f .env ]] || die ".env ausente — abortando update"
[[ -d storage ]] || die "storage/ ausente — abortando update"

log "git pull"
if [[ -n "${BRANCH}" ]]; then
  git fetch --all --prune
  git checkout "${BRANCH}"
  git pull --ff-only origin "${BRANCH}"
else
  git pull --ff-only
fi

log "composer install --no-dev"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "npm build"
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

log "migrate + optimize"
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

log "Reiniciando filas (supervisor)"
php artisan queue:restart || true
if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl reread || true
  supervisorctl update || true
  supervisorctl restart expandor-worker:* 2>/dev/null \
    || supervisorctl restart all 2>/dev/null \
    || true
fi

chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache 2>/dev/null || true

log "Saindo do modo manutenção"
php artisan up

ok "Update concluído — storage/ e .env preservados"
