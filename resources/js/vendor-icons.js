/**
 * Lucide only — same createIcons({ icons }) contract as the UMD CDN.
 */
import { createIcons, icons } from 'lucide';
import * as lucide from 'lucide';

function createIconsCompat(options = {}) {
    return createIcons({
        icons,
        ...options,
        attrs: { 'stroke-width': 2, ...(options.attrs || {}) },
    });
}

window.lucide = Object.assign({}, lucide, { icons, createIcons: createIconsCompat });
window.ExpandorVendor = {
    ...(window.ExpandorVendor || {}),
    lucide: typeof createIcons === 'function',
};

function paintIcons() {
    try {
        createIconsCompat();
    } catch (e) {
        /* ignore */
    }
}

paintIcons();
document.addEventListener('DOMContentLoaded', paintIcons);
