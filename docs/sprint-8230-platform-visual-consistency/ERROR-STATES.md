# ERROR STATES

Páginas branded em `resources/views/errors/`:

| Código | Título | Mensagem ao usuário |
|--------|--------|---------------------|
| 403 | Você não tem permissão para esta página | Fale com o administrador da empresa |
| 404 | Esta página não existe | Endereço mudou ou link incorreto |
| 419 | Sua sessão expirou | Atualize ou faça login de novo |
| 500 | Algo deu errado | Tente de novo; suporte se persistir |

Layout usa ThemeService. Log técnico do 500 permanece no servidor.

Sessão substituída (Seller): mensagem já validada na 8.2.25 — `SellerSingleSessionService::REPLACED_MESSAGE`. Não alterada.

Google Maps / SMTP: copy de integração explica o que falta e que o mapa padrão continua; resolver/entitlement intactos.
