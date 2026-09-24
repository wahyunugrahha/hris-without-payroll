/**
 * Menjaga token CSRF halaman tetap segar agar form & AJAX tidak gagal "Sesi telah berakhir" (419)
 * saat halaman lama terbuka, tab lain login/logout, atau sesi server sudah diganti.
 *
 *   <script src="assets/js/sesi.js" data-token="{{ csrf_token() }}" data-url="{{ route('csrf.token') }}"></script>
 *
 * - Token diambil ulang saat tab kembali aktif / field form difokus setelah lama diam, dan berkala.
 * - Semua input _token, meta csrf-token, serta AJAX jQuery memakai token terbaru.
 * - AJAX yang tetap kena 419: token diperbarui dan pengguna diminta mengulang aksinya.
 */
(function () {
    'use strict';

    var skrip = document.currentScript;
    if (!skrip || !skrip.dataset.url) {
        return;
    }

    var URL_TOKEN = skrip.dataset.url;
    var BATAS_SEGAR = 5 * 60 * 1000; // token dianggap perlu dicek ulang setelah 5 menit
    var token = skrip.dataset.token || '';
    var terakhir = Date.now();
    var sedangAmbil = null;

    function pasang(baru) {
        token = baru;
        document.querySelectorAll('input[name="_token"]').forEach(function (el) {
            el.value = baru;
        });
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            meta.setAttribute('content', baru);
        }
    }

    function segarkan() {
        if (sedangAmbil) {
            return sedangAmbil;
        }
        sedangAmbil = fetch(URL_TOKEN, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.token) {
                    pasang(data.token);
                }
                terakhir = Date.now();
            })
            .catch(function () { /* offline: coba lagi di kesempatan berikutnya */ })
            .then(function () { sedangAmbil = null; });

        return sedangAmbil;
    }

    function segarkanBilaBasi() {
        if (Date.now() - terakhir > BATAS_SEGAR) {
            segarkan();
        }
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            segarkanBilaBasi();
        }
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            segarkan();
        }
    });
    // Mulai mengisi form setelah lama diam → token sudah segar saat tombol simpan ditekan.
    document.addEventListener('focusin', function (e) {
        if (e.target.closest && e.target.closest('form')) {
            segarkanBilaBasi();
        }
    });
    setInterval(function () {
        if (!document.hidden) {
            segarkan();
        }
    }, 15 * 60 * 1000);

    window.tokenCsrf = function () {
        return token;
    };

    // AJAX jQuery: selalu kirim token terbaru (juga menimpa _token lama yang tertanam di data).
    function pasangJquery($) {
        $.ajaxPrefilter(function (opsi) {
            opsi.headers = opsi.headers || {};
            opsi.headers['X-CSRF-TOKEN'] = token;
            if (typeof opsi.data === 'string') {
                opsi.data = opsi.data.replace(/(^|&)_token=[^&]*/, '$1_token=' + encodeURIComponent(token));
            } else if (typeof FormData !== 'undefined' && opsi.data instanceof FormData && opsi.data.has('_token')) {
                opsi.data.set('_token', token);
            }
        });

        $(document).ajaxError(function (e, xhr) {
            if (xhr.status !== 419) {
                return;
            }
            var baru = xhr.responseJSON && xhr.responseJSON.token;
            (baru ? Promise.resolve(pasang(baru)) : segarkan()).then(function () {
                var pesan = 'Sesi halaman sudah diperbarui. Silakan ulangi aksi terakhir.';
                if (window.Swal) {
                    window.Swal.fire({ title: 'Coba lagi', text: pesan, icon: 'info', confirmButtonText: 'Ok' });
                } else {
                    window.alert(pesan);
                }
            });
        });
    }

    if (window.jQuery) {
        pasangJquery(window.jQuery);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) {
                pasangJquery(window.jQuery);
            }
        });
    }
})();
