@extends('layouts.presensi')

@section('header')
    <style>
        .presensi-header {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .content-wrapper {
            padding-bottom: 100px;
        }

        .filter-box {
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(9, 75, 135, 0.08);
            margin-bottom: 20px;
        }

        .history-card {
            display: flex;
            align-items: stretch;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(9, 75, 135, 0.08);
            margin-bottom: 14px;
            padding: 0;
            border: 1px solid #f5f5f5;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
            min-height: 110px;
        }

        .history-card:active {
            transform: scale(0.98);
        }

        .card-icon-wrapper {
            display: flex;
            align-items: center;
            padding-left: 16px;
            padding-right: 14px;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 5px 12px rgba(9, 75, 135, 0.12);
            flex-shrink: 0;
        }

        .card-content {
            flex-grow: 1;
            min-width: 0;
            padding: 16px 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .card-date {
            font-size: 16px;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .card-subtitle {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-desc {
            font-size: 12px;
            color: #636e72;
            line-height: 1.4;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-status {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
            width: 90px;
            min-width: 90px;
            border-left: 2px dashed #dfe6e9;
            background-color: #fafafa;
        }

        .status-badge {
            padding: 0px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
            margin-bottom: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .bg-pending {
            background: #fff3cd;
            color: #856404;
        }

        .bg-success-soft {
            background: #eaf3fb;
            color: #094b87;
        }

        .bg-danger-soft {
            background: #f8d7da;
            color: #721c24;
        }

        .days-wrapper {
            text-align: center;
        }

        .days-count {
            font-size: 20px;
            font-weight: 800;
            color: #2d3436;
            line-height: 1;
        }

        .days-label {
            font-size: 9px;
            color: #b2bec3;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 2px;
        }

        .action-area {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .btn-action {
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none !important;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            padding: 0px 4px;
        }

        .btn-edit {
            background: #fff3cd;
            color: #d39e00;
        }

        .btn-delete {
            background: #ffe5e5;
            color: #e03131;
        }

        .btn-sid {
            background: #e7f5ff;
            color: #1971c2;
        }

        .fab-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .fab-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .fab-wrapper {
            position: fixed;
            bottom: calc(110px + env(safe-area-inset-bottom));
            right: 16px;
            z-index: 2001;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .fab-main {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #0d5eaa 0%, #094b87 100%);
            border-radius: 50%;
            box-shadow: 0 10px 24px rgba(9, 75, 135, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            border: none;
            outline: none;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            -webkit-tap-highlight-color: transparent;
        }

        .fab-main:active {
            transform: scale(0.95);
        }

        .fab-main.active ion-icon {
            transform: rotate(45deg);
        }

        .fab-main ion-icon {
            transition: transform 0.3s ease;
        }

        .fab-menu {
            position: absolute;
            bottom: 70px;
            right: 0;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            pointer-events: none;
            width: max-content;
            padding-bottom: 5px;
        }

        .fab-menu.active {
            pointer-events: auto;
        }

        .fab-item {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            opacity: 0;
            transform: translateY(20px) scale(0.8);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .fab-menu.active .fab-item {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .fab-menu.active .fab-item:nth-child(1) {
            transition-delay: 0.05s;
        }

        .fab-menu.active .fab-item:nth-child(2) {
            transition-delay: 0.1s;
        }

        .fab-menu.active .fab-item:nth-child(3) {
            transition-delay: 0.15s;
        }

        .fab-menu.active .fab-item:nth-child(4) {
            transition-delay: 0.2s;
        }

        .fab-label {
            background: white;
            color: #333;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 12px;
            box-shadow: 0 6px 16px rgba(9, 75, 135, 0.12);
            white-space: nowrap;
        }

        .fab-icon-small {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 6px 14px rgba(9, 75, 135, 0.2);
        }

        .bg-grad-blue {
            background: linear-gradient(135deg, #0d5eaa 0%, #094b87 100%);
        }

        .bg-grad-red {
            background: linear-gradient(135deg, #0f6bc0 0%, #0b4f90 100%);
        }

        .bg-grad-orange {
            background: linear-gradient(135deg, #1f7fcf 0%, #0d5eaa 100%);
        }

        .bg-grad-cyan {
            background: linear-gradient(135deg, #2f8fdc 0%, #0d5eaa 100%);
        }

        @media (max-width: 576px) {
            .history-card {
                min-height: 90px;
            }

            .card-icon {
                width: 42px;
                height: 42px;
                font-size: 20px;
            }

            .card-icon-wrapper {
                padding-left: 12px;
                padding-right: 10px;
            }

            .card-date {
                font-size: 15px;
            }

            .card-subtitle {
                font-size: 12px;
            }

            .card-status {
                width: 100px;
                min-width: 80px;
            }

            .days-count {
                font-size: 18px;
            }

            .filter-box {
                padding: 12px;
            }
        }
    </style>

    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Riwayat Pengajuan</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="content-wrapper">

        <div class="row" style="margin-top: 24px;">
            <div class="col">
                @if (Session::get('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                        <ion-icon name="checkmark-circle-outline" style="vertical-align: -2px; font-size:18px;"></ion-icon>
                        {{ Session::get('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if (Session::get('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 10px;">
                        <ion-icon name="warning-outline" style="vertical-align: -2px; font-size:18px;"></ion-icon>
                        {{ Session::get('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="filter-box">
                    <form method="GET" action="/pengajuanizin/index">
                        <div class="row">
                            <div class="col-12 col-md-5 mb-2 mb-md-0">
                                <select name="bulan" id="bulan" class="form-control custom-select">
                                    <option value="">Semua Bulan</option>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ request('bulan') == $i ? 'selected' : '' }}>
                                            {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 col-md-5 mb-2 mb-md-0">
                                <select name="tahun" id="tahun" class="form-control custom-select">
                                    <option value="">Semua Tahun</option>
                                    @php
                                        $startYear = 2022;
                                        $currentYear = date('Y');
                                    @endphp
                                    @for ($y = $currentYear; $y >= $startYear; $y--)
                                        <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button class="btn btn-primary btn-block" style="border-radius: 8px;">
                                    <ion-icon name="search-outline"></ion-icon> Cari
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                @forelse ($data_izin as $d)
                    @php
                        $total_hari = $d->total_hari_view ?? 1;

                        $icon = 'document-text-outline';
                        $bg_color = '#e7f5ff';
                        $text_color = '#1971c2';
                        $label_jenis = 'Izin Absen';

                        if ($d->status == 'i') {
                            $icon = 'document-text-outline';
                            $bg_color = '#e7f5ff';
                            $text_color = '#1971c2';
                            $label_jenis = 'Izin Absen';
                        } elseif ($d->status == 's') {
                            $icon = 'medkit-outline';
                            $bg_color = '#ffe3e3';
                            $text_color = '#c92a2a';
                            $label_jenis = 'Sakit';
                        } elseif ($d->status == 'c') {
                            $icon = 'calendar-outline';
                            $bg_color = '#fff9db';
                            $text_color = '#f08c00';
                            if (!empty($d->masterCuti)) {
                                $label_jenis = $d->masterCuti->nama_cuti;
                            } else {
                                $label_jenis = 'Cuti';
                            }
                        } elseif ($d->status == 'r') {
                            $icon = 'calendar-clear-outline';
                            $bg_color = '#e3fafc';
                            $text_color = '#094b87';
                            $label_jenis = 'Roster';
                        } elseif ($d->status == 't') {
                            $icon = 'time-outline';
                            $bg_color = '#e3fafc';
                            $text_color = '#0d5eaa';
                            $label_jenis = 'Terlambat';
                        } elseif ($d->status == 'p') {
                            $icon = 'log-out-outline';
                            $bg_color = '#ecebff';
                            $text_color = '#094b87';
                            $label_jenis = 'Pulang Cepat';
                        }

                        $requestedDatesView = is_array($d->requested_dates_view ?? null)
                            ? $d->requested_dates_view
                            : [];

                        // Fallback: parse from CUTI_DATES metadata when requested_dates_view is not populated.
                        if (in_array($d->status, ['c', 'r'], true) && empty($requestedDatesView)) {
                            $metaDatesIso = [];
                            if (preg_match('/\[CUTI_DATES:([^\]]*)\]?/i', (string) $d->keterangan, $matches)) {
                                $metaDatesIso = collect(explode(',', (string) ($matches[1] ?? '')))
                                    ->map(function ($date) {
                                        return trim($date);
                                    })
                                    ->filter(function ($date) {
                                        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
                                    })
                                    ->unique()
                                    ->sort()
                                    ->values()
                                    ->all();
                            }

                            if (!empty($metaDatesIso)) {
                                $requestedDatesView = collect($metaDatesIso)
                                    ->map(function ($date) {
                                        return date('d-m-Y', strtotime($date));
                                    })
                                    ->values()
                                    ->all();
                            } else {
                                $rangeStart = \Carbon\Carbon::parse($d->tgl_izin_dari);
                                $rangeEnd = \Carbon\Carbon::parse($d->tgl_izin_sampai ?? $d->tgl_izin_dari);
                                if ($rangeEnd->lt($rangeStart)) {
                                    $rangeEnd = $rangeStart->copy();
                                }

                                $period = new \DatePeriod(
                                    $rangeStart,
                                    new \DateInterval('P1D'),
                                    $rangeEnd->copy()->addDay(),
                                );

                                $requestedDatesView = [];
                                foreach ($period as $date) {
                                    $requestedDatesView[] = $date->format('d-m-Y');
                                }
                            }
                        }

                        if (in_array($d->status, ['c', 'r'], true) && !empty($requestedDatesView)) {
                            $total_hari = count($requestedDatesView);
                        }
                    @endphp

                    <div class="history-card">
                        <div class="card-icon-wrapper">
                            <div class="card-icon"
                                style="background-color: {{ $bg_color }}; color: {{ $text_color }};">
                                <ion-icon name="{{ $icon }}"></ion-icon>
                            </div>
                        </div>

                        <div class="card-content">
                            @php
                                $tgl_dari = date('d-m-Y', strtotime($d->tgl_izin_dari));
                                $tgl_sampai = date('d-m-Y', strtotime($d->tgl_izin_sampai));
                            @endphp

                            <div class="card-subtitle" style="color: {{ $text_color }}">
                                {{ $label_jenis }}
                                @if ($d->status == 'c' && !empty($d->kode_cuti))
                                    <span
                                        style="font-size:10px; opacity:0.7; margin-left:3px;">({{ $d->kode_cuti }})</span>
                                @endif
                            </div>

                            <div class="card-desc">
                                @php
                                    $keteranganDisplay = $d->keterangan_view ?? $d->keterangan;
                                    $keteranganDisplay = preg_replace(
                                        '/\s*\[CUTI_DATES:[^\]]*\]?\s*/i',
                                        ' ',
                                        (string) $keteranganDisplay,
                                    );
                                    $keteranganDisplay = trim((string) $keteranganDisplay);
                                @endphp
                                @if (in_array($d->status, ['c', 'r'], true) && $keteranganDisplay === '')
                                    {{ $d->status === 'r' ? 'Pengajuan roster pada tanggal terpilih.' : 'Pengajuan cuti pada tanggal terpilih.' }}
                                @else
                                    {{ $keteranganDisplay !== '' ? $keteranganDisplay : '-' }}
                                @endif
                            </div>

                            @if (in_array($d->status, ['c', 'r'], true))
                                <div style="font-size: 11px; color: #6c757d; margin-top: 6px; line-height: 1.45;">
                                    <strong>{{ $d->status == 'r' ? 'Tanggal Roster' : 'Tanggal Cuti' }} :</strong>
                                    {{ !empty($requestedDatesView) ? implode(', ', $requestedDatesView) : ($tgl_dari == $tgl_sampai ? $tgl_dari : $tgl_dari . ' s/d ' . $tgl_sampai) }}
                                </div>
                            @else
                                <div class="card-date" style="font-size: 12px; color: #636e72; margin-top: 6px;">
                                    @if ($tgl_dari == $tgl_sampai)
                                        {{ $tgl_dari }}
                                    @else
                                        {{ $tgl_dari }} <span style="font-size: 11px; font-weight: 500;">s/d</span>
                                        {{ $tgl_sampai }}
                                    @endif
                                </div>
                            @endif

                            <div class="action-area">
                                <button type="button" class="btn-action" style="background: #d3f9d8; color: #2f5233;"
                                    onclick="viewDetail('{{ $d->kode_izin }}')">
                                    <ion-icon name="eye-outline"></ion-icon> Detail
                                </button>

                                @if ($d->status == 's' && isset($d->doc_sid) && $d->doc_sid != '-')
                                    @php
                                        $sidPath = null;
                                        if (Storage::disk('public')->exists('uploads/sid/' . $d->doc_sid)) {
                                            $sidPath = asset('storage/uploads/sid/' . $d->doc_sid);
                                        } elseif (
                                            Storage::disk('public')->exists('public/uploads/sid/' . $d->doc_sid)
                                        ) {
                                            $sidPath = asset('storage/public/uploads/sid/' . $d->doc_sid);
                                        }
                                    @endphp
                                    @if ($sidPath)
                                        <a href="{{ $sidPath }}" target="_blank" class="btn-action btn-sid">
                                            <ion-icon name="attach-outline"></ion-icon> SID
                                        </a>
                                    @endif
                                @endif

                                @if ($d->status_approved == 0)
                                    <a href="{{ url('/pengajuanizin/' . $d->kode_izin . '/edit') }}"
                                        class="btn-action btn-edit">
                                        <ion-icon name="create-outline"></ion-icon> Edit
                                    </a>
                                    <form action="{{ url('/pengajuanizin/' . $d->kode_izin . '/delete') }}" method="POST"
                                        style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-action btn-delete delete-button">
                                            <ion-icon name="trash-outline"></ion-icon> Hapus
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="card-status">
                            @if ($d->status_approved == 1)
                                <span class="status-badge bg-success-soft">Disetujui</span>
                            @elseif ($d->status_approved == 2)
                                <span class="status-badge bg-danger-soft">Ditolak</span>
                            @else
                                <span class="status-badge bg-pending">Menunggu</span>
                            @endif

                            <div class="days-wrapper">
                                <div class="days-count">{{ $total_hari }}</div>
                                <div class="days-label">Hari</div>
                            </div>
                        </div>
                    </div>

                @empty
                    <div style="text-align: center; margin-top: 50px; opacity: 0.6;">
                        <ion-icon name="file-tray-open-outline" style="font-size: 64px; color: #aeb5bc;"></ion-icon>
                        <h4 style="margin-top: 10px; font-size: 16px; color: #495057;">Tidak ada riwayat pengajuan</h4>
                        <p style="font-size: 12px; color: #868e96;">Silakan buat pengajuan baru melalui tombol (+) di bawah.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Detail Modal --}}
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content"
                style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom: 1px solid #f0f0f0; padding: 20px;">
                    <h5 class="modal-title" style="font-weight: 700; font-size: 16px;">Detail Pengajuan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div style="margin-bottom: 15px;">
                        <label
                            style="font-size: 12px; color: #999; font-weight: 600; display: block; margin-bottom: 5px;">Jenis
                            Pengajuan</label>
                        <div id="detailJenis" style="font-size: 14px; font-weight: 600; color: #333;">-</div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label
                            style="font-size: 12px; color: #999; font-weight: 600; display: block; margin-bottom: 5px;">Tanggal
                            Pengajuan</label>
                        <div style="font-size: 14px; color: #333;">
                            <span id="detailDari">-</span> s/d <span id="detailSampai">-</span>
                        </div>
                        <div id="detailTanggalList"
                            style="font-size: 12px; color: #555; margin-top: 6px; background: #f9fafb; border: 1px solid #eef1f4; border-radius: 8px; padding: 8px; line-height: 1.5; word-break: break-word;">
                            -
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label
                            style="font-size: 12px; color: #999; font-weight: 600; display: block; margin-bottom: 5px;">Keterangan</label>
                        <div id="detailKeterangan"
                            style="font-size: 13px; color: #666; line-height: 1.6; background: #f9f9f9; padding: 10px; border-radius: 8px;">
                            -</div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label
                            style="font-size: 12px; color: #999; font-weight: 600; display: block; margin-bottom: 5px;">Status
                            Pengajuan</label>
                        <div id="detailStatus" style="font-size: 13px; font-weight: 600;">-</div>
                    </div>

                    {{-- Section Catatan Penolakan --}}
                    <div id="detailCatatanDitolak" style="margin-bottom: 15px; display: none; background: #fee; border: 1px solid #fcc; border-radius: 8px; padding: 12px;">
                        <label
                            style="font-size: 12px; color: #c33; font-weight: 700; display: block; margin-bottom: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:inline; margin-right:4px;">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 8v4"/><path d="M12 16v.01"/>
                            </svg>
                            Alasan Penolakan
                        </label>
                        <div id="detailCatatanText"
                            style="font-size: 13px; color: #555; line-height: 1.5; background: #fff; padding: 8px; border-radius: 4px; border-left: 3px solid #e03131;">
                            -
                        </div>
                    </div>

                    {{-- UPDATED: Calendar Section Responsive & Centered --}}
                    <div id="detailCalendarSection" style="display: none; margin-top: 25px;">
                        <label
                            style="font-size: 12px; color: #999; font-weight: 600; display: block; margin-bottom: 10px; text-align:center;">KALENDER
                            PENGAJUAN CUTI</label>

                        {{-- Wrapper untuk memastikan kalender rata tengah dan responsif --}}
                        <div style="display: flex; justify-content: center; width: 100%;">
                            <div
                                style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); width: 100%; max-width: 350px;">
                                <div id="detailCalendar" style="width: 100%; display: flex; justify-content: center;">
                                </div>
                            </div>
                        </div>

                        {{-- Legend di bawah kalender --}}
                        <div class="d-flex justify-content-center align-items-center flex-wrap gap-3 mt-3"
                            style="font-size: 12px;">
                            <div class="d-flex align-items-center">
                                <span
                                    style="display:inline-block; width:12px; height:12px; background-color:#1c7ed6; border-radius:50%; margin-right:6px;"></span>
                                <span style="color: #333; font-weight: 600;">Diajukan</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span
                                    style="display:inline-block; width:12px; height:12px; background-color:#094b87; border-radius:50%; margin-right:6px;"></span>
                                <span style="color: #333; font-weight: 600;">Disetujui</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span
                                    style="display:inline-block; width:12px; height:12px; background-color:#e03131; border-radius:50%; margin-right:6px;"></span>
                                <span style="color: #333; font-weight: 600;">Ditolak</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f0f0f0; padding: 15px;">
                    <button type="button" class="btn btn-secondary btn-block" data-dismiss="modal"
                        style="border-radius: 8px; width: 100%;">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="fab-overlay" id="fabOverlay"></div>
    <div class="fab-wrapper">
        <div class="fab-menu" id="fabMenu">
            <a href="/pengajuanizin/createizinpulangcepat" class="fab-item">
                <span class="fab-label">Izin Pulang Cepat</span>
                <div class="fab-icon-small" style="background: linear-gradient(135deg, #0d5eaa, #094b87);">
                    <ion-icon name="log-out-outline"></ion-icon>
                </div>
            </a>
            <a href="/pengajuanizin/createizinterlambat" class="fab-item">
                <span class="fab-label">Izin Terlambat</span>
                <div class="fab-icon-small bg-grad-cyan"><ion-icon name="time-outline"></ion-icon></div>
            </a>
            <a href="/pengajuanizin/createizincuti" class="fab-item">
                <span class="fab-label">Pengajuan Cuti</span>
                <div class="fab-icon-small bg-grad-orange"><ion-icon name="calendar-outline"></ion-icon></div>
            </a>
            <a href="/pengajuanizin/createizinroster" class="fab-item">
                <span class="fab-label">Izin Roster</span>
                <div class="fab-icon-small" style="background: linear-gradient(135deg, #1f7fcf, #094b87);"><ion-icon name="calendar-clear-outline"></ion-icon></div>
            </a>
            <a href="/pengajuanizin/createizinsakit" class="fab-item">
                <span class="fab-label">Izin Sakit</span>
                <div class="fab-icon-small bg-grad-red"><ion-icon name="medkit-outline"></ion-icon></div>
            </a>
            <a href="/pengajuanizin/createizinabsen" class="fab-item">
                <span class="fab-label">Izin Absen</span>
                <div class="fab-icon-small bg-grad-blue"><ion-icon name="document-text-outline"></ion-icon></div>
            </a>
        </div>
        <button class="fab-main" id="fabMainBtn">
            <ion-icon name="add-outline"></ion-icon>
        </button>
    </div>
@endsection

@push('myscript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Responsive Datepicker Override */
        .datepicker-inline {
            width: 100% !important;
            max-width: 100%;
            display: block !important;
        }

        .datepicker table {
            width: 100%;
            table-layout: fixed;
            /* Ensures cells are equal width */
        }

        .datepicker table tr td,
        .datepicker table tr th {
            text-align: center;
            vertical-align: middle;
            height: 38px;
            /* Fixed height for consistent look */
            border-radius: 8px;
            /* Softer edges */
        }

        .datepicker table tr th {
            font-weight: 600;
            color: #666;
        }

        /* Status Styles */
        #detailCalendar .datepicker table tr td.day-approved {
            background-color: #094b87 !important;
            color: white !important;
            border-radius: 50%;
            font-weight: bold;
        }

        #detailCalendar .datepicker table tr td.day-requested {
            background-color: #1c7ed6 !important;
            color: white !important;
            border-radius: 50%;
            font-weight: bold;
        }

        #detailCalendar .datepicker table tr td.day-rejected {
            background-color: #e03131 !important;
            color: white !important;
            border-radius: 50%;
            text-decoration: line-through;
            opacity: 0.7;
        }

        /* Non-aktifkan klik tanggal di detail */
        #detailCalendar .datepicker-days table tbody {
            pointer-events: none;
            cursor: default;
        }

        /* Allow navigation buttons to be clickable */
        #detailCalendar .datepicker-days thead {
            pointer-events: auto;
        }

        body.detail-modal-open .fab-wrapper,
        body.detail-modal-open .fab-overlay {
            display: none !important;
        }
    </style>

    <script>
        const autoOpenDetailIzin = @json($openDetailIzin ?? null);

        function viewDetail(izinId) {
            $('#detailCalendarSection').hide();
            $('#detailCalendar').html('');

            $.ajax({
                url: '/pengajuanizin/detail/' + encodeURIComponent(izinId),
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#detailJenis').html(data.jenis_badge);
                    var requestedDates = Array.isArray(data.requested_dates) ? data.requested_dates : [];

                    if (requestedDates.length > 0) {
                        $('#detailDari').text(formatDateForDisplay(requestedDates[0]));
                        $('#detailSampai').text(formatDateForDisplay(requestedDates[requestedDates.length - 1]));
                        
                        var label = (parseInt(data.status_approved) === 1) ? 'Tanggal disetujui: ' : 'Tanggal dipilih: ';
                        $('#detailTanggalList').text(label + requestedDates.map(formatDateForDisplay).join(', '));
                    } else {
                        $('#detailDari').text(data.tgl_dari || '-');
                        $('#detailSampai').text(data.tgl_sampai || '-');
                        $('#detailTanggalList').text('Tanggal dipilih: -');
                    }

                    $('#detailKeterangan').text(data.keterangan || '-');
                    $('#detailStatus').html(data.status_badge);

                    // Tampilkan catatan penolakan jika ada
                    if (data.status_approved == 2 && data.catatan_ditolak) {
                        $('#detailCatatanDitolak').show();
                        $('#detailCatatanText').text(data.catatan_ditolak);
                    } else {
                        $('#detailCatatanDitolak').hide();
                        $('#detailCatatanText').text('-');
                    }

                    if (data.is_cuti === true) {
                        $('#detailCalendarSection').show();

                        if (!requestedDates.length) {
                            requestedDates = getDatesInRange(data.tgl_dari_iso, data.tgl_sampai_iso);
                        }
                        var approvedDates = data.approved_dates || [];
                        var rejectedDates = buildRejectedDates(requestedDates, approvedDates, data
                            .status_approved);

                        var sectionLabel = data.status === 'r' ? 'KALENDER PENGAJUAN ROSTER' : 'KALENDER PENGAJUAN CUTI';
                        $('#detailCalendarSection label').text(sectionLabel);

                        initCombinedDatepicker('#detailCalendar', approvedDates, rejectedDates, requestedDates,
                            data.tgl_dari_iso, data.tgl_sampai_iso);
                    } else {
                        $('#detailCalendarSection').hide();
                    }

                    $('#detailModal').modal('show');
                },
                error: function(err) {
                    console.error(err);
                    alert('Gagal memuat detail pengajuan');
                }
            });
        }

        function formatDateForDisplay(isoDateStr) {
            if (!isoDateStr) return '-';
            var p = String(isoDateStr).split('-');
            if (p.length !== 3) return isoDateStr;
            return p[2] + '-' + p[1] + '-' + p[0];
        }

        function buildRejectedDates(requestedDates, approvedDates, statusApproved) {
            var allDates = Array.isArray(requestedDates) ? requestedDates : [];
            if (!allDates.length) return [];

            // Pending: semua tetap "Diajukan" (biru), belum ada merah.
            if (parseInt(statusApproved, 10) === 0) return [];

            // Ditolak: semua tanggal yang diajukan jadi merah.
            if (parseInt(statusApproved, 10) === 2) return allDates;

            // Disetujui (termasuk partial): hanya yang tidak disetujui jadi merah.
            if (!approvedDates || approvedDates.length === 0) return allDates;
            return allDates.filter(function(dateStr) {
                return !approvedDates.includes(dateStr);
            });
        }

        function getDatesInRange(startDateStr, endDateStr) {
            var dates = [];
            var startDate = new Date(startDateStr);
            var endDate = new Date(endDateStr);
            while (startDate <= endDate) {
                var y = startDate.getFullYear();
                var m = String(startDate.getMonth() + 1).padStart(2, '0');
                var d = String(startDate.getDate()).padStart(2, '0');
                dates.push(y + '-' + m + '-' + d);
                startDate.setDate(startDate.getDate() + 1);
            }
            return dates;
        }

        function initCombinedDatepicker(selector, approvedDates, rejectedDates, requestedDates, startDateIso, endDateIso) {
            try {
                $(selector).datepicker('destroy');
            } catch (e) {}

            var requestedLookup = {};
            (requestedDates || []).forEach(function(dateStr) {
                requestedLookup[dateStr] = true;
            });

            var minDate = (requestedDates && requestedDates.length) ? requestedDates[0] : startDateIso;
            var maxDate = (requestedDates && requestedDates.length) ? requestedDates[requestedDates.length - 1] :
                endDateIso;

            $(selector).datepicker({
                format: 'yyyy-mm-dd',
                todayHighlight: false,
                startDate: new Date(minDate),
                endDate: new Date(maxDate),
                // endDate: new Date(endDateIso), // Optional: batasi navigasi akhir (dihapus agar user bebas geser bulan)
                language: 'id',
                beforeShowDay: function(date) {
                    var y = date.getFullYear();
                    var m = String(date.getMonth() + 1).padStart(2, '0');
                    var d = String(date.getDate()).padStart(2, '0');
                    var currDateStr = y + '-' + m + '-' + d;

                    if (Object.keys(requestedLookup).length && !requestedLookup[currDateStr]) {
                        return {
                            enabled: false,
                            classes: 'disabled'
                        };
                    }

                    if (approvedDates.includes(currDateStr)) {
                        return {
                            classes: 'day-approved'
                        };
                    }
                    if (rejectedDates.includes(currDateStr)) {
                        return {
                            classes: 'day-rejected'
                        };
                    }
                    return {
                        classes: 'day-requested'
                    };
                }
            });
            // Set view ke tanggal mulai cuti
            $(selector).datepicker('setDate', new Date(minDate));
        }

        $(function() {
            if (autoOpenDetailIzin) {
                setTimeout(function() {
                    viewDetail(autoOpenDetailIzin);
                }, 250);

                if (window.history && typeof window.history.replaceState === 'function') {
                    var cleanUrl = window.location.pathname + window.location.search.replace(/[?&]detail_izin=[^&]*/,
                        '').replace(/^&/, '?').replace(/\?$/, '');
                    window.history.replaceState({}, document.title, cleanUrl || window.location.pathname);
                }
            }

            $(".delete-button").click(function(e) {
                var form = $(this).closest('form');
                Swal.fire({
                    title: 'Batalkan Pengajuan?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#094b87',
                    cancelButtonColor: '#333',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                })
            });

            $('#bulan, #tahun').change(function() {
                $(this).closest('form').submit();
            });

            const fabBtn = document.getElementById('fabMainBtn');
            const fabMenu = document.getElementById('fabMenu');
            const fabOverlay = document.getElementById('fabOverlay');
            const detailModal = document.getElementById('detailModal');

            function toggleFab() {
                fabBtn.classList.toggle('active');
                fabMenu.classList.toggle('active');
                fabOverlay.classList.toggle('active');
            }

            function closeFab() {
                fabBtn.classList.remove('active');
                fabMenu.classList.remove('active');
                fabOverlay.classList.remove('active');
            }

            fabBtn.addEventListener('click', toggleFab);
            fabOverlay.addEventListener('click', toggleFab);

            if (detailModal) {
                detailModal.addEventListener('shown.bs.modal', function() {
                    closeFab();
                    document.body.classList.add('detail-modal-open');
                });

                detailModal.addEventListener('hidden.bs.modal', function() {
                    document.body.classList.remove('detail-modal-open');
                });
            }
        });
    </script>
@endpush
