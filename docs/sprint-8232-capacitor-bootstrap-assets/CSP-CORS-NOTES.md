# CSP-CORS-NOTES

## CSP (shell local)

Não abrir `script-src *` nem `connect-src *`.

Alvo futuro do shell:

```
default-src 'self';
script-src 'self';
style-src 'self' 'unsafe-inline';
img-src 'self' data: blob: https://*.tile.openstreetmap.org https://*.arcgisonline.com;
connect-src 'self' https://DOMINIO-EXPANDOR;
```

Quando Google Maps ativo:

```
script-src 'self' https://maps.googleapis.com https://maps.gstatic.com;
connect-src ... https://maps.googleapis.com;
img-src ... https://maps.gstatic.com https://maps.googleapis.com;
```

Nesta sprint o shell é arquivo local (Capacitor WebView) sem CSP HTTP header ainda.

## CORS

`config/cors.php` **continua ausente**. Não abrir CORS em produção sem auth mobile real.

Proposta (implementar na 8.2.33+):

```
paths: ['api/*', 'sanctum/csrf-cookie']
allowed_origins: [
  'capacitor://localhost',
  'http://localhost',
  'https://localhost',
  'https://dominio-expandor'
]
allowed_methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE']
supports_credentials: false  // Bearer Sanctum
```

Nunca `allowed_origins: ['*']` com cookies.
