/**
 * Seller/app vendor bundle — local Leaflet, MarkerCluster, GoogleMutant, Lucide.
 * IIFE classic script: window.L / window.lucide before operational-map.js.
 */
import '../css/seller-app.css';
import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.gridlayer.googlemutant';
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

window.L = L;
window.lucide = lucide;

window.ExpandorVendor = {
    leaflet: typeof L?.map === 'function',
    markerCluster: typeof L?.markerClusterGroup === 'function',
    googleMutant: typeof L?.gridLayer?.googleMutant === 'function',
    lucide: typeof lucide?.createIcons === 'function',
};

function paintIcons() {
    try {
        lucide.createIcons();
    } catch (e) {
        /* Lucide optional — shell must not go blank */
    }
}

paintIcons();
document.addEventListener('DOMContentLoaded', paintIcons);
