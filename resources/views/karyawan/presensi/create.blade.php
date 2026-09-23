@extends('layouts.presensi')
@section('header')
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Presensi</span>
        <a href="#" class="header-spacer headerButton" data-toggle="modal" data-target="#infoModal"></a>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

<style>
    /* Wrapper Utama agar responsif dan rata tengah di Desktop/Mobile */
    .presensi-container {
        padding: 16px;
        background: #f5f5f5;
        min-height: calc(100vh - 120px);
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .presensi-wrapper {
        width: 100%;
        max-width: 600px;
        /* Batas maksimal untuk Desktop */
    }

    /* Area Kamera */
    .presensi-card {
        position: relative;
        margin-bottom: 16px;
        border-radius: 12px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .webcam-capture {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .webcam-capture video {
        width: 100% !important;
        height: auto !important;
        object-fit: cover;
        filter: contrast(1.1) brightness(1.05);
    }

    /* Jam Digital */
    .jam-digital-malasngoding {
        background-color: rgba(39, 39, 39, 0.85);
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 10;
        min-width: 130px;
        border-radius: 10px;
        padding: 10px 12px;
        backdrop-filter: blur(4px);
    }

    .jam-digital-malasngoding p {
        color: #fff;
        font-size: 12px;
        text-align: left;
        margin-top: 0;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .jam-digital-malasngoding p:last-child {
        margin-bottom: 0;
    }

    /* Tombol Switch Kamera */
    #switch-camera {
        position: absolute;
        bottom: 16px;
        right: 16px;
        z-index: 10;
        border-radius: 50% !important;
        width: 55px !important;
        height: 55px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;
        border: 2px solid white !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }

    #switch-camera ion-icon {
        font-size: 28px !important;
        --ionicon-stroke-width: 40px;
        color: white !important;
    }

    /* Tombol Presensi */
    .presensi-button {
        width: 100%;
        margin: 0 0 16px 0;
        padding: 14px 20px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        height: auto !important;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .presensi-button ion-icon {
        font-size: 22px;
    }

    /* Map */
    #map {
        width: 100%;
        height: 200px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        z-index: 1;
    }

    /* Penyesuaian untuk layar sangat kecil (Mobile kecil) */
    @media (max-width: 400px) {
        .jam-digital-malasngoding {
            top: 8px;
            right: 8px;
            padding: 8px;
            min-width: 110px;
        }

        .jam-digital-malasngoding p {
            font-size: 11px;
        }

        #switch-camera {
            width: 45px !important;
            height: 45px !important;
            bottom: 10px;
            right: 10px;
        }

        #switch-camera ion-icon {
            font-size: 24px !important;
        }

        .presensi-button {
            padding: 12px 16px !important;
            font-size: 15px !important;
        }
    }
</style>

