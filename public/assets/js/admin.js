/**
 * Perilaku global panel admin (layouts.admin.tabler):
 *  1. Toggle tema terang/gelap.
 *  2. Notifikasi flash dari server (SweetAlert).
 *  3. Popover notifikasi & akun di topbar.
 *  4. Sidebar ringkas (desktop) / drawer (mobile).
 */
(function () {
    'use strict';

    // ── 1. Tema ─────────────────────────────────────────────────────────
    function simpanTema(tema) {
        document.documentElement.setAttribute('data-bs-theme', tema);
        try {
            localStorage.setItem('theme', tema);
        } catch (e) {
            /* storage diblokir: tema tetap berlaku untuk halaman ini */
        }
    }

    function initTema() {
        var url = new URL(window.location.href);
        var dariUrl = url.searchParams.get('theme');
        if (dariUrl === 'dark' || dariUrl === 'light') {
            simpanTema(dariUrl);
            url.searchParams.delete('theme');
            window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        }

        document.querySelectorAll('[href="?theme=dark"], [href="?theme=light"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                simpanTema(link.getAttribute('href').indexOf('dark') !== -1 ? 'dark' : 'light');
            });
        });
    }

    // ── 2. Flash ────────────────────────────────────────────────────────
    function initFlash() {
        var el = document.getElementById('admin-flash');
        if (!el || typeof Swal === 'undefined') {
            return;
        }

        var flash = JSON.parse(el.textContent || '{}');
        if (flash.success) {
            Swal.fire({ title: 'Berhasil!', text: flash.success, icon: 'success', confirmButtonText: 'Ok' });
        }
        if (flash.warning) {
            Swal.fire({ title: 'Peringatan!', text: flash.warning, icon: 'warning', confirmButtonText: 'Ok' });
        }
        if (flash.errors && flash.errors.length) {
            var ul = document.createElement('ul');
            ul.className = 'text-start mb-0';
            flash.errors.forEach(function (pesan) {
                var li = document.createElement('li');
                li.textContent = pesan;
                ul.appendChild(li);
            });
            Swal.fire({ title: 'Gagal!', html: ul, icon: 'error', confirmButtonText: 'Ok' });
        }
    }

    // ── 3. Popover topbar (notifikasi & akun) ───────────────────────────
    function initPopover() {
        var tombol = document.querySelectorAll('[data-popover-toggle]');

        function tutupSemua(kecuali) {
            tombol.forEach(function (btn) {
                if (btn === kecuali) {
                    return;
                }
                btn.setAttribute('aria-expanded', 'false');
                document.getElementById(btn.getAttribute('aria-controls')).hidden = true;
            });
        }

        tombol.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var panel = document.getElementById(btn.getAttribute('aria-controls'));
                var buka = panel.hidden;
                tutupSemua(btn);
                panel.hidden = !buka;
                btn.setAttribute('aria-expanded', buka ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.topbar-popover')) {
                tutupSemua(null);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var terbuka = document.querySelector('[data-popover-toggle][aria-expanded="true"]');
                tutupSemua(null);
                if (terbuka) {
                    terbuka.focus();
                }
            }
        });
    }

    // ── 4. Sidebar: ringkas (desktop) / drawer (mobile) ─────────────────
    function initSidebar() {
        var root = document.documentElement;
        var toggle = document.querySelector('[data-sidebar-toggle]');
        var backdrop = document.querySelector('[data-sidebar-close]');
        var desktop = window.matchMedia('(min-width: 1200px)');
        if (!toggle) {
            return;
        }

        // Label menu jadi tooltip saat sidebar ringkas (hanya ikon).
        document.querySelectorAll('.navbar-vertical .nav-link').forEach(function (link) {
            var judul = link.querySelector('.nav-link-title');
            if (judul && !link.hasAttribute('title')) {
                link.setAttribute('title', judul.textContent.trim());
            }
        });

        function sinkron() {
            var terbuka = desktop.matches
                ? !root.classList.contains('sidebar-collapsed')
                : root.classList.contains('sidebar-open');
            toggle.setAttribute('aria-expanded', terbuka ? 'true' : 'false');
            if (backdrop) {
                backdrop.hidden = desktop.matches || !root.classList.contains('sidebar-open');
            }
        }

        function tutupDrawer() {
            root.classList.remove('sidebar-open');
            sinkron();
        }

        toggle.addEventListener('click', function () {
            if (desktop.matches) {
                var ringkas = root.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem('sidebar', ringkas ? 'collapsed' : 'expanded');
                } catch (e) {
                    /* preferensi tidak tersimpan: tetap berlaku di halaman ini */
                }
            } else {
                root.classList.toggle('sidebar-open');
            }
            sinkron();
        });

        if (backdrop) {
            backdrop.addEventListener('click', tutupDrawer);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && root.classList.contains('sidebar-open')) {
                tutupDrawer();
                toggle.focus();
            }
        });
        desktop.addEventListener('change', tutupDrawer);
        sinkron();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTema();
        initFlash();
        initPopover();
        initSidebar();
    });
})();
