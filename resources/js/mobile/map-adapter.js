export const MapAdapter = {
    map: null,
    markersLayer: null,
    gpsCircle: null,

    init(elementId, config = {}) {
        const L = window.L;
        if (! L) {
            return null;
        }

        const el = document.getElementById(elementId);
        if (! el) {
            return null;
        }

        this.map = L.map(el, { zoomControl: true }).setView(
            config.center || [-15.78, -47.93],
            config.zoom || 13,
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
        }).addTo(this.map);

        this.markersLayer = L.markerClusterGroup ? L.markerClusterGroup() : L.layerGroup();
        this.map.addLayer(this.markersLayer);

        return this.map;
    },

    setCenter(lat, lng, zoom) {
        this.map?.setView([lat, lng], zoom || this.map.getZoom());
    },

    renderMarkers(markers = [], onSelect) {
        if (! this.markersLayer) {
            return;
        }

        this.markersLayer.clearLayers();
        const L = window.L;
        markers.forEach((item) => {
            if (item.latitude == null || item.longitude == null) {
                return;
            }
            const marker = L.marker([item.latitude, item.longitude]);
            marker.on('click', () => onSelect?.(item));
            this.markersLayer.addLayer(marker);
        });
    },

    recenterGps(position) {
        if (! this.map || ! position) {
            return;
        }

        const L = window.L;
        this.setCenter(position.latitude, position.longitude, 16);
        if (this.gpsCircle) {
            this.gpsCircle.remove();
        }
        this.gpsCircle = L.circle([position.latitude, position.longitude], {
            radius: Math.max(12, position.accuracy || 25),
            color: '#3B82F6',
            fillOpacity: 0.15,
        }).addTo(this.map);
    },

    boundsQuery() {
        if (! this.map || this.map.getZoom() < 10) {
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
