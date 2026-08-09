# Sounds

- `commission-coins.wav` — jingle local de moedas (~1.3s) após Contratar quando `commission_awarded.awarded` é true (Sprint 8.2.23 hotfix).
- No CDN dependency.
- Preload + unlock no primeiro gesto do vendedor; `play()` seguro.
- Falha de áudio nunca bloqueia a venda.
- Regenerar: `php scripts/generate-commission-coins-wav.php`

