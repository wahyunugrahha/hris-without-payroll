@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Absen Masuk Lembur</span>
        <div class="header-spacer"></div>
    </div>

    <style>
        .webcam-capture {
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            aspect-ratio: 4/3;
            width: 100%;
            margin-bottom: 16px;
        }

        .jam-digital-malasngoding {
            background-color: rgba(39, 39, 39, 0.85);
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 9999;
            width: auto;
            min-width: 140px;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
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

        @media (max-width: 480px) {
            .webcam-capture {
                border-radius: 10px;
            }
        }
    </style>
@endsection

@section('content')
    <div class="ios-form-container">
        {{-- Info Card --}}
        <div class="ios-form-card" style="margin-bottom: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.8); text-transform: uppercase; margin-bottom: 2px;">Tanggal</label>
                    <div style="font-size: 14px; color: white; font-weight: 600;">{{ \Carbon\Carbon::parse($lembur->tanggal_lembur)->format('d M Y') }}</div>
                </div>
                <div>
                    <label style="display: block; font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.8); text-transform: uppercase; margin-bottom: 2px;">Pekerjaan</label>
                    <div style="font-size: 12px; color: white;">{{ $lembur->pekerjaan }}</div>
                </div>
                <div colspan="2">
                    <label style="display: block; font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.8); text-transform: uppercase; margin-bottom: 2px;">Tempat</label>
                    <div style="font-size: 12px; color: white;">{{ $lembur->tempat }}</div>
                </div>
            </div>
        </div>

        {{-- Camera --}}
        <div style="position: relative; margin-bottom: 16px;">
            <div class="webcam-capture"></div>
            <div class="jam-digital-malasngoding">
                <p class="jam" style="font-weight: 600; font-size: 13px;"></p>
            </div>
        </div>

        <form method="POST" action="{{ route('lembur.storeAbsenMasuk', $lembur->id) }}" id="frmabsenmasuk">
            @csrf
            <input type="hidden" id="image_masuk" name="image">
            <input type="hidden" id="jam_mulai" name="jam_mulai">
            
            <div class="ios-form-card">
                <div style="background-color: #eef6fd; padding: 12px; border-radius: 8px; border-left: 4px solid #094b87;">
                    <div style="display: flex; align-items: start; gap: 8px;">
                        <ion-icon name="information-circle-outline" style="color: #15803d; font-size: 20px; margin-top: 2px;"></ion-icon>
                        <div>
                            <div style="font-size: 11px; font-weight: 700; color: #15803d; text-transform: uppercase; margin-bottom: 4px;">Catatan</div>
                            <div style="font-size: 13px; color: #15803d;">Waktu absen masuk akan otomatis tercatat berdasarkan waktu pengambilan foto.</div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="ios-button-primary" type="submit" id="takeabsen">
                <ion-icon name="camera-outline"></ion-icon>
                KONFIRMASI ABSEN MASUK
            </button>
        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
        $(document).ready(function() {
            @if (session('error'))
                Swal.fire({ title: 'Error!', text: '{{ session('error') }}', icon: 'error' });
            @endif

            const cancelUrl = "{{ route('lembur.cancelDraft', $lembur->id) }}";
            const csrfToken = "{{ csrf_token() }}";
            const alreadyStarted = @json(!empty($lembur->jam_mulai));
            let hasAbsenMasukSubmitted = alreadyStarted;

            // Kirim request pembatalan jika pengguna keluar tanpa absen
            function sendCancelDraft() {
                const payload = '_token=' + encodeURIComponent(csrfToken);

                if (navigator.sendBeacon) {
                    const blob = new Blob([payload], { type: 'application/x-www-form-urlencoded' });
                    return navigator.sendBeacon(cancelUrl, blob);
                }

                return fetch(cancelUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: payload
                });
            }

            // Tombol back di header - gunakan event capture untuk prioritas tertinggi
            $('.back-button').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                if (hasAbsenMasukSubmitted) {
                    window.history.back();
                    return; // jangan hapus jika sudah absen
                }
                
                // Konfirmasi pembatalan
                Swal.fire({
                    title: 'Batalkan Pengajuan?',
                    html: '<div style="text-align: left;"><p style="font-size: 14px; margin-bottom: 8px;">Anda belum melakukan absen masuk lembur.</p><p style="font-size: 13px; color: #dc2626; font-weight: 600;">Pengajuan lembur ini akan dihapus jika Anda kembali sekarang.</p></div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tetap di Sini'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Tampilkan loading
                        Swal.fire({
                            title: 'Membatalkan...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        // Kirim request pembatalan
                        fetch(cancelUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: '_token=' + encodeURIComponent(csrfToken)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'deleted') {
                                // Redirect dengan pesan
                                window.location.href = '{{ route("lembur.index") }}?cancelled=1&msg=' + encodeURIComponent(data.message || 'Pengajuan lembur telah dibatalkan.');
                            } else {
                                Swal.close();
                                window.history.back();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.close();
                            window.history.back();
                        });
                    }
                });
                
                return false;
            });

            // Hapus draft bila user menutup / reload tanpa absen
            window.addEventListener('beforeunload', function() {
                if (!hasAbsenMasukSubmitted) {
                    sendCancelDraft();
                }
            });

            // Webcam setup
            Webcam.set({
                width: '100%',
                height: 'auto',
                dest_width: 640,
                dest_height: 480,
                image_format: 'jpeg',
                jpeg_quality: 80,
                crop_width: 640,
                crop_height: 480,
            });

            Webcam.attach('.webcam-capture');

            // Set jam sekarang
            $('#btn_jam_sekarang').on('click', function() {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                $('#jam_mulai').val(hours + ':' + minutes);
            });

            // Validasi dan capture foto
            $('#takeabsen').on('click', function(e) {
                e.preventDefault();

                // Capture foto
                Webcam.snap(function(uri) {
                    $('#image_masuk').val(uri);
                    
                    // Validasi ukuran foto (3MB)
                    var imageSizeInBytes = uri.length * 0.75;
                    var maxSizeInBytes = 3 * 1024 * 1024;

                    if (imageSizeInBytes > maxSizeInBytes) {
                        Swal.fire({
                            title: 'Foto Terlalu Besar!',
                            text: 'Ukuran foto tidak boleh lebih dari 3MB. Silahkan ambil foto ulang.',
                            icon: 'warning',
                        });
                        return;
                    }

                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    $('#jam_mulai').val(hours + ':' + minutes);
                    
                    Swal.fire({
                        title: 'Konfirmasi',
                        text: 'Apakah Anda yakin akan melakukan absen masuk lembur?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Absen Masuk',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            hasAbsenMasukSubmitted = true;
                            $('#frmabsenmasuk').off('submit').submit();
                        }
                    });
                });
            });

            // Digital clock
            function jam() {
                var e = document.querySelector('.jam');
                if (!e) return;

                var d = new Date(),
                    h = String(d.getHours()).padStart(2, '0'),
                    m = String(d.getMinutes()).padStart(2, '0'),
                    s = String(d.getSeconds()).padStart(2, '0');
                
                e.innerHTML = h + ':' + m + ':' + s;
                setTimeout(jam, 1000);
            }

            jam();
        });
    </script>
@endpush

