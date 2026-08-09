# UX checklist (manual) — reward hotfix

- [ ] Seller: Contratar com comissão > 0 → overlay “Venda fechada!” aparece
- [ ] Valor em pt-BR (`R$ 18,50`)
- [ ] Texto “Comissão adicionada ao seu resultado.”
- [ ] Som de moedas (WAV local) toca uma vez
- [ ] Overlay some sozinho (~2–3s) sem clique
- [ ] Mapa continua usável (não bloqueia)
- [ ] Comissão 0 → sem overlay / sem som de moeda
- [ ] Reload da página → não repete celebração
- [ ] Abrir mapa de novo → não repete
- [ ] Manager abrindo mapa/dashboard → sem som de comissão
- [ ] Hard refresh após deploy (`?v=55`) confirma JS novo
- [ ] Mobile 320 / 360 / 390 / 430: overlay cabe e respeita safe-area

Registrar resultado em produção após deploy do hotfix.
