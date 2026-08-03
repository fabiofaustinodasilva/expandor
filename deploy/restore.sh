#!/usr/bin/env bash
# Expandor — restore a partir de backup-YYYY-MM-DD-HHMM.zip
# Uso:
#   cd /var/www/expandor && sudo bash deploy/restore.sh /caminho/backup-....zip
# ATENÇÃO: sobrescreve banco, storage/app e opcionalmente .env
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
ARCHIVE="${1:-}"
RESTORE_ENV="${RESTORE_ENV:-0}"
WEB_USER="${WEB_USER:-www-data}"
WORK_DIR="$(mktemp -d /tmp/expandor-restore-XXXXXX)"

log()  { echo -e "\n==> $*"; }
ok()   { echo "  [OK] $*"; }
die()  { echo "  [ERROR] $*" >&2; exit 1; }

cleanup() { rm -rf "${WORK_DIR}"; }
trap cleanup EXIT

[[ -n "${ARCHIVE}" ]] || die "Informe o ZIP: bash deploy/restore.sh /path/backup-YYYY-MM-DD-HHMM.zip"
[[ -f "${ARCHIVE}" ]] || die "Arquivo não encontrado: ${ARCHIVE}"
[[ -f "${APP_DIR}/artisan" ]] || die "APP_DIR inválido: ${APP_DIR}"

echo "Este processo PODE sobrescrever banco e uploads."
echo "Arquivo: ${ARCHIVE}"
read -r -p "Digite RESTORE para confirmar: " confirm
[[ "${confirm}" == "RESTORE" ]] || die "Cancelado."

log "Extraindo"
unzip -q "${ARCHIVE}" -d "${WORK_DIR}"

cd "${APP_DIR}"
php artisan down --retry=60 || true

# Carregar .env atual (ou do backup se RESTORE_ENV=1)
if [[ "${RESTORE_ENV}" == "1" && -f "${WORK_DIR}/env" ]]; then
  cp -a "${WORK_DIR}/env" "${APP_DIR}/.env"
  ok ".env restaurado do backup"
fi
[[ -f .env ]] || die ".env ausente"

set -a
# shellcheck disable=SC1091
source <(grep -E '^(DB_|APP_)' .env | sed 's/\r$//' || true)
set +a

restore_database() {
  local conn="${DB_CONNECTION:-mysql}"

  if [[ -f "${WORK_DIR}/database.sqlite" ]]; then
    local dbfile="${DB_DATABASE:-database/database.sqlite}"
    if [[ "${dbfile}" != /* ]]; then
      dbfile="${APP_DIR}/${dbfile}"
    fi
    mkdir -p "$(dirname "${dbfile}")"
    cp -a "${WORK_DIR}/database.sqlite" "${dbfile}"
    chown "${WEB_USER}:${WEB_USER}" "${dbfile}" 2>/dev/null || true
    ok "SQLite restaurado"
    return
  fi

  [[ -f "${WORK_DIR}/database.sql" ]] || die "database.sql ausente no backup"

  local host="${DB_HOST:-127.0.0.1}"
  local port="${DB_PORT:-3306}"
  local user="${DB_USERNAME:-}"
  local pass="${DB_PASSWORD:-}"

  MYSQL_PWD="${pass}" mysql -h "${host}" -P "${port}" -u "${user}" < "${WORK_DIR}/database.sql"
  ok "Banco restaurado via mysql"
}

restore_storage() {
  if [[ -f "${WORK_DIR}/storage-app.tar" ]]; then
    mkdir -p "${APP_DIR}/storage"
    tar -C "${APP_DIR}/storage" -xf "${WORK_DIR}/storage-app.tar"
    chown -R "${WEB_USER}:${WEB_USER}" "${APP_DIR}/storage/app"
    ok "storage/app restaurado"
  else
    warn_msg="storage-app.tar ausente — uploads não restaurados"
    echo "  [WARN] ${warn_msg}"
  fi
}

restore_database
restore_storage

php artisan storage:link --force 2>/dev/null || php artisan storage:link || true
php artisan optimize:clear
php artisan optimize
php artisan queue:restart || true
php artisan up

ok "Restore concluído"
echo "STATUS=OK"
