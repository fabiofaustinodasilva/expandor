#!/usr/bin/env bash
# Expandor — backup (banco + storage + .env)
# Uso:
#   cd /var/www/expandor && bash deploy/backup.sh
# Variáveis opcionais: BACKUP_DIR, KEEP_DAYS
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
BACKUP_ROOT="${BACKUP_DIR:-${APP_DIR}/storage/backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%Y-%m-%d-%H%M)"
WORK_DIR="${BACKUP_ROOT}/tmp-${STAMP}"
ARCHIVE="${BACKUP_ROOT}/backup-${STAMP}.zip"

log()  { echo -e "\n==> $*"; }
ok()   { echo "  [OK] $*"; }
die()  { echo "  [ERROR] $*" >&2; exit 1; }

load_env() {
  cd "${APP_DIR}"
  [[ -f .env ]] || die ".env não encontrado"
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^(DB_|APP_)=|^DB_|^APP_' .env | sed 's/\r$//' || true)
  set +a
}

backup_database() {
  local out="${WORK_DIR}/database.sql"
  local conn="${DB_CONNECTION:-mysql}"

  if [[ "${conn}" == "sqlite" ]]; then
    local dbfile="${DB_DATABASE:-database/database.sqlite}"
    if [[ "${dbfile}" != /* ]]; then
      dbfile="${APP_DIR}/${dbfile}"
    fi
    [[ -f "${dbfile}" ]] || die "SQLite não encontrado: ${dbfile}"
    cp -a "${dbfile}" "${WORK_DIR}/database.sqlite"
    ok "SQLite copiado"
    return
  fi

  local host="${DB_HOST:-127.0.0.1}"
  local port="${DB_PORT:-3306}"
  local name="${DB_DATABASE:-}"
  local user="${DB_USERNAME:-}"
  local pass="${DB_PASSWORD:-}"

  [[ -n "${name}" && -n "${user}" ]] || die "DB_DATABASE/DB_USERNAME ausentes no .env"

  if command -v mysqldump >/dev/null 2>&1; then
    MYSQL_PWD="${pass}" mysqldump \
      -h "${host}" -P "${port}" -u "${user}" \
      --single-transaction --routines --triggers --databases "${name}" \
      > "${out}"
    ok "Dump MySQL/MariaDB: ${name}"
  else
    die "mysqldump não instalado"
  fi
}

backup_storage_and_env() {
  mkdir -p "${WORK_DIR}/storage"
  # App uploads / public disk — não inclui logs gigantes se desejar; aqui inclui app/
  if [[ -d "${APP_DIR}/storage/app" ]]; then
    tar -C "${APP_DIR}/storage" -cf "${WORK_DIR}/storage-app.tar" app
    ok "storage/app empacotado"
  fi
  cp -a "${APP_DIR}/.env" "${WORK_DIR}/env"
  ok ".env copiado"
  echo "${STAMP}" > "${WORK_DIR}/BACKUP_META.txt"
  php -r "echo 'APP=' . (getenv('APP_NAME') ?: 'Expandor') . PHP_EOL;" 2>/dev/null || true
}

compress_and_cleanup() {
  mkdir -p "${BACKUP_ROOT}"
  (
    cd "${WORK_DIR}"
    zip -rq "${ARCHIVE}" .
  )
  rm -rf "${WORK_DIR}"
  ok "Arquivo: ${ARCHIVE}"

  if [[ "${KEEP_DAYS}" =~ ^[0-9]+$ ]] && [[ "${KEEP_DAYS}" -gt 0 ]]; then
    find "${BACKUP_ROOT}" -maxdepth 1 -type f -name 'backup-*.zip' -mtime "+${KEEP_DAYS}" -delete || true
    ok "Limpeza de backups > ${KEEP_DAYS} dias"
  fi
}

main() {
  load_env
  mkdir -p "${WORK_DIR}"
  log "Backup ${STAMP}"
  backup_database
  backup_storage_and_env
  compress_and_cleanup
  echo
  echo "STATUS=OK"
  echo "ARCHIVE=${ARCHIVE}"
}

main "$@"
