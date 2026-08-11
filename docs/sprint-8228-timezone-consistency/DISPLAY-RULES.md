# DISPLAY-RULES (final)

## Config real

```php
'timezone' => 'UTC', // storage — NÃO via APP_TIMEZONE
'display_timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
```

IANA `America/Sao_Paulo` (não `UTC-3` / `GMT-3`).

## Contratos

| Tipo | Persistência | Display | “Hoje” |
|------|--------------|---------|--------|
| Instant | UTC wall via `now()` | `AppTime::formatInstant` / `local` | `dayBoundsUtc` |
| Wall follow-up | dígitos locais | `formatWall` / `FollowUpSchedule` | `whereDate` + `AppTime::today()` |
| DATE | Y-m-d | format date only | comparar string/date |
| TTL auth/session | instante UTC / epoch | N/A (duração inalterada) | N/A |

## Equipe — ordem correta

1. Converter instante → BRT (`AppTime::local`)
2. Classificar hoje/ontem (`isTodayInstant` / `isYesterdayInstant` vs `AppTime::today()`)
3. Format `H:i` / `d/m/Y H:i`

Relativos “Agora” / “Há X min”: `diffInMinutes` entre instants UTC — sem conversão de display.
