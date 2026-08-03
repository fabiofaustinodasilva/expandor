# Checklist — auditoria visual login

Combinações a validar manualmente em `/login` após configurar Platform Branding:

| Cenário | Primary | Secondary | Logo | Esperado |
|---------|---------|-----------|------|----------|
| Logo clara + fundo escuro | `#3B82F6` | `#0F172A` | clara/branca | Texto claro, logo legível |
| Logo escura + fundo claro | `#0B1F3A` | `#FFFFFF` | escura | Texto escuro (`brand-contrast-light`) |
| Secondary branca | qualquer | `#FFFFFF` | qualquer | `--text` escuro no card |
| Primary escura | `#0B1F3A` | escuro | — | `--button-text` claro no Entrar |
| Primary clara | `#60A5FA` | escuro | — | Botão legível (contraste do accent) |
| Sem slogan | — | — | — | Sem linha de slogan; sem gap extra |
| Sem branding | — | — | — | Wordmark **Expandor** + “Bem-vindo ao Expandor” |
| Nome + slogan | Expandor | tagline | — | Slogan 1×; welcome só com nome |

Campos de identidade (Owner → Identidade da Plataforma):

- Nome  
- Slogan  
- Logo / logo reduzida / favicon  
- Cores primary / secondary / highlight  