@section('content')
    <div class="presensi-container">
        <div class="presensi-wrapper">

            <div class="presensi-card">
                <input type="hidden" id="lokasi">
                <div class="webcam-capture"></div>

                <button id="switch-camera" class="btn btn-success">
                    <ion-icon name="camera-reverse-outline"></ion-icon>
                </button>

                <div class="jam-digital-malasngoding">
                    <p><strong>{{ $harini }}</strong></p>
                    <p class="jam" style="font-weight: 600; font-size: 14px;"></p>
                    <p style="margin-top: 6px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 4px;">
                        @if (!empty($dinasLuar))
                            Dinas Luar
                        @else
                            {{ $jamkerja->nama_jam_kerja ?? 'Jam Kerja Belum Diatur' }}
                        @endif
                    </p>
                    @if (empty($dinasLuar))
                        <p>Mulai: {{ date('H:i', strtotime($jamkerja->awal_jam_masuk ?? '00:00:00')) }}</p>
                        <p>Masuk: {{ date('H:i', strtotime($jamkerja->jam_masuk ?? '00:00:00')) }}</p>
                        <p>Akhir: {{ date('H:i', strtotime($jamkerja->akhir_jam_masuk ?? '00:00:00')) }}</p>
                        <p>Pulang: {{ date('H:i', strtotime($jamkerja->jam_pulang ?? '00:00:00')) }}</p>
                    @else
                        <p>Status: Sedang Dinas Luar</p>
                        <p>Periode: {{ date('d/m', strtotime($dinasLuar->tgl_mulai)) }} -
                            {{ date('d/m', strtotime($dinasLuar->tgl_selesai)) }}</p>
                    @endif
                </div>
            </div>

            @if ($cek)
                @if ($cek->jam_out == null)
                    @if ($cek->foto_in == '-')
                        <div class="alert alert-info text-center" role="alert"
                            style="margin-bottom: 16px; border-radius: 10px; font-size: 13px;">
                            <ion-icon name="information-circle-outline"
                                style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></ion-icon>
                            Telah Presensi Masuk (Otomatis via Izin). Silakan lakukan Absen Pulang saat jam kerja berakhir.
                        </div>
                    @endif
                    <button id="takeabsen" class="btn btn-warning btn-block presensi-button" data-absen-type="out">
                        <ion-icon name="camera-outline"></ion-icon>
                        Absen Pulang
                    </button>
                @else
                    <div class="alert alert-success text-center" role="alert"
                        style="margin-bottom: 16px; border-radius: 10px;">
                        Anda sudah melakukan Presensi Masuk & Pulang hari ini.
                    </div>
                @endif
            @else
                <button id="takeabsen" class="btn btn-primary btn-block presensi-button" data-absen-type="in">
                    <ion-icon name="camera-outline"></ion-icon>
                    Absen Masuk
                </button>
            @endif

            <div id="map"></div>

        </div>
    </div>

    <audio id="notifikasi_in">
        <source src="{{ asset('assets/sound/notifikasi_in.mp3') }}" type="audio/mpeg">
    </audio>
    <audio id="notifikasi_out">
        <source src="{{ asset('assets/sound/notifikasi_out.mp3') }}" type="audio/mpeg">
    </audio>
@endsection

