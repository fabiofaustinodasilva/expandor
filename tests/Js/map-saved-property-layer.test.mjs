import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { test } from 'node:test';
import vm from 'node:vm';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const source = readFileSync(resolve(root, 'public/js/map-saved-property-layer.js'), 'utf8');
const sandbox = {
    module: { exports: {} },
    exports: {},
    console,
    URLSearchParams,
};
sandbox.module.exports = sandbox.exports;
vm.runInNewContext(source, sandbox);
const api = sandbox.module.exports;

test('default host is featureGroup (same path as clickable draft pin)', () => {
    const flags = api.parseDebugFlags('');
    assert.equal(flags.debugNoCluster, true);
    assert.equal(flags.debugCluster, false);

    const featureGroup = { id: 'fg' };
    const clusterGroup = { id: 'cg' };
    const resolved = api.resolveSavedPropertyHost(flags, featureGroup, clusterGroup);
    assert.equal(resolved.host, featureGroup);
    assert.equal(resolved.name, 'featureGroup');
});

test('debug_no_cluster=1 forces featureGroup', () => {
    const flags = api.parseDebugFlags('?debug_no_cluster=1');
    const resolved = api.resolveSavedPropertyHost(flags, { id: 'fg' }, { id: 'cg' });
    assert.equal(resolved.name, 'featureGroup');
});

test('debug_cluster=1 is the A/B cluster path only', () => {
    const flags = api.parseDebugFlags('?debug_cluster=1');
    assert.equal(flags.debugCluster, true);
    const clusterGroup = { id: 'cg' };
    const resolved = api.resolveSavedPropertyHost(flags, { id: 'fg' }, clusterGroup);
    assert.equal(resolved.host, clusterGroup);
    assert.equal(resolved.name, 'clusterGroup');
});
