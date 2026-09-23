@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                {{-- Judul Halaman --}}
                <div class="col">
                    <h2 class="page-title">
                        Monitoring Surat Peringatan
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                            data-bs-target="#modal-tambah-sp">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            Tambah SP Baru
                        </a>
                        {{-- Tombol Mobile (Icon Only) --}}
                        <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal"
                            data-bs-target="#modal-tambah-sp" aria-label="Tambah SP Baru">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    {{-- PAGE HEADER: Judul & Tombol Action Utama --}}

    <div class="page-body">
        <div class="container-xl">

            {{-- 1. Statistik Cards --}}
            <div class="row row-cards mb-4">
                {{-- Total Aktif --}}
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-danger text-white avatar">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-alert-triangle" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 9v4" />
                                            <path
                                                d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" />
                                            <path d="M12 16h.01" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">Total SP Aktif</div>
                                    <div class="text-muted">{{ $stats['total_aktif'] }} Karyawan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- SP 1 --}}
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-warning text-white avatar">SP1</span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">SP 1 Aktif</div>
                                    <div class="text-muted">{{ $stats['sp1_aktif'] }} Kasus</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- SP 2 --}}
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-orange text-white avatar">SP2</span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">SP 2 Aktif</div>
                                    <div class="text-muted">{{ $stats['sp2_aktif'] }} Kasus</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- SP 3 --}}
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-dark text-white avatar">SP3</span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">SP 3 Aktif</div>
                                    <div class="text-muted">{{ $stats['sp3_aktif'] }} Kasus</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Global Alerts --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M5 12l5 5l10 -10"></path>
                            </svg>
                        </div>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <div class="d-flex">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                        </div>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- 2. Main Card (Table & Filter) --}}
            <div class="card">
                <div class="card-header bg-transparent border-bottom-1 py-3">
                    <form action="{{ route('suratperingatan.index') }}" method="GET" class="w-100">
                        @php
                            $selectedJabatanId = request('jabatan_id');
                        @endphp
                        <div class="row g-2">
                            {{-- 2. Jabatan --}}
                            <div class="col-12 col-md-6 col-xl-2">
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

                            {{-- 2. Cabang --}}
                            <div class="col-12 col-md-6 col-xl-2">
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

                            {{-- 1. Level SP --}}
                            <div class="col-12 col-md-6 col-xl-2">
                                <select name="level" class="form-select">
                                    <option value="">Semua Tingkat (SP)</option>
                                    <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>SP 1</option>
                                    <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>SP 2</option>
                                    <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>SP 3</option>
                                </select>
                            </div>

                            {{-- 4. Status --}}
                            <div class="col-12 col-md-6 col-xl-2">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif
                                    </option>
                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>
                                        Non-Aktif</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="input-group">
                                    {{-- Input Field --}}
                                    <input type="text" name="q" value="{{ request('q') }}"
                                        class="form-control" placeholder="Cari Nama / NIK...">

                                    {{-- Tombol Cari --}}
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-search" width="24" height="24"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                            <path d="M21 21l-6 -6" />
                                        </svg>
                                        Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- TABLE --}}
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th class="w-1">No</th>
                                <th>Karyawan</th>
                                <th>Pelanggaran</th>
                                <th>Berlaku Hingga</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th class="text-end" style="min-width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $i => $it)
                                @php
                                    $today = now();
                                    $expires = \Carbon\Carbon::parse($it->expires_at);
                                    $is_active = $expires->gt($today);
                                    $days_left = $today->diffInDays($expires, false);

                                    // Warna Badge Level
                                    $bg_level = match ($it->level) {
                                        3 => 'bg-dark',
                                        2 => 'bg-orange',
                                        default => 'bg-warning',
                                    };

                                    // Ambil foto karyawan
                                    $fotoUrl = !empty($it->karyawan?->foto)
                                        ? asset('storage/uploads/karyawan/' . $it->karyawan->foto)
                                        : asset('assets/img/nophoto.png');
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $items->firstItem() + $i }}</td>
                                    <td>
                                        <div class="d-flex py-1 align-items-center">
                                            <span class="avatar me-2 rounded"
                                                style="background-image: url({{ $fotoUrl }})"></span>
                                            <div class="flex-fill">
                                                <div class="font-weight-medium">
                                                    {{ $it->karyawan?->nama_lengkap ?? 'Tidak Diketahui' }}</div>
                                                <div class="text-muted text-nowrap">
                                                    NIK: {{ $it->nik }}
                                                    <span
                                                        class="badge badge-sm bg-blue-lt ms-1">{{ $it->karyawan?->jabatan_nama ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge {{ $bg_level }} text-white me-2">SP
                                                {{ $it->level }}</span>
                                        </div>
                                        <div class="text-muted text-uppercase small">
                                            @if ($it->violation_type == 'late')
                                                Keterlambatan
                                            @elseif($it->violation_type == 'absent')
                                                Alpha / Mangkir
                                            @else
                                                Lainnya
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-body font-weight-medium">{{ $expires->format('d M Y') }}</div>
                                        @if ($is_active)
                                            <div class="text-danger small" title="{{ $expires->diffForHumans() }}">
                                                Sisa: {{ round($days_left) }} hari
                                            </div>
                                        @else
                                            <div class="text-muted small">Sudah Berakhir</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($is_active)
                                            <span class="badge bg-green-lt">Aktif</span>
                                        @else
                                            <span class="badge bg-secondary-lt">Non-Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="{{ $it->note }}">
                                            {{ $it->note ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <a href="{{ route('suratperingatan.cetak', $it->id) }}" target="_blank"
                                                class="btn btn-outline-primary btn-sm">
                                                Cetak
                                            </a>

                                            <a href="#" class="btn btn-azure btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#modal-detail"
                                                data-nama="{{ $it->karyawan?->nama_lengkap ?? '-' }}"
                                                data-nik="{{ $it->nik }}"
                                                data-jabatan="{{ $it->karyawan?->jabatan_nama ?? '-' }}"
                                                data-cabang="{{ $it->karyawan?->cabang?->nama_cabang ?? '-' }}"
                                                data-dept="{{ $it->karyawan?->departemen?->nama_dept ?? '-' }}"
                                                data-level="{{ $it->level }}" data-jenis="{{ $it->violation_type }}"
                                                data-tgl-terbit="{{ date('d-m-Y', strtotime($it->issued_at)) }}"
                                                data-tgl-akhir="{{ date('d-m-Y', strtotime($it->expires_at)) }}"
                                                data-status="{{ $is_active ? 'Aktif' : 'Non-Aktif' }}"
                                                data-keterangan="{{ $it->note }}">
                                                Detail
                                            </a>

                                            @if ($is_active)
                                                <form action="{{ route('suratperingatan.pemutihan', $it->id) }}"
                                                    method="POST" id="form-pemutihan-{{ $it->id }}">
                                                    @csrf
                                                    <button type="button" class="btn btn-outline-success btn-sm"
                                                        onclick="confirmPemutihan({{ $it->id }}, {{ json_encode($it->karyawan?->nama_lengkap ?? '') }})">
                                                        Putihkan
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-img"><img src="{{ asset('assets/img/empty.svg') }}"
                                                    height="128" alt=""></div>
                                            <p class="empty-title">Tidak ada data SP ditemukan</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-muted">
                        Menampilkan <span>{{ $items->firstItem() ?? 0 }}</span> sampai
                        <span>{{ $items->lastItem() ?? 0 }}</span> dari <span>{{ $items->total() }}</span> entri
                    </p>
                    <div class="ms-auto">
                        {{ $items->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALS SECTION --}}

    {{-- 1. Modal Detail --}}
    <div class="modal modal-blur fade" id="modal-detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Surat Peringatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5 border-end">
                            <div class="mb-3">
                                <label class="form-label text-muted">Nama Karyawan</label>
                                <div class="font-weight-bold fs-3" id="mdl-nama">Loading...</div>
                                <div class="text-muted" id="mdl-nik">NIK: -</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Jabatan</label>
                                <div class="form-control-plaintext pt-0" id="mdl-jabatan">-</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small">Cabang</label>
                                    <div class="form-control-plaintext pt-0 text-uppercase" id="mdl-cabang">-</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small">Departemen</label>
                                    <div class="form-control-plaintext pt-0 text-uppercase" id="mdl-dept">-</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Status SP</label>
                                <div id="mdl-status-badge"></div>
                            </div>
                        </div>
                        <div class="col-md-7 ps-md-4">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label text-muted small">Tingkat SP</label>
                                    <div class="fs-3 fw-bold" id="mdl-level">SP -</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small">Jenis Pelanggaran</label>
                                    <div id="mdl-jenis">-</div>
                                </div>
                            </div>
                            <div class="card bg-muted-lt mb-3">
                                <div class="card-body p-2">
                                    <div class="row text-center">
                                        <div class="col border-end">
                                            <div class="text-muted small">Tanggal Terbit</div>
                                            <div class="fw-bold" id="mdl-tgl-terbit">-</div>
                                        </div>
                                        <div class="col">
                                            <div class="text-muted small">Berlaku Sampai</div>
                                            <div class="fw-bold text-danger" id="mdl-tgl-akhir">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label text-muted">Keterangan</label>
                                <div class="p-3 bg-white border rounded" style="min-height: 100px;">
                                    <span id="mdl-keterangan" class="text-secondary">Tidak ada keterangan.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Modal Tambah SP --}}
    <div class="modal modal-blur fade" id="modal-tambah-sp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Surat Peringatan Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('suratperingatan.store') }}" method="POST" id="form-tambah-sp">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Baris 1: NIK & Level SP --}}
                            <div class="col-md-6">
                                <label class="form-label fw-medium">NIK Karyawan <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="select-karyawan" name="nik" required
                                    style="width: 100%;">
                                    <option value="">Cari nama atau NIK karyawan...</option>
                                    @foreach ($karyawanList as $k)
                                        <option value="{{ $k->nik }}">
                                            {{ $k->nik }} - {{ $k->nama_lengkap }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-hint">Ketik nama atau NIK untuk mencari</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Level SP <span class="text-danger">*</span></label>
                                <select class="form-select" name="level" required>
                                    <option value="">Pilih Level SP</option>
                                    <option value="1">SP 1</option>
                                    <option value="2">SP 2</option>
                                    <option value="3">SP 3</option>
                                </select>
                            </div>

                            {{-- Baris 2: Jenis Pelanggaran & Tanggal Terbit --}}
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Jenis Pelanggaran <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" name="violation_type" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="late">Keterlambatan</option>
                                    <option value="absent">Mangkir/Alpha</option>
                                    <option value="discipline">Pelanggaran Disiplin</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tanggal Terbit <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker" id="issued_at" name="issued_at"
                                    value="{{ date('Y-m-d') }}" required placeholder="YYYY-MM-DD">
                            </div>

                            {{-- Baris 3: Tanggal Berakhir & Durasi --}}
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tanggal Berakhir <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker" id="expires_at" name="expires_at"
                                    required placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Atau Durasi (Bulan)</label>
                                <input type="number" class="form-control" id="durasi_bulan" placeholder="Misal: 6"
                                    min="1" max="36">
                                <small class="form-hint">Otomatis menghitung tanggal berakhir</small>
                            </div>

                            {{-- Baris 4: Catatan Full Width --}}
                            <div class="col-12">
                                <label class="form-label fw-medium">Catatan</label>
                                <textarea class="form-control" name="note" rows="3"
                                    placeholder="Keterangan tambahan tentang pelanggaran (opsional)"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn" data-bs-dismiss="modal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon me-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M18 6l-12 12" />
                                <path d="M6 6l12 12" />
                            </svg>
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon me-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg>
                            Simpan
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
            // Init Datepicker
            $(".datepicker").datepicker({
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });

            // Inisialisasi Select2 untuk pencarian karyawan
            $('#select-karyawan').select2({
                dropdownParent: $('#modal-tambah-sp'),
                placeholder: "Cari NIK atau Nama...",
                allowClear: true,
                width: '100%'
            });

            // Logic Auto Hitung Tanggal Berakhir
            $('#durasi_bulan, #issued_at').on('input change', function() {
                const durasi = parseInt($('#durasi_bulan').val());
                const issuedVal = $('#issued_at').val();

                if (durasi && issuedVal) {
                    const tglTerbit = new Date(issuedVal);
                    if (!isNaN(tglTerbit.getTime())) {
                        tglTerbit.setMonth(tglTerbit.getMonth() + durasi);
                        const year = tglTerbit.getFullYear();
                        const month = String(tglTerbit.getMonth() + 1).padStart(2, '0');
                        const day = String(tglTerbit.getDate()).padStart(2, '0');

                        // Set nilai ke input expires_at
                        $('#expires_at').val(`${year}-${month}-${day}`);
                        // Jika menggunakan bootstrap-datepicker, update juga pluginnya
                        $('.datepicker').datepicker('update');
                    }
                }
            });
        });

        // Intercept Form Submit SP Baru
        $('#form-tambah-sp').on('submit', function(e) {
            e.preventDefault();
            const form = this;
            const nik = $('#select-karyawan').val();

            if (!nik) {
                Swal.fire('Error', 'Pilih karyawan terlebih dahulu.', 'error');
                return;
            }

            // Tampilkan loading sebentar
            Swal.fire({
                title: 'Memeriksa Status...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Cek SP Aktif via AJAX
            $.ajax({
                url: `/panel/surat-peringatan/check-active/${nik}`,
                type: 'GET',
                success: function(response) {
                    Swal.close();
                    if (response.active) {
                        const selectedLevel = parseInt($('select[name="level"]').val());
                        const activeLevel = parseInt(response.level);

                        if (selectedLevel < activeLevel) {
                            Swal.fire({
                                title: 'Penambahan Ditolak!',
                                html: `Karyawan masih memiliki <strong>SP ${activeLevel}</strong> yang aktif.<br>`,
                                icon: 'error',
                                confirmButtonColor: 'var(--color-accent)',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }

                        // Jika level sama atau lebih tinggi, minta konfirmasi
                        Swal.fire({
                            title: 'Ada SP Aktif!',
                            html: `Karyawan ini masih memiliki <strong>SP ${activeLevel}</strong> yang aktif hingga <strong>${response.expires_at}</strong>.<br><br>` +
                                `Menyimpan SP baru akan <strong>menonaktifkan (expired)</strong> SP lama tersebut.<br>Lanjutkan?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: 'var(--color-accent)',
                            cancelButtonColor: 'var(--color-muted)',
                            confirmButtonText: 'Ya, Gantikan & Simpan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        // Jika tidak ada SP aktif, langsung submit
                        form.submit();
                    }
                },
                error: function() {
                    Swal.close();
                    // Fallback jika AJAX gagal, biarkan controller yang handle deaktifasi di backend
                    form.submit();
                }
            });
        });

        // Script Modal Detail
        const modalDetail = document.getElementById('modal-detail');
        if (modalDetail) {
            modalDetail.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const data = button.dataset;

                document.getElementById('mdl-nama').textContent = data.nama;
                document.getElementById('mdl-nik').textContent = 'NIK: ' + data.nik;
                document.getElementById('mdl-jabatan').textContent = data.jabatan;
                document.getElementById('mdl-cabang').textContent = data.cabang;
                document.getElementById('mdl-dept').textContent = data.dept;
                document.getElementById('mdl-tgl-terbit').textContent = data.tglTerbit;
                document.getElementById('mdl-tgl-akhir').textContent = data.tglAkhir;
                document.getElementById('mdl-keterangan').textContent = data.keterangan || '-';

                // Level Styling
                const elLevel = document.getElementById('mdl-level');
                elLevel.textContent = 'SP ' + data.level;
                elLevel.className = 'fs-3 fw-bold ' + (data.level == 3 ? 'text-dark' : (data.level == 2 ?
                    'text-orange' : 'text-warning'));

                // Status Badge
                const elStatus = document.getElementById('mdl-status-badge');
                if (data.status === 'Aktif') {
                    elStatus.innerHTML = '<span class="badge bg-danger text-white">Aktif</span>';
                } else {
                    elStatus.innerHTML = '<span class="badge bg-secondary text-white">Non-Aktif</span>';
                }

                // Jenis Pelanggaran
                const jenisMap = {
                    'late': 'Keterlambatan',
                    'absent': 'Mangkir',
                    'discipline': 'Disiplin',
                    'other': 'Lainnya'
                };
                document.getElementById('mdl-jenis').textContent = jenisMap[data.jenis] || 'Lainnya';
            });
        }

        // SweetAlert Konfirmasi Pemutihan
        function confirmPemutihan(id, nama) {
            Swal.fire({
                title: 'Konfirmasi Pemutihan',
                html: 'Yakin ingin memutihkan SP untuk <strong></strong>?',
                didOpen: (popup) => { popup.querySelector('strong').textContent = nama; },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-accent)',
                cancelButtonColor: 'var(--color-muted)',
                confirmButtonText: 'Ya, Putihkan'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-pemutihan-' + id).submit();
                }
            })
        }
    </script>

    {{-- Toast Notification --}}
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 2000,
                toast: true,
                position: 'top-end'
            });
        </script>
    @endif
@endpush
