# Deploy Expandor — Documentação

Guia de instalação e operação em **Ubuntu 24.04** para homologação e produção.

Scripts e configs: [`deploy/`](../../deploy/).

## Índice

| Documento | Conteúdo |
|-----------|----------|
| [Instalação Ubuntu](./Instala%C3%A7%C3%A3o%20Ubuntu.md) | Servidor novo do zero |
| [Atualização](./Atualiza%C3%A7%C3%A3o.md) | `update.sh` sem perder uploads |
| [Backup](./Backup.md) | Backup automático |
| [Restore](./Restore.md) | Restauração |
| [Problemas comuns](./Problemas%20comuns.md) | Troubleshooting |
| [Checklist produção](./Checklist%20produ%C3%A7%C3%A3o.md) | Go-live |

## Arquitetura sugerida

```
Internet → Nginx (HTTPS) → PHP-FPM 8.4 → Laravel (Expandor)
                ↓
         Redis (cache/queue/session)
                ↓
         MariaDB/MySQL
                ↓
         Supervisor → queue:work
         Cron → schedule:run
```

## Princípios

1. Processo **repetível** via scripts  
2. Uploads em `storage/` **nunca** apagados no update  
3. Segredos só no `.env` do servidor  
4. Healthcheck antes e depois de mudanças  

Sprint: `docs/sprint-556-deploy/`
