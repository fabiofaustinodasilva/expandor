#!/usr/bin/env bash
# Expandor — instalação em Ubuntu 24.04 (homologação / produção)
# Uso (como root):
#   cd /var/www/expandor && bash deploy/install.sh
set -euo pipefail

APP_NAME="Expandor"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WEB_USER="${WEB_USER:-www-data}"
PHP_VERSION="${PHP_VERSION:-8.4}"
SKIP_APT="${SKIP_APT:-0}"

log()  { echo -e "\n==> $*"; }
ok()   { echo "  [OK] $*"; }
warn() { echo "  [WARN] $*"; }
die()  { echo "  [ERROR] $*" >&2; exit 1; }

require_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    die "Execute como root (sudo bash deploy/install.sh)."
  fi
}

install_packages() {
  log "Atualizando sistema e instalando dependências"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -y
  apt-get upgrade -y

  apt-get install -y \
    software-properties-common ca-certificates curl gnupg lsb-release \
    git unzip zip tar acl ufw fail2ban \
    nginx mariadb-server redis-server supervisor \
    nodejs npm

  # PHP 8.4 via ondrej (Ubuntu 24.04)
  if ! apt-cache show "php${PHP_VERSION}-fpm" &>/dev/null; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
  fi

  apt-get install -y \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-common" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-redis" \
    "php${PHP_VERSION}-tokenizer" \
    "php${PHP_VERSION}-opcache"

  if ! command -v composer >/dev/null 2>&1; then
    log "Instalando Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi

  systemctl enable --now nginx mariadb redis-server "php${PHP_VERSION}-fpm" supervisor
  ok "Pacotes e serviços base"
}

prepare_app() {
  log "Preparando aplicação em ${APP_DIR}"
  cd "${APP_DIR}"

  if [[ ! -f composer.json ]]; then
    die "composer.json não encontrado em ${APP_DIR}. Clone o repositório antes."
  fi

  if [[ ! -f .env ]]; then
    if [[ -f deploy/.env.example.production ]]; then
      cp deploy/.env.example.production .env
      warn "Criado .env a partir de deploy/.env.example.production — edite DB/APP_URL/APP_KEY antes de usar em produção."
    elif [[ -f .env.example ]]; then
      cp .env.example .env
      warn "Criado .env a partir de .env.example — ajuste para produção."
    else
      die ".env ausente e nenhum exemplo encontrado."
    fi
  else
    ok ".env já existe (não sobrescrito)"
  fi

  # Permissões iniciais
  mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public bootstrap/cache
  chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
  find storage bootstrap/cache -type d -exec chmod 775 {} \;
  find storage bootstrap/cache -type f -exec chmod 664 {} \; || true

  log "composer install --no-dev"
  sudo -u "${WEB_USER}" composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

  log "npm install && npm run build"
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
  npm run build

  log "Artisan: key, storage:link, migrate, optimize"
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
  else
    ok "APP_KEY já definido"
  fi

  php artisan storage:link --force || php artisan storage:link || true
  php artisan migrate --force
  php artisan optimize

  chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache public/storage 2>/dev/null || true
  ok "Aplicação preparada"
}

print_next_steps() {
  cat <<EOF

========================================
 ${APP_NAME} — instalação base concluída
========================================
Próximos passos manuais:
  1. Editar ${APP_DIR}/.env (DB_*, APP_URL, REDIS, MAIL, secrets)
  2. Criar banco/usuário MySQL/MariaDB
  3. Copiar deploy/nginx.conf → /etc/nginx/sites-available/expandor
     e ajustar server_name / root / SSL
  4. Copiar deploy/supervisor.conf → /etc/supervisor/conf.d/expandor-worker.conf
  5. Instalar cron: ver deploy/cron.txt
  6. Certificado HTTPS (certbot)
  7. bash deploy/healthcheck.sh

Documentação: docs/deployment/
EOF
}

main() {
  require_root
  if [[ "${SKIP_APT}" != "1" ]]; then
    install_packages
  else
    warn "SKIP_APT=1 — pulando apt"
  fi
  prepare_app
  print_next_steps
}

main "$@"
