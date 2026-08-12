const GPS_MESSAGE = 'Não foi possível acessar sua localização.';

function normalizeError(error) {
    const code = error?.code;
    const mapped = {
        1: 'permission_denied',
        2: 'unavailable',
        3: 'timeout',
    };

    return {
        code: mapped[code] || error?.code || 'position_error',
        message: GPS_MESSAGE,
    };
}

function webGetCurrentPosition(options) {
    return new Promise((resolve, reject) => {
        if (! navigator.geolocation) {
            reject({ code: 'unavailable', message: GPS_MESSAGE });

            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => resolve({
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
                accuracy: pos.coords.accuracy,
                source: 'web',
            }),
            (err) => reject(normalizeError(err)),
            options,
        );
    });
}

async function capacitorGetCurrentPosition(options) {
    const geo = window.Capacitor?.Plugins?.Geolocation;
    if (! geo?.getCurrentPosition) {
        return webGetCurrentPosition(options);
    }

    try {
        const pos = await geo.getCurrentPosition({
            enableHighAccuracy: options.enableHighAccuracy !== false,
            timeout: options.timeout ?? 15000,
            maximumAge: options.maximumAge ?? 5000,
        });

        return {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
            accuracy: pos.coords.accuracy,
            source: 'capacitor',
        };
    } catch (error) {
        throw normalizeError(error);
    }
}

function isLocationGranted(status = {}) {
    return status.location === 'granted' || status.coarseLocation === 'granted';
}

export const LocationService = {
    async getCurrentPosition(options = {}) {
        const opts = {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 5000,
            ...options,
        };

        if (window.Capacitor?.Plugins?.Geolocation) {
            return capacitorGetCurrentPosition(opts);
        }

        return webGetCurrentPosition(opts);
    },

    async ensureForegroundPermission() {
        const geo = window.Capacitor?.Plugins?.Geolocation;
        if (!geo?.checkPermissions) {
            return { granted: true, source: 'web' };
        }

        let status = await geo.checkPermissions();
        if (isLocationGranted(status)) {
            return { granted: true, source: 'capacitor' };
        }

        if (geo.requestPermissions) {
            status = await geo.requestPermissions({ permissions: ['location', 'coarseLocation'] });
        }

        return {
            granted: isLocationGranted(status),
            source: 'capacitor',
        };
    },

    async permissionStatus() {
        const geo = window.Capacitor?.Plugins?.Geolocation;
        if (geo?.checkPermissions) {
            return geo.checkPermissions();
        }

        return { location: 'prompt' };
    },
};

if (typeof window !== 'undefined') {
    window.ExpandorLocationService = LocationService;
}
