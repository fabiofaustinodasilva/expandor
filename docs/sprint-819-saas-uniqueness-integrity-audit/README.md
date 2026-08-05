# Sprint 8.1.9 — Auditoria Enterprise de Unicidade e Integridade SaaS

## Problemas encontrados

| # | Problema | Impacto |
|---|----------|---------|
| 1 | `users.email` único apenas por `(company_id, email)` | Mesmo e-mail podia virar admin de várias empresas (caso `suporte@iffinternet.com.br`) |
| 2 | Checkout/Mercado Pago não validava e-mail existente | Criava checkout + pagamento parcial antes de falhar no provisionamento |
| 3 | `companies.document` sem UNIQUE | Risco de CNPJ/CPF duplicado |
| 4 | Login usava match case-sensitive de e-mail | Ambiguidade com caixa diferente |
| 5 | Cadastro plataforma validava e-mail só na mesma empresa | Bypass da regra global |

## Correções aplicadas

1. **Migration** `2026_08_05_120001_enforce_global_user_email_and_company_document_uniqueness`
   - `users.email` → UNIQUE global
   - `companies.document` → UNIQUE (NULLs permitidos)
2. **`RegistrationIntegrityService`** — guard central de e-mail/documento
3. **Checkout** (`StoreCheckoutRequest` + `CreateCheckoutAction`) — bloqueia antes de criar sessão/pagamento
4. **`ProvisionCompanyAction`** — rede de segurança + mensagem amigável em violação UNIQUE
5. **Trial / Platform company create** — mesma mensagem de e-mail global
6. **Login + APIs** — lookup case-insensitive

Mensagem UX checkout:

> Este e-mail já possui uma conta cadastrada no Expandor. Utilize outro e-mail administrativo ou acesse sua conta existente.

## Inventário UNIQUE (críticos)

| Tabela / coluna | Antes | Depois / recomendação |
|-----------------|-------|------------------------|
| `users.email` | unique(company_id, email) | **unique(email)** ✅ |
| `companies.document` | index only | **unique(document)** ✅ |
| `companies.name` | sem unique | **Não aplicar** (nomes comerciais se repetem) |
| `companies.slug` | não existe | N/A |
| `checkout_sessions.uuid` | unique | OK |
| `payments (gateway, gateway_payment_id)` | unique | OK (idempotência MP) |
| `webhook_events (gateway, event_id)` | unique | OK |
| `brands.custom_domain` | unique | OK |
| `plans.slug` / `roles.slug` | unique | OK |

## Riscos restantes

1. **Dados legados** com e-mails duplicados: a migration falha até limpeza manual (manter o usuário mais antigo, migrar ou renomear os demais).
2. **Documento formatado vs só dígitos**: validação normaliza; registros antigos com formatos mistos podem exigir limpeza pontual.
3. **Soft-delete de usuário**: e-mail continua ocupando o UNIQUE (evita reuso ambíguo de login).
4. **Nome de empresa** continua livre (decisão consciente).
5. Recuperação pública de senha (forgot password) **não existe** no produto; reset administrativo de senha permanece funcional.

## Testes

`tests/Feature/Release/Sprint819SaasUniquenessIntegrityAuditTest.php`

- Caso 1: novo usuário + empresa → sucesso  
- Caso 2: mesmo e-mail → bloqueado  
- Caso 3: webhook MP repetido → não duplica  
- Caso 4: e-mails diferentes → sucesso  
- Caso 5: login case-insensitive  
- Caso 6: reset admin password + login  
- Extra: documento duplicado bloqueado  

## Operação produção

Antes do `migrate`:

```sql
-- Detectar e-mails duplicados
SELECT LOWER(email), COUNT(*) FROM users GROUP BY LOWER(email) HAVING COUNT(*) > 1;

-- Detectar documentos duplicados (não nulos)
SELECT document, COUNT(*) FROM companies WHERE document IS NOT NULL GROUP BY document HAVING COUNT(*) > 1;
```

Depois: `php artisan migrate`
