@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Persetujuan Dinas Luar</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0">
                {{-- Filter Section --}}
                <div class="card-header bg-transparent border-bottom-1 py-3">
                    <form action="{{ route('dinasluars.approval') }}" method="GET" autocomplete="off" class="w-100">
                        {{-- === BARIS 1: Rentang Tanggal, Cabang, Status === --}}
                        <div class="row g-2 mb-2">

                            {{-- 1. Rentang Tanggal (Dari - Sampai) --}}
                            <div class="col-12 col-xl-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <rect x="4" y="5" width="16" height="16" rx="2" />
                                            <line x1="16" y1="3" x2="16" y2="7" />
                                            <line x1="8" y1="3" x2="8" y2="7" />
                                            <line x1="4" y1="11" x2="20" y2="11" />
                                            <line x1="11" y1="15" x2="12" y2="15" />
                                            <line x1="12" y1="15" x2="12" y2="18" />
                                        </svg>
                                    </span>
                                    <input type="text" id="dari" class="form-control datepicker" name="dari"
                                        value="{{ request('dari') }}" placeholder="Dari Tanggal" autocomplete="off">
                                    <input type="text" id="sampai" class="form-control datepicker" name="sampai"
                                        value="{{ request('sampai') }}" placeholder="Sampai Tanggal" autocomplete="off">
                                </div>
                            </div>

                            {{-- 2. Cabang --}}
                            <div class="col-12 col-md-6 col-xl-4">
                                <select name="kode_cabang" class="form-select">
                                    <option value="">Semua Cabang</option>
                                    @foreach ($cabangs as $c)
                                        <option value="{{ $c->kode_cabang }}"
                                            {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                            {{ $c->nama_cabang }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. Status --}}
                            <div class="col-12 col-md-6 col-xl-4">
                                <select name="status_acc" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu" {{ request('status_acc') === 'menunggu' ? 'selected' : '' }}>
                                        Menunggu</option>
                                    <option value="acc" {{ request('status_acc') === 'acc' ? 'selected' : '' }}>Disetujui
                                    </option>
                                    <option value="tolak" {{ request('status_acc') === 'tolak' ? 'selected' : '' }}>Ditolak
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- === BARIS 2: Nama/NIK, Tombol Aksi === --}}
                        <div class="row g-2">

                            {{-- Container digabungkan menjadi satu col-12 col-md-12 col-xl-12 karena input group menangani lebarnya --}}
                            <div class="col-12 col-md-12 col-xl-12">
                                <div class="input-group">
                                    {{-- Input Field --}}
                                    <input type="text" class="form-control" name="nama_lengkap"
                                        placeholder="Cari Nama Karyawan atau NIK..." value="{{ request('nama_lengkap') }}">

                                    {{-- Tombol Cari --}}
                                    <button class="btn btn-success" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
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

                {{-- Alert Section --}}
                @if (session('success') || session('error') || session('warning') || !empty($isAdminCabang))
                    <div class="p-3 pb-0">
                        @if (!empty($isAdminCabang))
                            <div class="alert alert-info d-flex align-items-center mb-2" role="alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="12" cy="12" r="9" />
                                    <line x1="12" y1="8" x2="12.01" y2="8" />
                                    <polyline points="11 12 12 12 12 16 13 16" />
                                </svg>
                                <div class="small">Data dibatasi untuk cabang Anda.</div>
                            </div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center mb-2" role="alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                                <div>{{ session('success') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-2" role="alert">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="12" cy="12" r="9" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                <div>{{ session('error') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Karyawan</th>
                                <th>Cabang</th>
                                <th>Periode & Info</th>
                                <th>Keperluan</th>
                                <th>Biaya</th>
                                <th class="text-center">Status</th>
                                <th class="w-1 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dinasluars as $d)
                                @php
                                    $dari = \Carbon\Carbon::parse($d->tgl_mulai);
                                    $sampai = \Carbon\Carbon::parse($d->tgl_selesai);
                                    $jumlahHari = abs($sampai->diffInDays($dari)) + 1;
                                    $initials = collect(explode(' ', $d->karyawan->nama_lengkap ?? 'X'))
                                        ->map(function ($w) {
                                            return strtoupper(substr($w, 0, 1));
                                        })
                                        ->take(2)
                                        ->join('');
                                    $colors = [
                                        'blue',
                                        'azure',
                                        'indigo',
                                        'purple',
                                        'pink',
                                        'red',
                                        'orange',
                                        'yellow',
                                        'lime',
                                        'green',
                                        'teal',
                                        'cyan',
                                    ];
                                    $color = $colors[array_rand($colors)];
                                @endphp
                                <tr>
                                    <td><span
                                            class="text-muted">{{ $loop->iteration + ($dinasluars->currentPage() - 1) * $dinasluars->perPage() }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex py-1 align-items-center">
                                            @if (!empty($d->karyawan->foto))
                                                <img src="{{ asset('storage/uploads/karyawan/' . $d->karyawan->foto) }}"
                                                    class="avatar me-2 rounded-circle"
                                                    style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <span class="avatar me-2 bg-{{ $color }}-lt"
                                                    title="{{ $d->karyawan->nama_lengkap }}">{{ $initials }}</span>
                                            @endif
                                            <div class="flex-fill">
                                                <div class="font-weight-medium text-truncate" style="max-width: 150px;">
                                                    {{ $d->karyawan->nama_lengkap ?? '-' }}</div>
                                                <div class="text-muted small">{{ $d->karyawan->nik ?? '-' }}</div>
                                                <div class="text-muted small">{{ $d->karyawan->jabatan_nama ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $d->karyawan->cabang->nama_cabang ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="text-dark mb-1 fw-bold">
                                                {{ date('d M', strtotime($d->tgl_mulai)) }} <span
                                                    class="text-muted mx-1">&rarr;</span>
                                                {{ date('d M', strtotime($d->tgl_selesai)) }}
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge badge-sm bg-secondary-lt">{{ $jumlahHari }}
                                                    Hari</span>
                                                @if ($d->transportasi)
                                                    <span
                                                        class="badge badge-sm bg-azure-lt">{{ ucfirst($d->transportasi) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">
                                        <div style="max-width: 250px;">
                                            <div class="text-truncate text-dark fw-bold">{{ $d->alasan }}</div>
                                            <div class="small text-muted d-flex align-items-center mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-map-pin me-1" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="11" r="3" />
                                                    <path
                                                        d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" />
                                                </svg>
                                                {{ $d->lokasi_tujuan ?? '-' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if (!is_null($d->dana_diajukan))
                                            <div class="text-dark">Rp {{ number_format($d->dana_diajukan, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($d->status_acc === 'acc')
                                            <span class="badge bg-success-lt">Disetujui</span>
                                        @elseif ($d->status_acc === 'tolak')
                                            <span class="badge bg-danger-lt">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning-lt">Menunggu</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-list flex-nowrap justify-content-center">
                                            @if ($d->status_acc === 'menunggu')
                                                <a href="#" class="btn btn-icon btn-outline-primary btn-approve-dl"
                                                    title="Proses Pengajuan" data-bs-toggle="tooltip" data-mode="approve"
                                                    data-id="{{ $d->id }}"
                                                    data-nama="{{ $d->karyawan->nama_lengkap }}"
                                                    data-nik="{{ $d->karyawan->nik }}"
                                                    data-jabatan="{{ $d->karyawan->jabatan_nama }}"
                                                    data-cabang="{{ $d->karyawan->cabang->nama_cabang }}"
                                                    data-keperluan="{{ $d->alasan }}"
                                                    data-lokasi="{{ $d->lokasi_tujuan }}"
                                                    data-dana-raw="{{ $d->dana_diajukan }}"
                                                    data-berangkat="{{ date('d-m-Y', strtotime($d->tgl_mulai)) }}"
                                                    data-pulang="{{ date('d-m-Y', strtotime($d->tgl_selesai)) }}"
                                                    data-dasar="{{ $d->dasar_perjalanan }}"
                                                    data-transportasi="{{ $d->transportasi }}"
                                                    data-dana="Rp {{ number_format($d->dana_diajukan, 0, ',', '.') }}"
                                                    data-keterangan="{{ $d->keterangan }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M11 13l9 -9" />
                                                        <path d="M15 4h5v5" />
                                                        <path
                                                            d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('dinasluars.cetak', $d->id) }}"
                                                    class="btn btn-icon btn-outline-success" title="Cetak Formulir"
                                                    data-bs-toggle="tooltip" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path
                                                            d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                                        <rect x="6" y="9" width="12" height="9" />
                                                        <path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2" />
                                                    </svg>
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-icon btn-outline-info btn-approve-dl"
                                                    title="Lihat Detail" data-bs-toggle="tooltip" data-mode="read"
                                                    data-id="{{ $d->id }}"
                                                    data-nama="{{ $d->karyawan->nama_lengkap }}"
                                                    data-nik="{{ $d->karyawan->nik }}"
                                                    data-jabatan="{{ $d->karyawan->jabatan_nama }}"
                                                    data-cabang="{{ $d->karyawan->cabang->nama_cabang }}"
                                                    data-keperluan="{{ $d->alasan }}"
                                                    data-lokasi="{{ $d->lokasi_tujuan }}"
                                                    data-dana-raw="{{ $d->dana_diajukan }}"
                                                    data-berangkat="{{ date('d-m-Y', strtotime($d->tgl_mulai)) }}"
                                                    data-pulang="{{ date('d-m-Y', strtotime($d->tgl_selesai)) }}"
                                                    data-dasar="{{ $d->dasar_perjalanan }}"
                                                    data-transportasi="{{ $d->transportasi }}"
                                                    data-dana="Rp {{ number_format($d->dana_diajukan, 0, ',', '.') }}"
                                                    data-keterangan="{{ $d->keterangan }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                                        <path
                                                            d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('dinasluars.cetak', $d->id) }}"
                                                    class="btn btn-icon btn-outline-success" title="Cetak Formulir"
                                                    data-bs-toggle="tooltip" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path
                                                            d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                                        <rect x="6" y="9" width="12" height="9" />
                                                        <path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2" />
                                                    </svg>
                                                </a>
                                                <button type="button" class="btn btn-icon btn-ghost-danger btn-cancel-dl"
                                                    data-id="{{ $d->id }}" title="Batalkan"
                                                    data-bs-toggle="tooltip">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M10 10l4 4m0 -4l-4 4" />
                                                        <path
                                                            d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-img">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-database-off text-muted"
                                                    width="48" height="48" viewBox="0 0 24 24" stroke-width="1"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path
                                                        d="M12.983 8.978c3.955 -.182 7.017 -1.446 7.017 -2.978c0 -1.657 -3.582 -3 -8 -3c-1.661 0 -3.204 .19 -4.483 .515m-2.783 1.228c-1.26 .94 -1.734 1.257 -1.734 1.257c0 1.657 3.582 3 8 3c.63 0 1.244 -.027 1.84 -.079" />
                                                    <path d="M4 12c0 1.657 3.582 3 8 3c.628 0 1.242 -.027 1.836 -.078" />
                                                    <path d="M4 18c0 1.657 3.582 3 8 3c4.418 0 8 -1.343 8 -3" />
                                                    <path d="M3 3l18 18" />
                                                </svg>
                                            </div>
                                            <p class="empty-title">Tidak ada data ditemukan</p>
                                            <p class="empty-subtitle text-secondary">Coba ubah filter pencarian atau pilih
                                                rentang tanggal yang lain.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-secondary small">
                        Menampilkan <span>{{ $dinasluars->firstItem() ?? 0 }}</span> -
                        <span>{{ $dinasluars->lastItem() ?? 0 }}</span> dari <span>{{ $dinasluars->total() }}</span> data
                    </p>
                    <ul class="pagination m-0 ms-auto">
                        {{ $dinasluars->links('pagination::bootstrap-5') }}
                    </ul>
                </div>
            </div>
        </div>
    </div>
    {{-- Modal Approval/Detail --}}
    <div class="modal modal-blur fade" id="modal-dinasluar" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail & Proses Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- User Info Block --}}
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Nama Karyawan</label>
                            <div class="form-control-plaintext fw-bold" id="mdNama">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">NIK</label>
                            <div class="form-control-plaintext" id="mdNik">-</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Cabang</label>
                            <div class="form-control-plaintext" id="mdCabang">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Jabatan</label>
                            <div class="form-control-plaintext" id="mdJabatan">-</div>
                        </div>
                    </div>

                    <div class="hr-text">Detail Perjalanan Dinas</div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Keperluan</label>
                            <div class="form-control-plaintext" id="mdKeperluan"
                                style="background: #f8f9fa; padding: 0.5rem; border-radius: 4px;">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Lokasi Tujuan</label>
                            <div class="form-control-plaintext fw-bold" id="mdLokasi">-</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Tanggal Berangkat</label>
                            <div class="form-control-plaintext" id="mdBerangkat">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Tanggal Pulang</label>
                            <div class="form-control-plaintext" id="mdPulang">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Dasar Perjalanan</label>
                            <div class="form-control-plaintext" id="mdDasar">-</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Transportasi</label>
                            <div class="form-control-plaintext" id="mdTransport">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Dana Diajukan</label>
                            <div class="form-control-plaintext fw-bold text-success" id="mdDana">-</div>
                        </div>
                    </div>

                    <div id="mdKeteranganWrap" class="d-none mb-3">
                        <label class="form-label small text-muted">Keterangan Tambahan</label>
                        <div class="form-control-plaintext" id="mdKeterangan"
                            style="border: 1px dashed #e0e0e0; padding: 10px; border-radius: 4px;">-</div>
                    </div>

                    <div class="hr-text">Keputusan</div>

                    <form action="{{ route('dinasluars.approveorreject') }}" method="POST">
                        @csrf
                        <input type="hidden" id="id_dinasluar_form" name="id">

                        {{-- WRAPPER KONTEN FORM (Untuk Toggle) --}}
                        <div id="form-approval-content">
                            <div class="mb-3">
                                <label class="form-label">Nominal Biaya Disetujui</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" name="dana_disetujui" id="dana_disetujui" class="form-control"
                                        placeholder="0" value="">
                                </div>
                                <small class="text-muted">Anda dapat mengubah nominal biaya yang disetujui</small>
                            </div>

                            <div class="mb-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="status_acc" value="acc"
                                                class="form-selectgroup-input" checked>
                                            <span
                                                class="form-selectgroup-label d-flex align-items-center p-3 h-100 flex-column text-center justify-content-center">
                                                <span
                                                    class="selection-icon bg-success-lt text-success mb-2 p-2 rounded-circle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M5 12l5 5l10 -10" />
                                                    </svg>
                                                </span>
                                                <span class="form-selectgroup-title strong">Disetujui</span>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="status_acc" value="tolak"
                                                class="form-selectgroup-input">
                                            <span
                                                class="form-selectgroup-label d-flex align-items-center p-3 h-100 flex-column text-center justify-content-center">
                                                <span
                                                    class="selection-icon bg-danger-lt text-danger mb-2 p-2 rounded-circle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M18 6l-12 12" />
                                                        <path d="M6 6l12 12" />
                                                    </svg>
                                                </span>
                                                <span class="form-selectgroup-title strong">Ditolak</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan Approval (Opsional)</label>
                                <textarea name="catatan_approval" class="form-control" rows="2" placeholder="Tulis catatan disini..."></textarea>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <a href="#" class="btn btn-link w-100" data-bs-dismiss="modal">Batal</a>
                                </div>
                                <div class="col">
                                    <button type="submit" class="btn btn-primary w-100">Simpan Keputusan</button>
                                </div>
                            </div>
                        </div>

                        {{-- Tampilan Alternatif untuk Read Only --}}
                        <div id="read-only-content" class="d-none">
                            <div class="alert alert-info text-center mb-0">
                                Data ini sudah diproses. Silakan klik "Tutup" atau gunakan tombol "Batalkan" di tabel jika
                                ingin merubah status.
                            </div>
                            <div class="mt-3">
                                <a href="#" class="btn btn-secondary w-100" data-bs-dismiss="modal">Tutup</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Cancel --}}
    <div class="modal modal-blur fade" id="modal-batalkan-dl" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <div class="avatar avatar-xl bg-danger-lt rounded-circle mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v4" />
                            <path
                                d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                            <path d="M12 17h.01" />
                        </svg>
                    </div>
                    <h3>Batalkan Status?</h3>
                    <div class="text-secondary">Apakah Anda yakin ingin membatalkan persetujuan ini? Status akan
                        dikembalikan ke <b>Menunggu</b>.</div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Tutup</a>
                            </div>
                            <div class="col">
                                <form id="frmBatalkanDL" method="POST" action="">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">Ya, Batalkan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('myscript')
    <script>
        $(document).ready(function() {

            $('.btn-approve-dl').click(function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                var mode = btn.data('mode');

                $('#id_dinasluar_form').val(id);
                $('#mdNama').text(btn.data('nama'));
                $('#mdNik').text(btn.data('nik'));
                $('#mdJabatan').text(btn.data('jabatan'));
                $('#mdCabang').text(btn.data('cabang'));
                $('#mdKeperluan').text(btn.data('keperluan'));
                $('#mdLokasi').text(btn.data('lokasi'));
                $('#mdBerangkat').text(btn.data('berangkat'));
                $('#mdPulang').text(btn.data('pulang'));
                $('#mdDasar').text(btn.data('dasar'));
                $('#mdTransport').text(btn.data('transportasi'));
                $('#mdDana').text(btn.data('dana'));

                var danaRaw = btn.data('dana-raw') || 0;
                $('#dana_disetujui').val(danaRaw);

                var ket = btn.data('keterangan');
                if (ket && ket !== '') {
                    $('#mdKeterangan').text(ket);
                    $('#mdKeteranganWrap').removeClass('d-none');
                } else {
                    $('#mdKeteranganWrap').addClass('d-none');
                }

                if (mode === 'approve') {
                    $('#form-approval-content').removeClass('d-none');
                    $('#read-only-content').addClass('d-none');
                    $('.modal-title').text('Proses Approval Dinas Luar');
                } else {
                    $('#form-approval-content').addClass('d-none');
                    $('#read-only-content').removeClass('d-none');
                    $('.modal-title').text('Detail Dinas Luar');
                }

                $('#modal-dinasluar').modal('show');
            });

            $('.btn-cancel-dl').click(function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var url = "{{ url('/dinas-luar') }}" + "/" + id + "/cancel";
                $('#frmBatalkanDL').attr('action', url);
                $('#modal-batalkan-dl').modal('show');
            });

            if ($(".datepicker").length > 0) {
                $(".datepicker").datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'dd-mm-yyyy',
                    orientation: "bottom auto",
                }).on('changeDate', function(e) {
                    $(this).closest('form').submit();
                });
            }

            // Auto Submit on Select Change
            $("select[name='kode_cabang'], select[name='status_acc']").change(function() {
                $(this).closest('form').submit();
            });
        });
    </script>
@endpush
