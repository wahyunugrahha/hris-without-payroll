@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Data Presensi Harian</h2>
                    <p class="page-subtitle">Status kehadiran seluruh karyawan pada tanggal terpilih.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Presensi harian">
                <form id="formFilterMonitoring" class="list-toolbar" autocomplete="off">
                    <div class="list-toolbar-row">
                        <label class="search-field">
                            <span class="visually-hidden">Cari karyawan</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                            <input type="search" id="search" name="search" placeholder="Cari nama atau NIK…">
                        </label>
                        <button type="submit" class="btn btn-primary list-search-btn">Cari</button>
                    </div>
                    <div class="filter-bar">
                        <label class="filter-field">
                            <span class="filter-label">Tanggal</span>
                            <input type="date" id="tanggal" name="tanggal" value="{{ date('Y-m-d') }}" class="form-control form-control-sm">
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Status</span>
                            <select name="status" id="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="h">Hadir tepat waktu</option>
                                <option value="late">Terlambat</option>
                                <option value="n">Belum absen</option>
                                <option value="a">Alpha</option>
                                <option value="s">Sakit</option>
                                <option value="i">Izin</option>
                                <option value="c">Cuti</option>
                                <option value="r">Roster</option>
                                <option value="d">Dinas luar</option>
                                <option value="l">Libur</option>
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Jabatan</span>
                            <select name="jabatan_id" id="jabatan_id" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($jabatans as $j)
                                    <option value="{{ $j->id }}">{{ $j->nama_jabatan }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Departemen</span>
                            <select name="kode_dept" id="kode_dept" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                @foreach ($departemen as $d)
                                    <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="filter-field">
                            <span class="filter-label">Cabang</span>
                            <select name="kode_cabang" id="kode_cabang" class="form-select form-select-sm" @disabled(isset($forcedKodeCabang))>
                                <option value="">Semua</option>
                                @foreach ($cabang as $c)
                                    <option value="{{ $c->kode_cabang }}" @selected(isset($forcedKodeCabang) && $forcedKodeCabang == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-vcenter list-table">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Jabatan &amp; unit</th>
                                <th>Jadwal</th>
                                <th>Masuk / pulang</th>
                                <th>Status</th>
                                <th>Foto</th>
                                <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody id="loadpresensi" aria-live="polite"></tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-detail-presensi" tabindex="-1" aria-labelledby="judulDetailPresensi" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulDetailPresensi">Detail presensi</h5>
                        <p class="modal-subtitle"><span id="dt_nama"></span> · NIK <span id="dt_nik"></span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <section class="form-section">
                        <h6 class="form-section-title">Ringkasan</h6>
                        <dl class="info-grid">
                            <div><dt>Status</dt><dd><span id="dt_status" class="emp-status"></span></dd></div>
                            <div><dt>Jadwal</dt><dd id="dt_jadwal"></dd></div>
                            <div><dt>Jam masuk</dt><dd id="dt_jamin" class="cell-num"></dd></div>
                            <div><dt>Jam pulang</dt><dd id="dt_jamout" class="cell-num"></dd></div>
                            <div><dt>Jabatan</dt><dd id="dt_jabatan"></dd></div>
                            <div><dt>Departemen · cabang</dt><dd><span id="dt_dept"></span> · <span id="dt_cabang"></span></dd></div>
                            <div class="info-full d-none" id="dt_kejanggalan_wrap"><dt>Kejanggalan lokasi</dt><dd id="dt_kejanggalan" class="text-danger"></dd></div>
                        </dl>
                    </section>
                    <section class="form-section">
                        <h6 class="form-section-title">Foto & dokumen</h6>
                        <div class="detail-media">
                            <figure><div id="container_foto_in"></div><figcaption>Foto masuk</figcaption></figure>
                            <figure><div id="container_foto_out"></div><figcaption>Foto pulang</figcaption></figure>
                            <figure><div id="container_sid"></div><figcaption>Dokumen (SID)</figcaption></figure>
                        </div>
                    </section>
                    <section class="form-section">
                        <h6 class="form-section-title">Lokasi absen masuk</h6>
                        <div id="map-container" class="detail-map"></div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto d-none" id="btn-batal-presensi">Anulir presensi</button>
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            const kosong = (teks) => `<div class="detail-media-empty">${teks}</div>`;

            function loadPresensi(page = 1) {
                $.ajax({
                    type: 'GET',
                    url: '/presensi/getpresensi?page=' + page,
                    data: {
                        tanggal: $('#tanggal').val(),
                        jabatan_id: $('#jabatan_id').val(),
                        kode_dept: $('#kode_dept').val(),
                        kode_cabang: $('#kode_cabang').val(),
                        status: $('#status').val(),
                        search: $('#search').val()
                    },
                    beforeSend: function() {
                        $('#loadpresensi').html('<tr class="row-empty"><td colspan="7"><div class="list-empty"><div class="spinner-border spinner-border-sm text-secondary"></div><p class="mt-2 mb-0 text-secondary small">Memuat data…</p></div></td></tr>');
                    },
                    success: (respond) => $('#loadpresensi').html(respond),
                    error: () => $('#loadpresensi').html('<tr class="row-empty"><td colspan="7"><div class="list-empty text-danger">Data gagal dimuat. Coba lagi.</div></td></tr>')
                });
            }

            $('#formFilterMonitoring').on('submit', function(e) {
                e.preventDefault();
                loadPresensi(1);
            });
            $('#tanggal, #kode_dept, #kode_cabang, #jabatan_id, #status').on('change', () => loadPresensi(1));

            $(document).on('click', '#loadpresensi .pagination a', function(e) {
                e.preventDefault();
                const page = new URL(this.href).searchParams.get('page');
                if (page) loadPresensi(page);
            });

            loadPresensi(1);

            // Modal detail.
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data();

                $('#dt_nama').text(d.nama || '-');
                $('#dt_nik').text(d.nik || '-');
                $('#dt_jabatan').text(d.jabatan || '-');
                $('#dt_dept').text(d.dept || '-');
                $('#dt_cabang').text(d.cabang || '-');
                $('#dt_jadwal').text(d.jadwal || '-');
                $('#dt_jamin').text(d.jamin || '-');
                $('#dt_jamout').text(d.jamout || '-');
                $('#dt_status').text(d.status || '-').attr('class', 'emp-status emp-status--' + (d.nada || 'neutral') + ' ' + (d.hue || ''));
                $('#dt_kejanggalan').text(d.kejanggalan || '');
                $('#dt_kejanggalan_wrap').toggleClass('d-none', !d.kejanggalan);

                const gambar = (url, alt) => url ?
                    $('<a>', { href: url, target: '_blank', rel: 'noopener' }).append($('<img>', { src: url, alt: alt })) :
                    kosong('Tidak ada foto');
                $('#container_foto_in').html(gambar(d.fotoin, 'Foto masuk'));
                $('#container_foto_out').html(gambar(d.fotoout, 'Foto pulang'));
                $('#container_sid').html(d.sid ?
                    $('<a>', { href: d.sid, target: '_blank', rel: 'noopener', class: 'btn btn-sm w-100', text: 'Buka dokumen' }) :
                    kosong('Tidak ada dokumen'));

                $('#map-container').html(kosong('Memuat lokasi…'));
                $('#modal-detail-presensi').attr('data-id-presensi', d.id).attr('data-lokasi-valid', d.lokasivalid);

                // Anulir hanya untuk presensi hadir/terlambat yang sudah tercatat.
                const bisaAnulir = d.id && (d.status === 'Hadir' || d.status === 'Terlambat');
                $('#btn-batal-presensi').toggleClass('d-none', !bisaAnulir).data('id', bisaAnulir ? d.id : null);

                $('#modal-detail-presensi').modal('show');
            });

            $('#btn-batal-presensi').on('click', function() {
                const presensiId = $(this).data('id');
                Swal.fire({
                    title: 'Anulir presensi?',
                    text: 'Jam & foto presensi ini dikosongkan dan statusnya menjadi Dianulir. Karyawan dianggap belum absen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, anulir',
                    cancelButtonText: 'Kembali',
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    $.post('/presensi/monitoring/' + presensiId + '/batal', { _token: '{{ csrf_token() }}' })
                        .done(function(response) {
                            if (response.status) {
                                $('#modal-detail-presensi').modal('hide');
                                Swal.fire('Berhasil', response.message, 'success');
                                loadPresensi(1);
                            } else {
                                Swal.fire('Gagal', response.message, 'error');
                            }
                        })
                        .fail(() => Swal.fire('Gagal', 'Terjadi kesalahan sistem.', 'error'));
                });
            });

            // Peta dimuat setelah modal tampil agar ukuran Leaflet benar.
            $('#modal-detail-presensi').on('shown.bs.modal', function() {
                const id = $(this).attr('data-id-presensi');
                if ($(this).attr('data-lokasi-valid') == 1 && id) {
                    $.post('/presensi/tampilkanpeta', { _token: '{{ csrf_token() }}', id: id })
                        .done((res) => $('#map-container').html(res))
                        .fail(() => $('#map-container').html(kosong('Peta gagal dimuat')));
                } else {
                    $('#map-container').html(kosong('Lokasi tidak tersedia'));
                }
            });
        });
    </script>
@endpush
