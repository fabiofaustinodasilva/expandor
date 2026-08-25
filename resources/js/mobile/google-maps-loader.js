/**
 * Load Google Maps JavaScript API once for Capacitor / seller shell.
 * Key comes from tenant bootstrap (never hardcoded).
 */

const SCRIPT_ID = 'expandor-google-maps-js';

/**
 * @param {string} browserKey
 * @returns {Promise<void>}
 */
export function loadGoogleMapsScript(browserKey) {
    return new Promise((resolve, reject) => {
        const key = typeof browserKey === 'string' ? browserKey.trim() : '';
        if (!key) {
            reject(new Error('google_maps_missing_key'));

            return;
        }

        if (typeof window.google !== 'undefined' && window.google.maps) {
            resolve();

            return;
        }

        const existing = document.getElementById(SCRIPT_ID);
        if (existing) {
            const started = Date.now();
            (function poll() {
                if (typeof window.google !== 'undefined' && window.google.maps) {
                    resolve();

                    return;
                }
                if (Date.now() - started > 15000) {
                    reject(new Error('google_maps_timeout'));

                    return;
                }
                setTimeout(poll, 50);
            })();

            return;
        }

        const script = document.createElement('script');
        script.id = SCRIPT_ID;
        script.async = true;
        script.defer = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&v=weekly`;
        script.onerror = () => reject(new Error('google_maps_script_error'));
        script.onload = () => {
            if (typeof window.google !== 'undefined' && window.google.maps) {
                resolve();
            } else {
                reject(new Error('google_maps_unavailable'));
            }
        };
        document.head.appendChild(script);
    });
}

/**
 * @param {number} [timeoutMs]
 * @returns {Promise<true>}
 */
export function waitForGoogleMapsReady(timeoutMs = 12000) {
    return new Promise((resolve, reject) => {
        const started = Date.now();
        const L = window.L;
        (function poll() {
            const mutantReady = typeof L?.gridLayer?.googleMutant === 'function'
                || typeof L?.GridLayer?.GoogleMutant === 'function';
            if (typeof window.google !== 'undefined' && window.google.maps && mutantReady) {
                resolve(true);

                return;
            }
            if (Date.now() - started > timeoutMs) {
                reject(new Error('google_maps_timeout'));

                return;
            }
            setTimeout(poll, 50);
        })();
    });
}

export function googleMutantFactory() {
    const L = window.L;
    if (!L) {
        return null;
    }
    if (typeof L.gridLayer?.googleMutant === 'function') {
        return L.gridLayer.googleMutant.bind(L.gridLayer);
    }
    if (typeof L.GridLayer?.GoogleMutant === 'function') {
        return function googleMutant(opts) {
            return new L.GridLayer.GoogleMutant(opts);
        };
    }

    return null;
}

if (typeof window !== 'undefined') {
    window.ExpandorGoogleMapsLoader = {
        loadGoogleMapsScript,
        waitForGoogleMapsReady,
        googleMutantFactory,
    };
}
