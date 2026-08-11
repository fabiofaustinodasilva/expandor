# RESPONSIVE-AUDIT

## Prioridade

| Viewport | Prioridade | Notas |
|----------|------------|-------|
| 390×844 | Máxima | Seller campo |
| 1366×768 | Máxima | Notebook gerente |
| 320–430 | Alta | Sheets / CTAs |
| 768–1024 | Média | Híbrido |
| 1440–1920 | Média | Desktop conforto |

## Achados

1. **Sheets 8.2.26:** `100dvh` + footer fixo — ok; validar teclado iOS.  
2. **Footer hotfix:** stack &lt;480px — ok.  
3. **Manager 1366:** filtros + mapa + painel = aperto.  
4. **Tabelas comissões:** scroll horizontal em mobile.  
5. **Login:** ok mobile (sprints auth).  
6. **Hover:** desktop-only affordance em vários botões do mapa.

## Critérios de aceite futuros

- Nenhum CTA cortado em 1366@100%.  
- Seller one-hand em 390.  
- Sem zoom-out obrigatório.
