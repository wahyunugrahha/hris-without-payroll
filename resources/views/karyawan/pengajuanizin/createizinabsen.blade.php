@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Form Pengajuan Izin Absen</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="/pengajuanizin/storeizinabsen" id="frmizinabsen">
            @csrf
            <div class="ios-form-card">
                <div class="ios-form-group">
                    <label for="dari" class="ios-label">Dari Tanggal</label>
                    <input type="text" id="dari" name="dari" class="ios-input datepicker validate"
                        placeholder="Pilih Tanggal Mulai" required>
                </div>

                <div class="ios-divider"></div>
                <div class="ios-form-group">
                    <label for="sampai" class="ios-label">Sampai Tanggal</label>
                    <input type="text" id="sampai" name="sampai" class="ios-input datepicker validate"
                        placeholder="Pilih Tanggal Berakhir" required>
                </div>

                <div class="ios-divider"></div>
                <div class="ios-form-group">
                    <label for="jmlhari" class="ios-label">Jumlah Hari</label>
                    <input type="text" id="jmlhari" name="jmlhari" class="ios-input" placeholder="Jumlah hari izin"
                        readonly>
                </div>

                <div class="ios-divider"></div>
                <input type="hidden" name="status" value="i">

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="ios-textarea validate"
                        placeholder="Detail alasan izin absen" rows="4" required></textarea>
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
        var BLACKLIST_DATES = []; // Global array untuk menyimpan tanggal terlarang

        function fetchBlacklistDates(callback) {
            $.ajax({
                type: 'POST',
                url: '{{ route('pengajuanizin.getblacklistdates') }}',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                cache: false,
                success: function (dates) {
                    BLACKLIST_DATES = dates;
                    if (callback) callback();
                }
            });
        }

        function initializeDatepickers() {
            // Membuat tanggal hari ini
            var batasMundur = new Date();
            // Mengurangi 30 hari dari hari ini
            batasMundur.setDate(batasMundur.getDate() - 30);

            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: "yyyy-mm-dd",
                autoClose: true,
                minDate: batasMundur,

                // Disabling function: memblokir tanggal yang sudah terisi
                disableDayFn: function (date) {
                    var formattedDate = date.getFullYear() + '-' +
                        ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                        ('0' + date.getDate()).slice(-2);

                    return BLACKLIST_DATES.includes(formattedDate);
                }
            });
        }

        $(document).ready(function () {
            // 1. Ambil blacklist dates saat dokumen siap, lalu inisialisasi datepicker
            fetchBlacklistDates(initializeDatepickers);

            function calculateDays() {
                var start_date_str = $('#dari').val();
                var end_date_str = $('#sampai').val();
                let diffDays = 0;

                if (start_date_str && end_date_str) {
                    var date1 = new Date(start_date_str);
                    var date2 = new Date(end_date_str);

                    if (isNaN(date1.getTime()) || isNaN(date2.getTime())) {
                        $('#jmlhari').val('');
                        return 0;
                    }

                    if (date1 > date2) {
                        $('#jmlhari').val('');
                        Swal.fire({
                            title: 'Oopss!',
                            text: 'Tanggal Mulai tidak boleh setelah Tanggal Selesai',
                            icon: 'error',
                        }).then(() => {
                            $('#sampai').val("");
                            $('#sampai').focus();
                        });
                        return 0;
                    }

                    var timeDiff = Math.abs(date2.getTime() - date1.getTime());
                    diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

                    if (diffDays > 0) {
                        $('#jmlhari').val(diffDays + ' Hari');
                    } else {
                        $('#jmlhari').val('');
                    }
                } else {
                    $('#jmlhari').val('');
                }
                return diffDays;
            }

            $('#dari, #sampai').change(function () {
                var calculatedDays = calculateDays();
                var dari = $('#dari').val();
                var sampai = $('#sampai').val();

                // Cek overlap hanya jika kedua tanggal terisi dan valid
                if (calculatedDays > 0 && dari !== "" && sampai !== "") {
                    // Cek di server untuk overlap rentang tanggal
                    cekPengajuanIzin(dari, sampai);
                }
            });

            // Mengirim rentang tanggal ke server untuk cek overlap
            function cekPengajuanIzin(tgl_dari, tgl_sampai) {
                $.ajax({
                    type: 'POST',
                    url: '/presensi/cekpengajuanizin',
                    data: {
                        _token: '{{ csrf_token() }}',
                        tgl_dari: tgl_dari,
                        tgl_sampai: tgl_sampai
                    },
                    cache: false,
                    success: function (respond) {
                        if (respond == 1) {
                            Swal.fire({
                                title: 'Oopss!',
                                text: 'Terdapat overlap pengajuan/absensi pada rentang tanggal tersebut.',
                                icon: 'warning'
                            }).then(() => {
                                $('#dari').val("");
                                $('#sampai').val("");
                                calculateDays();
                            });
                        }
                    }
                });
            }

            $("#frmizinabsen").submit(function (e) {
                var dari = $("#dari").val();
                var sampai = $("#sampai").val();
                var jmlhari_val = calculateDays();
                var keterangan = $("#keterangan").val();

                if (dari == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Tanggal Mulai Harus Diisi',
                        icon: 'warning',
                    }).then(() => {
                        $("#dari").focus();
                    });
                    e.preventDefault();
                    return false;
                }

                if (sampai == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Tanggal Selesai Harus Diisi',
                        icon: 'warning',
                    }).then(() => {
                        $("#sampai").focus();
                    });
                    e.preventDefault();
                    return false;
                }

                if (jmlhari_val <= 0) {
                    e.preventDefault();
                    return false;
                }

                if (keterangan == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Keterangan Harus Diisi',
                        icon: 'warning',
                    }).then(() => {
                        $("#keterangan").focus();
                    });
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endpush