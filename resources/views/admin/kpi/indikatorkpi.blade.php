@extends('layouts.admin.tabler')
@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan & Persetujuan</div>
                    <h2 class="page-title">Histori Workbook Daily</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- ALERT NOTIFIKASI --}}
            @if (session('success'))
                <div class="alert alert-important alert-success alert-dismissible shadow-sm mb-3">
                    <div class="d-flex">
                        <div><i class="ti ti-check me-2"></i></div>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-important alert-danger alert-dismissible shadow-sm mb-3">
                    <div class="d-flex">
                        <div><i class="ti ti-alert-circle me-2"></i></div>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- CARD PROFIL KARYAWAN --}}
            <div class="card mb-3 shadow-sm border-0" style="background-color: #f8fafc;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span
                                class="avatar avatar-md bg-primary text-white">{{ substr($karyawan->nama_lengkap, 0, 2) }}</span>
                        </div>
                        <div class="col">
                            <h3 class="mb-0 text-primary">{{ $karyawan->nama_lengkap }} ({{ $karyawan->nik }})</h3>
                            <div class="text-muted mt-1" style="font-size: 13px;">
                                <i class="ti ti-briefcase me-1"></i> {{ $karyawan->jabatanRel->nama_jabatan ?? '-' }} &bull;
                                <i class="ti ti-building me-1"></i> {{ $karyawan->departemen->nama_dept ?? '-' }} <br>
                                <span class="badge bg-blue-lt mt-2 px-2 py-1">
                                    <b>Periode:</b> {{ \Carbon\Carbon::parse($tglAwal)->translatedFormat('d M Y') }}
                                    s/d {{ \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d M Y') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-auto ms-auto d-flex gap-2">
                            {{-- HITUNG LOGIKA STATUS APPROVAL --}}
                            @php
                                $totalLaporan = $riwayatKPI->count();
                                $totalApprovedHR = $riwayatKPI->where('status', 'approved_by_hr')->count();
                                $showApproveButton = $totalLaporan > 0 && $totalApprovedHR < $totalLaporan;
                                $belumDiApproveAtasan = $riwayatKPI
                                    ->whereNotIn('status', ['approved_by_atasan', 'approved_by_hr'])
                                    ->count();
                            @endphp

                            @if ($showApproveButton)
                                <button type="button" class="btn btn-success" id="btn-approve-bulk"
                                    data-ready="{{ $belumDiApproveAtasan == 0 ? 'yes' : 'no' }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-checks"
                                        width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M7 12l5 5l10 -10" />
                                        <path d="M2 12l5 5m5 -5l5 -5" />
                                    </svg>
                                    Approve Semua
                                </button>
                            @endif

                            <a href="{{ route('kpi.rekap.karyawan', ['bulan' => $reqBulan, 'tahun' => $reqTahun]) }}"
                                class="btn btn-outline-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 12l14 0" />
                                    <path d="M5 12l6 6" />
                                    <path d="M5 12l6 -6" />
                                </svg>
                                Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FORM HIDDEN UNTUK BULK APPROVE --}}
            <form id="form-bulk-approve" action="{{ route('kpi.rekap.karyawan.bulk_approve') }}" method="POST"
                style="display: none;">
                @csrf
                <input type="hidden" name="niks[]" value="{{ $karyawan->nik }}">
                <input type="hidden" name="bulan" value="{{ $reqBulan }}">
                <input type="hidden" name="tahun" value="{{ $reqTahun }}">
            </form>

            {{-- TABEL HISTORI WORKBOOK DAILY --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h3 class="card-title">Histori Capaian Harian (Workbook)</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="w-1 text-center">No</th>
                                <th>Tanggal Laporan</th>
                                <th class="text-center">Progress Workbook Daily</th>
                                <th class="text-center">Status Approval</th>
                                <th class="w-1 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayatKPI as $item)
                                @php
                                    $statusList = [
                                        'draft' => ['label' => 'Draft', 'color' => 'secondary'],
                                        'submitted' => ['label' => 'Menunggu Approval', 'color' => 'warning'],
                                        'approved_by_atasan' => ['label' => 'Disetujui Atasan', 'color' => 'success'],
                                        'approved_by_hr' => ['label' => 'Disetujui HR (Final)', 'color' => 'primary'],
                                        'rejected' => ['label' => 'Revisi / Ditolak', 'color' => 'danger'],
                                    ];
                                    $status = $statusList[$item->status] ?? [
                                        'label' => strtoupper($item->status),
                                        'color' => 'gray',
                                    ];

                                    $skorUtama = $item->kpiDailyDetail->sum('score');
                                    $skorExtra = $item->kpiDailyExtra->sum('score');
                                    $totalSkorMentah = floatval($skorUtama + $skorExtra);
                                    $persenProgress = ($totalSkorMentah / 40) * 100;
                                    if ($persenProgress > 100) {
                                        $persenProgress = 100;
                                    }
                                @endphp

                                <tr>
                                    <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                    <td class="fw-bold text-dark">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('l, d F Y') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="progress w-50" style="height: 6px;">
                                                <div class="progress-bar bg-green" style="width: {{ $persenProgress }}%"
                                                    role="progressbar"></div>
                                            </div>
                                            <span
                                                class="badge bg-green-lt fw-bold px-2 py-1">{{ round($persenProgress) }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-{{ $status['color'] }}-lt p-2 border border-{{ $status['color'] }}">
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('kpi.indikator.detail.index', $item->id) }}"
                                            class="btn btn-sm btn-primary shadow-sm">
                                            Buka Penilaian
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-arrow-right ms-1" width="16"
                                                height="16" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M5 12l14 0" />
                                                <path d="M13 18l6 -6" />
                                                <path d="M13 6l6 6" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="ti ti-file-x" style="font-size: 40px; margin-bottom: 10px;"></i><br>
                                        Karyawan belum membuat laporan KPI harian di periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TABEL KHUSUS KEGIATAN TAMBAHAN (EXTRA KPI) --}}
            @php
                // Mengumpulkan semua Extra KPI dari semua laporan di bulan ini
                $semuaExtra = collect();
                foreach ($riwayatKPI as $kpi) {
                    if ($kpi->kpiDailyExtra) {
                        foreach ($kpi->kpiDailyExtra as $ex) {
                            // Sisipkan tanggal laporan harian ke dalam object extra untuk info
                            $ex->tanggal_laporan = $kpi->tanggal;
                            $semuaExtra->push($ex);
                        }
                    }
                }
            @endphp

            @if ($semuaExtra->count() > 0)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h3 class="card-title text-success"><i class="ti ti-star me-2"></i>Daftar Kegiatan Tambahan
                            Karyawan</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover card-table">
                            <thead class="text-muted">
                                <tr>
                                    <th class="w-1 text-center">No</th>
                                    <th>Tanggal Input</th>
                                    <th>Nama Kegiatan Tambahan</th>
                                    <th>Catatan / Keterangan</th>
                                    <th class="text-center w-1">Point Tambahan</th>
                                    @if ($canEdit)
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($semuaExtra as $extra)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td class="text-muted">
                                            {{ \Carbon\Carbon::parse($extra->tanggal_laporan)->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="fw-bold text-dark">{{ $extra->indikator_tambahan }}</td>
                                        <td>{{ $extra->catatan ?: '-' }}</td>
                                        <td class="text-center text-success fw-bold">+{{ $extra->score ?? 10 }}</td>

                                        @if ($canEdit)
                                            <td class="text-center">
                                                <div class="btn-list flex-nowrap justify-content-center text-nowrap">
                                                    {{-- Tombol Edit --}}
                                                    <a href="#" class="btn btn-ghost-primary btn-icon"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-edit-extra-{{ $extra->id }}"
                                                        title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-pencil" width="24"
                                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                            <path d="M13.5 6.5l4 4" />
                                                        </svg>
                                                    </a>

                                                    {{-- Tombol Hapus --}}
                                                    <button type="button"
                                                        class="btn btn-ghost-danger btn-icon btn-delete-extra"
                                                        data-id="{{ $extra->id }}" title="Hapus">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="icon icon-tabler icon-tabler-trash" width="24"
                                                            height="24" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M4 7l16 0" />
                                                            <path d="M10 11l0 6" />
                                                            <path d="M14 11l0 6" />
                                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>

                                    {{-- FORM DELETE HIDDEN UNTUK MASING-MASING EXTRA --}}
                                    @if ($canEdit)
                                        <form id="deleteFormExtra{{ $extra->id }}" method="POST"
                                            action="{{ route('kpi.indikator.extra.destroy', $extra->id) }}"
                                            style="display: none;">
                                            @csrf @method('DELETE')
                                        </form>

                                        {{-- MODAL EDIT UNTUK MASING-MASING EXTRA --}}
                                        <div class="modal modal-blur fade" id="modal-edit-extra-{{ $extra->id }}"
                                            tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <form action="{{ route('kpi.indikator.extra.update', $extra->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit KPI Tambahan</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label required">Nama Kegiatan</label>
                                                                <input type="text" class="form-control"
                                                                    name="indikator_tambahan"
                                                                    value="{{ $extra->indikator_tambahan }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Catatan / Keterangan</label>
                                                                <textarea class="form-control" name="catatan" rows="4">{{ $extra->catatan }}</textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label required">Poin (Score)</label>
                                                                <input type="number" class="form-control" name="score"
                                                                    value="{{ $extra->score ?? 10 }}" required
                                                                    min="0">
                                                                <small class="text-muted">Admin dapat menyesuaikan bobot
                                                                    poin dari kegiatan ini.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button"
                                                                class="btn btn-link link-secondary me-auto"
                                                                data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Update
                                                                Data</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. Bulk Approve Logic
            const btnApproveBulk = document.getElementById('btn-approve-bulk');
            if (btnApproveBulk) {
                btnApproveBulk.addEventListener('click', function(e) {
                    e.preventDefault();

                    let isReady = this.getAttribute('data-ready') === 'yes';

                    // if (!isReady) {
                    //     Swal.fire({
                    //         title: 'Belum Bisa Di-Approve!',
                    //         text: "Pastikan semua laporan KPI harian karyawan ini telah diperiksa dan disetujui (diberi penilaian) oleh Atasan terlebih dahulu.",
                    //         icon: 'warning',
                    //         confirmButtonColor: '#3b82f6',
                    //         confirmButtonText: 'Mengerti'
                    //     });
                    //     return;
                    // }

                    Swal.fire({
                        title: 'Approve Semua KPI?',
                        text: "Tindakan ini akan menyetujui semua laporan harian beserta penilaian atasan untuk karyawan ini secara permanen.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2fb344',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Approve Semua!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('form-bulk-approve').submit();
                        }
                    });
                });
            }

            // 2. Delete Extra Logic
            const btnDeleteExtras = document.querySelectorAll('.btn-delete-extra');
            btnDeleteExtras.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    let id = this.getAttribute('data-id');

                    Swal.fire({
                        title: 'Hapus Kegiatan Ini?',
                        text: "Data KPI tambahan ini akan dihapus secara permanen.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('deleteFormExtra' + id).submit();
                        }
                    });
                });
            });

        });
    </script>
@endpush
