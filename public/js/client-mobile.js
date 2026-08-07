/**
 * Sprint 8.2.6 — Mobile UI helpers (nav drawer, table cards, no horizontal scroll).
 * No business logic — DOM/CSS only.
 */
(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function setNavOpen(shell, open) {
        if (!shell) return;
        shell.classList.toggle('is-nav-open', open);
        shell.setAttribute('data-nav-open', open ? '1' : '0');
        document.documentElement.classList.toggle('client-nav-lock', open);
        var btn = qs('.op-nav-toggle, .shell-nav-toggle', shell);
        if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        var backdrop = qs('.op-nav-backdrop, .shell-nav-backdrop', shell);
        if (backdrop) {
            backdrop.hidden = !open;
            backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
    }

    function initOperationalNav() {
        var shell = qs('.op-shell');
        if (!shell) return;

        var toggle = qs('.op-nav-toggle', shell);
        var backdrop = qs('.op-nav-backdrop', shell);
        var rail = qs('.op-rail', shell);

        if (toggle) {
            toggle.addEventListener('click', function () {
                setNavOpen(shell, !shell.classList.contains('is-nav-open'));
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                setNavOpen(shell, false);
            });
        }

        if (rail) {
            rail.addEventListener('click', function (e) {
                var a = e.target.closest('a');
                if (a && window.matchMedia('(max-width: 900px)').matches) {
                    setNavOpen(shell, false);
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && shell.classList.contains('is-nav-open')) {
                setNavOpen(shell, false);
            }
        });
    }

    function initAppShellNav() {
        qsa('.shell').forEach(function (shell) {
            var toggle = qs('.shell-nav-toggle', shell);
            if (!toggle) return;

            var backdrop = qs('.shell-nav-backdrop', shell);
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'shell-nav-backdrop';
                backdrop.hidden = true;
                backdrop.setAttribute('aria-hidden', 'true');
                shell.insertBefore(backdrop, shell.firstChild);
            }

            toggle.addEventListener('click', function () {
                var open = !shell.classList.contains('is-nav-open');
                setNavOpen(shell, open);
            });

            backdrop.addEventListener('click', function () {
                setNavOpen(shell, false);
            });

            qsa('.sidebar a', shell).forEach(function (a) {
                a.addEventListener('click', function () {
                    if (window.matchMedia('(max-width: 900px)').matches) {
                        setNavOpen(shell, false);
                    }
                });
            });
        });
    }

    function labelTableCards() {
        qsa('.client-data-table').forEach(function (wrap) {
            wrap.classList.add('client-data-table--responsive');
            var table = qs('table', wrap);
            if (!table) return;
            var headers = qsa('thead th', table).map(function (th) {
                return (th.textContent || '').trim();
            });
            if (!headers.length) return;
            qsa('tbody tr', table).forEach(function (tr) {
                qsa('td', tr).forEach(function (td, i) {
                    if (!td.getAttribute('data-label') && headers[i]) {
                        td.setAttribute('data-label', headers[i]);
                    }
                });
            });
        });

        // Raw tables in op-page / content without client-data-table wrapper
        qsa('.op-page table.table, .content table.table').forEach(function (table) {
            if (table.closest('.client-data-table')) return;
            if (table.parentElement && table.parentElement.classList.contains('table-wrap')) return;
            if (table.parentElement && table.parentElement.classList.contains('client-data-table')) return;
            var wrap = document.createElement('div');
            wrap.className = 'table-wrap client-table-scroll';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    function preventOrphanOverflow() {
        document.documentElement.classList.add('client-mobile-ready');
    }

    document.addEventListener('DOMContentLoaded', function () {
        initOperationalNav();
        initAppShellNav();
        labelTableCards();
        preventOrphanOverflow();
        if (window.lucide) {
            try { window.lucide.createIcons(); } catch (e) {}
        }
    });
})();
