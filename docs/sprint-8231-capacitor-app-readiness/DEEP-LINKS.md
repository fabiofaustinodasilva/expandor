# DEEP-LINKS

## Password reset hoje

E-mail → `url(route('password.reset'))` = `APP_URL/redefinir-senha/{token}?email=` → form web → redirect login. Sem auto-login.

## App (futuro — não implementar agora)

| Passo | Comportamento |
|-------|----------------|
| Esqueci senha no app | mesmo e-mail 8.2.24 |
| Link | Universal Link / App Link `https://{host}/redefinir-senha/{token}` abre o app se instalado; senão browser |
| Fallback | browser completo (já funciona) |
| Após reset | deep link `expandor://login` ou Universal Link `/login` |

Proposta (não fixar):  
Android App Link + iOS Associated Domains no host de produção.  
Scheme custom `br.com.expandor.app` só como fallback.

Não implementar nesta sprint (amplia demais).
