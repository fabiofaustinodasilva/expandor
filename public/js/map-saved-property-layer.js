/**
 * Host selection for persisted property pins.
 * Draft/adjust pins already use map.addLayer and are clickable.
 * MarkerClusterGroup ownership is the production failure mode for saved pins.
 */
(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) {
        module.exports = api;
    }
    root.ExpandorSavedPropertyLayer = api;
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    function parseDebugFlags(search) {
        const params = new URLSearchParams(search || '');
        const cluster = params.get('debug_cluster') === '1';
        const noCluster = params.get('debug_no_cluster') === '1' || !cluster;
        return {
            debugNoCluster: noCluster,
            debugCluster: cluster,
        };
    }

    function resolveSavedPropertyHost(flags, featureGroup, clusterGroup) {
        if (flags && flags.debugCluster && clusterGroup) {
            return { host: clusterGroup, name: 'clusterGroup' };
        }
        return { host: featureGroup, name: 'featureGroup' };
    }

    function runtimeTrace(step, detail) {
        try {
            console.info('[MapRuntime]', step, detail);
        } catch (_) { /* optional */ }
    }

    return {
        parseDebugFlags,
        resolveSavedPropertyHost,
        runtimeTrace,
    };
}));
