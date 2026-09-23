@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Data Presensi Harian</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            {{-- FORM FILTER --}}
                            <form id="formFilterMonitoring" autocomplete="off">

                                {{-- BARIS 1: Filter Utama (Tanggal & Organisasi) --}}
                                <div class="row g-2 align-items-center mb-2">

                                    {{-- 1. Input Tanggal --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <div class="input-icon">
                                            <span class="input-icon-addon">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-calendar-event" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path
                                                        d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                                    <path d="M16 3l0 4" />
                                                    <path d="M8 3l0 4" />
                                                    <path d="M4 11l16 0" />
                                                    <path d="M15 15h.01" />
                                                </svg>
                                            </span>
                                            <input type="text" id="tanggal" name="tanggal" value="{{ date('d-m-Y') }}"
                                                class="form-control" placeholder="Tanggal Presensi" readonly>
                                        </div>
                                    </div>

                                    {{-- 2. Input Jabatan --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="jabatan_id" id="jabatan_id" class="form-select">
                                            <option value="">Semua Jabatan</option>
                                            @foreach ($jabatans as $j)
                                                <option value="{{ $j->id }}">{{ $j->nama_jabatan }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 3. Input Departemen --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="kode_dept" id="kode_dept" class="form-select">
                                            <option value="">Semua Departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 4. Input Cabang --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="kode_cabang" id="kode_cabang" class="form-select"
                                            {{ isset($forcedKodeCabang) ? 'disabled' : '' }}>
                                            <option value="">Semua Cabang</option>
                                            @foreach ($cabang as $c)
                                                <option value="{{ $c->kode_cabang }}"
                                                    {{ isset($forcedKodeCabang) && $forcedKodeCabang == $c->kode_cabang ? 'selected' : '' }}>
                                                    {{ $c->nama_cabang }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- BARIS 2: Filter Status & Pencarian --}}
                                <div class="row g-2 align-items-center">

                                    {{-- 1. Input Status --}}
                                    <div class="col-12 col-md-4 col-xl-3">
                                        <select name="status" id="status" class="form-select">
                                            <option value="">Semua Status</option>
                                            <option value="h">Hadir (Tepat Waktu)</option>
                                            <option value="late">Hadir (Terlambat)</option>
                                            <option value="n">Belum Absen</option>
                                            <option value="a">Alpha</option>
                                            <option value="s">Sakit</option>
                                            <option value="i">Izin</option>
                                            <option value="c">Cuti</option>
                                            <option value="r">Roster</option>
                                            <option value="d">Dinas Luar</option>
                                            <option value="l">Libur</option>
                                        </select>
                                    </div>

                                    {{-- 2. Input Pencarian Nama/NIK --}}
                                    <div class="col-12 col-md-8 col-xl-9">
                                        <div class="input-group">
                                            <input type="text" id="search" name="search" class="form-control"
                                                placeholder="Cari Nama Karyawan atau NIK...">
                                            <button type="submit" class="btn btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-search" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="10" cy="10" r="7" />
                                                    <line x1="21" y1="21" x2="15" y2="15" />
                                                </svg>
                                                Cari Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABEL PRESENSI --}}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    {{-- PERBAIKAN: Hapus style background agar darkmode aman --}}
                                    <tr
                                        style="text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: var(--color-muted);">
                                        <th class="w-1">NO.</th>
                                        <th>NAMA / NIK</th>
                                        <th>DEPARTEMEN / JABATAN</th>
                                        <th>CABANG</th>
                                        <th class="text-center">JADWAL SHIFT</th>
                                        <th class="text-center">JAM (M/S)</th>
                                        <th class="text-center">STATUS</th>
                                        <th class="text-center">FOTO</th>
                                        <th class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody id="loadpresensi">
                                    {{-- DATA AKAN DIMUAT DISINI VIA AJAX --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div class="modal modal-blur fade" id="modal-detail-presensi" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Presensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 border-end">
                            <h4 class="mb-2 text-primary">Info Karyawan</h4>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-secondary" width="100">Nama</td>
                                    <td class="fw-bold" id="dt_nama"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">NIK</td>
                                    <td id="dt_nik"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Jabatan</td>
                                    <td id="dt_jabatan"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Departemen</td>
                                    <td id="dt_dept"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Cabang</td>
                                    <td id="dt_cabang"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Status</td>
                                    <td><span id="dt_status" class="badge"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-2 text-primary">Waktu Absensi</h4>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-secondary" width="100">Jadwal</td>
                                    <td id="dt_jadwal"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Masuk</td>
                                    <td id="dt_jamin" class="fw-bold"></td>
                                </tr>
                                <tr>
                                    <td class="text-secondary">Pulang</td>
                                    <td id="dt_jamout" class="fw-bold"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-muted-lt">
                                <div class="card-body p-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="small fw-bold mb-2">Foto Masuk</div>
                                            <div id="container_foto_in"></div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small fw-bold mb-2">Foto Pulang</div>
                                            <div id="container_foto_out"></div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small fw-bold mb-2">Dokumen (SID)</div>
                                            <div id="container_sid"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h4 class="mb-2 text-primary">Peta Lokasi</h4>
                            {{-- PERBAIKAN: Tambahkan width 100% dan pastikan container valid --}}
                            <div id="map-container"
                                class="border rounded d-flex align-items-center justify-content-center w-100"
                                style="height: 350px; width: 100%; overflow: hidden;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto d-none" id="btn-batal-presensi">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-trash" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M4 7l16 0"></path>
                            <path d="M10 11l0 6"></path>
                            <path d="M14 11l0 6"></path>
                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                        </svg>
                        Anulir / Batalkan
                    </button>
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(document).ready(function() {
            // Init Datepicker
            $("#tanggal").datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'dd-mm-yyyy',
                orientation: "bottom auto"
            });

            // --- FUNGSI LOAD DATA TABEL ---
            function loadPresensi(page = 1) {
                var tanggal = $("#tanggal").val();
                var jabatan_id = $("#jabatan_id").val();
                var kode_dept = $("#kode_dept").val();
                var kode_cabang = $("#kode_cabang").val();
                var status = $("#status").val();
                var search = $("#search").val();

                $.ajax({
                    type: 'GET',
                    url: '/presensi/getpresensi?page=' + page,
                    data: {
                        _token: '{{ csrf_token() }}',
                        tanggal: tanggal,
                        jabatan_id: jabatan_id,
                        kode_dept: kode_dept,
                        kode_cabang: kode_cabang,
                        status: status,
                        search: search
                    },
                    beforeSend: function() {
                        $("#loadpresensi").html(
                            '<tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted">Memuat data...</div></td></tr>'
                        );
                    },
                    success: function(respond) {
                        $("#loadpresensi").html(respond);
                    },
                    error: function() {
                        $("#loadpresensi").html(
                            '<tr><td colspan="9" class="text-center text-danger py-5">Gagal memuat data.</td></tr>'
                        );
                    }
                });
            }

            // Submit Filter
            $("#formFilterMonitoring").submit(function(e) {
                e.preventDefault();
                loadPresensi(1);
            });

            // Pagination Click
            $(document).on('click', '.pagination a', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                if (url) {
                    var page = url.split('page=')[1];
                    loadPresensi(page);
                }
            });

            // Load awal
            loadPresensi(1);

            $("#kode_dept, #kode_cabang, #jabatan_id, #status").change(function() {
                loadPresensi(1);
            });

            // --- LOGIC MODAL DETAIL & MAP ---
            $(document).on('click', '.btn-detail', function(e) {
                e.preventDefault();
                var d = $(this).data();

                // 1. ISI DATA TEKS & FOTO KE MODAL
                $('#dt_nama').text(d.nama || '-');
                $('#dt_nik').text(d.nik || '-');
                $('#dt_jabatan').text(d.jabatan || '-');
                $('#dt_dept').text(d.dept || '-');
                $('#dt_cabang').text(d.cabang || '-');
                $('#dt_jadwal').text(d.jadwal || '-');
                $('#dt_jamin').text(d.jamin).removeClass('text-success').addClass(d.jamin !== '-' ?
                    'text-success' : '');
                $('#dt_jamout').text(d.jamout).removeClass('text-danger').addClass(d.jamout !== '-' ?
                    'text-danger' : '');
                $('#dt_status').text(d.status || '').attr('class', 'badge ' + d.warnastatus);

                // Helper render gambar
                function renderModalImage(url, altText) {
                    if (url && url !== 'null' && url !== '-') {
                        return `<a href="${url}" target="_blank"><img src="${url}" class="img-fluid rounded border bg-white" style="width: 100%; height: 150px; object-fit: cover;" alt="${altText}"></a>`;
                    }
                    return `<div class="d-flex align-items-center justify-content-center border rounded bg-light text-muted small" style="height: 150px; width: 100%;">No Photo</div>`;
                }

                $('#container_foto_in').html(renderModalImage(d.fotoin, 'Foto Masuk'));
                $('#container_foto_out').html(renderModalImage(d.fotoout, 'Foto Pulang'));

                if (d.sid && d.sid !== 'null' && d.sid !== '-') {
                    $('#container_sid').html(
                        `<a href="${d.sid}" target="_blank" class="btn btn-primary w-100 btn-sm">Lihat Doc</a>`
                    );
                } else {
                    $('#container_sid').html(
                        `<div class="d-flex align-items-center justify-content-center border rounded bg-light text-muted small" style="height: 38px;">-</div>`
                    );
                }

                // 2. BERSIHKAN MAP CONTAINER LAMA & SET LOADING
                $('#map-container').html(
                    '<div class="d-flex flex-column justify-content-center align-items-center h-100"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted small">Memuat lokasi...</div></div>'
                );

                // 3. SIMPAN ID & STATUS LOKASI KE ATTRIBUTE MODAL
                $('#modal-detail-presensi')
                    .attr('data-id-presensi', d.id)
                    .attr('data-lokasi-valid', d.lokasivalid);

                // Logic Tombol Batal Presensi
                $('#btn-batal-presensi').addClass('d-none').removeData('id');
                // Tampilkan hanya jika memiliki ID Presensi (sudah Absen Masuk) dan statusnya bukan jenis Izin/Cuti
                if (d.id && d.id !== '-' && d.id !== null && d.id !== '') {
                    // Munculkan untuk status Hadir / Terlambat
                    if (d.status === 'Hadir' || d.status === 'Terlambat') {
                        $('#btn-batal-presensi').removeClass('d-none').data('id', d.id);
                    }
                }

                // 4. TAMPILKAN MODAL
                $('#modal-detail-presensi').modal('show');
            });

            // Action Batal Presensi
            $('#btn-batal-presensi').click(function(e) {
                e.preventDefault();
                var presensiId = $(this).data('id');

                Swal.fire({
                    title: 'Anulir Presensi?',
                    text: "Tindakan ini akan mengosongkan jam & foto presensi ini, dan mengubah statusnya menjadi Dianulir. Karyawan akan dianggap Belum Absen pada sistem.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    cancelButtonColor: 'var(--color-muted)',
                    confirmButtonText: 'Ya, Anulir!',
                    cancelButtonText: 'Kembali'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: '/presensi/monitoring/' + presensiId + '/batal',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.status) {
                                    $('#modal-detail-presensi').modal('hide');
                                    Swal.fire('Berhasil!', response.message, 'success');
                                    loadPresensi(1);
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error!', 'Terjadi kesalahan sistem.',
                                    'error');
                            }
                        });
                    }
                });
            });

            // --- EVENT SAAT MODAL SUDAH TAMPIL SEPENUHNYA ---
            $('#modal-detail-presensi').on('shown.bs.modal', function() {
                var id = $(this).attr('data-id-presensi');
                var lokasivalid = $(this).attr('data-lokasi-valid');

                if (lokasivalid == 1 && id && id !== '-') {
                    $.ajax({
                        type: "POST",
                        url: '/presensi/tampilkanpeta',
                        data: {
                            _token: '{{ csrf_token() }}',
                            id: id
                        },
                        cache: false,
                        success: function(res) {
                            $('#map-container').html(res);
                        },
                        error: function() {
                            $('#map-container').html(
                                '<div class="d-flex justify-content-center align-items-center h-100 text-danger">Gagal memuat peta</div>'
                            );
                        }
                    });
                } else {
                    $('#map-container').html(
                        '<div class="d-flex justify-content-center align-items-center h-100 text-muted small fst-italic">Lokasi tidak tersedia / Invalid</div>'
                    );
                }
            });
        });
    </script>
@endpush
