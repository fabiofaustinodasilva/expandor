/**
 * Lucide only — layouts that do not need the map stack (layouts.app).
 */
import * as lucide from 'lucide';

window.lucide = lucide;
window.ExpandorVendor = {
    ...(window.ExpandorVendor || {}),
    lucide: typeof lucide?.createIcons === 'function',
};

function paintIcons() {
    try {
        lucide.createIcons();
    } catch (e) {
        /* ignore */
    }
}

paintIcons();
document.addEventListener('DOMContentLoaded', paintIcons);
