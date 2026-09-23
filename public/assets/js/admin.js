/**
 * Perilaku global panel admin (layouts.admin.tabler):
 *  1. Toggle tema terang/gelap.
 *  2. Notifikasi flash dari server (SweetAlert).
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

    document.addEventListener('DOMContentLoaded', function () {
        initTema();
        initFlash();
    });
})();
