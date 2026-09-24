<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <meta name="theme-color" content="#094b87">
    <title>Sistem Absensi wndev</title>
    <meta name="description" content="Sistem Absensi Karyawan wndev">
    <meta name="keywords" content="absensi, karyawan, wndev" />

    <link rel="icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="manifest" href="{{ asset_v('__manifest.json') }}">
    <link rel="stylesheet" href="{{ asset_v('assets/css/style.css') }}">

    <style>
        .presensi-header {
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        #appCapsule {
            padding-top: 0;
        }

        body {
            scroll-behavior: smooth;
        }

        .izin-announce-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            padding: 16px;
            backdrop-filter: blur(3px);
        }

        .izin-announce-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .izin-announce-box {
            width: 100%;
            max-width: 420px;
            max-height: 80vh;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.25);
            overflow: hidden;
            transform: translateY(10px);
            transition: transform 0.2s ease;
        }

        .izin-announce-overlay.show .izin-announce-box {
            transform: translateY(0);
        }

        .izin-announce-head {
            background: linear-gradient(135deg, #2d6ea6 0%, #0d5eaa 100%);
            color: #fff;
            padding: 14px 16px;
            position: relative;
        }

        .izin-announce-head h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .izin-announce-head p {
            margin: 4px 0 0;
            font-size: 12px;
            opacity: 0.95;
        }

        .izin-announce-close-x {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .izin-announce-close-x:hover {
            background: rgba(255, 255, 255, 0.28);
        }

        .izin-announce-close-x:active {
            transform: scale(0.95);
        }

        .izin-announce-close-x ion-icon {
            font-size: 18px;
        }

        .izin-announce-body {
            padding: 14px;
            overflow: auto;
            max-height: calc(80vh - 140px);
        }

        .izin-announce-item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8fafc;
        }

        .izin-announce-item:last-child {
            margin-bottom: 0;
        }

        .izin-announce-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }

        .izin-announce-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 999px;
        }

        .izin-announce-badge.approved {
            color: #063a6b;
            background: #e8f2fb;
        }

        .izin-announce-badge.rejected {
            color: #991b1b;
            background: #fee2e2;
        }

        .izin-announce-time {
            font-size: 11px;
            color: #64748b;
            white-space: nowrap;
        }

        .izin-announce-item p {
            margin: 0;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.45;
        }

        .izin-announce-note {
            margin-top: 6px;
            color: #7f1d1d;
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            padding: 6px 8px;
            border-radius: 6px;
            font-size: 11px;
        }

        .izin-announce-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 8px;
        }

        .izin-announce-btn {
            border: 0;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 10px;
            cursor: pointer;
            text-decoration: none;
        }

        .izin-announce-btn.detail {
            color: #fff;
            background: #2d6ea6;
        }

    </style>
</head>

<body style="background-color:#e9ecef;">

    <div id="loader">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    @yield('header')

    <div id="appCapsule">
        @yield('content')
    </div>

    <div id="izinNotificationOverlay" class="izin-announce-overlay" aria-hidden="true">
        <div class="izin-announce-box" role="dialog" aria-modal="true" aria-labelledby="izinNotificationTitle">
            <div class="izin-announce-head">
                <button type="button" id="izinNotificationClose" class="izin-announce-close-x" aria-label="Tutup notifikasi">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
                <h4 id="izinNotificationTitle">Notifikasi Status Izin</h4>
                <p>Berikut notifikasi pembaruan status pengajuan izin Anda.</p>
            </div>
            <div id="izinNotificationBody" class="izin-announce-body"></div>
        </div>
    </div>

    @include('layouts.bottomNav')

    @include('layouts.script')

    <script>
        (function() {
            const notifications = @json(($karyawanIzinNotifications ?? collect())->values());

            if (!Array.isArray(notifications) || notifications.length === 0) {
                return;
            }

            const overlay = document.getElementById('izinNotificationOverlay');
            const body = document.getElementById('izinNotificationBody');
            const closeBtn = document.getElementById('izinNotificationClose');

            if (!overlay || !body || !closeBtn) {
                return;
            }

            const keyOf = (item) => 'izin-notification-seen-' + item.kode_izin + '-' + item.status + '-' + item.updated_at;

            const storageGet = (key) => {
                try {
                    return localStorage.getItem(key);
                } catch (e) {
                    return null;
                }
            };

            const storageSet = (key, value) => {
                try {
                    localStorage.setItem(key, value);
                } catch (e) {
                    // Ignore browser storage restrictions.
                }
            };

            const markSeen = (item) => {
                if (!item || !item.kode_izin || !item.updated_at) {
                    return;
                }
                storageSet(keyOf(item), '1');
            };

            const unseen = notifications.filter((item) => {
                if (!item || !item.kode_izin || !item.updated_at) {
                    return false;
                }
                return !storageGet(keyOf(item));
            });

            if (!unseen.length) {
                return;
            }

            const escapeHtml = (value) => {
                return String(value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            };

            body.innerHTML = unseen.map((item, index) => {
                const isApproved = parseInt(item.status, 10) === 1;
                return `
                    <div class="izin-announce-item" data-index="${index}">
                        <div class="izin-announce-row">
                            <span class="izin-announce-badge ${isApproved ? 'approved' : 'rejected'}">
                                ${isApproved ? 'Disetujui' : 'Ditolak'}
                            </span>
                            <span class="izin-announce-time">${escapeHtml(item.updated_human || '')}</span>
                        </div>
                        <p>Pengajuan <b>${escapeHtml(item.jenis || 'Izin')}</b> dengan kode <b>${escapeHtml(item.kode_izin)}</b> telah ${isApproved ? 'disetujui' : 'ditolak'}.</p>
                        ${item.catatan_ditolak ? `<div class="izin-announce-note">Catatan: ${escapeHtml(item.catatan_ditolak)}</div>` : ''}
                        <div class="izin-announce-actions">
                            <a class="izin-announce-btn detail" href="${escapeHtml(item.detail_url || '/pengajuanizin/index')}">Lihat Detail</a>
                        </div>
                    </div>
                `;
            }).join('');

            overlay.classList.add('show');
            overlay.setAttribute('aria-hidden', 'false');

            closeBtn.addEventListener('click', function() {
                unseen.forEach(markSeen);
                overlay.classList.remove('show');
                overlay.setAttribute('aria-hidden', 'true');
            });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    unseen.forEach(markSeen);
                    overlay.classList.remove('show');
                    overlay.setAttribute('aria-hidden', 'true');
                }
            });

            body.addEventListener('click', function(e) {
                const targetLink = e.target.closest('a.izin-announce-btn.detail');
                if (!targetLink) {
                    return;
                }

                const itemEl = targetLink.closest('.izin-announce-item');
                const idx = itemEl ? Number(itemEl.getAttribute('data-index')) : NaN;
                if (!Number.isNaN(idx) && unseen[idx]) {
                    markSeen(unseen[idx]);
                }
            });
        })();
    </script>
</body>

</html>
