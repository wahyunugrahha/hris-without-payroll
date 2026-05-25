@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Form Pengajuan Lembur</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="{{ route('lembur.store') }}" id="frmlembur">
            @csrf
            <div class="ios-form-card">

                <div class="ios-form-group">
                    <label for="tanggal_lembur" class="ios-label">Hari Tanggal</label>
                    <input type="text" id="tanggal_lembur" name="tanggal_lembur" class="ios-input datepicker validate" placeholder="YYYY-MM-DD" value="{{ old('tanggal_lembur') }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="pekerjaan" class="ios-label">Pekerjaan</label>
                    <input type="text" id="pekerjaan" name="pekerjaan" class="ios-input validate" placeholder="Contoh: Menyelesaikan laporan bulanan" value="{{ old('pekerjaan') }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="tempat" class="ios-label">Tempat</label>
                    <input type="text" id="tempat" name="tempat" class="ios-input validate" placeholder="Contoh: Kantor Pusat" value="{{ old('tempat') }}" required>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan / Catatan</label>
                    <textarea id="keterangan" name="keterangan" class="ios-textarea" rows="4" placeholder="Masukkan keterangan tambahan (opsional)">{{ old('keterangan') }}</textarea>
                </div>

            </div>

            <button class="ios-button-primary" type="submit">LANJUT KE ABSEN MASUK</button>
        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
        function initDatepickers() {
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: "yyyy-mm-dd",
                autoClose: true,
                minDate: new Date()
            });
        }

        $(document).ready(function() {
            @if (session('error'))
                Swal.fire({ title: 'Error!', text: '{{ session('error') }}', icon: 'error' });
            @endif

            initDatepickers();

            $('#frmlembur').on('submit', function(e) {
                var tanggal = $('#tanggal_lembur').val();
                var pekerjaan = $('#pekerjaan').val();
                var tempat = $('#tempat').val();

                if (!tanggal) {
                    Swal.fire({ title: 'Oopss!', text: 'Tanggal lembur harus diisi', icon: 'warning' });
                    e.preventDefault();
                    return false;
                }

                if (!pekerjaan) {
                    Swal.fire({ title: 'Oopss!', text: 'Pekerjaan harus diisi', icon: 'warning' });
                    e.preventDefault();
                    return false;
                }

                if (!tempat) {
                    Swal.fire({ title: 'Oopss!', text: 'Tempat harus diisi', icon: 'warning' });
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endpush

