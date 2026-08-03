# GeoSales CRM

# Documento 21

# Deploy e Infraestrutura

Versão: 1.0

Status: Oficial

---

# 1. Objetivo

Este documento define a infraestrutura necessária para executar o GeoSales CRM em ambiente de desenvolvimento, homologação e produção.

O objetivo é garantir:

- Segurança.
- Estabilidade.
- Performance.
- Escalabilidade.
- Facilidade de manutenção.

---

# 2. Ambientes

O projeto deverá possuir três ambientes:

---

# Desenvolvimento

Uso:

Programação local.

Testes iniciais.

---

# Homologação

Uso:

Validação antes da produção.

Testes com usuários.

---

# Produção

Uso:

Clientes reais.

Dados oficiais.

---

# 3. Sistema Operacional

Servidor recomendado:

Linux.

Distribuição:

Debian 12

ou

Ubuntu LTS.


---

# 4. Servidor Inicial Recomendado

Para início do SaaS:

CPU:

4 núcleos.


Memória:

8 GB RAM.


Armazenamento:

100 GB SSD.


Rede:

1 Gbps.


---

# 5. Stack Produção

Servidor Web:

Nginx


Aplicação:

Laravel PHP


Versão PHP:

8.2 ou superior


Banco:

MariaDB


Cache:

Redis


Fila:

Laravel Queue


SSL:

Let's Encrypt


---

# 6. Estrutura do Servidor

Exemplo:

```
/var/www/

└── geosales

    ├── current

    ├── storage

    ├── logs

    └── backups
```

---

# 7. Instalação Base

Pacotes necessários:

PHP.

Composer.

Nginx.

MariaDB.

Redis.

Node.js.

Supervisor.


---

# 8. Configuração Laravel

Produção:

Ativar:

APP_ENV=production

APP_DEBUG=false


---

# 9. Permissões

Diretórios:

storage

bootstrap/cache


devem possuir permissão correta.

---

# 10. Banco de Dados

Configuração:

MariaDB.


Obrigatório:

Usuário exclusivo.

Senha forte.

Backup automático.

---

# 11. Cache

Utilizar Redis para:

Sessões.

Cache.

Filas.

Processos temporários.


---

# 12. Filas Laravel

Processos pesados deverão utilizar Queue.

Exemplos:

Relatórios.

Exportações.

Envio WhatsApp.

Processamento IA.

---

# 13. Supervisor

Responsável por manter processos ativos.

Exemplo:

Worker Laravel.

Queue.

---

# 14. Nginx

Responsável:

Servir aplicação.

HTTPS.

Proxy.

Compressão.


---

# 15. Domínios

Estrutura sugerida:


Sistema:

```
app.geosales.com.br
```


API:

```
api.geosales.com.br
```


Documentação:

```
docs.geosales.com.br
```


---

# 16. SSL

Utilizar:

Let's Encrypt.


Renovação automática:

Certbot.


---

# 17. Uploads

Arquivos:

Fotos.

Vídeos.

Documentos.

Treinamentos.


Inicialmente:

Storage local.


Futuro:

Object Storage.

Exemplo:

S3 compatível.


---

# 18. Backup

Obrigatório:

Backup diário.

---

Banco:

Dump automático.


Arquivos:

Storage.


---

# 19. Retenção de Backup

Sugestão:

Diário:

7 dias.


Semanal:

4 semanas.


Mensal:

12 meses.


---

# 20. Segurança

Implementar:

Firewall.

SSH seguro.

Fail2ban.

Atualizações.

Monitoramento.


---

# 21. Logs

Registrar:

Laravel logs.

Nginx logs.

Banco.

Sistema.


---

# 22. Monitoramento

Monitorar:

CPU.

Memória.

Disco.

Banco.

Redis.

Fila.

Erros.


---

# 23. Ferramentas Futuras

Possíveis:

Zabbix.

Grafana.

Prometheus.


---

# 24. Escalabilidade

Quando crescer:

Separar serviços.


Modelo futuro:

Servidor Web.

Servidor Banco.

Servidor Arquivos.

Servidor Filas.


---

# 25. Banco Distribuído

Futuro:

Read replicas.

Cluster MariaDB.

Alta disponibilidade.


---

# 26. CDN

Utilizar para:

Imagens.

Vídeos.

Arquivos grandes.


---

# 27. Containers

Futuro:

Docker.

Kubernetes.

CI/CD.


---

# 28. Deploy Automatizado

Futuro:

Pipeline:

Git.

Testes.

Deploy automático.

Rollback.


---

# 29. Controle de Versão

Produção somente recebe:

Código aprovado.

Versão testada.

Backup realizado.


---

# 30. Atualizações

Antes de atualizar:

1. Backup.

2. Teste homologação.

3. Aplicar migration.

4. Atualizar código.

5. Validar sistema.

---

# 31. LGPD e Segurança de Dados

Implementar:

Controle de acesso.

Logs.

Criptografia.

Política de retenção.

Remoção de dados quando solicitado.


---

# 32. Alta Disponibilidade

Futuro:

Load Balancer.

Múltiplos servidores.

Failover.

---

# 33. Objetivo Final

Criar uma infraestrutura profissional capaz de suportar o crescimento do GeoSales CRM desde os primeiros clientes até uma grande plataforma SaaS multiempresa.
