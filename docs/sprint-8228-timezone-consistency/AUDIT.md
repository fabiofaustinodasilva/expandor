# AUDIT — Sprint 8.2.28

## 1. config/app.php (antes)

- `'timezone' => 'UTC'` hardcoded
- Sem `APP_TIMEZONE` / `display_timezone`

## 2. .env

- `.env.example` / `deploy/.env.example.production`: sem timezone (antes)
- Local: tipicamente sem `APP_TIMEZONE`

## 3. PHP default timezone

- Laravel define via `config('app.timezone')` → UTC

## 4. MySQL timezone

- App não executa `SET time_zone`
- Colunas auditadas: `timestamp` / `date` / `integer` (sessions)
- Preferência: **não** alterar `@@global.time_zone` / `@@session.time_zone`

## 5. Laravel / Carbon

- `now()` / Eloquent timestamps: wall clock no `app.timezone` (UTC)
- Sem `serializeDate` custom em `AppServiceProvider`
- Leitura: string naive reinterpretada no TZ default

## 6–9. Timestamps Eloquent / custom / casts

| Campo | Tipo | Escrita típica | Classe |
|-------|------|----------------|--------|
| `users.last_login_at` | timestamp | `now()` | **Instant UTC** |
| `users.last_seen_at` | timestamp | `now()` | Instant UTC |
| `audit_logs.created_at` | timestamp | `now()` / useCurrent | Instant UTC |
| `visits.visited_at` | timestamp | `now()` | Instant UTC |
| `visits.created_at` | timestamp | Eloquent | Instant UTC |
| `follow_ups.scheduled_at` | timestamp | form parse app TZ | **Wall local (BRT digits)** |
| `sales.created_at` | timestamp | Eloquent | Instant UTC |
| `sales_commissions.earned_at` | timestamp | visit/now | Instant UTC |
| `campaigns.start_date/end_date` | **date** | date only | DATE (não converter) |
| `password_reset_tokens.created_at` | timestamp | broker | Instant UTC |
| `sessions.last_activity` | integer | unix epoch | Epoch (independente) |

## 10–11. Formatadores / conversões manuais

- Antes: `->timezone(config('app.timezone'))` (= UTC) em Equipe → **causa do +3h**
- `FollowUpSchedule` usava app timezone para label/parse
- Sem helper central (pré-sprint)

## 12. JS

- `operational-map.js`: `combineFollowUpAt` envia `Y-m-d` / `Y-m-dTH:i` (browser local)
- `nowLabel()`: `toLocaleString('pt-BR')` no browser
- Risco de dupla conversão se backend já exibisse BRT e JS reinterpretasse ISO com Z — payload de follow-up **não** usa ISO com Z

## 13. API JSON

- Datas majoritariamente formatadas como string `d/m/Y H:i` no servidor
- Contrato pós-sprint: instants formatados via `AppTime` (BRT); walls via `FollowUpSchedule`/`formatWall`

## 14–15. Sessões / audit

- Sessão TTL: `config('session.lifetime')` + epoch/`now()` UTC — **não alterar**
- Audit display: passar a `AppTime::local`

## Veredito storage

**C — misto** (ver STORAGE-MODEL.md). Flip cego de `app.timezone` → **UNSAFE**. Zero migration de dados.
