# Sprint 5.4.3 — Correção do upload (Platform Branding)

## Sintoma

Ao salvar logo em `/platform/branding` aparecia:

```
validation.uploaded
validation.uploaded
```

## Causas

1. **PHP `upload_max_filesize = 2M`** em `.tools/php/php.ini`, enquanto a app aceita **5MB** → PHP rejeita o arquivo (`UPLOAD_ERR_INI_SIZE`) antes do Laravel.
2. **`APP_LOCALE=pt_BR`** sem `lang/pt_BR/validation.php` → Laravel exibia a chave crua `validation.uploaded` em vez da mensagem traduzida.
3. Formulário e nomes dos campos estavam corretos (`enctype="multipart/form-data"`, `logo` / `logo_small` / `favicon`).

## Correções

| Item | Mudança |
|------|---------|
| PHP | `upload_max_filesize = 10M`, `post_max_size = 16M` |
| Lang | `lang/pt_BR/validation.php` com `uploaded` amigável |
| Controller | Pré-checagem `assertRequestFilesValid()` antes do Validator |
| MediaUploadService | Log `media.upload_attempt` (nome, size, MIME, `getError()`) + mensagens por causa |
| UI hints | PNG, JPG, JPEG, WEBP até 5MB |

### Mensagem padrão

> Não foi possível enviar a imagem. Verifique tamanho e formato.

Causas específicas cobertas: tamanho, MIME, corrompido/parcial, bloqueio PHP (tmp/disk/extension).

## Limites atuais

| Camada | Limite |
|--------|--------|
| App (`MediaUploadService`) | 5MB |
| PHP `upload_max_filesize` | 10M |
| PHP `post_max_size` | 16M |
| Formatos | PNG, JPG, JPEG, WEBP |

## Reinício necessário

Após alterar `php.ini`, **reinicie** o `php artisan serve` (ou o processo PHP) para carregar os novos limites.

```bash
.\.tools\php\php.exe -i | findstr upload_max_filesize
# esperado: 10M
```

## Testes

```bash
.\.tools\php\php.exe artisan test --filter=PlatformBrandingUploadFailureTest
```

- Upload PHP inválido → mensagem amigável (não `validation.uploaded`)
- Upload parcial → mensagem de arquivo corrompido
- MIME inválido → erro claro
- PNG válido continua funcionando

## Arquivos

- `.tools/php/php.ini`
- `lang/pt_BR/validation.php`
- `app/Domains/Media/Services/MediaUploadService.php`
- `app/Http/Controllers/Web/Platform/PlatformBrandingController.php`
- `resources/views/platform/branding/edit.blade.php`
- `tests/Feature/Platform/PlatformBrandingUploadFailureTest.php`
