/**
 * Basemap tile URLs — aligned with public/js/map-provider.js (leaflet_osm).
 * CSP allowlist for Capacitor shell must include MAP_TILE_CSP_HOSTS (explicit subdomains).
 */

export const OSM_STREET_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

export const ESRI_SATELLITE_TILE_URL =
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';

export const OSM_TILE_SUBDOMAINS = ['a', 'b', 'c'];

/**
 * Hosts loaded as images (and optionally XHR) by Leaflet basemaps on mobile.
 * Do not use img-src * — WebView Android meta CSP ignores subdomain wildcards.
 *
 * @type {readonly string[]}
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
];

if (typeof window !== 'undefined') {
    window.ExpandorMapTileConfig = {
        OSM_STREET_TILE_URL,
        ESRI_SATELLITE_TILE_URL,
        OSM_TILE_SUBDOMAINS,
        MAP_TILE_CSP_HOSTS,
    };
}
