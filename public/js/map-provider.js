/**
 * Expandor Map Provider adapter.
 *
 * Separates basemap provider concerns from commercial layers (markers, clusters, filters).
 *
 * Current: Leaflet + OpenStreetMap (street) with Esri World Imagery (satellite fallback).
 * Future adapters (do not implement here): Google Maps Platform, Mapbox.
 *
 * Swap point: ExpandorMapProvider.create(map, { provider: 'google' | 'mapbox' | 'leaflet_osm' })
 */
(function (global) {
    'use strict';

    function createLeafletOsmProvider(map, options) {
        const hideAttribution = !!(options && options.hideAttribution);
        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: hideAttribution ? '' : '&copy; OpenStreetMap',
        });

        // Prepared satellite basemap — not a provider swap. Replace with Google/Mapbox tiles later.
        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                attribution: hideAttribution ? '' : 'Tiles &copy; Esri — satélite provisório até Google/Mapbox',
            }
        );

        let current = 'street';
        street.addTo(map);

        return {
            id: 'leaflet_osm',
            label: 'Leaflet + OpenStreetMap',
            supportsSatellite: true,
            satelliteNote: 'Satélite provisório (Esri). Troca futura: Google Maps Platform ou Mapbox via ExpandorMapProvider.',
            getBasemap() {
                return current;
            },
            setBasemap(mode) {
                const next = mode === 'satellite' ? 'satellite' : 'street';
                if (next === current) return current;
                if (next === 'satellite') {
                    map.removeLayer(street);
                    satellite.addTo(map);
                } else {
                    map.removeLayer(satellite);
                    street.addTo(map);
                }
                current = next;
                return current;
            },
        };
    }

    global.ExpandorMapProvider = {
        /**
         * @param {L.Map} map
         * @param {{ provider?: string }} [options]
         */
        create(map, options) {
            const provider = options?.provider || 'leaflet_osm';
            if (provider === 'leaflet_osm') {
                return createLeafletOsmProvider(map, options);
            }
            // Future: google / mapbox adapters register here.
            console.warn('[ExpandorMapProvider] Provider não disponível:', provider, '— usando leaflet_osm.');
            return createLeafletOsmProvider(map, options);
        },
    };

    /**
     * Commercial layer helpers (status → presentation). Independent of basemap provider.
     */
    global.ExpandorCommercialLayer = {
        groups: {
            customer: { label: 'Cliente ativo', color: '#22c55e', emoji: '🟢' },
            interested: { label: 'Interessado', color: '#3b82f6', emoji: '🔵' },
            visited: { label: 'Visitado', color: '#eab308', emoji: '🟡' },
            new: { label: 'Novo / sem abordagem', color: '#ef4444', emoji: '🔴' },
        },
        groupOf(marker) {
            return marker?.commercial_group
                || (marker?.status === 'customer' || marker?.status === 'installation_requested'
                    ? 'customer'
                    : marker?.status === 'interested'
                        ? 'interested'
                        : (marker?.status === 'return_later' || marker?.status === 'no_interest')
                            ? 'visited'
                            : 'new');
        },
        opportunityFromCounts(counts) {
            const total = (counts.customer || 0) + (counts.interested || 0) + (counts.visited || 0) + (counts.new || 0);
            if (!total) return { level: 'unknown', label: 'Sem dados na região' };
            const ratio = (counts.customer || 0) / total;
            if (ratio < 0.15) return { level: 'high', label: 'Oportunidade alta' };
            if (ratio > 0.4) return { level: 'low', label: 'Baixa oportunidade' };
            return { level: 'medium', label: 'Oportunidade média' };
        },
        clusterIcon(counts, total) {
            const order = ['new', 'interested', 'customer', 'visited'];
            let dominant = 'new';
            let max = -1;
            order.forEach((key) => {
                const n = counts[key] || 0;
                if (n > max) {
                    max = n;
                    dominant = key;
                }
            });
            const color = this.groups[dominant]?.color || '#ef4444';
            const size = total < 10 ? 36 : total < 50 ? 44 : 52;
            const bits = order
                .filter((k) => (counts[k] || 0) > 0)
                .map((k) => `${this.groups[k].emoji}${counts[k]}`)
                .slice(0, 3)
                .join(' ');
            return L.divIcon({
                className: 'commercial-cluster',
                html: `<div class="commercial-cluster-bubble" style="--cluster-color:${color};width:${size}px;height:${size}px">
                    <strong>${total}</strong>
                    <span class="commercial-cluster-mix">${bits || ''}</span>
                </div>`,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
            });
        },
    };
})(window);
