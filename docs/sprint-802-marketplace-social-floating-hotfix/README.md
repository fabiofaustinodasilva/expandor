# Sprint 8.0.2 — Marketplace Social & Floating Buttons Hotfix

## Problema

Configurações de WhatsApp e redes sociais eram salvas no CMS, mas frequentemente não apareciam de forma confiável na Landing (cache mutável + overlay de defaults com flags booleanas + ausência de TikTok/X).

## Correções

1. `MarketplaceSetting::socialNetworks()`, `hasWhatsAppButton()`, `whatsappDigits()`, `whatsappLink()` — fonte única para views
2. Overlay de defaults **não** sobrescreve flags/URLs sociais; clone da instância em cache
3. Cache de settings: `forget` + `put` imediato após save
4. Componentes WhatsApp (pulse) e Social Links (só redes válidas)
5. TikTok + X (Twitter) no schema, form, request e service
6. Preview administrativo em tempo real (WhatsApp + redes)
7. Hidden `0` nos checkboxes para desativação correta

## Testes

`tests/Feature/Marketplace/Sprint802MarketplaceSocialFloatingHotfixTest.php`

## Commit

`fix: render marketplace social and whatsapp buttons from cms settings`
