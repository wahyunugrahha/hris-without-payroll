@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="presensi-header">
        <a href="{{ route('lembur.index') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Absen Keluar Lembur</span>
        <div class="header-spacer"></div>
    </div>
    <style>
        .webcam-capture {
            border-radius: 12px;
            overflow: hidden;
            background: #000;
            width: 100%;
            aspect-ratio: 4/3;
            margin-bottom: 16px;
            position: relative;
        }

        .jam-digital {
            background-color: rgba(0, 0, 0, 0.7);
            position: absolute;
            top: 10px;
            right: 10px;
            color: #fff;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
        }

        .ios-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 12px;
            padding: 15px;
            color: white;
            margin-bottom: 15px;
        }

        .ios-card label {
            font-size: 10px;
            opacity: 0.8;
            text-transform: uppercase;
            display: block;
        }

        .ios-card div {
            font-weight: bold;
            font-size: 14px;
        }
    </style>
@endsection

@section('content')
    <div class="ios-form-container" style="padding: 16px;">
        <div class="ios-card">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label>Tanggal</label>
                    <div>{{ \Carbon\Carbon::parse($lembur->tanggal_lembur)->format('d M Y') }}</div>
                </div>
                <div>
                    <label>Jam Masuk</label>
                    <div>{{ $lembur->jam_mulai }}</div>
                </div>
                <div style="grid-column: span 2">
                    <label>Pekerjaan</label>
                    <div>{{ $lembur->pekerjaan }}</div>
                </div>
            </div>
        </div>



        <div style="position: relative; margin-bottom: 16px;">
            <div class="webcam-capture"></div>
            <div class="jam-digital" id="jam-now"></div>
        </div>

        <form method="POST" action="{{ route('lembur.storeAbsenKeluar', $lembur->id) }}" id="frmabsen">
            @csrf
            <input type="hidden" id="image" name="image">
            <button class="btn btn-primary btn-block btn-lg" type="button" id="btn-submit"
                style="width:100%; background:#f5576c; border:none; padding:12px; border-radius:10px; color:white; font-weight:bold;">
                <ion-icon name="camera-outline"></ion-icon> KONFIRMASI SELESAI
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

            Webcam.set({
                width: '100%',
                height: 'auto',
                dest_width: 640,
                dest_height: 480,
                image_format: 'jpeg',
                jpeg_quality: 90,
                crop_width: 640,
                crop_height: 480,
            });
            Webcam.attach('.webcam-capture');

            setInterval(() => {
                let now = new Date();
                $('#jam-now').text(now.toLocaleTimeString('en-GB', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                }));
            }, 1000);

            $('#btn-submit').click(function() {
                Webcam.snap(function(data_uri) {
                    // Validasi ukuran foto (3MB)
                    var imageSizeInBytes = data_uri.length * 0.75;
                    var maxSizeInBytes = 3 * 1024 * 1024;

                    if (imageSizeInBytes > maxSizeInBytes) {
                        Swal.fire({
                            title: 'Foto Terlalu Besar!',
                            text: 'Ukuran foto tidak boleh lebih dari 3MB. Silahkan ambil foto ulang.',
                            icon: 'warning',
                        });
                        return;
                    }

                    $('#image').val(data_uri);
                    Swal.fire({
                        title: 'Konfirmasi',
                        text: 'Lembur selesai?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Kirim',
                        confirmButtonColor: '#f5576c'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#frmabsen').submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
