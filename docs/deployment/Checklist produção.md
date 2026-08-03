# Checklist produção

Marque antes do go-live:

## Aplicação

- [ ] `APP_ENV=production`  
- [ ] `APP_DEBUG=false`  
- [ ] `APP_URL` com HTTPS  
- [ ] `APP_KEY` gerada  
- [ ] Migrações aplicadas  
- [ ] `php artisan optimize`  
- [ ] `storage:link` ok  
- [ ] Build frontend (`public/build`)  

## Infra

- [ ] Nginx com HTTPS válido  
- [ ] PHP-FPM 8.4 ativo  
- [ ] MariaDB/MySQL com usuário dedicado (sem root na app)  
- [ ] Redis ativo  
- [ ] Supervisor `expandor-worker` RUNNING  
- [ ] Cron `schedule:run`  
- [ ] Firewall (80/443; SSH restrito)  

## Dados / ops

- [ ] Backup automático testado  
- [ ] Restore testado em homologação  
- [ ] `bash deploy/healthcheck.sh` → OK  
- [ ] Monitoramento de disco/logs  

## Produto

- [ ] Login / cadastro trial / setup  
- [ ] Upload de logo (platform + tenant)  
- [ ] Mapa / clientes / visita / venda  
- [ ] Webhooks de pagamento (se aplicável)  

## Segurança

- [ ] Sem secrets no Git  
- [ ] `.env` só no servidor  
- [ ] Uploads fora de escrita pública indevida  
- [ ] Rate limits de login/cadastro ativos  
