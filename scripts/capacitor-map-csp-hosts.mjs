/**
 * CSP allowlist for map tiles in Capacitor shell (mirrors map-tile-config.js).
 * Explicit hosts — Android WebView meta CSP often blocks `https://*.tile.openstreetmap.org`.
 */
export const MAP_TILE_CSP_HOSTS = [
    'https://a.tile.openstreetmap.org',
    'https://b.tile.openstreetmap.org',
    'https://c.tile.openstreetmap.org',
    'https://tile.openstreetmap.org',
    'https://server.arcgisonline.com',
    'https://services.arcgisonline.com',
    'https://tiles.arcgisonline.com',
    'https://basemaps.arcgis.com',
    'https://maps.googleapis.com',
    'https://maps.gstatic.com',
    'https://khms0.googleapis.com',
    'https://khms1.googleapis.com',
    'https://khms2.googleapis.com',
    'https://khms3.googleapis.com',
    'https://mt0.google.com',
    'https://mt1.google.com',
    'https://mt2.google.com',
    'https://mt3.google.com',
    'https://khmdb0.google.com',
    'https://khmdb1.google.com',
    'https://khms0.google.com',
    'https://khms1.google.com',
    'https://khms2.google.com',
    'https://khms3.google.com',
    'https://maps.google.com',
    'https://csi.gstatic.com',
];
