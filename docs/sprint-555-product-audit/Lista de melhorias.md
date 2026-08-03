# Lista de melhorias — Sprint 5.5.5

## Aplicadas

1. Padronização de navegação (app shell + menu Mais)  
2. Linguagem comercial em status de visita/cliente  
3. Copy de Setup / Trial / Dashboard alinhada a “Setup”  
4. Branding PWA Expandor  
5. Redução de queries em permissões (eager load)  
6. Remoção de código/pastas mortas  

## Recomendadas (próximas)

1. Unificar layouts: migrar telas legado `layouts.app` (properties/visits CRUD) para `operational` onde fizer sentido  
2. Campanhas create/edit no mesmo shell do index  
3. Restringir eager load de visitas no `CustomerQueryService::paginate` (só última visita)  
4. Alinhar `sales-app` brand ao `$brand->name()` dinâmico  
5. Checklist visual mobile dedicado no mapa (smoke QA manual)  
