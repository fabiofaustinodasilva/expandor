# TENANT-INTEGRATIONS

## Placeholder atual

`IntegrationsController` + view `operations.integrations` — lista estática WhatsApp/Google Maps/Webhooks/ERP “Em breve”.

## Estruturas reutilizáveis

| Existente | Serve para Google? |
|-----------|--------------------|
| `WhatsAppConnection` + credentials encrypted | **Melhor analogia tenant** |
| `CompanySetting` plaintext | Fraco para secrets |
| `PaymentGatewaySetting` | Padrão encryption, mas **global** |
| `company_settings.map_provider` | Sinal legado; não usar como store de API key |

## Modelo desejado (conceito)

`company_integrations` (proposta — ver DATA-MODEL-PROPOSAL.md):

- Uma linha por `(company_id, integration_key)`  
- `configuration` JSON público (ex. mapId, region)  
- `credentials_encrypted` (server secrets)  
- `browser_key_masked` / ou browser key em campo separado com masking  
- `status`, `last_tested_at`, `last_error`

## UX empresa (futuro)

| Estado | UI |
|--------|-----|
| Plano não permite | “Disponível no Plano Pro” + Ver planos |
| Permite, não conectou | “Não conectado” + Configurar |
| Conectado | ● Conectado + Testar / Gerenciar / Desconectar |

## Seller
Zero configuração. Herda provider da empresa.
