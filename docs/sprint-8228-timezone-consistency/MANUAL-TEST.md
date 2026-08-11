# MANUAL-TEST (pós-deploy)

## A) EQUIPE
- [ ] Login agora → “Agora” / online
- [ ] Histórico de acessos → hora bate com relógio Brasil (não +3h)
- [ ] Offline → “Último acesso hoje/ontem às HH:mm” em BRT

## B) VISITA
- [ ] Registrar visita → horário no histórico/lista/drawer correto

## C) RETORNO
- [ ] Agendar `15:00` → Agenda mostra `15:00` (sem ±3h)
- [ ] Chip/filtro Hoje lista só o dia BRT

## D) VENDA
- [ ] Venda → hora em histórico/comissões coerente; valores $ inalterados

## E) HOJE
- [ ] Após ~21:00 BRT (UTC já amanhã): Visitas/Agenda/Vendas/Comissões “Hoje” ainda no dia local

## AUTH (smoke)
- [ ] Reset senha expira no TTL configurado
- [ ] Sessão seller single-device ok
