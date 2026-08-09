# Deploy note — Google Maps "Testar conexão" 405

## Causa
O formulário compartilhado usava `@method('PUT')` (hidden `_method=PUT`).
O botão "Testar conexão" fazia POST para `/operacao/integracoes/google-maps/test`
mas o spoof convertia a requisição em **PUT**, e a rota aceita só **POST** → **405**.

## Correção
`_method=PUT` vai apenas no botão "Salvar e ativar". Teste é POST puro.

## Após deploy
```bash
php artisan optimize:clear
php artisan route:cache
```
Confirme no browser (Network) que "Testar conexão" envia **POST** (não GET/PUT).
