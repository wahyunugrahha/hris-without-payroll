@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Monitoring Karyawan</div>
                    <h2 class="page-title">Data Pengajuan Izin / Sakit / Cuti / Roster</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0">
                {{-- Filter Section --}}
                <div class="card-header bg-transparent border-bottom-1 py-3">
                    <form action="/presensi/izinsakit" method="GET" autocomplete="off" class="w-100">
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-xl-4">
                                <div class="input-group">
                                    <span class="input-group-text date-filter-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-calendar-event" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                            <path d="M16 3l0 4" />
                                            <path d="M8 3l0 4" />
                                            <path d="M4 11l16 0" />
                                            <path d="M15 15h.01" />
                                        </svg>
                                    </span>
                                    <input type="hidden" name="bulan" id="bulan" value="{{ request('bulan') }}">
                                    <input type="text" id="bulan_visual" class="form-control month-visual-input"
                                        value="{{ $bulan_indo }}" placeholder="Pilih Bulan..." readonly>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <select name="kode_cabang" class="form-select">
                                    <option value="">Semua Cabang</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}"
                                            {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                            {{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <select name="status_approved" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="0" {{ request('status_approved') === '0' ? 'selected' : '' }}>
                                        Pending</option>
                                    <option value="1" {{ request('status_approved') == '1' ? 'selected' : '' }}>
                                        Disetujui</option>
                                    <option value="2" {{ request('status_approved') == '2' ? 'selected' : '' }}>
                                        Ditolak</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-12 col-md-6 col-xl-4">
                                <select name="status_pengajuan" class="form-select">
                                    <option value="">Semua Jenis Pengajuan</option>
                                    <option value="i" {{ request('status_pengajuan') === 'i' ? 'selected' : '' }}>Izin
                                    </option>
                                    <option value="s" {{ request('status_pengajuan') === 's' ? 'selected' : '' }}>Sakit
                                    </option>
                                    <option value="c" {{ request('status_pengajuan') === 'c' ? 'selected' : '' }}>Cuti
                                    </option>
                                    <option value="r" {{ request('status_pengajuan') === 'r' ? 'selected' : '' }}>Roster
                                    </option>
                                    <option value="t" {{ request('status_pengajuan') === 't' ? 'selected' : '' }}>
                                        Terlambat</option>
                                    <option value="p" {{ request('status_pengajuan') === 'p' ? 'selected' : '' }}>
                                        Izin Pulang Cepat</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-12 col-xl-8">
                                <div class="input-group">
                                    {{-- Input Field --}}
                                    <input type="text" class="form-control" name="nama_lengkap"
                                        placeholder="Cari Nama / NIK" value="{{ request('nama_lengkap') }}">

                                    {{-- Tombol Cari --}}
                                    <button class="btn btn-primary" type="submit">
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

                {{-- Alert --}}
                @if (session('success') || session('error'))
                    <div class="p-3 pb-0">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center mb-2" role="alert">
                                <div>{{ session('success') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-2" role="alert">
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
                                <th>Periode & Jenis</th>
                                <th>Keterangan</th>
                                <th class="text-center">Status</th>
                                <th class="w-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($izinsakit as $d)
                                @php
                                    $dari = \Carbon\Carbon::parse($d->tgl_izin_dari);
                                    $sampai = \Carbon\Carbon::parse($d->tgl_izin_sampai ?? $d->tgl_izin_dari);
                                    $jumlahHari = $d->total_hari_view ?? abs($sampai->diffInDays($dari)) + 1;
                                    $keteranganDisplay = $d->keterangan_view ?? $d->keterangan;
                                    $keteranganDisplay = trim((string) $keteranganDisplay);

                                    $requestedDatesView = is_array($d->requested_dates_view ?? null)
                                        ? $d->requested_dates_view
                                        : [];
                                    $requestedCount = count($requestedDatesView);

                                    $periodeDisplay = '';
                                    $periodeHint = '';
                                    if (in_array($d->status, ['c', 'r'], true) && $requestedCount > 0) {
                                        $firstRequested = \Carbon\Carbon::createFromFormat(
                                            'd-m-Y',
                                            $requestedDatesView[0],
                                        );
                                        $lastRequested = \Carbon\Carbon::createFromFormat(
                                            'd-m-Y',
                                            $requestedDatesView[$requestedCount - 1],
                                        );

                                        if ($firstRequested && $lastRequested) {
                                            $isContiguous = true;
                                            for ($i = 1; $i < $requestedCount; $i++) {
                                                $prev = \DateTime::createFromFormat(
                                                    'd-m-Y',
                                                    $requestedDatesView[$i - 1],
                                                );
                                                $curr = \DateTime::createFromFormat('d-m-Y', $requestedDatesView[$i]);

                                                if (!$prev || !$curr) {
                                                    $isContiguous = false;
                                                    break;
                                                }

                                                $nextExpected = (clone $prev)->modify('+1 day')->format('d-m-Y');
                                                if ($curr->format('d-m-Y') !== $nextExpected) {
                                                    $isContiguous = false;
                                                    break;
                                                }
                                            }

                                            if ($isContiguous) {
                                                if ($requestedCount === 1) {
                                                    $periodeDisplay = $firstRequested->format('d M Y');
                                                } else {
                                                    $periodeDisplay =
                                                        $firstRequested->format('d M') .
                                                        ' - ' .
                                                        $lastRequested->format('d M Y');
                                                }
                                                $periodeHint = $requestedCount . ' hari berurutan';
                                            } else {
                                                $periodeDisplay = !empty($d->requested_dates_compact)
                                                    ? $d->requested_dates_compact
                                                    : implode(', ', $requestedDatesView);
                                                $periodeHint =
                                                    'Non-berurutan (' . $requestedCount . ' tanggal dipilih)';
                                            }
                                        }
                                    }

                                    if ($periodeDisplay === '') {
                                        if ($d->tgl_izin_sampai && $d->tgl_izin_sampai != $d->tgl_izin_dari) {
                                            $periodeDisplay =
                                                date('d M', strtotime($d->tgl_izin_dari)) .
                                                ' - ' .
                                                date('d M Y', strtotime($d->tgl_izin_sampai));
                                        } else {
                                            $periodeDisplay = date('d M Y', strtotime($d->tgl_izin_dari));
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($izinsakit->currentPage() - 1) * $izinsakit->perPage() }}
                                    </td>
                                    <td>
                                        <div class="d-flex py-1 align-items-center">
                                            <div class="flex-fill">
                                                <div class="font-weight-medium">{{ $d->nama_lengkap }}</div>
                                                <div class="text-muted small">{{ $d->nik }}</div>
                                                <div class="text-muted small">
                                                    {{ $d->karyawan->jabatan_nama ?? 'Karyawan' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $d->karyawan->cabang->nama_cabang ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="text-dark mb-1" style="font-weight: 500">
                                                {{ $periodeDisplay }}
                                            </div>
                                            @if ($periodeHint !== '')
                                                <div class="text-muted small mb-1">{{ $periodeHint }}</div>
                                            @endif
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge badge-sm bg-secondary-lt">{{ $jumlahHari }}
                                                    Hari</span>
                                                @if ($d->status == 'i')
                                                    <span class="badge badge-sm bg-blue-lt">Izin</span>
                                                @elseif ($d->status == 's')
                                                    <span class="badge badge-sm bg-pink-lt">Sakit</span>
                                                @elseif ($d->status == 't')
                                                    <span class="badge badge-sm bg-orange-lt">Izin Terlambat</span>
                                                @elseif ($d->status == 'p')
                                                    <span class="badge badge-sm bg-indigo-lt">Pulang Cepat</span>
                                                @elseif ($d->status == 'r')
                                                    <span class="badge badge-sm bg-cyan-lt">Roster</span>
                                                @elseif (!empty($d->kode_cuti))
                                                    <span
                                                        class="badge badge-sm bg-teal-lt">{{ $d->jenis_cuti_formal }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-muted">
                                        <div>{{ $keteranganDisplay !== '' ? $keteranganDisplay : '-' }}</div>
                                        @if (in_array($d->status, ['c', 'r'], true) && !empty($d->requested_dates_text))
                                            @php
                                                $previewDates = collect($requestedDatesView)->take(6)->implode(', ');
                                                $remainingDates = max(0, $requestedCount - 6);
                                            @endphp
                                            <div class="small mt-1"><strong>{{ $d->status == 'r' ? 'Tanggal Roster' : 'Tanggal Cuti' }}:</strong>
                                                {{ $previewDates }}@if ($remainingDates > 0)
                                                    , +{{ $remainingDates }} tanggal lain
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($d->status_approved == 1)
                                            <span class="badge bg-success-lt">Disetujui</span>
                                        @elseif ($d->status_approved == 2)
                                            <span class="badge bg-danger-lt">Ditolak</span>
                                        @else
                                            <span class="badge bg-warning-lt">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="#" class="btn btn-icon btn-outline-info btn-detail"
                                                data-idizinsakit="{{ $d->id }}" title="Lihat Detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <circle cx="12" cy="12" r="2" />
                                                    <path
                                                        d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" />
                                                </svg>
                                            </a>
                                            @if ($d->status_approved == 0)
                                                <a href="#" class="btn btn-icon btn-outline-primary btn-approve"
                                                    data-idizinsakit="{{ $d->id }}" title="Proses Pengajuan">
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
                                            @else
                                                <button type="button" class="btn btn-icon btn-ghost-danger btn-cancel"
                                                    data-idizinsakit="{{ $d->id }}" title="Batalkan">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <circle cx="12" cy="12" r="9" />
                                                        <path d="M10 10l4 4m0 -4l-4 4" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">Tidak ada data ditemukan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex align-items-center">
                        {{ $izinsakit->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail Modal --}}
        <div class="modal modal-blur fade" id="modal-detail" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Pengajuan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6"><label class="form-label small text-muted">Nama</label>
                                <div class="form-control-plaintext" id="detail-nama">-</div>
                            </div>
                            <div class="col-md-6"><label class="form-label small text-muted">NIK</label>
                                <div class="form-control-plaintext" id="detail-nik">-</div>
                            </div>
                            <div class="col-md-6"><label class="form-label small text-muted">Jenis</label>
                                <div class="form-control-plaintext" id="detail-jenis">-</div>
                            </div>
                            <div class="col-md-6"><label class="form-label small text-muted">Tanggal</label>
                                <div class="form-control-plaintext"><span id="detail-dari"></span> s/d <span
                                        id="detail-sampai"></span></div>
                            </div>
                            <div class="col-12"><label class="form-label small text-muted">Keterangan</label>
                                <div class="form-control-plaintext detail-keterangan-box" id="detail-keterangan">-</div>
                            </div>
                            <div class="col-12 mt-2" id="detail-catatan-ditolak-wrapper" style="display:none;">
                                <div class="alert alert-danger mb-0">
                                    <div class="fw-semibold mb-1">Catatan Penolakan</div>
                                    <div id="detail-catatan-ditolak">-</div>
                                </div>
                            </div>

                            {{-- UPDATED UI: CONTAINER UNTUK KALENDER DETAIL --}}
                            <div class="col-12 mt-3" id="detail-datepicker-section" style="display: none;">
                                <label class="form-label small text-muted fw-bold mb-2">Detail Persetujuan Tanggal:</label>

                                {{-- Container untuk Datepicker Inline --}}
                                <div class="d-flex justify-content-center">
                                    <div id="detail-calendar-view"></div>
                                </div>

                                {{-- Legend Warna --}}
                                <div class="d-flex justify-content-center gap-3 mt-3 small">
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-success"
                                            style="width:10px;height:10px;padding:0; border-radius:50%"></span>
                                        <span>Disetujui</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-danger"
                                            style="width:10px;height:10px;padding:0; border-radius:50%"></span>
                                        <span>Ditolak</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-primary"
                                            style="width:10px;height:10px;padding:0; border-radius:50%"></span>
                                        <span>Menunggu</span>
                                    </div>
                                </div>
                            </div>
                            {{-- END UPDATED UI --}}

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MODERN APPROVAL MODAL (HORIZONTAL LAYOUT) --}}
        <div class="modal modal-blur fade" id="modal-izinsakit" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Proses Pengajuan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <form action="/presensi/approveizinsakit" method="POST" id="approvalForm">
                            @csrf
                            <input type="hidden" id="id_izinsakit_from" name="id_izinsakit_from">

                            {{-- GRID LAYOUT: Kiri Kalender, Kanan Action --}}
                            <div class="modal-grid">

                                {{-- KOLOM KIRI: KALENDER --}}
                                <div id="cutiDatepickerSection" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                        <label class="form-label fw-bold m-0 text-muted small text-uppercase">Pilih
                                            Tanggal</label>
                                        <span class="badge bg-primary-lt" id="jml_hari_info">0 Hari</span>
                                    </div>

                                    <div id="inline_datepicker"></div>
                                    <input type="hidden" id="selectedCutiDates" name="selected_cuti_dates" value="">

                                    <div class="mt-2 text-center text-muted small" style="font-size: 0.75rem;">
                                        * Klik tanggal untuk membatalkan
                                    </div>
                                </div>

                                {{-- KOLOM KANAN: KEPUTUSAN --}}
                                <div class="action-section">
                                    <label class="form-label fw-bold mb-3 text-muted small text-uppercase">Keputusan</label>

                                    <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
                                        {{-- Setujui --}}
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="status_approved" value="1"
                                                class="form-selectgroup-input" checked>
                                            <div class="form-selectgroup-label d-flex align-items-center p-3">
                                                <div class="me-3">
                                                    <span class="form-selectgroup-check"></span>
                                                </div>
                                                <div>
                                                    <span
                                                        class="selection-icon bg-success-lt text-success mb-1 d-inline-flex align-items-center justify-content-center rounded-circle"
                                                        style="width: 24px; height: 24px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14"
                                                            height="14" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M5 12l5 5l10 -10" />
                                                        </svg>
                                                    </span>
                                                    <div class="font-weight-medium">Disetujui</div>
                                                </div>
                                            </div>
                                        </label>

                                        {{-- Tolak --}}
                                        <label class="form-selectgroup-item flex-fill mt-2">
                                            <input type="radio" name="status_approved" value="2"
                                                class="form-selectgroup-input">
                                            <div class="form-selectgroup-label d-flex align-items-center p-3">
                                                <div class="me-3">
                                                    <span class="form-selectgroup-check"></span>
                                                </div>
                                                <div>
                                                    <span
                                                        class="selection-icon bg-danger-lt text-danger mb-1 d-inline-flex align-items-center justify-content-center rounded-circle"
                                                        style="width: 24px; height: 24px;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14"
                                                            height="14" viewBox="0 0 24 24" stroke-width="2"
                                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                                            stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M18 6l-12 12" />
                                                            <path d="M6 6l12 12" />
                                                        </svg>
                                                    </span>
                                                    <div class="font-weight-medium">Ditolak</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    {{-- Textarea Komentar Penolakan --}}
                                    <div class="mt-3" id="teks_alasan_ditolak" style="display: none;">
                                        <label class="form-label small text-muted fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                                        <textarea name="catatan_ditolak" class="form-control" rows="3" 
                                            placeholder="Jelaskan alasan penolakan pengajuan ini..."></textarea>
                                        <small class="text-muted">Komentar ini akan terlihat di halaman detail izin karyawan.</small>
                                    </div>

                                    <div class="mt-4 pt-2 border-top">
                                        <button class="btn btn-primary w-100" type="submit">
                                            Simpan Keputusan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cancel Modal --}}
        <div class="modal modal-blur fade" id="modal-batalkan" tabindex="-1" role="dialog" aria-hidden="true">
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
                                <path d="M12 9v2m0 4v.01" />
                                <path
                                    d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75" />
                            </svg>
                        </div>
                        <h3>Batalkan Status?</h3>
                        <div class="text-secondary">Status akan dikembalikan ke <b>Pending</b>.</div>
                    </div>
                    <div class="modal-footer">
                        <div class="row w-100">
                            <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Tutup</a></div>
                            <div class="col">
                                <form id="frmBatalkan" method="POST" action="">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">Ya, Batalkan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @push('myscript')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.id.min.js"></script>
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

        <style>
            /* Gaya datepicker umum ada di admin-theme.css; di sini hanya penanda status tanggal. */
            .detail-keterangan-box {
                background: var(--color-surface-2);
                color: var(--color-ink-2);
                padding: var(--space-sm);
                border-radius: var(--radius-control);
            }

            .datepicker tbody td.day.day-approved {
                background: var(--color-success) !important;
                color: var(--color-accent-ink) !important;
            }

            .datepicker tbody td.day.day-rejected {
                background: var(--color-danger) !important;
                color: var(--color-accent-ink) !important;
                opacity: 0.7;
                text-decoration: line-through;
            }

            .datepicker tbody td.day.day-pending {
                background: var(--color-accent) !important;
                color: var(--color-accent-ink) !important;
                opacity: 0.9;
            }

            /* Kalender detail hanya-baca; tombol bulan tetap bisa diklik. */
            #detail-calendar-view .datepicker-days tbody {
                pointer-events: none;
                cursor: default;
            }

            @media (min-width: 992px) {
                .modal-grid {
                    display: grid;
                    grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
                    gap: var(--space-lg);
                    align-items: start;
                }
            }
        </style>

        <script>
            $(document).ready(function() {
                function parseYmdToLocalDate(ymd) {
                    if (!ymd || typeof ymd !== 'string') {
                        return null;
                    }

                    var parts = ymd.split('-');
                    if (parts.length !== 3) {
                        return null;
                    }

                    // Gunakan jam 12:00 lokal agar aman dari isu timezone/DST.
                    return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]), 12, 0, 0, 0);
                }

                function normalizeLocalDate(dateObj) {
                    return new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate(), 12, 0, 0, 0);
                }

                // 1. DETAIL LOGIC (UPDATED WITH INLINE CALENDAR)
                $('body').on('click', '.btn-detail', function(e) {
                    e.preventDefault();
                    var id = $(this).data("idizinsakit");

                    // Reset View
                    $('#detail-calendar-view').html('');
                    $('#detail-datepicker-section').hide();
                    $('#detail-catatan-ditolak-wrapper').hide();
                    $('#detail-catatan-ditolak').text('-');

                    $.ajax({
                        url: '/presensi/detailijinsakit/' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $('#detail-nama').text(data.nama_lengkap);
                            $('#detail-nik').text(data.nik);
                            $('#detail-jenis').html(data.jenis_badge || '-');
                            $('#detail-dari').text(data.tgl_dari);
                            $('#detail-sampai').text(data.tgl_sampai);
                            $('#detail-keterangan').text(data.keterangan);

                            var statusApproved = Number(data.status_approved || 0);
                            var catatanDitolak = (data.catatan_ditolak || '').trim();
                            if (statusApproved === 2 && catatanDitolak !== '') {
                                $('#detail-catatan-ditolak').text(catatanDitolak);
                                $('#detail-catatan-ditolak-wrapper').show();
                            }

                            // --- LOGIC KALENDER DETAIL ---
                            // Tampilkan kalender untuk pengajuan multi tanggal (cuti / roster)
                            if (data.is_cuti) {
                                $('#detail-datepicker-section').show();
                                $('#detail-datepicker-section .form-label').text(data.status === 'r' ? 'Detail Persetujuan Tanggal Roster:' : 'Detail Persetujuan Tanggal:');

                                // Gunakan format standar Y-m-d dari controller
                                var requestedDates = Array.isArray(data.requested_dates) ? data
                                    .requested_dates : [];
                                var approvedDates = Array.isArray(data.approved_dates) ? data
                                    .approved_dates : [];
                                var statusApproved = Number(data.status_approved || 0);
                                var datesToShow = requestedDates.length ? requestedDates :
                                    getDateStringsInRange(data
                                        .tgl_dari_std, data.tgl_sampai_std);
                                var allowedDateLookup = {};
                                datesToShow.forEach(function(date) {
                                    allowedDateLookup[date] = true;
                                });
                                var startDate = parseYmdToLocalDate(datesToShow.length ?
                                    datesToShow[0] : data
                                    .tgl_dari_std);

                                // Destroy datepicker lama
                                $('#detail-calendar-view').datepicker('destroy');

                                // Init Datepicker (Visual Only)
                                $('#detail-calendar-view').datepicker({
                                    format: "yyyy-mm-dd",
                                    todayHighlight: false, // Matikan highlight hari ini agar tidak bingung
                                    language: 'id',
                                    // LOGIC WARNA:
                                    beforeShowDay: function(date) {
                                        // Konversi date object ke string YYYY-MM-DD
                                        var dateString = formatDateToYmd(
                                            normalizeLocalDate(date));

                                        if (!allowedDateLookup[dateString]) {
                                            return {
                                                enabled: false,
                                                classes: 'disabled'
                                            };
                                        }

                                        if (statusApproved === 0) {
                                            return {
                                                classes: 'day-pending',
                                                tooltip: 'Menunggu Persetujuan'
                                            };
                                        }

                                        if (statusApproved === 1) {
                                            if (approvedDates.includes(dateString)) {
                                                return {
                                                    classes: 'day-approved',
                                                    tooltip: 'Disetujui'
                                                };
                                            }
                                            return {
                                                classes: 'day-rejected',
                                                tooltip: 'Tidak Diambil / Ditolak'
                                            };
                                        }

                                        return {
                                            classes: 'day-rejected',
                                            tooltip: 'Ditolak'
                                        };
                                    }
                                });

                                // Set focus ke tanggal mulai agar kalender langsung menampilkan bulan yang relevan
                                $('#detail-calendar-view').datepicker('setDate', startDate);
                            }

                            $('#modal-detail').modal("show");
                        }
                    });
                });

                // 2. APPROVAL LOGIC (MODERN DATEPICKER)
                $('body').on('click', '.btn-approve', function(e) {
                    e.preventDefault();
                    var id = $(this).data("idizinsakit");
                    $('#id_izinsakit_from').val(id);

                    $.ajax({
                        url: '/presensi/detailijinsakit/' + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $('#inline_datepicker').datepicker('destroy');

                            if (data.is_cuti) {
                                $('#cutiDatepickerSection').show();
                                $('#cutiDatepickerSection .form-label').first().text(data.status === 'r' ? 'Pilih Tanggal Roster' : 'Pilih Tanggal Cuti');

                                var requestedDates = Array.isArray(data.requested_dates) ? data
                                    .requested_dates : [];
                                var datesToSelect = requestedDates.length ? requestedDates :
                                    getDateStringsInRange(data
                                        .tgl_dari_std, data.tgl_sampai_std);
                                var allowedDateLookup = {};
                                datesToSelect.forEach(function(date) {
                                    allowedDateLookup[date] = true;
                                });

                                var minDate = datesToSelect.length ? datesToSelect[0] : data
                                    .tgl_dari_std;
                                var maxDate = datesToSelect.length ? datesToSelect[datesToSelect
                                        .length - 1] : data
                                    .tgl_sampai_std;

                                $('#inline_datepicker').datepicker({
                                    format: "yyyy-mm-dd",
                                    multidate: true,
                                    todayHighlight: true,
                                    startDate: minDate,
                                    endDate: maxDate,
                                    beforeShowDay: function(date) {
                                        var dateStr = formatDateToYmd(
                                            normalizeLocalDate(date));
                                        if (!allowedDateLookup[dateStr]) {
                                            return {
                                                enabled: false,
                                                classes: 'disabled'
                                            };
                                        }
                                        return {
                                            enabled: true
                                        };
                                    },
                                    language: 'id'
                                });

                                $('#inline_datepicker').datepicker('setDates', datesToSelect);
                                updateHiddenInput();

                                $('#inline_datepicker').on('changeDate', function() {
                                    updateHiddenInput();
                                });

                            } else {
                                $('#cutiDatepickerSection').hide();
                                $('#selectedCutiDates').val("");
                            }
                        }
                    });
                    $('#modal-izinsakit').modal("show");
                });

                function updateHiddenInput() {
                    var selectedDates = $('#inline_datepicker').datepicker('getFormattedDate');
                    $('#selectedCutiDates').val(selectedDates);

                    var dateArray = $('#inline_datepicker').datepicker('getDates');
                    var count = dateArray.length;

                    if (count > 0) {
                        $('#jml_hari_info').text(count + ' Hari Dipilih').removeClass('bg-danger-lt').addClass(
                            'bg-primary-lt');
                    } else {
                        $('#jml_hari_info').text('0 Hari (Ditolak Semua)').removeClass('bg-primary-lt').addClass(
                            'bg-danger-lt');
                    }
                }

                function formatDateToYmd(dateObj) {
                    var year = dateObj.getFullYear();
                    var month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                    var day = dateObj.getDate().toString().padStart(2, '0');
                    return year + '-' + month + '-' + day;
                }

                function getDateStringsInRange(startDate, endDate) {
                    var dates = [];
                    var currDate = parseYmdToLocalDate(startDate);
                    var lastDate = parseYmdToLocalDate(endDate);

                    if (!currDate || !lastDate) {
                        return dates;
                    }

                    while (currDate <= lastDate) {
                        dates.push(formatDateToYmd(currDate));
                        currDate.setDate(currDate.getDate() + 1);
                    }

                    return dates;
                }

                $(document).on('submit', '#approvalForm', function(e) {
                    if ($('input[name="status_approved"]:checked').val() == 2) {
                        $('#selectedCutiDates').val('');
                    }
                });

                // Toggle textarea untuk alasan penolakan
                $('input[name="status_approved"]').on('change', function() {
                    if ($(this).val() == 2) {
                        $('#teks_alasan_ditolak').show();
                        $('textarea[name="catatan_ditolak"]').prop('required', true);
                    } else {
                        $('#teks_alasan_ditolak').hide();
                        $('textarea[name="catatan_ditolak"]').prop('required', false).val('');
                    }
                });

                // 3. CANCEL LOGIC
                $('body').on('click', '.btn-cancel', function(e) {
                    e.preventDefault();
                    var id = $(this).data("idizinsakit");
                    $('#frmBatalkan').attr('action', "/presensi/" + id + "/batalkanizinsakit");
                    $('#modal-batalkan').modal("show");
                });

                // 4. FILTER PERIODE
                $('#bulan_visual').datepicker({
                    format: "MM yyyy",
                    startView: "months",
                    minViewMode: "months",
                    language: "id",
                    autoclose: true,
                }).on('changeDate', function(e) {
                    var yyyy = e.date.getFullYear();
                    var mm = (e.date.getMonth() + 1).toString().padStart(2, '0');
                    $('#bulan').val(yyyy + '-' + mm);
                });
            });
        </script>
    @endpush
