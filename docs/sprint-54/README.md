# Sprint 5.4 — Identidade visual, White Label e experiência SaaS

## Objetivo
Camada de branding + UX multiempresa sem alterar regras de mapa, clientes, agenda, visitas, venda, comissão, estoque ou dashboard.

## O que foi entregue

### Empresa (`Configurações → Empresa`)
- Nome fantasia, razão social, CNPJ, telefone, WhatsApp comercial, e-mail, endereço
- Segmento: Internet, Energia Solar, Segurança, Venda Porta a Porta, Outro
- Reutiliza tabela `companies` (colunas novas: `whatsapp`, `address`, `segment`)

### Identidade visual (`Configurações → Branding`)
- Logo principal, logo reduzido (`logo_mark`), favicon
- Cores: primária, secundária, destaque (+ tokens de tema existentes)
- Serviço central: `BrandingService` + `ThemeService` + `BrandPayload`

### Aplicação do tema
- Login: logo + nome da empresa + Usuário / Senha / Entrar
- Shell operacional: CSS vars (`--primary`, `--secondary`, `--highlight`), logo no rail, usuário e cargo
- Dashboard / Clientes / Mapa / Relatórios herdam via layout operacional

### Meu perfil (`/meu-perfil`)
- Foto, nome, telefone, WhatsApp, alterar senha
- Não altera permissões

### Terminologia
- `CommercialTerminology` lê o segmento da empresa autenticada
- Internet/Solar: Plano / Instalação; Porta a Porta: Produto / Venda
- Enums internos intactos

### UX
- Componentes: `x-ux.button`, `x-ux.card`, `x-ux.badge`, `x-ux.alert`, `x-ux.modal`
- Partial de auditoria: `partials/ux/audit-meta` (só quando há dados reais)

## Migration
`database/migrations/2026_08_03_230001_add_saas_branding_profile_fields.php`

## Testes
`tests/Feature/Branding/SaasBrandingSprint54Test.php`
- Isolamento de logo/cores por tenant
- Permissão de branding/empresa
- Segmento + perfil
- Regressão: mapa, clientes, dashboard, comissões

## Resultado esperado
Nova empresa cadastra logo, cores e segmento → sistema adapta visual e textos sem mudar código.
