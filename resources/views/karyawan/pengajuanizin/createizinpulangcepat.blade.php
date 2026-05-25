@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Form Pengajuan Izin Pulang Cepat</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="{{ route('karyawan.izin.pulangcepat.store') }}" id="frmizinpulangcepat">
            @csrf
            <div class="ios-form-card">
                <div class="ios-form-group">
                    <label class="ios-label">Tanggal</label>
                    <input type="text" class="ios-input" value="{{ date('Y-m-d') }}" readonly>
                    <input type="hidden" name="dari" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="sampai" value="{{ date('Y-m-d') }}">
                    <input type="hidden" name="status" value="p">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="ios-textarea validate" placeholder="Detail alasan pulang cepat"
                        rows="4" required></textarea>
                </div>

            </div>

            <div class="ios-button-group">
                <button class="ios-button-primary" type="submit" name="action">
                    KIRIM PENGAJUAN
                </button>
            </div>

        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
        $(function() {
            $("#frmizinpulangcepat").on('submit', function(e) {
                var ket = $("#keterangan").val();
                if (ket.trim() === '') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Keterangan Harus Diisi',
                        icon: 'warning'
                    }).then(() => {
                        $("#keterangan").focus();
                    });
                }
            });
        });
    </script>
@endpush
