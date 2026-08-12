import {
    markerColorForPropertyStatus,
    markerMarkForPropertyStatus,
    markerStyleFromItem,
} from './visit-outcomes.js';
import {
    OSM_STREET_TILE_URL,
    ESRI_SATELLITE_TILE_URL,
    OSM_TILE_SUBDOMAINS,
} from './map-tile-config.js';

const PIN_SVG = '<svg class="map-house-pin-svg" viewBox="0 0 28 36" width="28" height="36" aria-hidden="true" focusable="false">'
    + '<path class="map-house-pin-body" d="M14 1.6C8.15 1.6 3.4 6.5 3.4 12.6c0 7.35 10.6 21.9 10.6 21.9s10.6-14.55 10.6-21.9C24.6 6.5 19.85 1.6 14 1.6z"/>'
    + '<path class="map-house-pin-house" d="M9.15 16.35 14 12.1l4.85 4.25V21.2h-2.75v-3.25h-4.2V21.2H9.15z"/>'
    + '</svg>';

function attachTileDiagnostics(layer, label) {
    layer.on('tileerror', (event) => {
        console.warn(`[EXP Vendedor] tile error (${label}):`, event?.tile?.src || event);
    });
}

export const MapAdapter = {
    map: null,
    markersLayer: null,
    gpsCircle: null,
    streetLayer: null,
    satelliteLayer: null,
    activeBasemap: 'street',
    onMapClick: null,
    selectedPropertyId: null,
    markerRegistry: new Map(),

    init(elementId, config = {}) {
        const L = window.L;
        if (!L) {
            return null;
        }

        const el = document.getElementById(elementId);
        if (!el) {
            return null;
        }

        if (this.map) {
            this.refreshLayout();

            return this.map;
        }

        this.map = L.map(el, { zoomControl: true }).setView(
            config.center || [-15.78, -47.93],
            config.zoom || 13,
        );

        this.streetLayer = L.tileLayer(OSM_STREET_TILE_URL, {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
            subdomains: OSM_TILE_SUBDOMAINS,
        });

        this.satelliteLayer = L.tileLayer(ESRI_SATELLITE_TILE_URL, {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19,
        });

        attachTileDiagnostics(this.streetLayer, 'street');
        attachTileDiagnostics(this.satelliteLayer, 'satellite');

        this.streetLayer.addTo(this.map);
        this.markersLayer = L.markerClusterGroup ? L.markerClusterGroup() : L.layerGroup();
        this.map.addLayer(this.markersLayer);

        this.map.on('click', (event) => {
            this.onMapClick?.(event.latlng.lat, event.latlng.lng);
        });

        requestAnimationFrame(() => this.refreshLayout());

        return this.map;
    },

    refreshLayout() {
        if (!this.map) {
            return;
        }

        this.map.invalidateSize({ animate: false });
        if (this.activeBasemap === 'satellite' && this.satelliteLayer) {
            this.satelliteLayer.redraw?.();
        } else {
            this.streetLayer?.redraw?.();
        }
    },

    pinHtml(color, mark, selected = false) {
        const fill = color || '#9ca3af';
        const selectedClass = selected ? ' is-selected' : '';
        const markHtml = mark
            ? `<span class="map-marker-mark" aria-hidden="true">${mark}</span>`
            : '';

        return `<div class="map-house-pin${selectedClass}" style="--pin-color:${fill}">`
            + PIN_SVG
            + markHtml
            + '</div>';
    },

    divIconForMarker(item) {
        const L = window.L;
        const { color, mark } = markerStyleFromItem(item);
        const propertyId = item.property_id || item.id;
        const selected = this.selectedPropertyId != null
            && Number(this.selectedPropertyId) === Number(propertyId);

        return L.divIcon({
            className: 'map-house-pin-icon',
            html: this.pinHtml(color, mark, selected),
            iconSize: [28, 36],
            iconAnchor: [14, 36],
            popupAnchor: [0, -32],
        });
    },

    createHouseMarker(item, onSelect) {
        const L = window.L;
        const marker = L.marker(
            [item.latitude, item.longitude],
            { icon: this.divIconForMarker(item) },
        );
        marker.on('click', () => {
            this.selectProperty(item.property_id || item.id);
            onSelect?.(item);
        });

        return marker;
    },

    setBasemap(mode) {
        if (!this.map || !this.streetLayer || !this.satelliteLayer) {
            return;
        }

        const next = mode === 'satellite' ? 'satellite' : 'street';
        if (next === this.activeBasemap) {
            return;
        }

        if (next === 'satellite') {
            this.map.removeLayer(this.streetLayer);
            this.satelliteLayer.addTo(this.map);
        } else {
            this.map.removeLayer(this.satelliteLayer);
            this.streetLayer.addTo(this.map);
        }

        this.activeBasemap = next;
        this.refreshLayout();
    },

    setCenter(lat, lng, zoom) {
        this.map?.setView([lat, lng], zoom || this.map.getZoom());
    },

    selectProperty(propertyId) {
        const previous = this.selectedPropertyId;
        this.selectedPropertyId = propertyId != null ? Number(propertyId) : null;

        if (previous != null) {
            this.refreshMarkerIcon(previous);
        }
        if (this.selectedPropertyId != null) {
            this.refreshMarkerIcon(this.selectedPropertyId);
        }
    },

    refreshMarkerIcon(propertyId) {
        const entry = this.markerRegistry.get(Number(propertyId));
        if (!entry) {
            return;
        }
        entry.marker.setIcon(this.divIconForMarker(entry.data));
    },

    updateMarker(propertyId, patch = {}) {
        const id = Number(propertyId);
        const entry = this.markerRegistry.get(id);
        if (!entry) {
            return;
        }

        entry.data = {
            ...entry.data,
            ...patch,
        };

        if (patch.status && !patch.color) {
            entry.data.color = markerColorForPropertyStatus(patch.status);
        }
        if (patch.status && patch.mark === undefined) {
            entry.data.mark = markerMarkForPropertyStatus(patch.status);
        }

        entry.marker.setIcon(this.divIconForMarker(entry.data));
        this.selectProperty(id);
    },

    renderMarkers(markers = [], onSelect) {
        if (!this.markersLayer) {
            return;
        }

        const selected = this.selectedPropertyId;
        this.markersLayer.clearLayers();
        this.markerRegistry.clear();

        markers.forEach((item) => {
            if (item.latitude == null || item.longitude == null) {
                return;
            }

            const propertyId = Number(item.property_id || item.id);
            const marker = this.createHouseMarker(item, onSelect);
            this.markerRegistry.set(propertyId, { marker, data: item });
            this.markersLayer.addLayer(marker);
        });

        if (selected != null) {
            this.selectedPropertyId = selected;
            this.refreshMarkerIcon(selected);
        }
    },

    recenterGps(position) {
        if (!this.map || !position) {
            return;
        }

        const L = window.L;
        this.setCenter(position.latitude, position.longitude, 16);
        if (this.gpsCircle) {
            this.gpsCircle.remove();
        }
        this.gpsCircle = L.circle([position.latitude, position.longitude], {
            radius: Math.max(12, position.accuracy || 25),
            color: '#F97316',
            fillOpacity: 0.15,
        }).addTo(this.map);
    },

    boundsQuery() {
        if (!this.map || this.map.getZoom() < 10) {
            return {};
        }

        const b = this.map.getBounds();

        return {
            min_latitude: b.getSouth(),
            max_latitude: b.getNorth(),
            min_longitude: b.getWest(),
            max_longitude: b.getEast(),
        };
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorMapAdapter = MapAdapter;
}
