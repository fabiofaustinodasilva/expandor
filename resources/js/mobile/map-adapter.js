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
import {
    googleMutantFactory,
    loadGoogleMapsScript,
    waitForGoogleMapsReady,
} from './google-maps-loader.js';

const PIN_SVG = '<svg class="map-house-pin-svg" viewBox="0 0 28 36" width="28" height="36" aria-hidden="true" focusable="false">'
    + '<path class="map-house-pin-body" d="M14 1.6C8.15 1.6 3.4 6.5 3.4 12.6c0 7.35 10.6 21.9 10.6 21.9s10.6-14.55 10.6-21.9C24.6 6.5 19.85 1.6 14 1.6z"/>'
    + '<path class="map-house-pin-house" d="M9.15 16.35 14 12.1l4.85 4.25V21.2h-2.75v-3.25h-4.2V21.2H9.15z"/>'
    + '</svg>';

function attachTileDiagnostics(layer, label) {
    layer.on('tileerror', (event) => {
        console.warn(`[EXP Vendedor] tile error (${label}):`, event?.tile?.src || event);
    });
}

function safeRemoveLayer(map, layer) {
    if (!map || !layer) {
        return;
    }
    try {
        if (map.hasLayer(layer)) {
            map.removeLayer(layer);
        }
    } catch (e) {
        /* noop */
    }
}

