# Sprint 8.2.28 — Timezone e consistência temporal (FECHAMENTO)

## Arquitetura final (código real)

| Camada | Valor |
|--------|--------|
| `config('app.timezone')` **antes** | `'UTC'` hardcoded |
| `config('app.timezone')` **depois** | `'UTC'` hardcoded (storage / `now()`) |
| `APP_TIMEZONE` | → `config('app.display_timezone')` (default `America/Sao_Paulo`) |
| PHP / Carbon default | UTC (via `app.timezone`) |
| Display / dia operacional | `America/Sao_Paulo` via `App\Support\AppTime` |
| MySQL | sem `SET time_zone` na app; colunas `timestamp` / `date` / epoch |

**Por que não flipamos `app.timezone` para São Paulo?**  
Storage é **misto** (instants UTC + follow-ups wall-clock). Flip sem migration reinterpretaria strings naive e quebraria presença/`now()`. Preferência: UTC storage + conversão só na apresentação.

## Helper

`App\Support\AppTime` — `formatInstant` / `local` / `dayBoundsUtc` / `today` / `formatWall` / `parseWall`.

## Documentos

| Arquivo | Conteúdo |
|---------|----------|
| [AUDIT.md](./AUDIT.md) | Auditoria pré-implementação |
| [STORAGE-MODEL.md](./STORAGE-MODEL.md) | Modelo C misto |
| [DISPLAY-RULES.md](./DISPLAY-RULES.md) | Instant vs wall vs DATE |
| [TEAM-PRESENCE.md](./TEAM-PRESENCE.md) | Causa +3h / Equipe |
| [FOLLOWUPS.md](./FOLLOWUPS.md) | Agenda / retornos |
| [DAY-BOUNDARIES.md](./DAY-BOUNDARIES.md) | Filtros Hoje |
| [FRONTEND-DATES.md](./FRONTEND-DATES.md) | Contrato JS |
| [FUTURE-TENANT-TIMEZONE.md](./FUTURE-TENANT-TIMEZONE.md) | SaaS futuro |
| [DEPLOYMENT.md](./DEPLOYMENT.md) | Produção |
| [TEST-REPORT.md](./TEST-REPORT.md) | Testes / regressões |
| [MANUAL-TEST.md](./MANUAL-TEST.md) | Checklist pós-deploy |
| [CHANGELOG.md](./CHANGELOG.md) | Delta |

## Branch / commit

- Branch: `feature/sprint-8228-timezone-consistency`
- Commit: `fix: normalize platform timezone display and day boundaries`
- **Sem push / sem merge**
