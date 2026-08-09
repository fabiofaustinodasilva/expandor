/**
 * Expandor Map Provider adapter.
 *
 * Separates basemap provider concerns from commercial layers (markers, clusters, filters).
 *
 * Providers:
 * - leaflet_osm: OpenStreetMap street + Esri World Imagery satellite (fallback absoluto)
 * - google_maps: Leaflet host + GoogleMutant (official Maps JavaScript API tiles — ToS-safe)
 *
 * Swap point: ExpandorMapProvider.create(map, { provider: 'google_maps' | 'leaflet_osm' })
 */
(function (global) {
    'use strict';

    function createLeafletOsmProvider(map, options) {
        const hideAttribution = !!(options && options.hideAttribution);
        const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: hideAttribution ? '' : '&copy; OpenStreetMap',
        });

        const satellite = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            {
                maxZoom: 19,
                attribution: hideAttribution ? '' : 'Tiles &copy; Esri',
            }
        );

        let current = 'street';
        street.addTo(map);

        return {
            id: 'leaflet_osm',
            label: 'Leaflet + OpenStreetMap',
            supportsSatellite: true,
            satelliteNote: 'Satélite (Esri) no provider padrão.',
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
            destroy() {
                try { map.removeLayer(street); } catch (e) { /* noop */ }
                try { map.removeLayer(satellite); } catch (e) { /* noop */ }
            },
        };
    }

    function googleMutantFactory() {
        if (typeof L === 'undefined') return null;
        if (typeof L.gridLayer?.googleMutant === 'function') {
            return L.gridLayer.googleMutant.bind(L.gridLayer);
        }
        if (typeof L.GridLayer?.GoogleMutant === 'function') {
            return function (opts) {
                return new L.GridLayer.GoogleMutant(opts);
            };
        }
        return null;
    }

    function createGoogleMapsProvider(map, options) {
        if (typeof google === 'undefined' || !google.maps) {
            throw new Error('google_maps_unavailable');
        }
        const factory = googleMutantFactory();
        if (!factory) {
            throw new Error('googlemutant_unavailable');
        }

        // Google branding/attribution must remain visible (ToS). Do not strip for sellers.
        const street = factory({ type: 'roadmap', maxZoom: 21 });
        const satellite = factory({ type: 'satellite', maxZoom: 21 });

        let current = 'street';
        street.addTo(map);

        return {
            id: 'google_maps',
            label: 'Google Maps (Leaflet + GoogleMutant)',
            supportsSatellite: true,
            satelliteNote: 'Satélite Google (Maps JavaScript API via GoogleMutant).',
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
            destroy() {
                try { map.removeLayer(street); } catch (e) { /* noop */ }
                try { map.removeLayer(satellite); } catch (e) { /* noop */ }
            },
        };
    }

    global.ExpandorMapProvider = {
        /**
         * @param {L.Map} map
         * @param {{ provider?: string, hideAttribution?: boolean }} [options]
         */
        create(map, options) {
            const provider = options?.provider || 'leaflet_osm';
            if (provider === 'google_maps') {
                return createGoogleMapsProvider(map, options || {});
            }
            if (provider === 'leaflet_osm') {
                return createLeafletOsmProvider(map, options || {});
            }
            console.warn('[ExpandorMapProvider] Provider não disponível:', provider, '— usando leaflet_osm.');
            return createLeafletOsmProvider(map, options || {});
        },

        waitForGoogleMaps(timeoutMs) {
            const timeout = timeoutMs || 12000;
            return new Promise((resolve, reject) => {
                const started = Date.now();
                (function poll() {
                    if (typeof google !== 'undefined' && google.maps && googleMutantFactory()) {
                        resolve(true);
                        return;
                    }
                    if (Date.now() - started > timeout) {
                        reject(new Error('google_maps_timeout'));
                        return;
                    }
                    setTimeout(poll, 50);
                })();
            });
        },
    };

    /**
     * Commercial layer helpers (status → presentation). Independent of basemap provider.
     */
    global.ExpandorCommercialLayer = {
        groups: {
            customer: { label: 'Cliente / instalação', color: '#22c55e', mark: '' },
            interested: { label: 'Interessado', color: '#3b82f6', mark: '' },
            visited: { label: 'Visitados', color: '#eab308', mark: '' },
            return: { label: 'Retorno', color: '#f97316', mark: 'R' },
            no_interest: { label: 'Sem interesse', color: '#64748b', mark: '×' },
            new: { label: 'Novo', color: '#ef4444', mark: '' },
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
        colorOf(marker) {
            if (marker?.color) return marker.color;
            const status = marker?.status;
            if (status === 'return_later') return this.groups.return.color;
            if (status === 'no_interest') return this.groups.no_interest.color;
            if (status === 'interested') return this.groups.interested.color;
            if (status === 'customer' || status === 'installation_requested') return this.groups.customer.color;
            return this.groups.new.color;
        },
        markOf(marker) {
            const status = marker?.status;
            if (status === 'return_later') return 'R';
            if (status === 'no_interest') return '×';
            return '';
        },
        opportunityFromCounts(counts) {
            const total = (counts.customer || 0) + (counts.interested || 0) + (counts.visited || 0) + (counts.new || 0);
            if (!total) return { level: 'unknown', label: 'Sem dados na região' };
            const ratio = (counts.customer || 0) / total;
            if (ratio < 0.15) return { level: 'high', label: 'Alto potencial' };
            if (ratio > 0.4) return { level: 'low', label: 'Baixo potencial' };
            return { level: 'medium', label: 'Médio potencial' };
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
                .map((k) => {
                    const short = k === 'customer' ? 'C' : k === 'interested' ? 'I' : k === 'visited' ? 'V' : 'N';
                    return `${short}${counts[k]}`;
                })
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
