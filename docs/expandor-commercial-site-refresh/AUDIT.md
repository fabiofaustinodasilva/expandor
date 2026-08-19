# Auditoria — site comercial Expandor

Fonte pública: Marketplace CMS + defaults em `config/marketplace_defaults.php`.
Entrada: `GET /` (`marketplace.home`) → `MarketplaceController@home` → `resources/views/marketplace/landing.blade.php`.

## SITE ENTRY
`/` e `/marketplace` (landing). Preview autenticado: `platform.marketplace.preview`.

## PRICING
Home `#planos` e `/planos` usam **planos comerciais de display** (`commercial_plans` no config). Não leem mais os models `Plan` do billing para o visitante.

## PLANO GRÁTIS
Removido do site. Backend `PlanSeeder` ainda tem slug `free` (R$ 0, 3 usuários) por compatibilidade — ver `PRICING.md`. Funil `/cadastro` e trial técnico **não foram destruídos**.

## SEÇÃO "TELAS DA OPERAÇÃO DE CAMPO"
`#produto` (tipo CMS `showcase`). Vitrine EXP Vendedor: 1 telefone + tabs (Mapa, Agenda, Produtos, Venda, Resultados, Comissão).

## SEÇÃO "VEJA O SISTEMA FUNCIONANDO"
`#demonstracao` (tipo CMS `video`). Jornada visual em 6 etapas. Não usa mais o frame “Demonstração do CRM”.

## CTA ATUAL
Primário: **Agendar demonstração** → `#demo` (formulário existente `POST /marketplace/leads`). WhatsApp só se CMS tiver número habilitado. Sem telefone hardcoded.

## SCREENSHOTS DISPONÍVEIS
Originais: `docs/expandor-commercial-presentation/screenshots/`.
Derivados web: `public/images/marketplace/product/` (WebP + PNG). Ver `SCREENSHOT-USAGE.md`.

## CMS
Se o banco já tiver seções/FAQ antigos, o assembler sanitiza copy comercial obsoleta (hero antigo, “Começar agora”, FAQ de teste sem cartão, depoimentos inventados). CMS **customizado de verdade** continua prevalecendo. Restaurar defaults: botão no admin do site público (`MarketplaceDefaultSeeder --force`).
