# FIRST-APPROACH-AUDIT

## Fluxo

1. (opc) Minha localização  
2. Toque no mapa  
3. Modal “Novo ponto”  
4. Escolher situação (5 chips)  
5. Campos condicionais (retorno / venda)  
6. Salvar → `POST map.first-approach`  
7. Mapa + toast  

## Campos (seller create)

| Campo | Obrigatório? | Visível? |
|-------|--------------|----------|
| latitude/longitude | sim (backend) | amigável / hidden |
| status (outcome) | sim | chips |
| campaign_id | se >1 campanha | select |
| contact_name / phone | opcional | sim |
| notes | opcional | sim |
| street | auto “Posição no mapa” | escondido CSS |
| sale fields | se Contratou | sale-finalize |
| follow_up date/time | **opcional** mesmo em Retorno | bloco retorno |

## Contagens (aprox.)

- Telas: 1 (mapa) + 1 modal  
- Toques mínimos: 3 (toque + status + salvar)  
- Decisões: 1 (status) + opcionais  

## Burocracia evitável (futuro)

- Digitar rua (já oculto — bom)  
- Retorno sem forçar data (risco P0 — ver RETURNS)  
- Múltiplas campanhas exigem escolha (ok se auto 1 campanha)

## Campanha

- Lista `sellerCampaigns` no map page  
- Auto-seleciona se só 1 ativa  
- Sem campanha: mensagem clara no modal
