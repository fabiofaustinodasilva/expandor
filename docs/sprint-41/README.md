# Sprint 4.1 — Jornada comercial de campo (primeiro atendimento)

## Fluxo

```
Adicionar ponto (seller)
  → GPS + dados
  → Resultado do atendimento (VisitStatus)
  → POST /map/first-approach
  → Property (NEW) + Visit + outcome + audits
  → Marker com cor do resultado
  → Oferta de ajustar pin (opcional)
```

`POST /map/points` permanece para manager/edição/legado.

## Contrato

`RegisterFirstApproachAction` — atômico, reutilizável no mobile depois.

| Resultado | VisitStatus | Property | Cor |
|---|---|---|---|
| Contratou | `installation_requested` | idem | Verde |
| Interessado | `interested` | idem | Azul |
| Retornar | `return_later` | idem | Amarelo |
| Sem interesse | `no_interest` | idem | Amarelo |
| Não encontrado | `not_home` | permanece `new` | Vermelho |

**Contratou:** plano + observação obrigatórios.  
**Campanha:** 1 ativa → auto; várias → escolher; zero → bloqueio com mensagem fixa.  
**Retornar:** `follow_up_at` opcional → `FollowUp`.

## Arquivos

- `app/Domains/Visits/Actions/RegisterFirstApproachAction.php`
- `app/Domains/Visits/Requests/StoreFirstApproachRequest.php`
- `app/Http/Controllers/Web/Maps/MapFirstApproachController.php`
- `app/Domains/Visits/Services/VisitService.php` (`first_approach` no histórico)
- `app/Http/Controllers/Web/Maps/MapController.php`
- `routes/web.php` → `map.first-approach`
- `resources/views/maps/index.blade.php`
- `public/js/operational-map.js`
- `public/js/field-offline-queue.js` (`first_approach`)
- `tests/Feature/Maps/FirstApproachTest.php`

Sem migrations novas.

## Prints

- `primeiro-atendimento-modal.png`
- `resultado-opcoes.png` (se disponível)

## Testes

```
123 passed (680 assertions)
```
