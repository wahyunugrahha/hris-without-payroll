@extends('layouts.presensi')

@section('header')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Form Izin</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="/presensi/storeizin" id="frmizin">
            @csrf
            <div class="ios-form-card">
                <div class="ios-form-group">
                    <label for="tgl_izin" class="ios-label">Tanggal Izin/Sakit</label>
                    <input type="text" id="tgl_izin" name="tgl_izin" class="ios-input datepicker"
                        placeholder="Pilih tanggal">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="status" class="ios-label">Status</label>
                    <div class="ios-select-wrapper">
                        <select name="status" id="status" class="ios-select">
                            <option value="" disabled selected>Pilih status</option>
                            <option value="i">Izin</option>
                            <option value="s">Sakit</option>
                        </select>
                        <ion-icon name="chevron-down" class="ios-select-icon"></ion-icon>
                    </div>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan Izin/Sakit</label>

                    <textarea name="keterangan" id="keterangan" class="ios-textarea" placeholder="Detail alasan izin/sakit" rows="4"></textarea>

                </div>

            </div>

            <div class="ios-button-group">
                <button class="ios-button-primary" type="submit" name="action">
                    AJUKAN IZIN
                </button>
            </div>

        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
        $(document).ready(function() {
            var today = new Date();
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: "yyyy-mm-dd",
                autoClose: true,
                minDate: today,
                setDefaultDate: true,
                defaultDate: today,
            });

            $("#tgl_izin").change(function(e) {
                var tgl_izin = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: '/presensi/cekpengajuanizin',
                    data: {
                        _token: '{{ csrf_token() }}',
                        tgl_izin: tgl_izin
                    },
                    cache: false,
                    success: function(respond) {
                        if (respond == 1) {
                            Swal.fire({
                                title: 'Oopss!',
                                text: 'Anda Sudah Melakukan Input Pengajuan Izin Pada Tanggal Tersebut',
                                icon: 'warning'
                            }).then((result) => {
                                $('#tgl_izin').val("");
                            });
                        }
                    }
                });
            });


            var selects = document.querySelectorAll('select');
            M.FormSelect.init(selects);


            $("#frmizin").submit(function(e) {
                var tgl_izin = $("#tgl_izin").val();
                var status = $("#status").val();
                var keterangan = $("#keterangan").val();

                if (tgl_izin == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Tanggal Izin Harus Diisi',
                        icon: 'warning',
                    }).then((result) => {
                        $("#tgl_izin").focus();
                    });
                    e.preventDefault();
                    return false;
                } else if (status == "" || status == null) {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Status Izin Harus Diisi',
                        icon: 'warning',
                    }).then((result) => {
                        $("#status").focus();
                    });
                    e.preventDefault();
                    return false;
                } else if (keterangan == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Keterangan Harus Diisi',
                        icon: 'warning',
                    }).then((result) => {
                        $("#keterangan").focus();
                    });
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endpush
