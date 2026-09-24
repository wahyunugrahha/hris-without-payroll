@extends('layouts.admin.tabler')

@php
    $statusKpi = [
        'draft' => ['Draft', 'neutral'],
        'submitted' => ['Menunggu atasan', 'warning'],
        'approved_by_atasan' => ['Disetujui atasan', 'info'],
        'approved_by_hr' => ['Disetujui HR', 'success'],
        'rejected' => ['Revisi / ditolak', 'danger'],
    ];
    [$labelStatus, $nadaStatus] = $statusKpi[$kpiDaily->status] ?? [ucfirst($kpiDaily->status), 'neutral'];
    $karyawan = $kpiDaily->karyawan;
    $indikatorMaster = $kpiMaster?->kpiMasterDetail ?? collect();
    $terkunci = $kpiDaily->status === 'approved_by_hr';
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">KPI Karyawan / Workbook harian</div>
                    <h2 class="page-title">{{ $karyawan->nama_lengkap ?? 'Detail KPI' }}</h2>
                    <p class="page-subtitle">
                        {{ \Carbon\Carbon::parse($kpiDaily->tanggal)->translatedFormat('l, d F Y') }} · NIK {{ $kpiDaily->nik }}
                        · {{ $karyawan->jabatanRel->nama_jabatan ?? '-' }} · {{ $karyawan->cabang->nama_cabang ?? '-' }}
                    </p>
                </div>
                <div class="col-auto ms-auto d-flex align-items-center gap-3">
                    <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                    <a href="{{ route('kpi.indikator.index', ['nik' => $kpiDaily->nik, 'bulan' => (int) $bulanBack, 'tahun' => $tahunBack]) }}" class="btn">Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl d-grid gap-3">
            <section class="card list-card" aria-labelledby="judul-workbook">
                <div class="card-header">
                    <h3 class="card-title" id="judul-workbook">Workbook harian</h3>
                    <span class="card-subtitle ms-auto">
                        {{ $kpiDaily->kpiDailyDetail->where('is_checked', true)->count() }} dari {{ $indikatorMaster->count() }} diselesaikan
                    </span>
                </div>
                @if ($indikatorMaster->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Master KPI untuk karyawan ini tidak ditemukan.</p>
                        <p class="mb-0 text-secondary small">Periksa jabatan, departemen, dan cabang karyawan di Master KPI.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th>Indikator</th>
                                    <th class="text-end">Nilai</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($indikatorMaster as $masterDetail)
                                    @php $savedDetail = $kpiDaily->kpiDailyDetail->firstWhere('kpi_master_detail_id', $masterDetail->id); @endphp
                                    <tr>
                                        <td class="text-secondary cell-num">{{ $loop->iteration }}</td>
                                        <td class="cell-person">
                                            <span class="cell-main">{{ $masterDetail->indikator }}</span>
                                            @if ($savedDetail?->catatan)
                                                <span class="cell-sub cell-clamp">{{ $savedDetail->catatan }}</span>
                                            @endif
                                        </td>
                                        <td data-label="Nilai" class="cell-num text-lg-end">{{ floatval($masterDetail->score_indikator) }}%</td>
                                        <td data-label="Status">
                                            @if (!$savedDetail)
                                                <span class="emp-status">Tidak diisi</span>
                                            @elseif ($savedDetail->is_checked)
                                                <span class="emp-status emp-status--success">Selesai</span>
                                            @else
                                                <span class="emp-status emp-status--warning">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="cell-actions">
                                            <button type="button" class="btn btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#modal-detail-master-{{ $masterDetail->id }}">Bukti & catatan</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="card list-card" aria-labelledby="judul-atasan">
                <div class="card-header">
                    <h3 class="card-title" id="judul-atasan">Penilaian atasan</h3>
                </div>
                @if (!$penilaianAtasan)
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Belum ada penilaian atasan.</p>
                        <p class="mb-0 text-secondary small">Atasan belum mengirim penilaian untuk laporan ini.</p>
                    </div>
                @elseif ($indikatorsAtasan->isEmpty())
                    <div class="list-empty">
                        <p class="mb-0 text-secondary small">Indikator penilaian atasan belum diatur di Master KPI.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th class="w-1">#</th>
                                    <th>Indikator</th>
                                    <th>Catatan atasan</th>
                                    <th class="w-1"><span class="visually-hidden">Bukti</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($indikatorsAtasan as $item)
                                    @php $detail = $penilaianAtasan->details->firstWhere('kpi_master_atasan_id', $item->id); @endphp
                                    <tr>
                                        <td class="text-secondary cell-num">{{ $loop->iteration }}</td>
                                        <td class="cell-person"><span class="cell-main">{{ $item->indikator }}</span></td>
                                        <td data-label="Catatan"><div class="cell-clamp">{{ $detail?->catatan ?: '-' }}</div></td>
                                        <td class="cell-actions">
                                            @if ($detail?->bukti_foto)
                                                <a href="{{ asset('storage/' . $detail->bukti_foto) }}" target="_blank" rel="noopener" class="btn btn-sm">Lihat bukti</a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if ($canEdit && $kpiDaily->status !== 'draft')
                <div class="save-bar">
                    <span class="text-secondary small">Keputusan berlaku untuk laporan karyawan dan penilaian atasan sekaligus.</span>
                    <div class="d-flex gap-2">
                        @if ($kpiDaily->status !== 'rejected')
                            <button type="button" class="btn btn-outline-danger" id="btn-reject-trigger"
                                data-url="{{ route('kpi.indikator.reject', $kpiDaily->id) }}">Tolak / revisi</button>
                        @endif
                        <button type="button" class="btn btn-primary" id="btn-approve-trigger">Setujui (final)</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @foreach ($indikatorMaster as $masterDetail)
        @php $savedDetail = $kpiDaily->kpiDailyDetail->firstWhere('kpi_master_detail_id', $masterDetail->id); @endphp
        <div class="modal modal-blur fade" id="modal-detail-master-{{ $masterDetail->id }}" tabindex="-1"
            aria-labelledby="judul-indikator-{{ $masterDetail->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judul-indikator-{{ $masterDetail->id }}">Bukti & catatan</h5>
                            <p class="modal-subtitle">{{ $masterDetail->indikator }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('kpi.indikator.detail.update', $kpiDaily->id) }}" method="POST" class="modal-form-body">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="details[{{ $masterDetail->id }}][kpi_master_detail_id]" value="{{ $masterDetail->id }}">
                        @if ($savedDetail)
                            <input type="hidden" name="details[{{ $masterDetail->id }}][id]" value="{{ $savedDetail->id }}">
                        @endif
                        <div class="modal-body">
                            <div class="mb-3">
                                <span class="form-label d-block">Bukti foto</span>
                                @if ($savedDetail?->bukti_foto)
                                    <a href="{{ asset('storage/' . $savedDetail->bukti_foto) }}" target="_blank" rel="noopener" class="image-preview">
                                        <img src="{{ asset('storage/' . $savedDetail->bukti_foto) }}" alt="Bukti foto {{ $masterDetail->indikator }}">
                                    </a>
                                @else
                                    <div class="detail-media-empty">Tidak ada bukti foto</div>
                                @endif
                            </div>
                            <div>
                                <label class="form-label" for="catatan-{{ $masterDetail->id }}">Catatan</label>
                                <textarea name="details[{{ $masterDetail->id }}][catatan]" id="catatan-{{ $masterDetail->id }}" class="form-control" rows="4"
                                    placeholder="Tambahkan catatan…" @readonly($terkunci)>{{ $savedDetail?->catatan }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                            @if ($canEdit)
                                <button type="submit" class="btn btn-primary">Simpan catatan</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <form id="form-approve-submit" action="{{ route('kpi.indikator.approve', $kpiDaily->id) }}" method="POST" hidden>
        @csrf
        @method('PUT')
        <input type="hidden" name="action" value="approve_hr">
    </form>

    <form id="form-reject-submit" action="" method="POST" hidden>
        @csrf
        @method('PUT')
        <input type="hidden" name="alasan_reject" id="input_alasan_reject">
    </form>
@endsection

@push('myscript')
    <script>
        $(function() {
            $('#btn-approve-trigger').on('click', function() {
                Swal.fire({
                    title: 'Setujui KPI?',
                    text: 'Laporan karyawan dan penilaian atasan ini disetujui secara final.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, setujui',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => r.isConfirmed && $('#form-approve-submit').trigger('submit'));
            });

            $('#btn-reject-trigger').on('click', function() {
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Tolak / revisi KPI',
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Contoh: nilai tidak sesuai bukti…',
                    inputValidator: (v) => !v.trim() && 'Alasan penolakan wajib diisi.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Kirim penolakan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => {
                    if (!r.isConfirmed) return;
                    $('#input_alasan_reject').val(r.value.trim());
                    $('#form-reject-submit').attr('action', url).trigger('submit');
                });
            });
        });
    </script>
@endpush
