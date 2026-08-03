#!/usr/bin/env bash
# Expandor — healthcheck de infraestrutura
# Exit codes: 0=OK, 1=WARNING, 2=ERROR
set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PHP_VERSION="${PHP_VERSION:-8.4}"
STATUS="OK"
WARNINGS=0
ERRORS=0

pass() { echo "  [OK] $*"; }
warn() { echo "  [WARNING] $*"; WARNINGS=$((WARNINGS + 1)); STATUS="WARNING"; }
fail() { echo "  [ERROR] $*"; ERRORS=$((ERRORS + 1)); STATUS="ERROR"; }

cd "${APP_DIR}" || { echo "STATUS=ERROR"; exit 2; }

echo "== Expandor healthcheck =="
echo "APP_DIR=${APP_DIR}"
echo

# .env / APP_KEY
if [[ ! -f .env ]]; then
  fail ".env ausente"
else
  pass ".env presente"
  if grep -q '^APP_DEBUG=true' .env; then
    warn "APP_DEBUG=true (não recomendado em produção)"
  fi
  if grep -q '^APP_ENV=local' .env; then
    warn "APP_ENV=local"
  fi
  if ! grep -q '^APP_KEY=base64:' .env; then
    fail "APP_KEY inválida/ausente"
  else
    pass "APP_KEY definida"
  fi
fi

# PHP
if command -v php >/dev/null 2>&1; then
  pass "PHP $(php -r 'echo PHP_VERSION;')"
else
  fail "PHP não encontrado"
fi

# Banco
if [[ -f artisan ]]; then
  if php artisan db:show --json >/dev/null 2>&1 || php artisan migrate:status >/dev/null 2>&1; then
    pass "Banco acessível (artisan)"
  else
    # fallback mysql ping via .env
    if php -r '
      require "vendor/autoload.php";
      $app = require "bootstrap/app.php";
      $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
      try { DB::connection()->getPdo(); echo "ok"; } catch (Throwable $e) { exit(1); }
    ' >/dev/null 2>&1; then
      pass "Banco acessível (PDO)"
    else
      fail "Banco inacessível"
    fi
  fi
else
  fail "artisan ausente"
fi

# Redis
if command -v redis-cli >/dev/null 2>&1; then
  if redis-cli ping 2>/dev/null | grep -qi PONG; then
    pass "Redis PONG"
  else
    warn "Redis sem resposta (ok se CACHE/QUEUE não usam redis)"
  fi
else
  warn "redis-cli não instalado"
fi

# Queue config
if [[ -f .env ]]; then
  if grep -qE '^QUEUE_CONNECTION=sync' .env; then
    warn "QUEUE_CONNECTION=sync (sem worker assíncrono)"
  else
    pass "QUEUE_CONNECTION != sync"
  fi
fi

# Storage / permissões
if [[ -d storage/app/public ]]; then
  pass "storage/app/public existe"
else
  warn "storage/app/public ausente"
fi
if [[ -L public/storage ]] || [[ -d public/storage ]]; then
  pass "public/storage link/pasta ok"
else
  fail "public/storage ausente — rode php artisan storage:link"
fi
if [[ -w storage/logs ]]; then
  pass "storage/logs gravável"
else
  fail "storage/logs sem permissão de escrita"
fi

# Disco
DISK_USE=$(df -P "${APP_DIR}" 2>/dev/null | awk 'NR==2{gsub(/%/,"",$5); print $5}')
if [[ -n "${DISK_USE}" ]]; then
  if [[ "${DISK_USE}" -ge 90 ]]; then
    fail "Disco ${DISK_USE}% usado"
  elif [[ "${DISK_USE}" -ge 80 ]]; then
    warn "Disco ${DISK_USE}% usado"
  else
    pass "Disco ${DISK_USE}% usado"
  fi
fi

# Scheduler (cron)
if crontab -l 2>/dev/null | grep -q 'artisan schedule:run'; then
  pass "Cron schedule:run encontrado (usuário atual)"
elif crontab -u www-data -l 2>/dev/null | grep -q 'artisan schedule:run'; then
  pass "Cron schedule:run encontrado (www-data)"
else
  warn "Cron schedule:run não encontrado — ver deploy/cron.txt"
fi

# Supervisor / queue worker
if command -v supervisorctl >/dev/null 2>&1; then
  if supervisorctl status 2>/dev/null | grep -qiE 'expandor|RUNNING'; then
    pass "Supervisor com processo RUNNING"
  else
    warn "Supervisor sem worker Expandor RUNNING"
  fi
else
  warn "supervisorctl não disponível"
fi

# PHP-FPM
if systemctl is-active --quiet "php${PHP_VERSION}-fpm" 2>/dev/null; then
  pass "php${PHP_VERSION}-fpm ativo"
elif systemctl is-active --quiet php-fpm 2>/dev/null; then
  pass "php-fpm ativo"
else
  warn "PHP-FPM não detectado como ativo"
fi

# Nginx
if systemctl is-active --quiet nginx 2>/dev/null; then
  pass "nginx ativo"
else
  warn "nginx não ativo"
fi

echo
echo "STATUS=${STATUS}"
if [[ "${ERRORS}" -gt 0 ]]; then
  exit 2
fi
if [[ "${WARNINGS}" -gt 0 ]]; then
  exit 1
fi
exit 0