@push('myscript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var notifikasi_in = document.getElementById('notifikasi_in');
        var notifikasi_out = document.getElementById('notifikasi_out');
        var status_absen = $("#takeabsen").data('absen-type');
        var currentFacingMode = 'user';

        function configureWebcam(facingMode) {
            const isPortrait = window.innerHeight > window.innerWidth;
            const w = isPortrait ? 480 : 640;
            const h = isPortrait ? 640 : 480;

            Webcam.set({
                width: '100%',
                height: 'auto',
                dest_width: w,
                dest_height: h,
                image_format: 'jpeg',
                jpeg_quality: 75,
                crop_width: w,
                crop_height: h,
                constraints: {
                    facingMode: facingMode,
                    width: {
                        ideal: w
                    },
                    height: {
                        ideal: h
                    }
                }
            });
            Webcam.reset();
            Webcam.attach('.webcam-capture');
        }

        $(document).ready(function() {
            configureWebcam(currentFacingMode);
        });

        window.addEventListener("orientationchange", function() {
            setTimeout(() => {
                configureWebcam(currentFacingMode);
            }, 500);
        });

        $('#switch-camera').click(function() {
            currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
            configureWebcam(currentFacingMode);
        });

        var lokasi = document.getElementById('lokasi');
        var map;
        var marker;

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(successCallback, errorCallback, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: 'Geolocation tidak didukung oleh browser ini.',
                icon: 'error',
            });
        }

        function successCallback(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            lokasi.value = lat + "," + lon;

            if (!map) {
                map = L.map('map').setView([lat, lon], 18);
                var lokasiList = @json($lokasi_list ?? []);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                marker = L.marker([lat, lon]).addTo(map)
                    .bindPopup('Lokasi Anda')
                    .openPopup();

                var bounds = L.latLngBounds();
                bounds.extend([lat, lon]);

                if (lokasiList.length > 0) {
                    lokasiList.forEach(function(l) {
                        var circle = L.circle([l.lat, l.lon], {
                            color: 'red',
                            fillColor: '#f03',
                            fillOpacity: 0.35,
                            radius: l.radius
                        }).addTo(map).bindPopup("Jarak Presensi " + (l.nama || '') + " (" + l.radius + " meter)");
                        bounds.extend(circle.getBounds());
                    });
                } else {
                    var lokasi_kantor = "{{ $lokasi_kantor->lokasi_kantor ?? '0,0' }}".split(',');
                    var lat_kantor = lokasi_kantor[0];
                    var lon_kantor = lokasi_kantor[1];
                    var radius = "{{ $lokasi_kantor->radius ?? 0 }}";
                    var circle = L.circle([lat_kantor, lon_kantor], {
                        color: 'red',
                        fillColor: '#f03',
                        fillOpacity: 0.35,
                        radius: radius
                    }).addTo(map).bindPopup("Jarak Presensi (" + radius + " meter)");
                    bounds.extend(circle.getBounds());
                }

                map.fitBounds(bounds, { padding: [50, 50] });

                setTimeout(function() {
                    map.invalidateSize();
                }, 500);
            } else {
                marker.setLatLng([lat, lon]);
            }
        }

        function errorCallback(error) {
            let errorMessage = '';
            let title = 'Gagal Lokasi!';

            switch (error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage =
                        'Izin lokasi (GPS) ditolak oleh Anda. Silahkan izinkan akses lokasi di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Informasi lokasi tidak tersedia (sinyal GPS lemah/terhalang).';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Waktu tunggu untuk mendapatkan lokasi habis. Coba lagi atau pastikan GPS Anda aktif.';
                    break;
                default:
                    errorMessage = 'Terjadi kesalahan yang tidak diketahui saat mendapatkan lokasi.';
                    break;
            }

            Swal.fire({
                title: title,
                text: errorMessage,
                icon: 'error',
            });
        }

        $('#takeabsen').click(function(e) {
            e.preventDefault();
            var $btn = $(this);
            var originalHtml = $btn.html();
            var lokasi_val = $("#lokasi").val();

            if (lokasi_val == "") {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tunggu sebentar, lokasi belum terdeteksi. Pastikan GPS Anda aktif dan berikan izin lokasi.',
                    icon: 'warning',
                });
                return;
            }

            // Disable button to prevent multiple clicks
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...'
                );

            Webcam.snap(function(uri) {
                var image = uri;
                var imageSizeInBytes = image.length * 0.75;
                var maxSizeInBytes = 3 * 1024 * 1024;

                if (imageSizeInBytes > maxSizeInBytes) {
                    Swal.fire({
                        title: 'Foto Terlalu Besar!',
                        text: 'Ukuran foto tidak boleh lebih dari 3MB. Silahkan ambil foto dengan kualitas lebih rendah.',
                        icon: 'warning',
                    });
                    $btn.prop('disabled', false).html(originalHtml);
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: '/presensi/store',
                    data: {
                        _token: "{{ csrf_token() }}",
                        image: image,
                        lokasi: lokasi_val,
                        absen_type: status_absen
                    },
                    cache: false,
                    success: function(respond) {
                        if (respond.success) {
                            if (status_absen == 'in') {
                                notifikasi_in.play();
                            } else if (status_absen == 'out') {
                                notifikasi_out.play();
                            }

                            Swal.fire({
                                title: 'Berhasil!',
                                text: respond.message ||
                                    'Terimakasih, Selamat Bekerja!',
                                icon: 'success',
                            }).then((result) => {
                                window.location.reload();
                            });
                        } else {
                            $btn.prop('disabled', false).html(originalHtml);
                            Swal.fire({
                                title: 'Error!',
                                text: respond.error ||
                                    'Terjadi kesalahan saat menyimpan data. Silahkan hubungi team terkait.',
                                icon: 'error',
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false).html(originalHtml);
                        if (xhr.responseJSON && xhr.responseJSON.redirect_url) {
                            Swal.fire({
                                title: 'KPI Belum Lengkap!',
                                text: xhr.responseJSON.error,
                                icon: 'warning',
                                confirmButtonText: 'Isi KPI Sekarang',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = xhr.responseJSON
                                        .redirect_url;
                                }
                            });
                            return;
                        }

                        let errorMessage = 'Terjadi kesalahan saat mengirim data ke server.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Error Server!',
                            text: errorMessage,
                            icon: 'error',
                        });
                    }
                });
            });
        });
    </script>

    <script type="text/javascript">
        function set(e) {
            return e < 10 ? '0' + e : e;
        }

        function jam() {
            var e = document.querySelector('.jam');
            if (!e) return;

            var d = new Date();
            e.innerHTML = set(d.getHours()) + ':' + set(d.getMinutes()) + ':' + set(d.getSeconds());

            setTimeout(jam, 1000);
        }

        $(document).ready(function() {
            jam();
        });
    </script>
@endpush
