# Sounds

- `commission-coins.wav` — short local jingle after Contratar when `commission_awarded.play_reward` is true (Sprint 8.2.23).
- No CDN dependency.
- If the file is missing or autoplay fails, `operational-map.js` falls back to a short Web Audio beep.
- Audio failures never block the sale flow.
