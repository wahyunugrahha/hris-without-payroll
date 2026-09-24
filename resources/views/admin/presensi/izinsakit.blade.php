@extends('layouts.admin.tabler')

@php
    $jenisPengajuan = ['i' => 'Izin', 's' => 'Sakit', 'c' => 'Cuti', 'r' => 'Roster', 't' => 'Izin terlambat', 'p' => 'Pulang cepat'];
    $statusApproval = [0 => ['Menunggu', 'warning'], 1 => ['Disetujui', 'success'], 2 => ['Ditolak', 'danger']];
    $filterAktif = collect([request('bulan'), request('kode_cabang'), request('status_approved'), request('status_pengajuan'), request('nama_lengkap')])
        ->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Izin, Sakit, Cuti & Roster</h2>
                    <p class="page-subtitle">Tinjau dan putuskan pengajuan ketidakhadiran karyawan.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar pengajuan">
                @php
                    $statusKini = request('status_approved');
                    $urlStatus = fn ($s) => url('/presensi/izinsakit') . '?' . http_build_query(array_merge(request()->except(['page', 'status_approved']), $s === null ? [] : ['status_approved' => $s]));
                @endphp
                <nav class="list-tabs" aria-label="Status pengajuan">
                    <a href="{{ $urlStatus(null) }}" class="list-tab {{ blank($statusKini) ? 'is-current' : '' }}" @if (blank($statusKini)) aria-current="page" @endif>Semua <span class="list-tab-count">{{ $jumlahStatus['semua'] ?? 0 }}</span></a>
                    @foreach ($statusApproval as $nilai => [$label, $nada])
                        @php $aktif = $statusKini === (string) $nilai; @endphp
                        <a href="{{ $urlStatus($nilai) }}" class="list-tab {{ $aktif ? 'is-current' : '' }}" @if ($aktif) aria-current="page" @endif>{{ $label }} <span class="list-tab-count list-tab-count--{{ $nada }}">{{ $jumlahStatus[(string) $nilai] ?? 0 }}</span></a>
                    @endforeach
                    <span class="list-tabs-note">Jumlah {{ $bulan_indo ?: 'periode terpilih' }}</span>
                </nav>

                <form action="/presensi/izinsakit" method="GET" class="list-toolbar" autocomplete="off">
                    @if (request()->filled('status_approved'))
                        <input type="hidden" name="status_approved" value="{{ request('status_approved') }}">
                    @endif
                    <div class="list-toolbar-row">
                        <x-admin.search name="nama_lengkap" placeholder="Cari nama atau NIK…" label="Cari karyawan" />
                        <button type="button" class="btn d-lg-none" data-bs-toggle="collapse" data-bs-target="#filterPanel"
                            aria-expanded="false" aria-controls="filterPanel">
                            Filter
                            @if ($filterAktif > 0)
                                <span class="filter-count">{{ $filterAktif }}</span>
                            @endif
                        </button>
                    </div>
                    <div class="collapse filter-panel" id="filterPanel">
                        <div class="filter-bar">
                            <label class="filter-field">
                                <span class="filter-label">Bulan</span>
                                <input type="month" name="bulan" value="{{ request('bulan') }}" class="form-control form-control-sm" data-auto-submit>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Jenis</span>
                                <select name="status_pengajuan" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($jenisPengajuan as $kode => $label)
                                        <option value="{{ $kode }}" @selected(request('status_pengajuan') === $kode)>{{ $label }}</option>
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
                            @if ($filterAktif > 0)
                                <a href="/presensi/izinsakit" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($izinsakit->total() > 0)
                        Menampilkan <strong>{{ $izinsakit->firstItem() }}–{{ $izinsakit->lastItem() }}</strong>
                        dari <strong>{{ $izinsakit->total() }}</strong> pengajuan
                        @if ($bulan_indo)
                            · {{ $bulan_indo }}
                        @endif
                    @endif
                </div>

                @if ($izinsakit->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada pengajuan yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                Ubah kata kunci atau filter — atau <a href="/presensi/izinsakit">reset filter</a>.
                            @else
                                Pengajuan dari aplikasi karyawan akan tampil di sini.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th>Jenis</th>
                                    <th>Periode</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($izinsakit as $d)
                                    @php
                                        $dari = \Carbon\Carbon::parse($d->tgl_izin_dari);
                                        $sampai = \Carbon\Carbon::parse($d->tgl_izin_sampai ?? $d->tgl_izin_dari);
                                        $jumlahHari = $d->total_hari_view ?? abs($sampai->diffInDays($dari)) + 1;
                                        $keteranganDisplay = trim((string) ($d->keterangan_view ?? $d->keterangan));

                                        $requestedDatesView = is_array($d->requested_dates_view ?? null) ? $d->requested_dates_view : [];
                                        $requestedCount = count($requestedDatesView);

                                        // Periode: cuti/roster bisa berupa tanggal tidak berurutan.
                                        $periodeDisplay = '';
                                        $periodeHint = '';
                                        if (in_array($d->status, ['c', 'r'], true) && $requestedCount > 0) {
                                            $first = \DateTime::createFromFormat('d-m-Y', $requestedDatesView[0]);
                                            $last = \DateTime::createFromFormat('d-m-Y', $requestedDatesView[$requestedCount - 1]);
                                            if ($first && $last) {
                                                $berurutan = true;
                                                for ($i = 1; $i < $requestedCount; $i++) {
                                                    $prev = \DateTime::createFromFormat('d-m-Y', $requestedDatesView[$i - 1]);
                                                    $curr = \DateTime::createFromFormat('d-m-Y', $requestedDatesView[$i]);
                                                    if (!$prev || !$curr || $curr->format('d-m-Y') !== (clone $prev)->modify('+1 day')->format('d-m-Y')) {
                                                        $berurutan = false;
                                                        break;
                                                    }
                                                }
                                                if ($berurutan) {
                                                    $periodeDisplay = $requestedCount === 1 ? $first->format('d M Y') : $first->format('d M') . ' – ' . $last->format('d M Y');
                                                } else {
                                                    $periodeDisplay = !empty($d->requested_dates_compact) ? $d->requested_dates_compact : implode(', ', $requestedDatesView);
                                                    $periodeHint = 'tidak berurutan';
                                                }
                                            }
                                        }
                                        if ($periodeDisplay === '') {
                                            $periodeDisplay = $d->tgl_izin_sampai && $d->tgl_izin_sampai != $d->tgl_izin_dari
                                                ? date('d M', strtotime($d->tgl_izin_dari)) . ' – ' . date('d M Y', strtotime($d->tgl_izin_sampai))
                                                : date('d M Y', strtotime($d->tgl_izin_dari));
                                        }

                                        $labelJenis = $d->status === 'c' && !empty($d->kode_cuti) ? $d->jenis_cuti_formal : ($jenisPengajuan[$d->status] ?? $d->status);
                                        [$labelStatus, $nadaStatus] = $statusApproval[(int) $d->status_approved] ?? $statusApproval[0];
                                        $pending = (int) $d->status_approved === 0;
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name" title="{{ $d->nama_lengkap }}">{{ $d->nama_lengkap }}</span>
                                            <span class="person-sub">{{ $d->nik }} · {{ $d->karyawan->cabang->nama_cabang ?? '-' }}</span>
                                        </td>
                                        <td data-label="Jenis">
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::ketidakhadiran($d->status) }}">{{ $labelJenis }}</span>
                                        </td>
                                        <td data-label="Periode">
                                            <div class="cell-main cell-num">{{ $periodeDisplay }}</div>
                                            <div class="cell-sub">{{ $jumlahHari }} hari{{ $periodeHint ? ' · ' . $periodeHint : '' }}</div>
                                        </td>
                                        <td data-label="Keterangan">
                                            <div class="cell-clamp" title="{{ $keteranganDisplay }}">{{ $keteranganDisplay !== '' ? $keteranganDisplay : '-' }}</div>
                                        </td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $nadaStatus }}">{{ $labelStatus }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                @if ($pending)
                                                    <button type="button" class="btn btn-sm btn-approve" data-idizinsakit="{{ $d->id }}">Proses</button>
                                                @endif
                                                <x-admin.row-menu :label="$d->nama_lengkap">
                                                    <button type="button" class="dropdown-item btn-detail" data-idizinsakit="{{ $d->id }}">Lihat detail</button>
                                                    @if ($pending)
                                                        <button type="button" class="dropdown-item btn-approve" data-idizinsakit="{{ $d->id }}">Proses pengajuan</button>
                                                    @else
                                                        <div class="dropdown-divider"></div>
                                                        <button type="button" class="dropdown-item text-danger btn-cancel" data-idizinsakit="{{ $d->id }}">Batalkan keputusan</button>
                                                    @endif
                                                </x-admin.row-menu>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($izinsakit->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $izinsakit->currentPage() }} dari {{ $izinsakit->lastPage() }}</span>
                        {{ $izinsakit->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    {{-- Detail --}}
    <div class="modal modal-blur fade" id="modal-detail" tabindex="-1" aria-labelledby="judulDetailIzin" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulDetailIzin">Detail pengajuan</h5>
                        <p class="modal-subtitle"><span id="detail-nama">-</span> · NIK <span id="detail-nik">-</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <dl class="info-grid">
                        <div><dt>Jenis</dt><dd id="detail-jenis">-</dd></div>
                        <div><dt>Tanggal</dt><dd class="cell-num"><span id="detail-dari"></span> s/d <span id="detail-sampai"></span></dd></div>
                        <div class="info-full"><dt>Keterangan</dt><dd id="detail-keterangan" class="info-note">-</dd></div>
                        <div class="info-full" id="detail-catatan-ditolak-wrapper" style="display:none;">
                            <dt>Catatan penolakan</dt><dd id="detail-catatan-ditolak" class="text-danger">-</dd>
                        </div>
                    </dl>

                    <section class="form-section mt-3" id="detail-datepicker-section" style="display: none;">
                        <h6 class="form-section-title">Detail persetujuan tanggal</h6>
                        <div class="d-flex justify-content-center">
                            <div id="detail-calendar-view"></div>
                        </div>
                        <div class="date-legend">
                            <span class="emp-status emp-status--success">Disetujui</span>
                            <span class="emp-status emp-status--danger">Ditolak</span>
                            <span class="emp-status emp-status--info">Menunggu</span>
                        </div>
                    </section>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Proses pengajuan --}}
    <div class="modal modal-blur fade" id="modal-izinsakit" tabindex="-1" aria-labelledby="judulProsesIzin" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="judulProsesIzin">Proses pengajuan</h5>
                        <p class="modal-subtitle">Keputusan langsung terlihat di aplikasi karyawan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form action="/presensi/approveizinsakit" method="POST" id="approvalForm" class="modal-form-body">
                    @csrf
                    <input type="hidden" id="id_izinsakit_from" name="id_izinsakit_from">
                    <div class="modal-body">
                        <div class="modal-grid">
                            <div id="cutiDatepickerSection" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="form-section-title m-0">Pilih tanggal</span>
                                    <span class="check-list-count" id="jml_hari_info">0 hari</span>
                                </div>
                                <div id="inline_datepicker"></div>
                                <input type="hidden" id="selectedCutiDates" name="selected_cuti_dates" value="">
                                <div class="form-hint text-center">Klik tanggal untuk tidak menyetujui hari tersebut.</div>
                            </div>

                            <div>
                                <span class="form-section-title d-block">Keputusan</span>
                                <div class="decision-options">
                                    <label class="decision-option">
                                        <input type="radio" name="status_approved" value="1" checked>
                                        <span class="emp-status emp-status--success">Setujui</span>
                                    </label>
                                    <label class="decision-option">
                                        <input type="radio" name="status_approved" value="2">
                                        <span class="emp-status emp-status--danger">Tolak</span>
                                    </label>
                                </div>

                                <div class="mt-3" id="teks_alasan_ditolak" style="display: none;">
                                    <label class="form-label required" for="catatan_ditolak">Alasan penolakan</label>
                                    <textarea name="catatan_ditolak" id="catatan_ditolak" class="form-control" rows="3"
                                        placeholder="Jelaskan alasan penolakan…"></textarea>
                                    <div class="form-hint">Terlihat oleh karyawan di detail pengajuan.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary" type="submit">Simpan keputusan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="frmBatalkan" method="POST" action="" hidden>
        @csrf
    </form>
