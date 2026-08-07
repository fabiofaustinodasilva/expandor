# MOBILE-CHECKLIST — Sprint 8.2.8

Validar manualmente (Chrome DevTools / device):

| Viewport | Meu Local | Minha localização | Mapa | Form | Teclado | Outcomes | Salvar / toast | Marcadores |
|----------|-----------|-------------------|------|------|---------|----------|----------------|------------|
| 360×800 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| 375×812 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| 390×844 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| 412×915 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| 768×1024 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |

## Critérios

- [ ] Meu Local visível no toolbar (não só em “Mais”).  
- [ ] Minha localização acessível acima da bottom nav / safe area (`#map-bottom-left-controls`).  
- [ ] FAB não cobre busca crítica nem botão Salvar do form.  
- [ ] Form scrollável (`max-height` / `88dvh` field seller).  
- [ ] Outcomes em grid 2 colunas no mobile.  
- [ ] Teclado não esconde Salvar (sticky submit).  
- [ ] GPS negado: mensagem do briefing.  
- [ ] Após salvar: mapa usável sem drawer de ajuste.  

## Automatizado

Assertions HTML/JS em `Sprint828FieldSalesMobileFlowTest` (estrutura + copy + permissões de UI).
