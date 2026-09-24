@extends('layouts.admin.tabler')

@php
    $statusKpi = [
        'draft' => ['Draft', 'neutral'],
        'submitted' => ['Menunggu atasan', 'warning'],
        'approved_by_atasan' => ['Disetujui atasan', 'info'],
        'approved_by_hr' => ['Disetujui HR', 'success'],
        'rejected' => ['Revisi / ditolak', 'danger'],
    ];
    $totalLaporan = $riwayatKPI->count();
    $totalApprovedHR = $riwayatKPI->where('status', 'approved_by_hr')->count();
    $showApproveButton = $totalLaporan > 0 && $totalApprovedHR < $totalLaporan;
    $semuaExtra = $riwayatKPI->flatMap(fn ($kpi) => $kpi->kpiDailyExtra->each(fn ($ex) => $ex->tanggal_laporan = $kpi->tanggal));
    $periode = \Carbon\Carbon::parse($tglAwal)->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($tglAkhir)->translatedFormat('d M Y');
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">KPI Karyawan / Rekap KPI</div>
                    <h2 class="page-title">{{ $karyawan->nama_lengkap }}</h2>
                    <p class="page-subtitle">
                        NIK {{ $karyawan->nik }} · {{ $karyawan->jabatanRel->nama_jabatan ?? '-' }} · {{ $karyawan->departemen->nama_dept ?? '-' }} · {{ $periode }}
                    </p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        <a href="{{ route('kpi.rekap.karyawan', ['bulan' => $reqBulan, 'tahun' => $reqTahun]) }}" class="btn">Kembali</a>
                        @if ($showApproveButton)
                            <button type="button" class="btn btn-primary" id="btn-approve-bulk">Setujui semua</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl d-grid gap-3">
            <form id="form-bulk-approve" action="{{ route('kpi.rekap.karyawan.bulk_approve') }}" method="POST" hidden>
                @csrf
                <input type="hidden" name="niks[]" value="{{ $karyawan->nik }}">
                <input type="hidden" name="bulan" value="{{ $reqBulan }}">
                <input type="hidden" name="tahun" value="{{ $reqTahun }}">
            </form>

            <section class="card list-card" aria-labelledby="judul-histori">
                <div class="summary-strip" role="group" aria-label="Ringkasan workbook">
                    <div class="summary-item">
                        <span class="summary-label">Laporan harian</span>
                        <span class="summary-value">{{ $totalLaporan }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Disetujui HR</span>
                        <span class="summary-value">{{ $totalApprovedHR }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Belum final</span>
                        <span class="summary-value">{{ $totalLaporan - $totalApprovedHR }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Kegiatan tambahan</span>
                        <span class="summary-value">{{ $semuaExtra->count() }}</span>
                    </div>
                </div>
                <h3 class="visually-hidden" id="judul-histori">Histori workbook harian</h3>

                @if ($riwayatKPI->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Belum ada laporan KPI harian di periode ini.</p>
                        <p class="mb-0 text-secondary small">Laporan dibuat karyawan dari aplikasi.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Capaian workbook</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayatKPI as $item)
                                    @php
                                        [$labelStatus, $nadaStatus] = $statusKpi[$item->status] ?? [ucfirst($item->status), 'neutral'];
                                        $skor = floatval($item->kpiDailyDetail->sum('score') + $item->kpiDailyExtra->sum('score'));
                                        $persen = min(100, ($skor / 40) * 100);
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}</span>
                                            <span class="person-sub">{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('l') }}</span>
                                        </td>
                                        <td data-label="Capaian">
                                            <div class="meter">
                                                <div class="meter-track"><div class="meter-fill" style="width: {{ $persen }}%"></div></div>
                                                <span class="cell-num">{{ round($persen) }}%</span>
                                            </div>
                                        </td>
                                        <td data-label="Status"><span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span></td>
                                        <td class="cell-actions">
                                            <a href="{{ route('kpi.indikator.detail.index', $item->id) }}" class="btn btn-sm">Buka penilaian</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            @if ($semuaExtra->isNotEmpty())
                <section class="card list-card" aria-labelledby="judul-extra">
                    <div class="card-header">
                        <h3 class="card-title" id="judul-extra">Kegiatan tambahan</h3>
                        <span class="card-subtitle ms-auto">Poin di luar indikator utama</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>Catatan</th>
                                    <th class="text-end">Poin</th>
                                    @if ($canEdit)
                                        <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($semuaExtra as $extra)
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name" title="{{ $extra->indikator_tambahan }}">{{ $extra->indikator_tambahan }}</span>
                                            <span class="person-sub">{{ \Carbon\Carbon::parse($extra->tanggal_laporan)->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td data-label="Catatan"><div class="cell-clamp">{{ $extra->catatan ?: '-' }}</div></td>
                                        <td data-label="Poin" class="cell-num text-lg-end fw-medium">+{{ $extra->score ?? 10 }}</td>
                                        @if ($canEdit)
                                            <td class="cell-actions">
                                                <x-admin.row-menu :label="$extra->indikator_tambahan">
                                                    <button type="button" class="dropdown-item btn-edit-extra" data-id="{{ $extra->id }}"
                                                        data-nama="{{ $extra->indikator_tambahan }}" data-catatan="{{ $extra->catatan }}"
                                                        data-score="{{ $extra->score ?? 10 }}">Edit</button>
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('kpi.indikator.extra.destroy', $extra->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"
                                                            data-confirm="Kegiatan tambahan ini akan dihapus permanen."
                                                            data-confirm-title="Hapus kegiatan?">Hapus</button>
                                                    </form>
                                                </x-admin.row-menu>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if ($canEdit)
        <div class="modal modal-blur fade" id="modal-edit-extra" tabindex="-1" aria-labelledby="judulEditExtra" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-form modal-form-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="judulEditExtra">Edit kegiatan tambahan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="" method="POST" id="form-edit-extra" class="modal-form-body">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label required" for="extra-nama">Nama kegiatan</label>
                                <input type="text" class="form-control" name="indikator_tambahan" id="extra-nama" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="extra-catatan">Catatan</label>
                                <textarea class="form-control" name="catatan" id="extra-catatan" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="form-label required" for="extra-score">Poin</label>
                                <input type="number" class="form-control" name="score" id="extra-score" min="0" required>
                                <div class="form-hint">Sesuaikan poin kegiatan ini bila perlu.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('myscript')
    <script>
        $(function() {
            $('#btn-approve-bulk').on('click', function() {
                Swal.fire({
                    title: 'Setujui semua KPI?',
                    text: 'Semua laporan harian beserta penilaian atasan karyawan ini disetujui secara final.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, setujui semua',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((r) => r.isConfirmed && $('#form-bulk-approve').trigger('submit'));
            });

            const urlExtra = "{{ route('kpi.indikator.extra.update', '__ID__') }}";
            $('.btn-edit-extra').on('click', function() {
                const d = $(this).data();
                $('#form-edit-extra').attr('action', urlExtra.replace('__ID__', d.id));
                $('#extra-nama').val(d.nama);
                $('#extra-catatan').val(d.catatan);
                $('#extra-score').val(d.score);
                $('#modal-edit-extra').modal('show');
            });
        });
    </script>
@endpush
