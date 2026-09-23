@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Data Master
                    </div>
                    <h2 class="page-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                        Data Karyawan
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        {{-- Tombol Template & Import Excel --}}
                        @can('karyawan-create-admin')
                            <a href="{{ route('karyawan.template') }}" class="btn btn-secondary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                    <path d="M7 11l5 5l5 -5" />
                                    <path d="M12 4l0 12" />
                                </svg>
                                Download Template
                            </a>
                            <a href="{{ route('karyawan.template') }}" class="btn btn-secondary d-sm-none btn-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                    <path d="M7 11l5 5l5 -5" />
                                    <path d="M12 4l0 12" />
                                </svg>
                            </a>

                            <a href="#" class="btn btn-info d-none d-sm-inline-block" data-bs-toggle="modal"
                                data-bs-target="#modal-importkaryawan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-upload"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                    <path d="M12 11v6" />
                                    <path d="M9.5 13.5l2.5 -2.5l2.5 2.5" />
                                </svg>
                                Import Excel
                            </a>
                            <a href="#" class="btn btn-info d-sm-none btn-icon" data-bs-toggle="modal"
                                data-bs-target="#modal-importkaryawan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-upload"
                                    width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                    <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                    <path d="M12 11v6" />
                                    <path d="M9.5 13.5l2.5 -2.5l2.5 2.5" />
                                </svg>
                            </a>
                        @endcan

                        {{-- Tombol Export Excel --}}
                        <a href="{{ route('karyawan.export', request()->all()) }}"
                            class="btn btn-success d-none d-sm-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                <path d="M8 11h8v7h-8z" />
                                <path d="M8 15h8" />
                                <path d="M11 11v7" />
                            </svg>
                            Export Excel
                        </a>
                        <a href="{{ route('karyawan.export', request()->all()) }}"
                            class="btn btn-success d-sm-none btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-spreadsheet"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                <path d="M8 11h8v7h-8z" />
                                <path d="M8 15h8" />
                                <path d="M11 11v7" />
                            </svg>
                        </a>

                        @can('karyawan-create-admin')
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                                data-bs-target="#modal-inputkaryawan" id="btnTambahkaryawan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Data
                            </a>
                            <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal"
                                data-bs-target="#modal-inputkaryawan">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    @if (Session::get('success'))
                        <div class="alert alert-success alert-important alert-dismissible" role="alert">
                            {{ Session::get('success') }}
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if (Session::get('warning'))
                        <div class="alert alert-warning alert-important alert-dismissible" role="alert">
                            {{ Session::get('warning') }}
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-important alert-dismissible" role="alert">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path
                                            d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                    </svg>
                                </div>
                                <div>
                                    <strong>Gagal Disimpan:</strong>
                                    <ul class="mb-0 ps-2">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <a class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body border-bottom py-3">
                            @php
                                $selectedJabatanId = request('jabatan_id');
                            @endphp
                            <form action="{{ route('karyawan.index') }}" method="GET">
                                <div class="row g-2 align-items-center">

                                    {{-- 2. Dropdown Jabatan --}}
                                    <div class="col-6 col-xl-2">
                                        <select name="jabatan_id" class="form-select">
                                            <option value="">Semua Jabatan</option>
                                            @foreach ($jabatans as $j)
                                                <option value="{{ $j->id }}"
                                                    {{ $selectedJabatanId == $j->id ? 'selected' : '' }}>
                                                    {{ $j->nama_jabatan }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 3. Dropdown Departemen --}}
                                    <div class="col-6 col-xl-2">
                                        <select name="kode_dept" class="form-select">
                                            <option value="">Semua Departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}"
                                                    {{ request('kode_dept') == $d->kode_dept ? 'selected' : '' }}>
                                                    {{ $d->nama_dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 4. Dropdown Cabang --}}
                                    <div class="col-6 col-xl-2">
                                        <select name="kode_cabang" class="form-select">
                                            <option value="">Semua Cabang</option>
                                            @foreach ($cabang as $c)
                                                <option value="{{ $c->kode_cabang }}"
                                                    {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                                    {{ $c->nama_cabang }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6 col-xl-2">
                                        <select name="status_filter" class="form-select">
                                            @foreach ($statusFilterOptions as $statusOption)
                                                <option value="{{ $statusOption }}"
                                                    {{ $statusFilter == $statusOption ? 'selected' : '' }}>
                                                    {{ $statusOption }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 5. Search & Tombol Cari (Menyatu) --}}
                                    <div class="col-12 col-xl-4"> {{-- Lebar disesuaikan agar mengisi sisa ruang --}}
                                        <div class="input-group">
                                            {{-- Input Field --}}
                                            <input type="text" class="form-control" name="nama_karyawan"
                                                value="{{ request('nama_karyawan') }}"
                                                placeholder="Cari Nama atau NIK...">

                                            {{-- Tombol Cari Data (Menyatu di Kanan Input) --}}
                                            <button type="submit" class="btn btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icon-tabler-search">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                    <path d="M21 21l-6 -6" />
                                                </svg>
                                                Cari Data
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>

                        {{-- TABLE --}}
                        <div class="table-responsive">
                            <table class="table table-vcenter table-mobile-md card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Karyawan</th>
                                        <th>Jabatan & Dept</th>
                                        <th>Kontak</th>
                                        <th>Cabang (PT)</th>
                                        <th class="w-1">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($karyawan as $data)
                                        @php
                                            $fotoUrl = !empty($data->foto)
                                                ? asset('storage/uploads/karyawan/' . $data->foto)
                                                : asset('assets/img/nophoto.png');
                                        @endphp
                                        <tr>
                                            <td data-label="Karyawan">
                                                <div class="d-flex py-1 align-items-center">
                                                    <span class="avatar me-2"
                                                        style="background-image: url({{ $fotoUrl }})"></span>
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">
                                                            {{ $data->nama_lengkap }}
                                                            @if ($data->status_aktif == \App\Models\Karyawan::STATUS_MENUNGGU_APPROVAL)
                                                                <span class="badge bg-warning-lt ms-1">New</span>
                                                            @endif
                                                        </div>
                                                        <div class="text">NIK: {{ $data->nik }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Jabatan">
                                                <div>{{ $data->jabatan_nama ?? '-' }}</div>
                                                <div class="text-muted text-truncate">
                                                    {{ $data->departemen->nama_dept ?? '-' }}</div>
                                            </td>
                                            <td data-label="Kontak">{{ $data->no_hp ?? '-' }}</td>
                                            <td data-label="PT">{{ $data->cabang->nama_cabang ?? '-' }}</td>
                                            <td>
                                                <div class="btn-list flex-nowrap">
                                                    {{-- Tombol Lihat Detail --}}
                                                    <a href="{{ route('karyawan.show', $data->nik) }}"
                                                        class="btn btn-ghost-secondary btn-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="icon icon-tabler icons-tabler-outline icon-tabler-file-description">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                                            <path
                                                                d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2" />
                                                            <path d="M9 17h6" />
                                                            <path d="M9 13h6" />
                                                        </svg>
                                                    </a>

                                                    @can('karyawan-edit-admin')
                                                    {{-- Tombol Set Jam Kerja --}}
                                                    <a href="{{ route('konfigurasi.setjamkerja', $data->nik) }}"
                                                        class="btn btn-ghost-info btn-icon" title="Set Jam Kerja">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-clock-cog" width="24"
                                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M21 12a9 9 0 1 0 -9.972 8.948c.32 .034 .644 .052 .972 .052" />
                                                            <path d="M12 7v5l2 2" />
                                                            <path d="M19.001 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                                            <path d="M19.001 15.5v1.5" />
                                                            <path d="M19.001 21v1.5" />
                                                            <path d="M22.032 17.25l-1.299 .75" />
                                                            <path d="M17.27 20l-1.3 .75" />
                                                            <path d="M15.97 17.25l1.3 .75" />
                                                            <path d="M20.733 20l1.3 .75" />
                                                        </svg>
                                                    </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer d-flex align-items-center">
                            {{ $karyawan->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
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
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-upload me-1"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                            <path d="M12 11v6" />
                            <path d="M9.5 13.5l2.5 -2.5l2.5 2.5" />
                        </svg>
                        Import Data Karyawan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('karyawan.import') }}" method="POST" enctype="multipart/form-data"
                    id="frmImportKaryawan">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h4 class="alert-title">Petunjuk Import:</h4>
                            <ul class="mb-0">
                                <li>File harus berformat <strong>.xlsx</strong> atau <strong>.xls</strong></li>
                                <li>Maksimal ukuran file <strong>5MB</strong></li>
                                <li>Gunakan template export sebagai acuan format kolom</li>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            // 4. Auto Dismiss Alert Static (3 Detik)
            setTimeout(function() {
                var alerts = document.querySelectorAll(
                    '.alert-success, .alert-warning'); // Hanya success/warning yg auto close
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 3000);
        });
    </script>
@endpush
