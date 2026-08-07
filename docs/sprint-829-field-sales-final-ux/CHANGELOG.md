# CHANGELOG — Sprint 8.2.9

## Alterado

- `public/js/operational-map.js` (v=44)
  - `locateMyPosition()` — Meu Local + Minha localização só GPS/center.
  - `openCreateAtMapTap` — toque no mapa abre form.
  - `gpsErrorMessage` — deny / unavailable / timeout / unsupported.
  - `pointSubmitting` — evita duplo envio.
  - Remoção de handlers empty-spot legados.
- `resources/views/maps/index.blade.php`
  - Copy tips/brief/empty; título **Novo ponto**; **Voltar ao mapa**.
  - CSS 320px / 430px; cache bust `?v=44`.
- Testes 827/828/Pilot/FirstApproach alinhados.
- `tests/.../Sprint829FieldSalesFinalUxTest.php` novo.

## Não alterado

Banco, migrations, tenancy, auth, Policies, billing, MP, MapCommercialGroup / cores (só documentado).

## Backend necessário

Ver AUDIT.md — separar visual return vs no_interest no mapa.
