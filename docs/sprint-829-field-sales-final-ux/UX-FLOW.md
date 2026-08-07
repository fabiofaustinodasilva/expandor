# UX-FLOW — Sprint 8.2.9

## Princípio

**Localizar → tocar → informar → salvar → continuar no mapa.**

## Fluxos

### Meu Local (toolbar) / Minha localização (FAB)

1. Toque.
2. GPS (ou erro amigável).
3. Mapa centraliza + marcador rascunho “Meu Local”.
4. Toast **Localização obtida**.
5. **Não** abre formulário; **não** cria ponto.

### Toque no mapa

1. Abre modal **Novo ponto** com lat/lng do toque.
2. Campos essenciais + situação (VisitStatus existentes).
3. Salvar / Voltar ao mapa.

### Após salvar (vendedor)

1. Toast **Ponto registrado** (ou toast comercial se venda).
2. Fecha modal; sem ajuste pós-criação.
3. Recarrega pins; centra no ponto.

### GPS — mensagens

| Caso | Mensagem |
|------|----------|
| Permissão negada | Verifique a permissão de localização do navegador. |
| Indisponível / timeout | Não foi possível acessar sua localização. |
| Sem suporte | Este navegador não oferece localização. |

## Linguagem

Preferida: Meu Local, Minha localização, Novo ponto, Ponto registrado, Visita, Retorno, Venda.  
Evitar: coordenada, property, opportunity, prospect.
