# UX-FLOW — Sprint 8.2.8

## Princípio

**Mapa > registro rápido > resultado > continuar.**

Mínimo de telas intermediárias. Sem “escolher tipo” antes do form.

## Fluxos

### A — Meu Local (criar no GPS)

1. Vendedor toca **Meu Local**.  
2. Browser pede localização se necessário.  
3. Sucesso: toast **Localização obtida**, mapa centra, marcador rascunho, **formulário abre**.  
4. Negado: toast **Permita o acesso à localização para usar o Meu Local.**  

### B — Minha localização (só recentralizar)

1. Toca **Minha localização** (FAB inferior esquerdo, junto ao basemap).  
2. GPS → centra zoom ≥ 17.  
3. Toast **Localização obtida**.  
4. **Não** abre formulário; **não** cria ponto.  

### C — Toque no mapa

1. Toque em local vazio (com permissão de criar).  
2. Formulário direto com lat/lng do clique.  

### D — Preencher e salvar

Campos prioritários (seller):

- Nome / responsável  
- Telefone / WhatsApp  
- Observação curta  
- Situação / interesse (botões VisitStatus)  
- Campanha quando aplicável  

Lat/lng automáticos; label amigável (“Posição pronta…”) para seller.

Salvar:

- Toast **Ponto registrado** (ou toast comercial se venda/instalação).  
- Fecha modal; **não** abre ajuste pós-criação para field seller.  
- Recarrega marcadores; centra no ponto criado.  

Erro:

- **Não foi possível salvar. Tente novamente.**  

### E — Continuidade

Vendedor permanece no mapa e pode andar / Meu Local / toque de novo.

## O que não volta

- “Casa sem cadastro”  
- “Próxima casa” / “Registrar próxima” obrigatório  
- Escolha de tipo antes do form  
