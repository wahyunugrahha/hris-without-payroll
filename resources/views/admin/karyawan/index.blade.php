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

                {{-- Ringkasan status: Aktif/Nonaktif bisa diklik untuk memfilter (filter lain tetap). --}}
                @php
                    $urlStatus = fn ($status) => route('karyawan.index', array_merge(request()->except(['page', 'status_filter']), ['status_filter' => $status]));
                @endphp
                <div class="summary-strip" role="group" aria-label="Ringkasan status karyawan">
                    <div class="summary-item">
                        <span class="summary-label">Total karyawan</span>
                        <span class="summary-value">{{ number_format($ringkasan['total'], 0, ',', '.') }}</span>
                    </div>
                    <a href="{{ $urlStatus(\App\Models\Karyawan::STATUS_AKTIF) }}"
                        class="summary-item summary-item--success {{ $statusFilter === \App\Models\Karyawan::STATUS_AKTIF ? 'is-current' : '' }}"
                        @if ($statusFilter === \App\Models\Karyawan::STATUS_AKTIF) aria-current="true" @endif>
                        <span class="summary-label">Aktif</span>
                        <span class="summary-value">{{ number_format($ringkasan['aktif'], 0, ',', '.') }}</span>
                    </a>
                    <a href="{{ $urlStatus(\App\Models\Karyawan::STATUS_NONAKTIF) }}"
                        class="summary-item summary-item--neutral {{ $statusFilter === \App\Models\Karyawan::STATUS_NONAKTIF ? 'is-current' : '' }}"
                        @if ($statusFilter === \App\Models\Karyawan::STATUS_NONAKTIF) aria-current="true" @endif>
                        <span class="summary-label">Nonaktif</span>
                        <span class="summary-value">{{ number_format($ringkasan['nonaktif'], 0, ',', '.') }}</span>
                    </a>
                    @if ($ringkasan['menunggu'] > 0)
                        <a href="{{ $urlStatus(\App\Models\Karyawan::STATUS_MENUNGGU_APPROVAL) }}"
                            class="summary-item summary-item--warning {{ $statusFilter === \App\Models\Karyawan::STATUS_MENUNGGU_APPROVAL ? 'is-current' : '' }}"
                            @if ($statusFilter === \App\Models\Karyawan::STATUS_MENUNGGU_APPROVAL) aria-current="true" @endif>
                            <span class="summary-label">Menunggu approval</span>
                            <span class="summary-value">{{ number_format($ringkasan['menunggu'], 0, ',', '.') }}</span>
                        </a>
                    @endif
                </div>

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
    @php
        // Error validasi & isian lama hanya milik form tambah bila form ini yang dikirim (bukan form edit).
        $errTambah = old('_form') === 'tambah' && $errors->any();
        $isian = fn ($field, $default = null) => $errTambah ? old($field, $default) : $default;
        $galat = fn ($field) => $errTambah ? $errors->first($field) : null;
        $kelasGalat = fn ($field) => $galat($field) ? ' is-invalid' : '';
    @endphp
    <div class="modal modal-blur fade" id="modal-inputkaryawan" tabindex="-1" role="dialog"
        aria-labelledby="judulTambahKaryawan" aria-describedby="deskripsiTambahKaryawan" aria-hidden="true"
        @if ($errTambah) data-inline-errors data-buka-otomatis @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulTambahKaryawan">Tambah Karyawan</h5>
                        <p class="modal-subtitle" id="deskripsiTambahKaryawan">Lengkapi informasi karyawan untuk menambahkan data baru.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <form action="{{ route('karyawan.store') }}" method="POST" id="formKaryawan" enctype="multipart/form-data"
                    class="modal-form-body" novalidate data-validasi-native>
                    @csrf
                    <input type="hidden" name="_form" value="tambah">

                    <div class="modal-body">
                        @if ($errTambah)
                            <div class="form-error-summary" role="alert">
                                Periksa {{ $errors->count() }} isian yang ditandai di bawah.
                            </div>
                        @endif

                        <fieldset class="form-section">
                            <legend class="form-section-title">Informasi pribadi</legend>
                            <div class="form-grid">
                                <div>
                                    <label class="form-label required" for="tambah-nik">NIK</label>
                                    <input type="text" class="form-control{{ $kelasGalat('nik') }}" id="tambah-nik" name="nik"
                                        value="{{ $isian('nik') }}" placeholder="Contoh: 202400123" inputmode="numeric"
                                        autocomplete="off" required @if ($galat('nik')) aria-describedby="galat-nik" @endif>
                                    @if ($galat('nik'))
                                        <div class="invalid-feedback" id="galat-nik">{{ $galat('nik') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label required" for="tambah-nama">Nama lengkap</label>
                                    <input type="text" class="form-control{{ $kelasGalat('nama_lengkap') }}" id="tambah-nama"
                                        name="nama_lengkap" value="{{ $isian('nama_lengkap') }}" placeholder="Sesuai KTP"
                                        autocomplete="off" required @if ($galat('nama_lengkap')) aria-describedby="galat-nama_lengkap" @endif>
                                    @if ($galat('nama_lengkap'))
                                        <div class="invalid-feedback" id="galat-nama_lengkap">{{ $galat('nama_lengkap') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label required" for="tambah-panggilan">Nama panggilan</label>
                                    <input type="text" class="form-control{{ $kelasGalat('nama_panggilan') }}" id="tambah-panggilan"
                                        name="nama_panggilan" value="{{ $isian('nama_panggilan') }}" placeholder="Nama sehari-hari"
                                        autocomplete="off" required @if ($galat('nama_panggilan')) aria-describedby="galat-nama_panggilan" @endif>
                                    @if ($galat('nama_panggilan'))
                                        <div class="invalid-feedback" id="galat-nama_panggilan">{{ $galat('nama_panggilan') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label required" for="tambah-hp">No. HP</label>
                                    <input type="tel" class="form-control{{ $kelasGalat('no_hp') }}" id="tambah-hp" name="no_hp"
                                        value="{{ $isian('no_hp') }}" placeholder="08xxxxxxxxxx" inputmode="numeric"
                                        autocomplete="off" required @if ($galat('no_hp')) aria-describedby="galat-no_hp" @endif>
                                    @if ($galat('no_hp'))
                                        <div class="invalid-feedback" id="galat-no_hp">{{ $galat('no_hp') }}</div>
                                    @endif
                                </div>

                                {{-- Upload foto: input file asli disembunyikan, dipakai apa adanya oleh form. --}}
                                <div class="form-grid-full">
                                    <span class="form-label" id="label-foto">Foto</span>
                                    <div class="upload{{ $galat('foto') ? ' is-invalid' : '' }}" data-upload>
                                        <input type="file" name="foto" id="tambah-foto" class="upload-input"
                                            accept="image/jpeg,image/png" aria-labelledby="label-foto"
                                            aria-describedby="petunjuk-foto" data-maks-byte="{{ 3 * 1024 * 1024 }}">
                                        <label for="tambah-foto" class="upload-drop" data-upload-kosong>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path d="M15 8h.01" />
                                                <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" />
                                                <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" />
                                                <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" />
                                            </svg>
                                            <span class="upload-title">Unggah foto</span>
                                            <span class="upload-hint" id="petunjuk-foto">JPG / PNG · maks. 3 MB · seret ke sini atau</span>
                                            <span class="btn btn-sm">Pilih foto</span>
                                        </label>
                                        <div class="upload-preview" data-upload-isi hidden>
                                            <img src="" alt="Pratinjau foto karyawan" data-upload-gambar>
                                            <div class="min-w-0 flex-fill">
                                                <div class="upload-nama" data-upload-nama></div>
                                                <div class="upload-ukuran" data-upload-ukuran></div>
                                            </div>
                                            <label for="tambah-foto" class="btn btn-sm">Ganti</label>
                                            <button type="button" class="btn btn-sm btn-ghost-danger" data-upload-hapus>Hapus</button>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback d-block" data-upload-galat>{{ $galat('foto') }}</div>
                                    @if ($errTambah && !$galat('foto'))
                                        <div class="form-hint">Foto perlu dipilih ulang setelah ada isian yang salah.</div>
                                    @endif
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="form-section">
                            <legend class="form-section-title">Data kepegawaian</legend>
                            <div class="form-grid">
                                <div>
                                    <label class="form-label required" for="tambah-jabatan">Jabatan</label>
                                    <select name="jabatan_id" id="tambah-jabatan" class="form-select{{ $kelasGalat('jabatan_id') }}" required>
                                        <option value="">Pilih jabatan</option>
                                        @foreach ($jabatans as $j)
                                            <option value="{{ $j->id }}" @selected($isian('jabatan_id') == $j->id)>{{ $j->nama_jabatan }}</option>
                                        @endforeach
                                    </select>
                                    @if ($galat('jabatan_id'))
                                        <div class="invalid-feedback">{{ $galat('jabatan_id') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label required" for="tambah-dept">Departemen</label>
                                    <select name="kode_dept" id="tambah-dept" class="form-select{{ $kelasGalat('kode_dept') }}" required>
                                        <option value="">Pilih departemen</option>
                                        @foreach ($departemen as $d)
                                            <option value="{{ $d->kode_dept }}" @selected($isian('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                        @endforeach
                                    </select>
                                    @if ($galat('kode_dept'))
                                        <div class="invalid-feedback">{{ $galat('kode_dept') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label required" for="tambah-cabang">PT</label>
                                    <select name="kode_cabang" id="tambah-cabang" class="form-select{{ $kelasGalat('kode_cabang') }}" required>
                                        @if ($cabang->count() !== 1)
                                            <option value="">Pilih PT</option>
                                        @endif
                                        @foreach ($cabang as $c)
                                            <option value="{{ $c->kode_cabang }}" @selected($isian('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                        @endforeach
                                    </select>
                                    @if ($galat('kode_cabang'))
                                        <div class="invalid-feedback">{{ $galat('kode_cabang') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label" for="tambah-tmt">TMT (tanggal bergabung)</label>
                                    <input type="date" class="form-control{{ $kelasGalat('tmt') }}" id="tambah-tmt" name="tmt"
                                        value="{{ $isian('tmt', date('Y-m-d')) }}">
                                    @if ($galat('tmt'))
                                        <div class="invalid-feedback">{{ $galat('tmt') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label" for="tambah-awal-kontrak">Awal kontrak</label>
                                    <input type="date" class="form-control{{ $kelasGalat('tanggal_awal_kontrak') }}" id="tambah-awal-kontrak"
                                        name="tanggal_awal_kontrak" value="{{ $isian('tanggal_awal_kontrak') }}">
                                    @if ($galat('tanggal_awal_kontrak'))
                                        <div class="invalid-feedback">{{ $galat('tanggal_awal_kontrak') }}</div>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label" for="tambah-ptkp">Status PTKP</label>
                                    <select name="status_ptkp" id="tambah-ptkp" class="form-select{{ $kelasGalat('status_ptkp') }}">
                                        <option value="TK" @selected($isian('status_ptkp', 'TK') === 'TK')>TK</option>
                                        @for ($i = 0; $i <= 10; $i++)
                                            <option value="K/{{ $i }}" @selected($isian('status_ptkp') === "K/{$i}")>K/{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @if ($galat('status_ptkp'))
                                        <div class="invalid-feedback">{{ $galat('status_ptkp') }}</div>
                                    @endif
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="form-section">
                            <legend class="form-section-title">Pengaturan presensi</legend>
                            <label class="setting-row" for="tambah-whitelist">
                                <span class="min-w-0">
                                    <span class="setting-title">Whitelist presensi</span>
                                    <span class="setting-desc">Karyawan tidak diwajibkan melakukan presensi harian.</span>
                                </span>
                                <span class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="tambah-whitelist"
                                        name="is_whitelist" value="1" @checked($isian('is_whitelist'))>
                                </span>
                            </label>
                        </fieldset>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" data-tombol-simpan>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" hidden data-spinner></span>
                            <span data-label-simpan>Simpan Karyawan</span>
                        </button>
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
        // Modal Tambah Karyawan: validasi inline, pratinjau foto, cegah submit ganda, konfirmasi sebelum menutup.
        (function() {
            const modalEl = document.getElementById('modal-inputkaryawan');
            const form = document.getElementById('formKaryawan');
            if (!modalEl || !form || typeof bootstrap === 'undefined') return;

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const tombolSimpan = form.querySelector('[data-tombol-simpan]');
            let berubah = false;
            let menyimpan = false;
            let bolehTutup = false;

            // ── Pesan error di dekat field ──
            const labelUntuk = (el) => (form.querySelector(`label[for="${el.id}"]`)?.textContent || el.name).trim();

            function tandai(el, pesan) {
                const wadah = el.closest('div');
                let umpan = wadah.querySelector(':scope > .invalid-feedback');
                el.classList.toggle('is-invalid', Boolean(pesan));
                el.toggleAttribute('aria-invalid', Boolean(pesan));
                if (pesan) {
                    if (!umpan) {
                        umpan = document.createElement('div');
                        umpan.className = 'invalid-feedback';
                        umpan.id = 'galat-' + el.name;
                        wadah.appendChild(umpan);
                    }
                    umpan.textContent = pesan;
                    el.setAttribute('aria-describedby', umpan.id);
                } else if (umpan) {
                    umpan.remove();
                    el.removeAttribute('aria-describedby');
                }
            }

            form.addEventListener('input', (e) => {
                berubah = true;
                if (e.target.classList.contains('is-invalid') && e.target.value.trim()) tandai(e.target, null);
            });
            form.addEventListener('change', (e) => {
                berubah = true;
                if (e.target.matches('select.is-invalid') && e.target.value) tandai(e.target, null);
            });

            // ── Upload foto (input file asli tetap yang dikirim) ──
            const upload = form.querySelector('[data-upload]');
            const inputFoto = upload.querySelector('input[type="file"]');
            const kosong = upload.querySelector('[data-upload-kosong]');
            const isi = upload.querySelector('[data-upload-isi]');
            const galatFoto = form.querySelector('[data-upload-galat]');
            const maksByte = Number(inputFoto.dataset.maksByte);
            let urlPratinjau = null;

            const ukuranTeks = (b) => b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.ceil(b / 1024) + ' KB';

            function kosongkanFoto(pesan = '') {
                inputFoto.value = '';
                if (urlPratinjau) URL.revokeObjectURL(urlPratinjau);
                urlPratinjau = null;
                isi.hidden = true;
                kosong.hidden = false;
                galatFoto.textContent = pesan;
                upload.classList.toggle('is-invalid', Boolean(pesan));
            }

            inputFoto.addEventListener('change', () => {
                const file = inputFoto.files[0];
                if (!file) return kosongkanFoto();
                if (!['image/jpeg', 'image/png'].includes(file.type)) return kosongkanFoto('Foto harus berformat JPG atau PNG.');
                if (file.size > maksByte) return kosongkanFoto('Ukuran foto maksimal 3 MB.');

                if (urlPratinjau) URL.revokeObjectURL(urlPratinjau);
                urlPratinjau = URL.createObjectURL(file);
                upload.querySelector('[data-upload-gambar]').src = urlPratinjau;
                upload.querySelector('[data-upload-nama]').textContent = file.name;
                upload.querySelector('[data-upload-ukuran]').textContent = ukuranTeks(file.size);
                galatFoto.textContent = '';
                upload.classList.remove('is-invalid');
                kosong.hidden = true;
                isi.hidden = false;
            });

            upload.querySelector('[data-upload-hapus]').addEventListener('click', () => {
                kosongkanFoto();
                inputFoto.focus();
            });

            ['dragenter', 'dragover'].forEach((ev) => kosong.addEventListener(ev, (e) => {
                e.preventDefault();
                kosong.classList.add('is-drag');
            }));
            ['dragleave', 'drop'].forEach((ev) => kosong.addEventListener(ev, () => kosong.classList.remove('is-drag')));
            kosong.addEventListener('drop', (e) => {
                e.preventDefault();
                const file = e.dataTransfer.files[0];
                if (!file) return;
                const dt = new DataTransfer();
                dt.items.add(file);
                inputFoto.files = dt.files;
                inputFoto.dispatchEvent(new Event('change', { bubbles: true }));
            });

            // ── Submit: cek field wajib, lalu kunci tombol agar tidak terkirim dua kali ──
            form.addEventListener('submit', (e) => {
                if (menyimpan) {
                    e.preventDefault();
                    return;
                }
                let pertama = null;
                form.querySelectorAll('[required]').forEach((el) => {
                    const salah = !el.value.trim();
                    tandai(el, salah ? `${labelUntuk(el)} wajib diisi.` : null);
                    if (salah && !pertama) pertama = el;
                });
                if (pertama) {
                    e.preventDefault();
                    pertama.focus();
                    return;
                }

                menyimpan = true;
                tombolSimpan.disabled = true;
                tombolSimpan.querySelector('[data-spinner]').hidden = false;
                tombolSimpan.querySelector('[data-label-simpan]').textContent = 'Menyimpan…';
            });

            // ── Buka / tutup ──
            function bersihkan() {
                form.reset();
                form.querySelectorAll('.is-invalid').forEach((el) => tandai(el, null));
                form.querySelector('.form-error-summary')?.remove();
                kosongkanFoto();
                berubah = false;
                modalEl.removeAttribute('data-inline-errors');
            }

            modalEl.addEventListener('shown.bs.modal', () => {
                (form.querySelector('.is-invalid') || form.querySelector('#tambah-nik')).focus();
            });

            // Isian belum disimpan tidak langsung hilang saat ditutup (X, Batal, Esc, atau klik di luar).
            modalEl.addEventListener('hide.bs.modal', (e) => {
                if (menyimpan || !berubah || bolehTutup) {
                    bolehTutup = false;
                    return;
                }
                e.preventDefault();
                Swal.fire({
                    target: modalEl, // tetap di dalam modal agar fokus keyboard tidak direbut modal Bootstrap
                    title: 'Data belum disimpan',
                    text: 'Yakin ingin keluar? Isian yang sudah diketik akan hilang.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, keluar',
                    cancelButtonText: 'Lanjut mengisi',
                    confirmButtonColor: 'var(--color-danger)',
                    reverseButtons: true,
                }).then((hasil) => {
                    if (!hasil.isConfirmed) return;
                    bersihkan();
                    bolehTutup = true;
                    modal.hide();
                });
            });

            // Validasi server gagal: buka lagi modal dengan isian lama & pesan di tiap field.
            if (modalEl.hasAttribute('data-buka-otomatis')) {
                berubah = true;
                modal.show();
            }
        })();
    </script>
    <script>
        $(function() {
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