export const MapAdapter = {
    map: null,
    markersLayer: null,
    gpsCircle: null,
    streetLayer: null,
    satelliteLayer: null,
    activeBasemap: 'street',
    /** @type {'leaflet_osm'|'google_maps'} */
    providerId: 'leaflet_osm',
    onMapClick: null,
    selectedPropertyId: null,
    markerRegistry: new Map(),
    adjustState: null,
    adjustPreviewMarker: null,
    _googleUpgradeToken: 0,

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

        this.map = L.map(el, { zoomControl: true, maxZoom: 21 }).setView(
            config.center || [-15.78, -47.93],
            config.zoom || 13,
        );

        this.mountLeafletOsmBasemap();
        this.providerId = 'leaflet_osm';

        this.markersLayer = L.markerClusterGroup ? L.markerClusterGroup({
            showCoverageOnHover: false,
            maxClusterRadius: 55,
            spiderfyOnMaxZoom: true,
            disableClusteringAtZoom: 17,
        }) : L.layerGroup();
        this.map.addLayer(this.markersLayer);

        this.map.on('click', (event) => {
            const lat = event.latlng.lat;
            const lng = event.latlng.lng;
            if (this.adjustState) {
                this.setAdjustPreview(lat, lng);
                this.adjustState.onPick?.(lat, lng);

                return;
            }
            this.onMapClick?.(lat, lng);
        });

        requestAnimationFrame(() => this.refreshLayout());

        return this.map;
    },

    mountLeafletOsmBasemap() {
        const L = window.L;
        if (!this.map || !L) {
            return;
        }

        safeRemoveLayer(this.map, this.streetLayer);
        safeRemoveLayer(this.map, this.satelliteLayer);

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

        const basemap = this.activeBasemap === 'satellite' ? this.satelliteLayer : this.streetLayer;
        basemap.addTo(this.map);
        this.providerId = 'leaflet_osm';
    },

    /**
     * Upgrade basemap to Google Maps (Leaflet + GoogleMutant) when tenant is entitled.
     * Falls back silently to OSM/Esri — never throws to the UI.
     *
     * @param {{ browserKey?: string, timeoutMs?: number }} options
     * @returns {Promise<boolean>}
     */
    async upgradeToGoogleMaps(options = {}) {
        const key = typeof options.browserKey === 'string' ? options.browserKey.trim() : '';
        if (!this.map || !key) {
            return false;
        }

        const token = ++this._googleUpgradeToken;

        try {
            const previousGmAuthFailure = window.gm_authFailure;
            window.gm_authFailure = () => {
                try {
                    if (typeof previousGmAuthFailure === 'function') {
                        previousGmAuthFailure();
                    }
                } catch (e) {
                    /* noop */
                }
                if (token === this._googleUpgradeToken) {
                    console.warn('[EXP Vendedor] Google Maps auth failure — keeping Leaflet OSM fallback.');
                    this.mountLeafletOsmBasemap();
                }
            };

            await loadGoogleMapsScript(key);
            await waitForGoogleMapsReady(options.timeoutMs || 12000);

            if (token !== this._googleUpgradeToken) {
                return false;
            }

            const factory = googleMutantFactory();
            if (!factory || typeof window.google === 'undefined' || !window.google.maps) {
                throw new Error('googlemutant_unavailable');
            }

            const previousBasemap = this.activeBasemap;
            safeRemoveLayer(this.map, this.streetLayer);
            safeRemoveLayer(this.map, this.satelliteLayer);

            this.streetLayer = factory({ type: 'roadmap', maxZoom: 21 });
            this.satelliteLayer = factory({ type: 'satellite', maxZoom: 21 });

            const next = previousBasemap === 'satellite' ? this.satelliteLayer : this.streetLayer;
            next.addTo(this.map);
            this.activeBasemap = previousBasemap === 'satellite' ? 'satellite' : 'street';
            this.providerId = 'google_maps';
            this.refreshLayout();

            return true;
        } catch (error) {
            console.warn('[EXP Vendedor] Google Maps unavailable — OSM/Esri fallback.', error?.message || error);
            if (token === this._googleUpgradeToken) {
                this.mountLeafletOsmBasemap();
            }

            return false;
        }
    },

    waitForView() {
        return new Promise((resolve) => {
            if (!this.map) {
                resolve();

                return;
            }
            this.refreshLayout();
            requestAnimationFrame(() => {
                this.refreshLayout();
                resolve();
            });
        });
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

    pinHtml(color, mark, selected = false, draft = false) {
        const fill = color || '#9ca3af';
        const selectedClass = selected ? ' is-selected' : '';
        const draftClass = draft ? ' is-draft' : '';
        const markHtml = mark
            ? `<span class="map-marker-mark" aria-hidden="true">${mark}</span>`
            : '';

        return `<div class="map-house-pin${selectedClass}${draftClass}" style="--pin-color:${fill}">`
            + PIN_SVG
            + markHtml
            + '</div>';
    },

    divIconForMarker(item, options = {}) {
        const L = window.L;
        const { color, mark } = markerStyleFromItem(item);
        const propertyId = item.property_id || item.id;
        const selected = this.selectedPropertyId != null
            && Number(this.selectedPropertyId) === Number(propertyId);

        return L.divIcon({
            className: 'map-house-pin-icon',
            html: this.pinHtml(color, mark, selected, !!options.draft),
            iconSize: [28, 36],
            iconAnchor: [14, 36],
            popupAnchor: [0, -32],
        });
    },

    createHouseMarker(item, onSelect) {
        const L = window.L;
        const propertyId = Number(item.property_id || item.id);
        const marker = L.marker(
            [item.latitude, item.longitude],
            { icon: this.divIconForMarker(item) },
        );
        marker.on('click', (event) => {
            if (this.adjustState) {
                return;
            }
            L.DomEvent.stopPropagation(event);
            this.selectProperty(propertyId);
            onSelect?.({ ...item, property_id: propertyId, id: propertyId });
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
            return false;
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

        const lat = patch.latitude != null ? Number(patch.latitude) : null;
        const lng = patch.longitude != null ? Number(patch.longitude) : null;
        if (lat != null && lng != null && !Number.isNaN(lat) && !Number.isNaN(lng)) {
            entry.data.latitude = lat;
            entry.data.longitude = lng;
            entry.marker.setLatLng([lat, lng]);
        }

        entry.marker.setIcon(this.divIconForMarker(entry.data));
        this.selectProperty(id);

        return true;
    },

    beginAdjust(propertyId, options = {}) {
        const id = propertyId != null ? Number(propertyId) : null;
        this.cancelAdjust({ keepCallback: false });
        this.adjustState = {
            propertyId: id,
            mode: options.mode || (id ? 'existing' : 'create'),
            originalLat: options.latitude != null ? Number(options.latitude) : null,
            originalLng: options.longitude != null ? Number(options.longitude) : null,
            pendingLat: null,
            pendingLng: null,
            onPick: typeof options.onPick === 'function' ? options.onPick : null,
        };
        document.body.classList.add('seller-adjust-mode');
        if (options.latitude != null && options.longitude != null) {
            this.setAdjustPreview(Number(options.latitude), Number(options.longitude));
            this.setCenter(Number(options.latitude), Number(options.longitude), Math.max(this.map?.getZoom() || 15, 17));
        }
    },

    setAdjustPreview(lat, lng) {
        const L = window.L;
        if (!this.map || !L) {
            return;
        }
        if (this.adjustState) {
            this.adjustState.pendingLat = lat;
            this.adjustState.pendingLng = lng;
        }
        if (this.adjustPreviewMarker) {
            this.adjustPreviewMarker.setLatLng([lat, lng]);

            return;
        }
        this.adjustPreviewMarker = L.marker([lat, lng], {
            icon: this.divIconForMarker({ color: '#F97316', mark: '' }, { draft: true }),
            zIndexOffset: 1000,
            draggable: true,
        }).addTo(this.map);
        this.adjustPreviewMarker.on('dragend', () => {
            const pos = this.adjustPreviewMarker.getLatLng();
            if (this.adjustState) {
                this.adjustState.pendingLat = pos.lat;
                this.adjustState.pendingLng = pos.lng;
                this.adjustState.onPick?.(pos.lat, pos.lng);
            }
        });
    },

    getAdjustPending() {
        if (!this.adjustState) {
            return null;
        }

        return {
            propertyId: this.adjustState.propertyId,
            latitude: this.adjustState.pendingLat,
            longitude: this.adjustState.pendingLng,
            mode: this.adjustState.mode,
        };
    },

    cancelAdjust() {
        if (this.adjustPreviewMarker) {
            this.adjustPreviewMarker.remove();
            this.adjustPreviewMarker = null;
        }
        this.adjustState = null;
        document.body.classList.remove('seller-adjust-mode');
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
            this.markerRegistry.set(propertyId, { marker, data: { ...item, property_id: propertyId, id: propertyId } });
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
