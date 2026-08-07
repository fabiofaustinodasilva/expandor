# STATUS-MAPPING — Sprint 8.2.10

| Status domínio (`PropertyStatus`) | Grupo filtro (`commercial_group`) | Cor pin | Marca | Legenda |
|-----------------------------------|-----------------------------------|---------|-------|---------|
| `customer` | `customer` | `#22c55e` verde | — | Cliente / instalação |
| `installation_requested` | `customer` | `#22c55e` verde | — | Cliente / instalação |
| `interested` | `interested` | `#3b82f6` azul | — | Interessado |
| `return_later` | `visited` | `#f97316` laranja | **R** | Retorno |
| `no_interest` | `visited` | `#64748b` slate | **×** | Sem interesse |
| `new` | `new` | `#ef4444` vermelho | — | Novo |

## Justificativa das cores

- **Laranja (retorno):** quente, ação pendente; contraste com azul/verde; distinto do vermelho (novo).
- **Slate (sem interesse):** frio/neutro; não compete com vermelho de “novo”; contraste no mapa escuro + borda branca + marca ×.
- Marcas **R** / **×** evitam dependência exclusiva de cor (daltonismo / pin pequeno).

## Filtro UI “Visitados”

Continua mapeando `data-group="visited"` → ambos os status. Apenas a pintura do pin/legenda diferencia.
