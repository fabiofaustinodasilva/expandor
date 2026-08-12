# Assets EXP Vendedor (Capacitor + Android)

Arquivos oficiais (PNG):

| Arquivo | Uso |
|---|---|
| `exp-vendedor-logo.png` | Login (logo completa EXP VENDEDOR) |
| `exp-vendedor-icon.png` | Header + ícone Android + splash |

Cores da identidade:
- Azul Expandor: `#1E4A8C`
- Azul escuro: `#0B1F3A`
- Laranja: `#F97316`

Build:
- `npm run build` copia PNGs para `public/capacitor-shell/vendor/`
- `scripts/generate-exp-vendedor-android-assets.mjs` gera mipmap/splash Android

Não usar SVG placeholder — substituir apenas os PNG acima quando a arte oficial mudar.
