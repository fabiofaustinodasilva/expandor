# TIMEZONE-AUDIT

## Achado principal (P0)

```
config/app.php → 'timezone' => 'UTC'   // hardcoded
.env.example   → sem APP_TIMEZONE
Locale         → pt_BR
```

A Equipe formata com `->timezone(config('app.timezone'))`, que hoje é UTC→UTC.  
Operador em São Paulo vê horários **~3 horas à frente** do relógio local (ex.: 18:00 BRT aparece 21:00).

## Evidências

| Peça | Path |
|------|------|
| Config | `config/app.php` L68 |
| Equipe label | `TeamPresenceActivityService::accessLabel` L283–291 |
| Conexões | mesmo service L239–247 |
| Blade Equipe | `resources/views/operations/team.blade.php` |
| Map updated_at | `MapQueryService` (usa app timezone) |

## Armazenamento

Laravel padrão: timestamps UTC no banco (Carbon).  
Problema não é “salvar errado”, é **exibir** sem converter para `America/Sao_Paulo`.

## Superfície de impacto (inventário)

| Superfície | Usa timezone()? | Risco |
|------------|-----------------|-------|
| Equipe / presença / conexões | Sim (UTC) | **Alto** — bug observado |
| Visitas index/show | Não (`format` direto) | Alto |
| Comissões | Não | Alto |
| Audit / privacy | Não | Médio |
| PIX / payments | Misto | Médio |
| Map JS `nowLabel()` | Browser local | Divergente do server |
| Drawer history `h.at` | String server | Depende do payload |

## SaaS futuro

Hoje produto = BR. Recomendação:

1. Curto prazo: `env('APP_TIMEZONE', 'America/Sao_Paulo')`.  
2. Médio prazo: timezone por `companies` só se houver tenants multi-país.  
3. Sempre: storage UTC; display via presenter único.

## Correção proposta (NÃO implementada)

1. `config/app.php`: `env('APP_TIMEZONE', 'America/Sao_Paulo')`.  
2. Documentar em `.env.example` / deploy.  
3. Helper/presenter `ExpandorDate::display($dt)` usado em views.  
4. Testes: Equipe “Último acesso hoje às HH:mm” em BRT.  
5. Não misturar `date_default_timezone_set` ad-hoc.

**Aguardar aprovação antes de codar.**
