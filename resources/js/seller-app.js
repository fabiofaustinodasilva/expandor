/**
 * Seller/app vendor bundle — local Leaflet, MarkerCluster, GoogleMutant, Lucide.
 * IIFE classic script: window.L / window.lucide before operational-map.js.
 *
 * Lucide ESM requires createIcons({ icons }). The UMD CDN did that implicitly.
 */
import '../css/seller-app.css';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.gridlayer.googlemutant';
import { createIcons, icons } from 'lucide';
import * as lucide from 'lucide';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconUrl,
    iconRetinaUrl,
    shadowUrl,
});

function createIconsCompat(options = {}) {
    return createIcons({
        icons,
        ...options,
        attrs: { 'stroke-width': 2, ...(options.attrs || {}) },
    });
}

window.L = L;
window.lucide = Object.assign({}, lucide, { icons, createIcons: createIconsCompat });

window.ExpandorVendor = {
    leaflet: typeof L?.map === 'function',
    markerCluster: typeof L?.markerClusterGroup === 'function',
    googleMutant: typeof L?.gridLayer?.googleMutant === 'function',
    lucide: typeof createIcons === 'function',
};

function paintIcons() {
    try {
        createIconsCompat();
    } catch (e) {
        /* Lucide optional — shell must not go blank */
    }
}

paintIcons();
document.addEventListener('DOMContentLoaded', paintIcons);
