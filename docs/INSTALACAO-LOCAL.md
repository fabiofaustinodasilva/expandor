# Instalação Local — GeoSales CRM

Guia rápido para subir a **Fase 01 (fundação SaaS)** em ambiente de desenvolvimento.

---

## Requisitos

- PHP **8.2+** (extensões: `openssl`, `curl`, `mbstring`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`)
- Composer 2
- MariaDB 10.6+ (ou MySQL 8+)
- Node.js 20+ (opcional nesta fase — o layout admin usa CSS embutido)
- Git

> No Windows, se o PHP não estiver no PATH, o projeto pode usar o PHP portátil em `.tools/php` (quando disponível).

---

## Instalação

Na pasta do projeto:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

No PowerShell:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

---

## Configuração do banco

Edite o `.env`:

```env
APP_NAME="GeoSales CRM"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=geosales_crm
DB_USERNAME=root
DB_PASSWORD=
```

Crie o banco no MariaDB:

```sql
CREATE DATABASE geosales_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Migrations

Somente estrutura:

```bash
php artisan migrate
```

Recriar do zero (apaga dados):

```bash
php artisan migrate:fresh
```

---

## Seed (fundação + demo)

### Opção recomendada — comando único

```bash
php artisan geosales:install
```

Com banco limpo:

```bash
php artisan geosales:install --fresh
```

O comando:
1. Executa as migrations
2. Semeia planos, roles e permissões
3. Cria a empresa **Única Network Demo**
4. Cria assinatura **Professional** ativa
5. Cria usuários demo e configurações iniciais

### Opção manual

```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=Database\\Seeders\\DemoSeeder
```

`db:seed` padrão carrega apenas planos e roles/permissões.  
O ambiente demo completo fica no `DemoSeeder` / `geosales:install`.

---

## Subir a aplicação

```bash
php artisan serve
```

Acesse: [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)

---

## Credenciais demo

| Usuário | E-mail | Perfil | Senha |
|---------|--------|--------|-------|
| Administrador Demo | `admin@unicanetwork.demo` | Administrator | `password` |
| Gerente Demo | `manager@unicanetwork.demo` | Manager | `password` |
| Vendedor Demo | `seller@unicanetwork.demo` | Seller | `password` |

Empresa: **Única Network Demo**  
Plano: **Professional** (assinatura ativa)

---

## Testes

```bash
php artisan test
```

---

## Observações da Fase 01

Já disponível:
- Multiempresa (`empresa_id` / `company_id`)
- Auth web + API (Sanctum)
- Dashboard, Company e Users
- Policies e menu por permissão

Ainda **não** implementado (Fase 02+):
- Addresses / Properties / Residents
- Campaigns / Visits
- Mapa
