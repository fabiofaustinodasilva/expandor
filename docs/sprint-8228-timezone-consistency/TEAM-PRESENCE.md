# TEAM-PRESENCE — causa +3h e correção

## Caminho real do bug

1. **Armazenamento:** `last_seen_at` / `last_login_at` / audit `created_at` gravados com `now()` sob `app.timezone=UTC` → dígitos UTC (ex.: atividade 18:00 BRT → `21:00:00` UTC no banco).
2. **Timezone da aplicação:** `config('app.timezone') = UTC`.
3. **Formatação/display:** `accessLabel` e blades faziam `$ref->timezone(config('app.timezone'))` (= UTC) e formatavam `H:i` → usuário via **21:00** em vez de **18:00**.

Não bastava “era UTC”: o bug era **exibir o wall UTC sem converter para o fuso operacional**.

## Correção

```php
$local = AppTime::local($ref); // America/Sao_Paulo
AppTime::isTodayInstant($ref) / isYesterdayInstant($ref)
```

Timeline / último login / conexões: `AppTime::zone()` nas blades da Equipe.
