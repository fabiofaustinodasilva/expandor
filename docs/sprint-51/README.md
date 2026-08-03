# Sprint 5.1 — Terminologia Comercial Universal (UX)

**Data:** 2026-08-03  
**Escopo:** apenas textos exibidos ao usuário  
**Não alterado:** banco, migrations, enums (`VisitStatus`, `PropertyStatus`), APIs, fluxo de venda, comissões/estoque/indicadores (regras)

---

## Objetivo

Generalizar a linguagem da interface para qualquer empresa de vendas externas, mantendo o status interno `installation_requested`.

Preparação para a Sprint 6.0: cada empresa poderá escolher sua própria terminologia sem espalhar strings em dezenas de Blades.

---

## Estratégia

### Camada única

`App\Support\CommercialTerminology`

Responsável por textos como:

| Método | Texto padrão |
|---|---|
| `saleCompleted()` | Venda realizada |
| `saleCompletedBadge()` | 🟢 Venda realizada |
| `sales()` | Vendas |
| `salesOfDay()` | Vendas do dia |
| `totalSales()` | Total de vendas |
| `commissionPerSale()` | Comissão por venda |
| `newSales()` | Novas vendas |
| `visitResult()` | badge comercial do status |
| `visitStatusOptions()` | opções de filtro/gráfico |
| `agendaOutcomeOptions()` | desfechos da agenda |
| `propertyStatusLabel()` | labels de mapa (UI) |

Disponível em todas as views via `View::share('commercial', CommercialTerminology::class)` → `{{ $commercial::sales() }}`.

### O que permanece interno

- Enum `VisitStatus::INSTALLATION_REQUESTED` = `installation_requested`
- `VisitStatus::label()` = `Instalação solicitada` (formal)
- `VisitStatus::commercialLabel()` = `🟢 Contratou` (legado do enum; UI passa a usar `CommercialTerminology`)
- Colunas/métricas `installations`, `contracts` nos DTOs/repositórios
- APIs mobile continuam com labels formais dos enums

---

## Arquivos alterados

### Nova camada
- `app/Support/CommercialTerminology.php`

### Providers
- `app/Providers/AppServiceProvider.php` — `View::share('commercial', …)`

### Controllers (só labels de apresentação)
- `app/Http/Controllers/Web/Operations/MyVisitsController.php`
- `app/Http/Controllers/Web/Visits/FollowUpController.php`
- `app/Http/Controllers/Web/Dashboard/DashboardController.php`
- `app/Http/Controllers/Web/Maps/MapController.php`

### Services (texto de alerta / label de marcador UI)
- `app/Domains/Analytics/Services/DashboardMetricsService.php`
- `app/Domains/Maps/Services/MapQueryService.php`

### Views
- `resources/views/dashboard/index.blade.php`
- `resources/views/maps/index.blade.php`
- `resources/views/operations/team.blade.php`
- `resources/views/crm/dashboard.blade.php`
- `resources/views/commissions/products/form.blade.php`
- `resources/views/commissions/products/index.blade.php`
- `resources/views/visits/follow-ups/index.blade.php`

### Front (toast UX)
- `public/js/operational-map.js` — toast de venda via `data-sale-registered-toast`

### Teste de apresentação (único ajuste de assert de copy)
- `tests/Feature/Visits/VisitHistoryTest.php` — `assertSee('🟢 Venda realizada')`  
  O teste que garante o label formal do enum (`Instalação solicitada` / `🟢 Contratou`) **permanece intacto**.

### Documentação
- `docs/sprint-51/README.md` (este arquivo)

---

## Mapeamento de copy

| Antes (UI) | Depois |
|---|---|
| Instalação solicitada (exibição) | Venda realizada |
| 🟢 Contratou (histórico/agenda UI) | 🟢 Venda realizada |
| Contratos / Instalações (funil, KPIs, ranking) | Vendas |
| Instalações solicitadas | Vendas realizadas |
| Contratos / visitas | Vendas / visitas |
| Comissão fixa (formulário produto) | Comissão por venda |
| Instalações (Analytics) | Vendas (Analytics) |
| Contrato registrado (toast) | Venda registrada |

---

## Sprint 6.0 (próximo passo)

Estender `CommercialTerminology` para ler preferências por empresa (ex.: config/JSON):

- Internet → “Instalação agendada”
- Energia solar → “Proposta aceita”
- Cosméticos → “Pedido confirmado”
- Seguros → “Apólice fechada”

Sem alterar enums, rotas ou regras de negócio.
