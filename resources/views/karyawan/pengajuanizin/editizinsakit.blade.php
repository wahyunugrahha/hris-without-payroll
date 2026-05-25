@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Edit Izin Sakit</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="ios-form-container">
        <form method="POST" action="{{ route('pengajuanizin.updateizinsakit', $dataizin->kode_izin) }}"
            id="frmeditizinsakit" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="ios-form-card">

                <div class="ios-form-group">
                    <label for="dari" class="ios-label">Dari Tanggal</label>
                    <input type="text" id="dari" name="dari" class="ios-input datepicker validate"
                        placeholder="Pilih Tanggal Mulai" required value="{{ $dataizin->tgl_izin_dari }}">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="sampai" class="ios-label">Sampai Tanggal</label>
                    <input type="text" id="sampai" name="sampai" class="ios-input datepicker validate"
                        placeholder="Pilih Tanggal Berakhir" required value="{{ $dataizin->tgl_izin_sampai }}">
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label for="jmlhari" class="ios-label">Jumlah Hari</label>
                    <input type="text" id="jmlhari" name="jmlhari" class="ios-input" placeholder="Jumlah hari sakit"
                        readonly>
                </div>

                <div class="ios-divider"></div>

                <div class="ios-form-group">
                    <label class="ios-label">Surat Dokter (Optional)</label>
                    <input type="file" name="sid" id="sid" accept=".pdf, .jpg, .jpeg, .png">
                        <small class="text-muted" style="display: block; margin-top: 5px;">
                        File saat ini:
                        @php
                            $sidPathEdit = null;
                                if (isset($dataizin->doc_sid) && $dataizin->doc_sid != '-') {
                                if (Storage::disk('public')->exists('uploads/sid/' . $dataizin->doc_sid)) {
                                    $sidPathEdit = asset('storage/uploads/sid/' . $dataizin->doc_sid);
                                } elseif (Storage::disk('public')->exists('public/uploads/sid/' . $dataizin->doc_sid)) {
                                    $sidPathEdit = asset('storage/public/uploads/sid/' . $dataizin->doc_sid);
                                }
                            }
                        @endphp
                        @if ($sidPathEdit)
                            <a href="{{ $sidPathEdit }}" target="_blank">Lihat File</a>
                        @else
                            (Belum ada file)
                        @endif
                    </small>
                </div>

                <div class="ios-divider"></div>

                <input type="hidden" name="status" value="s">

                <div class="ios-form-group">
                    <label for="keterangan" class="ios-label">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" class="ios-textarea validate" placeholder="Detail alasan sakit"
                        rows="4" required>{{ $dataizin->keterangan }}</textarea>
                </div>

            </div>

            <div class="ios-button-group">
                <button class="ios-button-primary" type="submit" name="action">
                    SIMPAN PERUBAHAN
                </button>
            </div>

        </form>
    </div>

    <div class="ios-safe-area"></div>
@endsection

@push('myscript')
    <script>
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
                    return 0;
                }

                var timeDiff = Math.abs(date2.getTime() - date1.getTime());
                diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

                if (diffDays > 0) {
                    $('#jmlhari').val(diffDays + ' Hari');
                } else {
                    $('#jmlhari').val('');
                }
                return diffDays;
            } else {
                $('#jmlhari').val('');
                return 0;
            }
        }

        $(document).ready(function() {
            calculateDays(); // Hitung saat load

            var batasMundur = new Date();
            batasMundur.setDate(batasMundur.getDate() - 30);
            var datepickers = document.querySelectorAll('.datepicker');
            M.Datepicker.init(datepickers, {
                format: "yyyy-mm-dd",
                autoClose: true,
                minDate: batasMundur,
            });

            $('#dari, #sampai').change(function() {
                calculateDays();
            });

            $("#frmeditizinsakit").submit(function(e) {
                var dari = $("#dari").val();
                var sampai = $("#sampai").val();
                var jmlhari_val = calculateDays();
                var keterangan = $("#keterangan").val();

                if (dari == "" || sampai == "" || jmlhari_val <= 0 || keterangan == "") {
                    Swal.fire({
                        title: 'Oopss!',
                        text: 'Semua field harus diisi dengan benar.',
                        icon: 'warning',
                    });
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endpush
