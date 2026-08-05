# Sprint 8.1.1 — Production QA / Final Visual Polish

## Objetivo

Elevar a qualidade percebida do Expandor ao nível enterprise — **sem novos recursos** e **sem alterar** CRM, Billing, Tenancy, Auth, Campanhas, Agenda, Clientes ou regras de Marketplace.

## Entregas

1. **Design System compartilhado** em `resources/views/partials/rc-ux-polish.blade.php`
   - Tokens `--ds-*`
   - Botões: primary, secondary, ghost, outline, danger, success, loading, disabled, block
   - Cards, inputs, tipografia, badges, alerts, tables (`.table-wrap`)
   - Focus-visible, reduced-motion, scrollbar
2. **Layouts unificados**
   - `platform` / `app`: menu mobile colapsável + `aria-label`
   - `guest`: tokens alinhados + polish
   - `sales-app`: inclui design system (antes ficava de fora)
   - `operational`: botão primary alinhado a tokens
3. **Landing**
   - Fallbacks SEO sem jargão (CRM/SaaS)
   - Responsividade 360/414/768/1024
   - Focus rings, alts de imagem, classes demo (sem ROI/growth no DOM)
4. **Acessibilidade**
   - `:focus-visible`, labels de nav, toasts com `role="status"`
5. **Component** `<x-ux.button>` com todas as variantes

## Não alterado

Billing · CRM · Tenancy · Auth · Campaigns · Agenda · Clientes · Controllers de domínio

## Commit

`chore: unify expandor design system and production visual polish`
