/**
 * Perilaku global panel admin (layouts.admin.tabler):
 *  1. Toggle tema terang/gelap.
 *  2. Notifikasi flash dari server (SweetAlert).
 *  3. Popover notifikasi & akun di topbar.
 *  4. Sidebar ringkas (desktop) / drawer (mobile).
 *  5. Form daftar: filter auto-submit & konfirmasi aksi berbahaya.
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
        if (flash.error) {
            Swal.fire({ title: 'Gagal!', text: flash.error, icon: 'error', confirmButtonText: 'Ok' });
        }
        if (flash.errors && flash.errors.length && !document.querySelector('[data-inline-errors]')) {
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

    // ── 5. Form daftar ──────────────────────────────────────────────────
    // <select data-auto-submit> mengirim form filternya saat berubah.
    // <button type="submit" data-confirm="Teks"> meminta konfirmasi sebelum form dikirim.
    function initFormDaftar() {
        document.addEventListener('change', function (e) {
            if (e.target.matches('[data-auto-submit]') && e.target.form) {
                e.target.form.submit();
            }
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-confirm]');
            if (!btn || !btn.form || typeof Swal === 'undefined') {
                return;
            }
            e.preventDefault();
            Swal.fire({
                title: btn.dataset.confirmTitle || 'Hapus data?',
                text: btn.dataset.confirm,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-danger)',
                confirmButtonText: btn.dataset.confirmOk || 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function (hasil) {
                if (hasil.isConfirmed) {
                    btn.form.submit();
                }
            });
        });
    }

    // Hapus data master yang punya relasi: GET {url} → {success, relations:{kunci: jumlah}},
    // tampilkan dampaknya, lalu kirim form hapus bila dikonfirmasi.
    //   opsi = { url, jenis: 'cabang', nama, form: HTMLFormElement,
    //            kosongkan: {karyawan: 'karyawan'}, ikutTerhapus: {kpi: 'data KPI'} }
    window.hapusDenganRelasi = function (opsi) {
        var esc = function (t) {
            var d = document.createElement('div');
            d.textContent = t;
            return d.innerHTML;
        };
        var daftar = function (rels, label) {
            return Object.keys(label || {}).filter(function (k) { return rels[k] > 0; })
                .map(function (k) { return '<li>' + rels[k] + ' ' + label[k] + '</li>'; }).join('');
        };

        Swal.fire({
            title: 'Memeriksa data…',
            text: 'Menganalisis data yang terkait dengan ' + opsi.jenis + ' ini.',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); }
        });

        fetch(opsi.url, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    Swal.fire('Gagal', 'Keterkaitan data tidak dapat diperiksa.', 'error');
                    return;
                }
                var kosong = daftar(res.relations, opsi.kosongkan);
                var hapus = daftar(res.relations, opsi.ikutTerhapus);
                var html = '<p class="mb-3">Hapus ' + opsi.jenis + ' <b>' + esc(opsi.nama) + '</b>?</p>';
                if (kosong || hapus) {
                    html += '<div class="text-start small">';
                    if (kosong) html += '<p class="mb-1">Akan <b>dikosongkan</b> dan perlu diatur ulang:</p><ul class="mb-2">' + kosong + '</ul>';
                    if (hapus) html += '<p class="mb-1 text-danger">Ikut <b>terhapus permanen</b>:</p><ul class="mb-0 text-danger">' + hapus + '</ul>';
                    html += '</div>';
                } else {
                    html += '<p class="text-secondary small mb-0">Tidak ada data lain yang bergantung pada ' + opsi.jenis + ' ini.</p>';
                }

                Swal.fire({
                    title: 'Hapus ' + opsi.jenis + '?',
                    html: html,
                    icon: kosong || hapus ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then(function (hasil) {
                    if (hasil.isConfirmed) opsi.form.submit();
                });
            })
            .catch(function () {
                Swal.fire('Gagal', 'Tidak dapat menghubungi server.', 'error');
            });
    };

    document.addEventListener('DOMContentLoaded', function () {
        initFormDaftar();
        initTema();
        initFlash();
        initPopover();
        initSidebar();
    });
})();
