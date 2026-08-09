# RETURNS-AUDIT

## Criticidade: P0

Exemplo: “Volta amanhã depois das 18h.”

## Como criar hoje

Outcome **Retornar depois** no FirstApproach / visita.  
Campos: data + hora **opcionais**.  
Só cria `FollowUp` se `follow_up_at` preenchido.

## Onde aparece

| Superfície | Conteúdo |
|------------|----------|
| Agenda rail `follow-ups.index` | FollowUps pendentes (próprio seller) |
| Sales App “Retornos” | Lista simplificada |
| Mapa | pin Retorno + métrica “Retornos” do dia |
| Day brief | não lista retornos individualmente |

## Completar

Agenda → outcome modal (pode virar venda / novo retorno).  
Sales App complete endpoint.

## Resposta

> O vendedor corre risco de esquecer um retorno?

**SIM — P0.** Se marcar “Retornar depois” **sem data**, a visita muda status mas **não entra na Agenda**. Não há lembrete push. Depende do vendedor lembrar ou ver o pin no mapa.

## Recomendação futura (8.2.16)

- Exigir data (mín. dia) ao escolher retorno  
- Lista “Hoje” no open do mapa / day brief  
- Um único lugar Agenda (unificar Sales App Retornos)
