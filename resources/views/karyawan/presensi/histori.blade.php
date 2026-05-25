@extends('layouts.presensi')

@section('header')
    {{-- Header tetap sesuai permintaan (tidak dirubah style warnanya) --}}
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Histori Presensi</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    {{-- Filter Section dengan tampilan Card Modern --}}
    <div class="row" style="margin-top: 20px;">
        <div class="col-12">
            <div class="card shadow-sm border-0 mb-3" style="border-radius: 15px;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <h5 class="card-title mb-0" style="font-size: 1rem; font-weight: 600;">Filter Histori</h5>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-1">Bulan</label>
                                <select name="bulan" id="bulan" class="form-control custom-select">
                                    <option value="">Pilih Bulan</option>
                                    @for ($i = 1; $i <= 12; $i++)
                                        @php
                                            $prevMonth = $i == 1 ? 12 : $i - 1;
                                            $label = '26 ' . $namabulan[$prevMonth] . ' - 25 ' . $namabulan[$i];
                                        @endphp
                                        <option value="{{ $i }}" {{ date('m') == $i ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group mb-0">
                                <label class="text-muted small mb-1">Tahun</label>
                                <select name="tahun" id="tahun" class="form-control custom-select">
                                    <option value="">Pilih Tahun</option>
                                    @php
                                        $tahun_sekarang = date('Y');
                                        $tahun_awal = $tahun_sekarang - 5;
                                    @endphp
                                    @for ($t = $tahun_sekarang; $t >= $tahun_awal; $t--)
                                        <option value="{{ $t }}" {{ date('Y') == $t ? 'selected' : '' }}>
                                            {{ $t }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <button class="btn btn-primary btn-block btn-lg shadow-sm" id="btnCari"
                                style="border-radius: 10px;">
                                <ion-icon name="search-outline"></ion-icon> Cari Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- List Container --}}
    <div class="row">
        <div class="col-12" id="historiList">
            {{-- Data akan dimuat disini via AJAX --}}
        </div>
    </div>

    {{-- Spacer agar tidak tertutup menu bawah (jika ada) --}}
    <div style="height: 70px;"></div>

    {{-- MODAL DETAIL (Dipercantik) --}}
    <div class="modal fade" id="historiDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Detail Presensi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{-- Status Badge Besar --}}
                    <div class="text-center mb-3">
                        <span id="historiTanggal" class="d-block font-weight-bold text-dark"
                            style="font-size: 1.1rem;">-</span>
                        <span id="historiStatus" class="badge badge-pill badge-primary mt-1"
                            style="font-size: 0.9rem; padding: 8px 15px;">-</span>
                    </div>

                    {{-- Grid Informasi --}}
                    <div class="card bg-light border-0 mb-3" style="border-radius: 10px;">
                        <div class="card-body py-2">
                            <div class="row text-center">
                                <div class="col-6 border-right">
                                    <small class="text-muted">Jam Masuk</small>
                                    <h6 class="mb-0 font-weight-bold text-success" id="historiJamMasuk">-</h6>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Jam Pulang</small>
                                    <h6 class="mb-0 font-weight-bold text-danger" id="historiJamKeluar">-</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted mb-0">Keterangan:</label>
                        <p class="mb-0 font-weight-500" id="historiKeterangan">-</p>
                    </div>

                    <hr>

                    {{-- Foto Grid --}}
                    <div class="row">
                        <div class="col-6">
                            <label class="small text-muted mb-1 d-block text-center">Foto Masuk</label>
                            <div class="img-thumbnail rounded overflow-hidden p-0"
                                style="height: 120px; display: flex; align-items: center; justify-content: center; background: #f1f1f1;">
                                <img id="historiFotoMasuk" src="" class="img-fluid"
                                    style="height: 100%; width: 100%; object-fit: cover; display: none;">
                                <span id="historiFotoMasukEmpty" class="text-muted small" style="display: none;"><ion-icon
                                        name="image-outline"></ion-icon> No Img</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted mb-1 d-block text-center">Foto Pulang</label>
                            <div class="img-thumbnail rounded overflow-hidden p-0"
                                style="height: 120px; display: flex; align-items: center; justify-content: center; background: #f1f1f1;">
                                <img id="historiFotoKeluar" src="" class="img-fluid"
                                    style="height: 100%; width: 100%; object-fit: cover; display: none;">
                                <span id="historiFotoKeluarEmpty" class="text-muted small" style="display: none;"><ion-icon
                                        name="image-outline"></ion-icon> No Img</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-block rounded-pill"
                        data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            loadHistori();

            $("#btnCari").click(function(e) {
                e.preventDefault();
                loadHistori();
            });

            function loadHistori() {
                var bulan = $("#bulan").val();
                var tahun = $("#tahun").val();

                if (bulan === "" || tahun === "") {
                    // Alert yang lebih modern
                    return;
                }

                $("#historiList").html(
                    '<div class="text-center mt-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Memuat data...</p></div>'
                );

                $.ajax({
                    type: 'POST',
                    url: '{{ route('presensi.gethistori') }}',
                    data: {
                        _token: "{{ csrf_token() }}",
                        bulan: bulan,
                        tahun: tahun
                    },
                    cache: false,
                    success: function(respond) {
                        $("#historiList").html(respond);
                    },
                    error: function(xhr, status, error) {
                        $("#historiList").html(
                            '<div class="alert alert-danger">Gagal memuat data. Silakan coba lagi.</div>'
                        );
                    }
                });
            }

            // Script Modal tetap sama logikanya, hanya ID target yang menyesuaikan
            $(document).on('click', '.card-histori', function() { // Ubah trigger klik ke Card, bukan tombol kecil
                var $btn = $(this);
                var fotoMasuk = $btn.data('foto-masuk') || '';
                var fotoKeluar = $btn.data('foto-keluar') || '';

                $('#historiTanggal').text($btn.data('tanggal') || '-');
                $('#historiStatus').text($btn.data('status') || '-');

                // Set warna badge status di modal
                var statusClass = $btn.data('status-class') || 'badge-secondary';
                $('#historiStatus').attr('class', 'badge badge-pill mt-1 ' + statusClass);

                $('#historiKeterangan').text($btn.data('keterangan') || '-');
                $('#historiJamMasuk').text($btn.data('jam-masuk') || '-');
                $('#historiJamKeluar').text($btn.data('jam-keluar') || '-');

                // Logic Foto
                if (fotoMasuk && fotoMasuk !== "null") {
                    $('#historiFotoMasuk').attr('src', fotoMasuk).show();
                    $('#historiFotoMasukEmpty').hide();
                } else {
                    $('#historiFotoMasuk').hide();
                    $('#historiFotoMasukEmpty').show();
                }

                if (fotoKeluar && fotoKeluar !== "null") {
                    $('#historiFotoKeluar').attr('src', fotoKeluar).show();
                    $('#historiFotoKeluarEmpty').hide();
                } else {
                    $('#historiFotoKeluar').hide();
                    $('#historiFotoKeluarEmpty').show();
                }

                $('#historiDetailModal').modal('show');
            });
        });
    </script>

    {{-- Custom CSS untuk mempercantik Form Select --}}
    <style>
        .custom-select {
            height: 45px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
        }
    </style>
@endpush
