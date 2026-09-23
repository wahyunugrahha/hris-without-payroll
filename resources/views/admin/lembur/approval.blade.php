@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Data Lembur</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check" width="24"
                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M5 12l5 5l10 -10" />
                    </svg>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-alert-circle" width="24"
                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                        <path d="M12 8v4" />
                        <path d="M12 16h.01" />
                    </svg>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-12">
                    {{-- PERBAIKAN: Hapus 'shadow-sm' dan 'border-0'. Gunakan class 'card' standar agar rapi --}}
                    <div class="card">
                        <div class="card-body">
                            @php
                                $selectedJabatanId = request('jabatan_id');
                            @endphp
                            <form action="{{ route('admin.lembur.approval') }}" method="GET" autocomplete="off"
                                class="w-100">
                                @php
                                    $selectedJabatanId = request('jabatan_id');
                                @endphp

                                {{-- === BARIS 1: Tanggal, Jabatan, Departemen, Cabang === --}}
                                <div class="row g-2 mb-2">
                                    {{-- 1. Tanggal Lembur --}}
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
                                            <input type="text" id="tanggal" name="tanggal"
                                                value="{{ !empty(request('tanggal')) ? request('tanggal') : date('d-m-Y') }}"
                                                class="form-control" placeholder="Tanggal Lembur" readonly>
                                        </div>
                                    </div>

                                    {{-- 2. Jabatan --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="jabatan_id" id="jabatan_id" class="form-select">
                                            <option value="">Semua Jabatan</option>
                                            @foreach ($jabatans as $j)
                                                <option value="{{ $j->id }}"
                                                    {{ $selectedJabatanId == $j->id ? 'selected' : '' }}>
                                                    {{ $j->nama_jabatan }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 3. Departemen --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="kode_dept" id="kode_dept" class="form-select">
                                            <option value="">Semua Departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}"
                                                    {{ request('kode_dept') == $d->kode_dept ? 'selected' : '' }}>
                                                    {{ $d->nama_dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- 4. Cabang --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="kode_cabang" id="kode_cabang" class="form-select">
                                            <option value="">Semua Cabang</option>
                                            @foreach ($cabang as $c)
                                                <option value="{{ $c->kode_cabang }}"
                                                    {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                                    {{ $c->nama_cabang }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- === BARIS 2: Pencarian & Tombol Aksi === --}}
                                <div class="row g-2">
                                    {{-- 5. Status Approval --}}
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <select name="status" id="status" class="form-select">
                                            <option value="">Semua Status</option>
                                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>
                                                Menunggu
                                            </option>
                                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>
                                                Disetujui
                                            </option>
                                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>
                                                Ditolak
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-md-6 col-xl-9">
                                        <div class="input-group">
                                            {{-- Input Field --}}
                                            <input type="text" id="search" name="search"
                                                value="{{ request('search') }}" class="form-control"
                                                placeholder="Cari berdasarkan NIK atau Nama Karyawan...">

                                            {{-- Tombol Cari --}}
                                            <button type="submit" class="btn btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
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

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Daftar Lembur</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-vcenter table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 4%;">No</th>
                                            <th style="width: 14%;">Nama / NIK</th>
                                            <th style="width: 10%;" class="d-none d-md-table-cell">Jabatan</th>
                                            <th style="width: 10%;" class="d-none d-md-table-cell">Cabang</th>
                                            <th style="width: 9%;">Tanggal</th>
                                            <th style="width: 12%;">Pekerjaan</th>
                                            <th style="width: 10%;" class="d-none d-lg-table-cell">Tempat</th>
                                            <th style="width: 9%;">Jam (M/S)</th>
                                            <th style="width: 8%;">Total Jam</th>
                                            <th style="width: 8%;">Riwayat</th>
                                            <th style="width: 8%;">Status</th>
                                            <th style="width: 10%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lemburs as $lembur)
                                            <tr>
                                                <td>{{ $loop->iteration + ($lemburs->currentPage() - 1) * $lemburs->perPage() }}
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $lembur->karyawan->nama_lengkap ?? '-' }}
                                                    </div>
                                                    <div class="text-secondary small">{{ $lembur->nik }}</div>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    {{ $lembur->karyawan->jabatan_nama ?? '-' }}
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    {{ $lembur->karyawan->cabang->nama_cabang ?? '-' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($lembur->tanggal_lembur)->format('d-m-Y') }}
                                                </td>
                                                <td>{{ \Illuminate\Support\Str::limit($lembur->pekerjaan, 30) }}</td>
                                                <td class="d-none d-lg-table-cell">
                                                    {{ \Illuminate\Support\Str::limit($lembur->tempat, 20) }}</td>
                                                <td>
                                                    <div class="small text-secondary">{{ $lembur->jam_mulai ?? '-' }}
                                                    </div>
                                                    <div class="small text-secondary">{{ $lembur->jam_selesai ?? '-' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info-lt text-info">{{ $lembur->total_jam }}
                                                        jam</span>
                                                </td>
                                                <td>
                                                    @if ($lembur->update_count > 0)
                                                        <span class="badge bg-purple-lt text-purple"
                                                            title="Awal: {{ $lembur->jam_selesai_awal }} &#10;Final: {{ $lembur->jam_selesai }} &#10;Update: {{ \Carbon\Carbon::parse($lembur->last_update_at)->format('d-m-Y H:i') }}">
                                                            Ubah {{ $lembur->update_count }}x
                                                        </span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($lembur->status_approved == 0)
                                                        <span class="badge bg-warning-lt text-warning">Menunggu</span>
                                                    @elseif ($lembur->status_approved == 1)
                                                        <span class="badge bg-success-lt text-success">Disetujui</span>
                                                    @else
                                                        <span class="badge bg-danger-lt text-danger">Ditolak</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary btn-detail-lembur"
                                                        title="Detail / Approval" data-bs-toggle="modal"
                                                        data-bs-target="#modal-detail-lembur"
                                                        data-id="{{ $lembur->id }}"
                                                        data-status="{{ $lembur->status_approved }}"
                                                        data-nama="{{ $lembur->karyawan->nama_lengkap ?? '-' }}"
                                                        data-nik="{{ $lembur->nik }}"
                                                        data-jabatan="{{ $lembur->karyawan->jabatan_nama ?? '-' }}"
                                                        data-cabang="{{ $lembur->karyawan->cabang->nama_cabang ?? '-' }}"
                                                        data-tanggal="{{ \Carbon\Carbon::parse($lembur->tanggal_lembur)->format('d-m-Y') }}"
                                                        data-pekerjaan="{{ $lembur->pekerjaan }}"
                                                        data-tempat="{{ $lembur->tempat }}"
                                                        data-jammulai="{{ $lembur->jam_mulai }}"
                                                        data-jamselesai="{{ $lembur->jam_selesai }}"
                                                        data-totaljam="{{ $lembur->total_jam }}"
                                                        data-kode="{{ $lembur->kode_lembur }}"
                                                        data-keterangan="{{ $lembur->keterangan ?? '-' }}"
                                                        data-jamselesaiawal="{{ $lembur->jam_selesai_awal ?? '' }}"
                                                        data-updatecount="{{ $lembur->update_count ?? 0 }}"
                                                        data-lastupdate="{{ $lembur->last_update_at ? \Carbon\Carbon::parse($lembur->last_update_at)->format('d-m-Y H:i') : '' }}"
                                                        data-foto-masuk="{{ !empty($lembur->foto_masuk) ? asset('storage/uploads/absensi/' . $lembur->foto_masuk) : '' }}"
                                                        data-foto-keluar="{{ !empty($lembur->foto_keluar) ? asset('storage/uploads/absensi/' . $lembur->foto_keluar) : '' }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20"
                                                            height="20" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="icon icon-tabler icon-tabler-external-link">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" />
                                                            <path d="M11 13l9 -9" />
                                                            <path d="M15 4h5v5" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Reject modal per row dihapus, approval/penolakan melalui modal detail -->
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center py-5">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-inbox mb-3" width="64"
                                                        height="64" viewBox="0 0 24 24" stroke-width="1"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path
                                                            d="M4 4m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 13h3l3 3h4l3 -3h3" />
                                                    </svg>
                                                    <p class="text-muted">Tidak ada data lembur yang ditemukan</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <div class="text-secondary small">
                                    Menampilkan
                                    {{ $lemburs->firstItem() ?? 0 }} –
                                    {{ $lemburs->lastItem() ?? 0 }}
                                    dari {{ $lemburs->total() }} data
                                </div>

                                {{ $lemburs->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto Dokumentasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Foto">
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-detail-lembur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Lembur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nama:</strong><br><span id="mdNama">-</span></p>
                            <p class="mb-1"><strong>NIK:</strong><br><span id="mdNik">-</span></p>
                            <p class="mb-1"><strong>Jabatan:</strong><br><span id="mdJabatan">-</span></p>
                            <p class="mb-0"><strong>Cabang:</strong><br><span id="mdCabang">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Tanggal Lembur:</strong><br><span id="mdTanggal">-</span></p>
                            <p class="mb-1"><strong>Kode Lembur:</strong><br><span id="mdKode">-</span></p>
                            <p class="mb-1"><strong>Jam Mulai:</strong><br><span id="mdJamMulai">-</span></p>
                            <p class="mb-0"><strong>Jam Selesai:</strong><br><span id="mdJamSelesai">-</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Pekerjaan:</strong><br><span id="mdPekerjaan">-</span></p>
                            <p class="mb-0"><strong>Tempat:</strong><br><span id="mdTempat">-</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Total Jam:</strong><br><span id="mdTotalJam">-</span></p>
                            <p class="mb-0"><strong>Keterangan:</strong><br><span id="mdKeterangan">-</span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-12">
                            <form id="formUpdateJam" action="{{ route('admin.lembur.update-jam') }}" method="POST"
                                class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="id" id="updateJamId">
                                <div class="col-md-4">
                                    <label for="editJamMulai" class="form-label">Jam Mulai</label>
                                    <input type="time" class="form-control" id="editJamMulai" name="jam_mulai" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="editJamSelesai" class="form-label">Jam Selesai</label>
                                    <input type="time" class="form-control" id="editJamSelesai" name="jam_selesai" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100" id="btnUpdateJam">
                                        Simpan Perubahan Jam
                                    </button>
                                </div>
                                <div class="col-12">
                                    <small id="editJamHint" class="text-secondary d-none"></small>
                                </div>
                            </form>
                        </div>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Foto Masuk</label>
                            <div class="border rounded p-2 text-center" id="wrapFotoMasuk">
                                <img id="mdFotoMasuk" src="" class="img-fluid" alt="Foto Masuk"
                                    style="max-height: 220px; display: none;">
                                <div id="mdFotoMasukEmpty" class="text-secondary">Tidak ada foto</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto Keluar</label>
                            <div class="border rounded p-2 text-center" id="wrapFotoKeluar">
                                <img id="mdFotoKeluar" src="" class="img-fluid" alt="Foto Keluar"
                                    style="max-height: 220px; display: none;">
                                <div id="mdFotoKeluarEmpty" class="text-secondary">Tidak ada foto</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center">
                    <div id="mdStatusWrap" class="text-secondary small"></div>
                    <div class="d-flex gap-2" id="mdActionWrap">
                        <form id="formApprove" action="{{ route('admin.lembur.approve') }}" method="POST"
                            class="d-inline d-none">
                            @csrf
                            <input type="hidden" name="id" id="approveId">
                            <button type="submit" class="btn btn-success">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-check"
                                    width="20" height="20" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l5 5l10 -10" />
                                </svg>
                                Setujui
                            </button>
                        </form>
                        <form id="formReject" action="{{ route('admin.lembur.reject') }}" method="POST"
                            class="d-inline d-none">
                            @csrf
                            <input type="hidden" name="id" id="rejectId">
                            <input type="hidden" name="catatan" id="rejectCatatanHidden">
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#modal-reject-reason">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x"
                                    width="20" height="20" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M18 6l-12 12" />
                                    <path d="M6 6l12 12" />
                                </svg>
                                Tolak
                            </button>
                        </form>
                        <form id="formCancel" action="{{ route('admin.lembur.cancel') }}" method="POST"
                            class="d-inline d-none">
                            @csrf
                            <input type="hidden" name="id" id="cancelId">
                            <button type="submit" class="btn btn-outline-danger">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="icon icon-tabler icon-tabler-square-rounded-x" width="20" height="20"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M10 10l4 4m0 -4l-4 4" />
                                    <path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" />
                                </svg>
                                Batalkan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="modal-reject-reason" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alasan Penolakan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejectCatatan" class="form-label required">Catatan</label>
                        <textarea id="rejectCatatan" class="form-control" rows="4" placeholder="Masukkan alasan penolakan" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="btnSubmitReject">Kirim Penolakan</button>
                </div>
            </div>
        </div>
    </div>

    @push('myscript')
        <script>
            function viewImage(imageUrl) {
                document.getElementById('modalImage').src = imageUrl;
                var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
                imageModal.show();
            }

            $(document).ready(function() {
                // Init Datepicker
                $("#tanggal").datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'dd-mm-yyyy',
                    orientation: "bottom auto"
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const modalEl = document.getElementById('modal-detail-lembur');
                modalEl.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const normalizeTime = (value) => {
                        if (!value || value === '-') return '';
                        return value.toString().substring(0, 5);
                    };

                    const setText = (id, value) => document.getElementById(id).textContent = value || '-';

                    setText('mdNama', button.getAttribute('data-nama'));
                    setText('mdNik', button.getAttribute('data-nik'));
                    setText('mdJabatan', button.getAttribute('data-jabatan'));
                    setText('mdCabang', button.getAttribute('data-cabang'));
                    setText('mdTanggal', button.getAttribute('data-tanggal'));
                    setText('mdKode', button.getAttribute('data-kode'));
                    setText('mdJamMulai', button.getAttribute('data-jammulai'));
                    setText('mdJamSelesai', button.getAttribute('data-jamselesai'));
                    setText('mdJamSelesai', button.getAttribute('data-jamselesai'));
                    setText('mdPekerjaan', button.getAttribute('data-pekerjaan'));
                    setText('mdTempat', button.getAttribute('data-tempat'));
                    setText('mdTotalJam', button.getAttribute('data-totaljam'));
                    setText('mdKeterangan', button.getAttribute('data-keterangan'));

                    // Set hidden IDs for actions
                    const idVal = button.getAttribute('data-id');
                    document.getElementById('approveId').value = idVal;
                    document.getElementById('rejectId').value = idVal;
                    document.getElementById('cancelId').value = idVal;
                    document.getElementById('updateJamId').value = idVal;

                    // Set nilai default form edit jam
                    document.getElementById('editJamMulai').value = normalizeTime(button.getAttribute('data-jammulai'));
                    document.getElementById('editJamSelesai').value = normalizeTime(button.getAttribute('data-jamselesai'));

                    // Status handling: show/hide action buttons
                    const status = button.getAttribute('data-status');
                    const statusWrap = document.getElementById('mdStatusWrap');
                    const actionWrap = document.getElementById('mdActionWrap');
                    const formApprove = document.getElementById('formApprove');
                    const formReject = document.getElementById('formReject');
                    const formCancel = document.getElementById('formCancel');
                    const editJamMulai = document.getElementById('editJamMulai');
                    const editJamSelesai = document.getElementById('editJamSelesai');
                    const btnUpdateJam = document.getElementById('btnUpdateJam');
                    const editJamHint = document.getElementById('editJamHint');
                    let statusText = 'Status: Menunggu';
                    formApprove.classList.add('d-none');
                    formReject.classList.add('d-none');
                    formCancel.classList.add('d-none');
                    actionWrap.classList.remove('d-none');
                    if (status === '1') {
                        statusText = 'Status: Disetujui';
                        formCancel.classList.remove('d-none');
                    } else if (status === '2') {
                        statusText = 'Status: Ditolak';
                        actionWrap.classList.add('d-none');
                    } else {
                        formApprove.classList.remove('d-none');
                        formReject.classList.remove('d-none');
                    }
                    statusWrap.textContent = statusText;

                    const isPending = status === '0';
                    editJamMulai.disabled = !isPending;
                    editJamSelesai.disabled = !isPending;
                    btnUpdateJam.disabled = !isPending;
                    if (isPending) {
                        editJamHint.classList.add('d-none');
                        editJamHint.textContent = '';
                    } else {
                        editJamHint.classList.remove('d-none');
                        editJamHint.textContent = 'Edit jam hanya tersedia saat status Menunggu.';
                    }

                    const fotoMasuk = button.getAttribute('data-foto-masuk');
                    const fotoKeluar = button.getAttribute('data-foto-keluar');

                    const imgMasuk = document.getElementById('mdFotoMasuk');
                    const emptyMasuk = document.getElementById('mdFotoMasukEmpty');
                    if (fotoMasuk) {
                        imgMasuk.src = fotoMasuk;
                        imgMasuk.style.display = 'block';
                        emptyMasuk.style.display = 'none';
                    } else {
                        imgMasuk.src = '';
                        imgMasuk.style.display = 'none';
                        emptyMasuk.style.display = 'block';
                    }

                    const imgKeluar = document.getElementById('mdFotoKeluar');
                    const emptyKeluar = document.getElementById('mdFotoKeluarEmpty');
                    if (fotoKeluar) {
                        imgKeluar.src = fotoKeluar;
                        imgKeluar.style.display = 'block';
                        emptyKeluar.style.display = 'none';
                    } else {
                        imgKeluar.src = '';
                        imgKeluar.style.display = 'none';
                        emptyKeluar.style.display = 'block';
                    }
                });

                const rejectModal = document.getElementById('modal-reject-reason');
                const btnSubmitReject = document.getElementById('btnSubmitReject');
                const rejectCatatan = document.getElementById('rejectCatatan');
                const rejectCatatanHidden = document.getElementById('rejectCatatanHidden');
                if (btnSubmitReject) {
                    btnSubmitReject.addEventListener('click', function() {
                        const note = rejectCatatan.value.trim();
                        if (!note) {
                            rejectCatatan.focus();
                            return;
                        }
                        rejectCatatanHidden.value = note;
                        if (window.bootstrap && window.bootstrap.Modal) {
                            const modal = window.bootstrap.Modal.getOrCreateInstance(rejectModal);
                            modal.hide();
                        } else {
                            rejectModal.classList.remove('show');
                            rejectModal.style.display = 'none';
                        }
                        document.getElementById('formReject').submit();
                    });
                }
            });
        </script>
    @endpush
@endsection
