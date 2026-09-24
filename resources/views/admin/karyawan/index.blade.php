@extends('layouts.admin.tabler')

@php
    $ikon = fn ($path, $ukuran = 18) => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
    $pathUnggah = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 9l5 -5l5 5" /><path d="M12 4l0 12" />';
    $pathUnduh = '<path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" />';
    $pathTambah = '<path d="M12 5l0 14" /><path d="M5 12l14 0" />';
    $pathCari = '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" />';
    $pathFilter = '<path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z" />';

    // Filter yang sedang aktif (status default = Aktif, jadi status lain dihitung aktif).
    $filterAktif = collect([
        request('jabatan_id'),
        request('kode_dept'),
        request('kode_cabang'),
        request('nama_karyawan'),
        $statusFilter !== \App\Models\Karyawan::STATUS_AKTIF ? $statusFilter : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Karyawan</h2>
                    <p class="page-subtitle">Kelola informasi karyawan perusahaan.</p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        @canany(['karyawan-import-excel-admin', 'karyawan-download-template-admin'])
                            <div class="dropdown">
                                <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown"
                                    aria-expanded="false" aria-label="Import">
                                    {!! $ikon($pathUnggah) !!}<span class="d-none d-md-inline">Import</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    @can('karyawan-import-excel-admin')
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                            data-bs-target="#modal-importkaryawan">Import dari Excel</button>
                                    @endcan
                                    @can('karyawan-download-template-admin')
                                        <a href="{{ route('karyawan.template') }}" class="dropdown-item">Unduh template Excel</a>
                                    @endcan
                                </div>
                            </div>
                        @endcanany

                        @can('karyawan-export-excel-admin')
                            <a href="{{ route('karyawan.export', request()->all()) }}" class="btn"
                                aria-label="Export Excel" title="Export data sesuai filter">
                                {!! $ikon($pathUnduh) !!}<span class="d-none d-md-inline">Export</span>
                            </a>
                        @endcan

                        @can('karyawan-create-admin')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#modal-inputkaryawan" id="btnTambahkaryawan" aria-label="Tambah karyawan">
                                {!! $ikon($pathTambah) !!}<span class="d-none d-sm-inline">Tambah Karyawan</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar karyawan">

                {{-- Toolbar: pencarian + filter (logika filter tetap di controller) --}}
                <form action="{{ route('karyawan.index') }}" method="GET" class="list-toolbar" id="filterKaryawan">
                    <div class="list-toolbar-row">
                        <label class="search-field">
                            <span class="visually-hidden">Cari karyawan</span>
                            {!! $ikon($pathCari, 16) !!}
                            <input type="search" name="nama_karyawan" value="{{ request('nama_karyawan') }}"
                                placeholder="Cari nama atau NIK…" autocomplete="off">
                        </label>
                        <button type="submit" class="btn btn-primary list-search-btn">Cari</button>
                        <button type="button" class="btn d-lg-none" data-bs-toggle="collapse"
                            data-bs-target="#filterPanel" aria-expanded="false" aria-controls="filterPanel">
                            {!! $ikon($pathFilter, 16) !!} Filter
                            @if ($filterAktif > 0)
                                <span class="filter-count">{{ $filterAktif }}</span>
                            @endif
                        </button>
                    </div>

                    <div class="collapse filter-panel" id="filterPanel">
                        <div class="filter-bar">
                            <label class="filter-field">
                                <span class="filter-label">Jabatan</span>
                                <select name="jabatan_id" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($jabatans as $j)
                                        <option value="{{ $j->id }}" @selected(request('jabatan_id') == $j->id)>{{ $j->nama_jabatan }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Departemen</span>
                                <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}" @selected(request('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Status</span>
                                <select name="status_filter" class="form-select form-select-sm" data-auto-submit>
                                    @foreach ($statusFilterOptions as $statusOption)
                                        <option value="{{ $statusOption }}" @selected($statusFilter == $statusOption)>{{ $statusOption }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @if ($filterAktif > 0)
                                <a href="{{ route('karyawan.index') }}" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($karyawan->total() > 0)
                        Menampilkan <strong>{{ $karyawan->firstItem() }}–{{ $karyawan->lastItem() }}</strong>
                        dari <strong>{{ $karyawan->total() }}</strong> karyawan
                    @endif
                </div>

                @if ($karyawan->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada karyawan yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            Ubah kata kunci atau filter
                            @if ($filterAktif > 0)
                                — atau <a href="{{ route('karyawan.index') }}">reset filter</a>
                            @endif.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Jabatan &amp; Departemen</th>
                                    <th>Kontak</th>
                                    <th>Cabang</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($karyawan as $data)
                                    @php
                                        $fotoUrl = !empty($data->foto) ? asset('storage/uploads/karyawan/' . $data->foto) : asset('assets/img/nophoto.png');
                                        $status = $data->status_aktif ?: '-';
                                        $nadaStatus = match ($status) {
                                            \App\Models\Karyawan::STATUS_AKTIF => 'success',
                                            \App\Models\Karyawan::STATUS_MENUNGGU_APPROVAL => 'warning',
                                            \App\Models\Karyawan::STATUS_DIBERHENTIKAN => 'danger',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <a href="{{ route('karyawan.show', $data->nik) }}" class="person">
                                                <span class="avatar" style="background-image: url('{{ $fotoUrl }}')"></span>
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $data->nama_lengkap }}">{{ $data->nama_lengkap }}</span>
                                                    <span class="person-sub">NIK {{ $data->nik }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td data-label="Jabatan">
                                            <div class="cell-main">{{ $data->jabatan_nama ?? '-' }}</div>
                                            <div class="cell-sub">{{ $data->departemen->nama_dept ?? '-' }}</div>
                                        </td>
                                        <td data-label="Kontak" class="cell-num">{{ $data->no_hp ?: '-' }}</td>
                                        <td data-label="Cabang">{{ $data->cabang->nama_cabang ?? '-' }}</td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $status }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            <div class="dropdown">
                                                <button type="button" class="row-menu-btn" data-bs-toggle="dropdown"
                                                    data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"
                                                    aria-label="Aksi untuk {{ $data->nama_lengkap }}" title="Aksi">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                    </svg>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a href="{{ route('karyawan.show', $data->nik) }}" class="dropdown-item">Lihat detail</a>
                                                    @can('karyawan-edit-admin')
                                                        <button type="button" class="dropdown-item edit" data-nik="{{ $data->nik }}">Edit</button>
                                                        <a href="{{ route('konfigurasi.setjamkerja', $data->nik) }}" class="dropdown-item">Atur jam kerja</a>
                                                    @endcan
                                                    @can('karyawan-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('karyawan.destroy', $data->nik) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger delete-confirm"
                                                                data-nama="{{ $data->nama_lengkap }}">Hapus</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($karyawan->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $karyawan->currentPage() }} dari {{ $karyawan->lastPage() }}</span>
                        {{ $karyawan->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- MODAL TAMBAH DATA --}}
    <div class="modal modal-blur fade" id="modal-inputkaryawan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('karyawan.store') }}" method="POST" id="formKaryawan"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">NIK</label>
                                <input type="text" class="form-control" name="nik" placeholder="NIK" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama_lengkap"
                                    placeholder="Nama Lengkap" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nama Panggilan</label>
                                <input type="text" class="form-control" name="nama_panggilan" placeholder="Panggilan"
                                    required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Jabatan</label>
                                <select name="jabatan_id" class="form-select" required>
                                    <option value="">Pilih Jabatan</option>
                                    @foreach ($jabatans as $j)
                                        <option value="{{ $j->id }}">{{ $j->nama_jabatan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">No. HP</label>
                                <input type="text" class="form-control" name="no_hp" placeholder="08xxx" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status PTKP</label>
                                <select name="status_ptkp" class="form-select">
                                    <option value="TK">TK</option>
                                    @for ($i = 0; $i <= 10; $i++)
                                        <option value="K/{{ $i }}">K/{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">TMT (Join Date)</label>
                                <input type="date" class="form-control" name="tmt" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Awal Kontrak</label>
                                <input type="date" class="form-control" name="tanggal_awal_kontrak">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Departemen</label>
                                <select name="kode_dept" class="form-select" required>
                                    <option value="">Pilih</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">PT</label>
                                <select name="kode_cabang" class="form-select" required>
                                    <option value="">Pilih</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}">{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_whitelist" value="1"
                                        {{ old('is_whitelist') ? 'checked' : '' }}>
                                    <span class="form-check-label">Whitelist (karyawan dikecualikan dari presensi
                                        harian)</span>
                                </label>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Foto</label>
                                <input type="file" class="form-control" name="foto">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary ms-auto">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT CONTAINER (Target AJAX) --}}
    <div class="modal modal-blur fade" id="modal-editkaryawan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="loadededitform">
                    {{-- Form Edit akan diload di sini --}}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Excel --}}
    <div class="modal fade" id="modal-importkaryawan" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import data karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="{{ route('karyawan.import') }}" method="POST" enctype="multipart/form-data"
                    id="frmImportKaryawan">
                    @csrf
                    <div class="modal-body">
                        <div class="import-guide">
                            <h4 class="import-guide-title">Petunjuk import</h4>
                            <ul class="mb-0">
                                <li>File harus berformat <strong>.xlsx</strong> atau <strong>.xls</strong></li>
                                <li>Maksimal ukuran file <strong>5MB</strong></li>
                                <li>Gunakan template sebagai acuan format kolom
                                    @can('karyawan-download-template-admin')
                                        — <a href="{{ route('karyawan.template') }}">unduh template Excel</a>
                                    @endcan
                                </li>
                                <li>Header kolom yang diperlukan:
                                    <ul>
                                        <li><code>NIK</code>, <code>Nama_Lengkap</code>, <code>Nama_Panggilan</code></li>
                                        <li><code>Jabatan</code>, <code>Kode_Dept</code>, <code>Kode_Cabang</code></li>
                                        <li><code>No_HP</code>, <code>Email</code></li>
                                        <li><code>TMT_Join_Date</code>, <code>Awal_Kontrak</code>,
                                            <code>Tanggal_Habis_Kontrak</code>
                                        </li>
                                        <li>Dan kolom lainnya...</li>
                                    </ul>
                                </li>
                            </ul>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Pilih File Excel</label>
                            <input type="file" class="form-control" name="file" accept=".xlsx,.xls" required>
                            <small class="form-hint">Format: .xlsx atau .xls, Max: 5MB</small>
                        </div>

                        @if (Session::get('import_errors'))
                            <div class="alert alert-danger">
                                <h4 class="alert-title">Error Detail:</h4>
                                <div>{!! Session::get('import_errors') !!}</div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-upload"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                <path d="M7 9l5 -5l5 5" />
                                <path d="M12 4l0 12" />
                            </svg>
                            Upload & Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('myscript')
    <script>
        $(function() {
            // 1. Reset Form Tambah saat dibuka
            $('#btnTambahkaryawan').on('click', function() {
                $('#formKaryawan')[0].reset();
            });

            // 2. Load Form Edit via AJAX
            $('.edit').click(function(e) {
                e.preventDefault();
                var nik = $(this).data('nik');
                $('#loadededitform').html('<div class="text-center p-3">Loading...</div>');
                $('#modal-editkaryawan').modal('show');

                // Gunakan path yang benar sesuai route
                $('#loadededitform').load('/karyawan/' + nik + '/edit', function(response, status, xhr) {
                    if (status == "error") {
                        $('#loadededitform').html(
                            '<div class="alert alert-danger">Gagal memuat data.</div>');
                    }
                });
            });

            // 3. Konfirmasi Hapus SweetAlert
            $('.delete-confirm').click(function(e) {
                var form = $(this).closest("form");
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Data?',
                    text: "Data " + $(this).data("nama") + " akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                })
            });

            // 4. Filter langsung diterapkan saat pilihan berubah (tetap lewat GET yang sama).
            $('[data-auto-submit]').on('change', function() {
                this.form.submit();
            });

            // 5. Error import ditampilkan di modal import: buka otomatis agar terlihat.
            @if (Session::get('import_errors'))
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-importkaryawan')).show();
            @endif
        });
    </script>
@endpush
