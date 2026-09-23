/**
 * Perilaku global panel admin (layouts.admin.tabler):
 *  1. Toggle tema terang/gelap.
 *  2. Notifikasi flash dari server (SweetAlert).
 *  3. Command palette "Cari menu" (Ctrl/⌘ + K) dari link sidebar.
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

    // ── 3. Command palette ──────────────────────────────────────────────
    function kumpulkanMenu() {
        var hasil = [];
        var terlihat = {};

        document.querySelectorAll('.navbar-vertical a[href]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#' || a.classList.contains('dropdown-toggle') || terlihat[href]) {
                return;
            }
            var menu = a.closest('.dropdown-menu');
            var induk = menu && menu.previousElementSibling
                ? menu.previousElementSibling.textContent.trim()
                : '';
            terlihat[href] = true;
            hasil.push({ label: a.textContent.replace(/\s+/g, ' ').trim(), grup: induk, href: a.href });
        });

        return hasil;
    }

    function initPalette() {
        var dialog = document.getElementById('admin-palette');
        if (!dialog || typeof dialog.showModal !== 'function') {
            return;
        }

        var input = dialog.querySelector('input');
        var list = dialog.querySelector('[role="listbox"]');
        var menu = kumpulkanMenu();
        var cocok = [];
        var aktif = 0;

        function render() {
            var q = input.value.toLowerCase().trim();
            cocok = menu.filter(function (m) {
                return !q || (m.label + ' ' + m.grup).toLowerCase().indexOf(q) !== -1;
            });
            aktif = Math.min(aktif, Math.max(cocok.length - 1, 0));
            list.replaceChildren();

            if (!cocok.length) {
                var kosong = document.createElement('li');
                kosong.className = 'admin-palette-empty';
                kosong.textContent = 'Menu tidak ditemukan.';
                list.appendChild(kosong);
                input.removeAttribute('aria-activedescendant');
                return;
            }

            cocok.forEach(function (m, i) {
                var li = document.createElement('li');
                li.id = 'admin-palette-opt-' + i;
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', i === aktif ? 'true' : 'false');

                var label = document.createElement('span');
                label.textContent = m.label;
                li.appendChild(label);
                if (m.grup) {
                    var grup = document.createElement('small');
                    grup.textContent = m.grup;
                    li.appendChild(grup);
                }

                li.addEventListener('mousemove', function () {
                    if (aktif !== i) {
                        aktif = i;
                        tandai();
                    }
                });
                li.addEventListener('click', function () {
                    buka(i);
                });
                list.appendChild(li);
            });
            tandai();
        }

        function tandai() {
            list.querySelectorAll('[role="option"]').forEach(function (li, i) {
                li.setAttribute('aria-selected', i === aktif ? 'true' : 'false');
            });
            var terpilih = document.getElementById('admin-palette-opt-' + aktif);
            if (terpilih) {
                input.setAttribute('aria-activedescendant', terpilih.id);
                terpilih.scrollIntoView({ block: 'nearest' });
            }
        }

        function buka(i) {
            if (cocok[i]) {
                window.location.href = cocok[i].href;
            }
        }

        function tampilkan() {
            if (dialog.open) {
                return;
            }
            input.value = '';
            aktif = 0;
            render();
            dialog.showModal();
            input.focus();
        }

        input.addEventListener('input', function () {
            aktif = 0;
            render();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                aktif = (aktif + 1) % Math.max(cocok.length, 1);
                tandai();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                aktif = (aktif - 1 + cocok.length) % Math.max(cocok.length, 1);
                tandai();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                buka(aktif);
            }
        });

        // Klik di luar panel (backdrop) menutup dialog. Esc sudah ditangani <dialog>.
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog) {
                dialog.close();
            }
        });

        document.querySelectorAll('[data-admin-palette-open]').forEach(function (btn) {
            btn.addEventListener('click', tampilkan);
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                tampilkan();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTema();
        initFlash();
        initPalette();
    });
})();