@endsection

@push('myscript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.id.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <script>
        $(function() {
            // Tanggal lokal jam 12:00 agar aman dari isu timezone/DST.
            function parseYmdToLocalDate(ymd) {
                const parts = typeof ymd === 'string' ? ymd.split('-') : [];
                return parts.length === 3 ? new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12) : null;
            }

            function formatDateToYmd(d) {
                return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            }

            function getDateStringsInRange(startDate, endDate) {
                const dates = [];
                const curr = parseYmdToLocalDate(startDate);
                const last = parseYmdToLocalDate(endDate);
                if (!curr || !last) return dates;
                while (curr <= last) {
                    dates.push(formatDateToYmd(curr));
                    curr.setDate(curr.getDate() + 1);
                }
                return dates;
            }

            function tanggalDiminta(data) {
                return Array.isArray(data.requested_dates) && data.requested_dates.length ?
                    data.requested_dates : getDateStringsInRange(data.tgl_dari_std, data.tgl_sampai_std);
            }

            // 1. Detail (kalender hanya-baca untuk cuti/roster).
            $('body').on('click', '.btn-detail', function() {
                const id = $(this).data('idizinsakit');
                $('#detail-calendar-view').datepicker('destroy').html('');
                $('#detail-datepicker-section').hide();
                $('#detail-catatan-ditolak-wrapper').hide();

                $.getJSON('/presensi/detailijinsakit/' + id, function(data) {
                    $('#detail-nama').text(data.nama_lengkap);
                    $('#detail-nik').text(data.nik);
                    $('#detail-jenis').html(data.jenis_badge || '-');
                    $('#detail-dari').text(data.tgl_dari);
                    $('#detail-sampai').text(data.tgl_sampai);
                    $('#detail-keterangan').text(data.keterangan || '-');

                    const statusApproved = Number(data.status_approved || 0);
                    const catatan = (data.catatan_ditolak || '').trim();
                    if (statusApproved === 2 && catatan !== '') {
                        $('#detail-catatan-ditolak').text(catatan);
                        $('#detail-catatan-ditolak-wrapper').show();
                    }

                    if (data.is_cuti) {
                        $('#detail-datepicker-section').show();
                        const dates = tanggalDiminta(data);
                        const diizinkan = Object.fromEntries(dates.map((t) => [t, true]));
                        const disetujui = Array.isArray(data.approved_dates) ? data.approved_dates : [];

                        $('#detail-calendar-view').datepicker({
                            format: 'yyyy-mm-dd',
                            todayHighlight: false,
                            language: 'id',
                            beforeShowDay: function(date) {
                                const t = formatDateToYmd(date);
                                if (!diizinkan[t]) return { enabled: false, classes: 'disabled' };
                                if (statusApproved === 0) return { classes: 'day-pending', tooltip: 'Menunggu persetujuan' };
                                if (statusApproved === 1 && disetujui.includes(t)) return { classes: 'day-approved', tooltip: 'Disetujui' };
                                return { classes: 'day-rejected', tooltip: 'Ditolak / tidak diambil' };
                            }
                        }).datepicker('setDate', parseYmdToLocalDate(dates[0] || data.tgl_dari_std));
                    }

                    $('#modal-detail').modal('show');
                });
            });

            // 2. Proses pengajuan (pilih tanggal yang disetujui untuk cuti/roster).
            function updateHiddenInput() {
                $('#selectedCutiDates').val($('#inline_datepicker').datepicker('getFormattedDate'));
                const n = $('#inline_datepicker').datepicker('getDates').length;
                $('#jml_hari_info').text(n > 0 ? n + ' hari dipilih' : '0 hari · semua ditolak');
            }

            $('body').on('click', '.btn-approve', function() {
                const id = $(this).data('idizinsakit');
                $('#id_izinsakit_from').val(id);
                $('#approvalForm')[0].reset();
                $('#teks_alasan_ditolak').hide();

                $.getJSON('/presensi/detailijinsakit/' + id, function(data) {
                    $('#inline_datepicker').datepicker('destroy');
                    if (!data.is_cuti) {
                        $('#cutiDatepickerSection').hide();
                        $('#selectedCutiDates').val('');
                        return;
                    }

                    $('#cutiDatepickerSection').show();
                    const dates = tanggalDiminta(data);
                    const diizinkan = Object.fromEntries(dates.map((t) => [t, true]));
                    $('#inline_datepicker').datepicker({
                        format: 'yyyy-mm-dd',
                        multidate: true,
                        todayHighlight: true,
                        startDate: dates[0] || data.tgl_dari_std,
                        endDate: dates[dates.length - 1] || data.tgl_sampai_std,
                        beforeShowDay: (date) => diizinkan[formatDateToYmd(date)] ? { enabled: true } : { enabled: false, classes: 'disabled' },
                        language: 'id'
                    }).datepicker('setDates', dates).off('changeDate').on('changeDate', updateHiddenInput);
                    updateHiddenInput();
                });
                $('#modal-izinsakit').modal('show');
            });

            $('#approvalForm').on('submit', function() {
                if ($('input[name="status_approved"]:checked').val() == 2) {
                    $('#selectedCutiDates').val('');
                }
            });

            $('input[name="status_approved"]').on('change', function() {
                const tolak = $(this).val() == 2;
                $('#teks_alasan_ditolak').toggle(tolak);
                $('#catatan_ditolak').prop('required', tolak);
                if (!tolak) $('#catatan_ditolak').val('');
            });

            // 3. Batalkan keputusan → kembali menunggu.
            $('body').on('click', '.btn-cancel', function() {
                const id = $(this).data('idizinsakit');
                Swal.fire({
                    title: 'Batalkan keputusan?',
                    text: 'Status pengajuan dikembalikan ke Menunggu.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, batalkan',
                    cancelButtonText: 'Tutup',
                    reverseButtons: true
                }).then((r) => {
                    if (r.isConfirmed) $('#frmBatalkan').attr('action', '/presensi/' + id + '/batalkanizinsakit').trigger('submit');
                });
            });
        });
    </script>
@endpush
