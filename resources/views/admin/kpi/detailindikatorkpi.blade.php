@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle fw-bold">Monitoring Kinerja</div>
                    <h2 class="page-title text-uppercase fw-bolder mb-2">
                        {{ $kpiDaily->karyawan->nama_lengkap ?? 'Detail KPI' }}
                    </h2>

                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <span class="badge bg-blue-lt px-2 py-1">
                            <i class="ti ti-id-badge me-1"></i> NIK: {{ $kpiDaily->nik }}
                        </span>
                        <span class="badge bg-purple-lt px-2 py-1">
                            <i class="ti ti-calendar-event me-1"></i>
                            {{ \Carbon\Carbon::parse($kpiDaily->tanggal)->format('d F Y') }}
                        </span>
                    </div>

                    <div class="row g-1">
                        <div class="col-auto">
                            <div class="card card-sm bg-light-lt border-0 shadow-none">
                                <div class="card-body py-1 px-2 d-flex align-items-center">
                                    <div class="avatar avatar-xs bg-blue-lt me-2"><i class="ti ti-briefcase"></i></div>
                                    <div>
                                        <div class="text-muted small" style="font-size: 10px; line-height: 1;">Jabatan</div>
                                        <div class="fw-bold text-dark">
                                            {{ $kpiDaily->karyawan->jabatanRel->nama_jabatan ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="card card-sm bg-light-lt border-0 shadow-none">
                                <div class="card-body py-1 px-2 d-flex align-items-center">
                                    <div class="avatar avatar-xs bg-green-lt me-2"><i class="ti ti-building"></i></div>
                                    <div>
                                        <div class="text-muted small" style="font-size: 10px; line-height: 1;">Departemen
                                        </div>
                                        <div class="fw-bold text-dark">
                                            {{ $kpiDaily->karyawan->departemen->nama_dept ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-auto">
                            <div class="card card-sm bg-light-lt border-0 shadow-none">
                                <div class="card-body py-1 px-2 d-flex align-items-center">
                                    <div class="avatar avatar-xs bg-orange-lt me-2"><i class="ti ti-map-pin"></i></div>
                                    <div>
                                        <div class="text-muted small" style="font-size: 10px; line-height: 1;">Cabang</div>
                                        <div class="fw-bold text-dark">{{ $kpiDaily->karyawan->cabang->nama_cabang ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-auto ms-auto d-flex align-items-center gap-3">
                    @php
                        $statusColor =
                            [
                                'draft' => 'secondary',
                                'submitted' => 'blue',
                                'approved_by_atasan' => 'green',
                                'approved_by_hr' => 'teal',
                                'rejected' => 'red',
                            ][$kpiDaily->status] ?? 'gray';
                    @endphp

                    <span class="badge bg-{{ $statusColor }}-lt p-2 px-3 border border-{{ $statusColor }}">
                        Status: {{ ucwords(str_replace('_', ' ', $kpiDaily->status)) }}
                    </span>

                    <a href="{{ route('kpi.indikator.index', ['nik' => $kpiDaily->nik, 'bulan' => (int) $bulanBack, 'tahun' => $tahunBack]) }}"
                        class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-left"
                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M5 12l14 0"></path>
                            <path d="M5 12l6 6"></path>
                            <path d="M5 12l6 -6"></path>
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">
            {{-- ALERT --}}
            @if (session('success'))
                <div class="alert alert-important alert-success alert-dismissible shadow-sm mb-4">
                    <div class="d-flex">
                        <div><svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path d="M5 12l5 5l10 -10" />
                            </svg></div>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-important alert-danger alert-dismissible shadow-sm mb-4">
                    <div class="d-flex">
                        <div><svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path d="M12 9v2m0 4v.01" />
                                <path
                                    d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.84 2.75z" />
                            </svg></div>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row row-cards">
                <div class="col-12">
                    <div class="card shadow-sm border-0">

                        {{-- TABEL WORKBOOK DAILY KARYAWAN --}}
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="w-1">No</th>
                                        <th>Indikator KPI</th>
                                        <th>Nilai Indikator</th>
                                        <th class="text-center w-1">Diselesaikan</th>
                                        <th class="text-center w-1">Detail / Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($kpiMaster->kpiMasterDetail as $masterDetail)
                                        @php
                                            $savedDetail = $kpiDaily->kpiDailyDetail
                                                ->where('kpi_master_detail_id', $masterDetail->id)
                                                ->first();
                                        @endphp
                                        <tr class="{{ $savedDetail && $savedDetail->is_checked ? 'bg-green-lt' : '' }}">
                                            <td class="text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-medium text-dark">
                                                    {{ $masterDetail->indikator }}
                                                </div>
                                            </td>
                                            <td>{{ floatval($masterDetail->score_indikator) }} %</td>
                                            <td class="text-center">
                                                @if ($savedDetail)
                                                    <input type="checkbox"
                                                        class="form-check-input kpi-checkbox border-success"
                                                        {{ $savedDetail->is_checked ? 'checked' : '' }} disabled>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-icon btn-ghost-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-detail-master-{{ $masterDetail->id }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-info-circle" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                        <path d="M12 9h.01"></path>
                                                        <path d="M11 12h1v4h1"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>

                                        {{-- MODAL DETAIL KARYAWAN --}}
                                        <div class="modal modal-blur fade"
                                            id="modal-detail-master-{{ $masterDetail->id }}" tabindex="-1"
                                            role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <form
                                                        action="{{ route('kpi.indikator.detail.update', $kpiDaily->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden"
                                                            name="details[{{ $masterDetail->id }}][kpi_master_detail_id]"
                                                            value="{{ $masterDetail->id }}">
                                                        @if ($savedDetail)
                                                            <input type="hidden"
                                                                name="details[{{ $masterDetail->id }}][id]"
                                                                value="{{ $savedDetail->id }}">
                                                        @endif

                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Detail Penilaian Karyawan</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Indikator KPI</label>
                                                                <input type="text" class="form-control"
                                                                    value="{{ $masterDetail->indikator }}" readonly>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Bukti Foto</label>
                                                                    @if ($savedDetail && $savedDetail->bukti_foto)
                                                                        <div class="card card-sm border">
                                                                            <a href="{{ asset('storage/' . $savedDetail->bukti_foto) }}"
                                                                                target="_blank" class="d-block"
                                                                                title="Klik untuk memperbesar">
                                                                                <img src="{{ asset('storage/' . $savedDetail->bukti_foto) }}"
                                                                                    class="card-img-top object-cover"
                                                                                    style="height: 150px; width: 100%; object-fit: cover;"
                                                                                    alt="Bukti Foto">
                                                                            </a>
                                                                        </div>
                                                                    @else
                                                                        <div
                                                                            class="text-muted fst-italic border p-4 rounded bg-light text-center">
                                                                            Tidak ada bukti foto
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Catatan / Keterangan
                                                                        Karyawan</label>
                                                                    <textarea name="details[{{ $masterDetail->id }}][catatan]" class="form-control" rows="4"
                                                                        placeholder="Tambahkan catatan..." {{ in_array($kpiDaily->status, ['approved_by_hr']) ? 'readonly' : '' }}>{{ $savedDetail ? $savedDetail->catatan : '' }}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button"
                                                                class="btn btn-link link-secondary me-auto"
                                                                data-bs-dismiss="modal">Tutup</button>
                                                            @if ($canEdit)
                                                                <button type="submit" class="btn btn-primary">Simpan
                                                                    Perubahan</button>
                                                            @endif
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- RINCIAN PENILAIAN ATASAN --}}
                        <div class="card-header border-top border-bottom-0 mt-3" style="background-color: var(--color-surface-2);">
                            <h3 class="card-title d-flex align-items-center gap-2 mb-0"
                                style="font-size: 14px; font-weight: 600; color: var(--color-success);">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="icon icon-tabler icon-tabler-clipboard-check" width="20" height="20"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path
                                        d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2">
                                    </path>
                                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z">
                                    </path>
                                    <path d="M9 14l2 2l4 -4"></path>
                                </svg>
                                Hasil Penilaian Atasan Harian
                            </h3>
                        </div>

                        @if ($penilaianAtasan)
                            <div class="table-responsive">
                                <table class="table table-vcenter table-bordered card-table m-0">
                                    <thead class="bg-light text-muted" style="font-size: 12px;">
                                        <tr>
                                            <th class="w-1 text-center">No</th>
                                            <th class="text-center">Indikator Penilaian</th>
                                            <th class="text-center">Catatan Atasan</th>
                                            <th class="text-center">Bukti Foto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($indikatorsAtasan as $item)
                                            @php
                                                $detail = $penilaianAtasan->details
                                                    ->where('kpi_master_atasan_id', $item->id)
                                                    ->first();
                                                $score = $detail ? $detail->score : 0;
                                                $catatan = $detail ? $detail->catatan : '-';
                                            @endphp
                                            <tr style="font-size: 13px;">
                                                <td class="text-center text-muted">{{ $loop->iteration }}</td>
                                                <td class="fw-medium text-dark">{{ $item->indikator }}</td>
                                                <td class="text-muted">{{ $catatan }}</td>
                                                <td class="text-center">
                                                    @if ($detail && $detail->bukti_foto)
                                                        <a href="{{ asset('storage/' . $detail->bukti_foto) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-tabler icon-tabler-photo" width="16"
                                                                height="16" viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <line x1="15" y1="8" x2="15.01"
                                                                    y2="8" />
                                                                <rect x="4" y="4" width="16" height="16"
                                                                    rx="3" />
                                                                <path d="M4 15l4 -4a3 5 0 0 1 3 0l5 5" />
                                                                <path d="M14 14l1 -1a3 5 0 0 1 3 0l2 2" />
                                                            </svg>
                                                            Lihat
                                                        </a>
                                                    @else
                                                        <span class="text-muted fst-italic">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-3 text-muted">Format indikator
                                                    belum tersedia.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-soft-secondary m-3 border" style="border-radius: 10px;">
                                <div class="d-flex align-items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-info-circle text-muted me-3" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                        <path d="M12 9h.01" />
                                        <path d="M11 12h1v4h1" />
                                    </svg>
                                    <div>
                                        <span class="text-muted" style="font-size: 13px;">Atasan belum membuat atau
                                            mengirimkan penilaian kinerja harian untuk laporan ini.</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- APPROVAL BUTTONS SECTION (APPROVE KEDUANYA SEKALIGUS) --}}
                        <div class="card-footer d-flex align-items-center bg-light-lt mt-3">
                            <div class="ms-auto">
                                @if ($canEdit && $kpiDaily->status !== 'draft')
                                    @if ($kpiDaily->status !== 'rejected')
                                        <button type="button" class="btn btn-danger shadow-sm px-4 me-2"
                                            id="btn-reject-trigger"
                                            data-url="{{ route('kpi.indikator.reject', $kpiDaily->id) }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x"
                                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path d="M18 6l-12 12"></path>
                                                <path d="M6 6l12 12"></path>
                                            </svg>
                                            Tolak / Revisi
                                        </button>
                                    @endif

                                    <button type="button" class="btn btn-success shadow-sm px-4 ms-2"
                                        id="btn-approve-trigger">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"
                                            class="icon icon-tabler icons-tabler-outline icon-tabler-check">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 12l5 5l10 -10" />
                                        </svg>
                                        Setujui KPI (Final)
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Submit Hidden --}}
    <form id="form-approve-submit" action="{{ route('kpi.indikator.approve', $kpiDaily->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="approve_hr">
    </form>

    <form id="form-reject-submit" action="" method="POST" style="display: none;">
        @csrf
        @method('PUT')
        <input type="hidden" name="alasan_reject" id="input_alasan_reject">
    </form>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. LOGIC APPROVE
            const btnApprove = document.getElementById('btn-approve-trigger');
            if (btnApprove) {
                btnApprove.addEventListener('click', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Setujui KPI?',
                        text: "Tindakan ini akan menyetujui Laporan Karyawan dan Penilaian Atasan ini secara final.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--color-accent)',
                        cancelButtonColor: 'var(--color-muted)',
                        confirmButtonText: 'Ya, Setujui!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('form-approve-submit').submit();
                        }
                    });
                });
            }

            // 2. LOGIC REJECT (DENGAN INPUT ALASAN)
            const btnReject = document.getElementById('btn-reject-trigger');
            if (btnReject) {
                btnReject.addEventListener('click', function(e) {
                    e.preventDefault();
                    let url = this.getAttribute('data-url');

                    Swal.fire({
                        title: 'Tolak / Revisi KPI',
                        input: 'textarea',
                        inputLabel: 'Masukkan Alasan Penolakan',
                        inputPlaceholder: 'Contoh: Nilai tidak sesuai bukti...',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--color-danger)',
                        cancelButtonColor: 'var(--color-muted)',
                        confirmButtonText: 'Kirim Penolakan',
                        cancelButtonText: 'Batal',
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Anda wajib mengisi alasan penolakan!'
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('input_alasan_reject').value = result.value;
                            let formReject = document.getElementById('form-reject-submit');
                            formReject.action = url;
                            formReject.submit();
                        }
                    });
                });
            }
        });
    </script>
@endpush
