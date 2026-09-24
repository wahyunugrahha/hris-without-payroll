@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

    @php
        $details = $details ?? collect([]);
    @endphp

    <div class="presensi-header">
        <a href="{{ route('kpi.atasan.index', ['nik' => $nikBack, 'bulan' => $bulanBack, 'tahun' => $tahunBack]) }}"
            class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Detail KPI Karyawan</span>
        <div class="header-spacer" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="main-container">

        {{-- ALERT ERROR --}}
        @if (session('error'))
            <div class="alert alert-soft-danger d-flex align-items-center mb-3">
                <ion-icon name="alert-circle-outline" style="font-size:24px; margin-right:10px;"></ion-icon>
                <div>
                    <strong>Gagal</strong><br>{{ session('error') }}
                </div>
            </div>
        @endif

        {{-- USER INFO & STATUS CARD --}}
        <div class="card-section mb-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h4 class="user-name">{{ $kpiDaily->karyawan->nama_lengkap }}</h4>
                    <span class="user-role">{{ $kpiDaily->karyawan->jabatanRel->nama_jabatan ?? '-' }}</span>
                    <div class="user-dept mt-1">
                        <ion-icon name="business-outline"></ion-icon>
                        {{ $kpiDaily->karyawan->departemen->nama_dept ?? '-' }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="date-badge mb-2">
                        <span class="d-block day">{{ \Carbon\Carbon::parse($kpiDaily->tanggal)->format('d') }}</span>
                        <span
                            class="d-block month">{{ \Carbon\Carbon::parse($kpiDaily->tanggal)->translatedFormat('M') }}</span>
                    </div>
                </div>
            </div>

            {{-- STATUS BAR --}}
            <div class="mt-2 pt-2 border-top">
                <div class="d-flex flex-wrap gap-2 align-items-center">

                    {{-- 1. Status Utama --}}
                    @if ($kpiDaily->status == 'rejected')
                        <div class="status-pill status-danger">
                            <ion-icon name="alert-circle"></ion-icon> Ditolak / Revisi
                        </div>
                    @elseif ($kpiDaily->status == 'submitted')
                        <div class="status-pill status-warning">
                            <ion-icon name="time"></ion-icon> Menunggu Approval
                        </div>
                    @elseif ($kpiDaily->status == 'draft')
                        <div class="status-pill status-secondary">
                            <ion-icon name="document-text"></ion-icon> Draft
                        </div>
                    @endif

                    {{-- 2. Riwayat Approval --}}
                    @if (!empty($kpiDaily->approve_atasan))
                        <div class="status-pill status-success">
                            <ion-icon name="checkmark-done-circle"></ion-icon> Disetujui Atasan: {{ $namaAtasan }}
                        </div>
                    @endif

                    @if (!empty($kpiDaily->approve_hr))
                        <div class="status-pill status-primary">
                            <ion-icon name="ribbon"></ion-icon> Disetujui HR: {{ $namaHR }} (Final)
                        </div>
                    @endif

                </div>

                {{-- 3. Catatan Reject --}}
                @if ($kpiDaily->status == 'rejected' && !empty($kpiDaily->alasan_reject))
                    <div class="mt-2 text-left p-2 rounded"
                        style="background-color: #fff2f2; border: 1px dashed #dc3545; font-size: 13px; line-height: 1.4;">
                        <span style="color: #dc3545; font-weight: bold;">
                            <ion-icon name="create-outline" style="vertical-align: -2px; margin-right: 2px;"></ion-icon>
                            Catatan Perbaikan:
                        </span>
                        <span class="text-dark" style="white-space: pre-line;">
                            {{ $kpiDaily->alasan_reject }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- PROGRESS BAR CARD (Harian Karyawan) --}}
        <div class="card-section mb-3">
            <div class="d-flex justify-content-between align-items-end mb-2">
                <span class="section-label">Progress Capaian Harian</span>
                <span id="dailyProgressPercent" class="progress-percent">0%</span>
            </div>
            <div class="progress custom-progress">
                <div id="dailyProgressBar" class="progress-bar" style="width:0%;"></div>
            </div>
            <div class="text-right mt-2">
                <small class="text-muted">Item Selesai: <span id="dailyTaskCountText"
                        class="text-dark font-weight-bold">0</span></small>
            </div>
        </div>

        {{-- LIST DETAIL KPI HARIAN KARYAWAN (HANYA READ ONLY) --}}
        <div class="list-wrapper">
            @foreach ($kpiDaily->kpiDailyDetail as $detail)
                @php
                    $isChecked = $detail->is_checked == 1;
                    $namaIndikator = $detail->kpiMasterDetail->indikator ?? 'Indikator tidak ditemukan';
                    $bobot = $detail->kpiMasterDetail->bobot ?? ($detail->kpiMasterDetail->score_indikator ?? 0);
                @endphp

                <div class="task-card {{ $isChecked ? 'checked' : '' }}">
                    <div class="task-header mb-0">
                        <label class="custom-checkbox-container" style="pointer-events: none;">
                            <input class="task-check daily-check" type="checkbox" {{ $isChecked ? 'checked' : '' }}
                                disabled data-bobot="{{ $bobot }}">
                            <span class="checkmark">
                                <ion-icon name="checkmark-outline"></ion-icon>
                            </span>
                        </label>
                        <div class="task-title-wrapper">
                            <span class="task-title">{{ $namaIndikator }}</span>
                        </div>
                    </div>

                    @if (!empty($detail->catatan))
                        <div class="note-preview mt-2">
                            <ion-icon name="document-text-outline"></ion-icon>
                            <span>{{ $detail->catatan }}</span>
                        </div>
                    @endif

                    @if ($detail->bukti_foto)
                        <div class="photo-area mt-3">
                            <div class="existing-photo-card">
                                <img src="{{ asset_v('storage/' . $detail->bukti_foto) }}" class="existing-img"
                                    onerror="this.onerror=null;this.src='{{ asset_v('assets/img/nophoto.png') }}';">
                                <div class="photo-badge">
                                    <ion-icon name="image"></ion-icon> Bukti Foto
                                </div>
                                <div class="photo-overlay">
                                    <a href="{{ asset_v('storage/' . $detail->bukti_foto) }}" target="_blank"
                                        class="btn-view">Lihat Foto</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- KPI EKSTRA HARIAN --}}
        @if ($kpiDaily->kpiDailyExtra && $kpiDaily->kpiDailyExtra->count() > 0)
            <div class="card-section mb-3 mt-3">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2" style="border-bottom: 1px dashed #e2e8f0;">
                    <span class="section-label m-0" style="color: #334155; font-weight: 700;">KPI Kegiatan Tambahan</span>
                </div>

                <div class="extra-list">
                    @foreach ($kpiDaily->kpiDailyExtra as $extra)
                        <div class="extra-item"
                            style="padding-bottom: {{ $loop->last ? '0' : '12px' }}; border-bottom: {{ $loop->last ? 'none' : '1px solid #f1f5f9' }}; margin-bottom: {{ $loop->last ? '0' : '12px' }};">

                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div style="flex: 1; padding-right: 10px;">
                                    <span style="color: #64748b; font-size: 12px; display: block; margin-bottom: 2px;">
                                        Nama Kegiatan:
                                    </span>
                                    <span
                                        style="color: #1e293b; font-size: 14px; font-weight: 600; line-height: 1.3; display: block;">
                                        {{ $extra->indikator_tambahan }}
                                    </span>
                                </div>
                                <div>
                                    <span class="badge"
                                        style="background: #eef6fd; color: #2d6ea6; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600; white-space: nowrap;">
                                        +{{ $extra->score ?? 10 }} Poin
                                    </span>
                                </div>
                            </div>

                            @if ($extra->catatan)
                                <div class="mt-2"
                                    style="background: #f8fafc; border-radius: 6px; padding: 8px 10px; display: flex; gap: 6px; align-items: flex-start;">
                                    <ion-icon name="document-text-outline"
                                        style="color: #94a3b8; font-size: 14px; margin-top: 2px; flex-shrink: 0;"></ion-icon>
                                    <div style="color: #64748b; font-size: 12px; line-height: 1.4;">
                                        <span style="font-weight: 600; color: #475569;">Catatan:</span>
                                        {{ $extra->catatan }}
                                    </div>
                                </div>
                            @else
                                <div class="mt-1 d-flex align-items-center gap-1"
                                    style="color: #94a3b8; font-size: 11px; font-style: italic;">
                                    <ion-icon name="remove-outline"></ion-icon> Tidak ada catatan
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- FORM INDIKATOR ATASAN & TOMBOL APPROVAL --}}
        <form id="formApproveKPI" action="{{ route('kpi.atasan.approve', $kpiDaily->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf

            @if (isset($indikators) && count($indikators) > 0)
                <div class="list-wrapper mt-4">
                    <h6 class="mb-2"
                        style="font-size: 13px; font-weight: 700; color: #094b87; text-transform: uppercase;">
                        Formulir Penilaian Atasan
                    </h6>

                    @foreach ($indikators as $item)
                        @php
                            $detailItem = $details->get($item->id);
                            $isChecked = $detailItem && $detailItem->is_checked == 1;
                            $catatan = $detailItem ? $detailItem->catatan : '';
                            $buktiFoto = $detailItem ? $detailItem->bukti_foto : null;
                            $score = $detailItem ? $detailItem->score : 0;

                            $disabled = $isApproved ? 'disabled' : '';
                            $pointerEvent = $isApproved ? 'none' : 'auto';
                        @endphp

                        <div class="task-card {{ $isChecked ? 'checked' : '' }}" id="card-{{ $item->id }}">
                            {{-- HEADER CHECKBOX --}}
                            <div class="task-header mb-0 d-flex align-items-center">
                                <label class="custom-checkbox-container m-0" style="pointer-events: {{ $pointerEvent }}">
                                    <input type="hidden" name="kpi[{{ $item->id }}][is_checked]" value="0">
                                    <input class="task-check atasan-check" type="checkbox"
                                        id="check-{{ $item->id }}" name="kpi[{{ $item->id }}][is_checked]"
                                        value="1" data-id="{{ $item->id }}" {{ $isChecked ? 'checked' : '' }}
                                        {{ $disabled }}>
                                    <span class="checkmark {{ $isApproved ? 'disabled-mark' : '' }}">
                                        <ion-icon name="checkmark-outline"></ion-icon>
                                    </span>
                                </label>
                                <div class="task-title-wrapper ms-3">
                                    <label for="check-{{ $item->id }}" class="task-title m-0"
                                        style="cursor: pointer;">{{ $item->indikator }}</label>
                                </div>
                            </div>

                            {{-- Hidden Input untuk Score (Agar dikirim ke form) --}}
                            <input type="hidden" name="kpi[{{ $item->id }}][score]" id="score-{{ $item->id }}"
                                value="{{ $score }}">

                            @if ($isApproved)
                                @if (!empty($catatan))
                                    <div class="note-preview mt-2 d-flex">
                                        <ion-icon name="document-text-outline"></ion-icon>
                                        <span>{{ $catatan }}</span>
                                    </div>
                                @endif

                                @if ($buktiFoto)
                                    <div class="photo-area mt-3">
                                        <div class="existing-photo-card mb-2">
                                            <img src="{{ asset_v('storage/' . $buktiFoto) }}" class="existing-img"
                                                onerror="this.onerror=null;this.src='{{ asset_v('assets/img/nophoto.png') }}';">
                                            <div class="photo-badge"><ion-icon name="image"></ion-icon> Tersimpan</div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="note-preview mt-2" id="note-display-{{ $item->id }}"
                                    style="{{ empty($catatan) ? 'display:none;' : 'display:flex;' }}">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                    <span id="note-preview-{{ $item->id }}">{{ $catatan }}</span>
                                </div>

                                <div class="photo-area mt-3">
                                    @if ($buktiFoto)
                                        <div id="existing-foto-box-{{ $item->id }}" class="existing-photo-card mb-2">
                                            <img src="{{ asset_v('storage/' . $buktiFoto) }}" class="existing-img"
                                                onerror="this.onerror=null;this.src='{{ asset_v('assets/img/nophoto.png') }}';">
                                            <div class="photo-badge"><ion-icon name="image"></ion-icon> Tersimpan</div>
                                        </div>
                                    @endif

                                    <div id="preview-container-{{ $item->id }}" class="photo-preview-box mb-2"
                                        style="display: none;">
                                        <img id="preview-img-{{ $item->id }}" src="" class="preview-img">
                                        <button type="button" class="btn-remove-photo"
                                            onclick="removeFoto(event, '{{ $item->id }}')">
                                            <ion-icon name="close-outline"></ion-icon>
                                        </button>
                                    </div>
                                </div>

                                {{-- TOMBOL AKSI ATASAN --}}
                                <div class="action-buttons mt-3 pt-3 border-top">
                                    <div class="btn-action"
                                        onclick="addNote('{{ $item->id }}', '{{ addslashes($item->indikator) }}')">
                                        <ion-icon name="create-outline"></ion-icon>
                                        <span
                                            id="btn-text-{{ $item->id }}">{{ $catatan ? 'Ubah Catatan' : 'Beri Catatan' }}</span>
                                        <input type="hidden" name="kpi[{{ $item->id }}][catatan]"
                                            id="input-note-{{ $item->id }}" value="{{ $catatan }}">
                                    </div>

                                    <div class="btn-action"
                                        onclick="document.getElementById('input-foto-{{ $item->id }}').click()">
                                        <ion-icon name="camera-outline"></ion-icon>
                                        <span
                                            id="foto-text-{{ $item->id }}">{{ $buktiFoto ? 'Ganti Foto' : 'Upload Bukti' }}</span>
                                        <input type="file" name="kpi[{{ $item->id }}][foto]"
                                            id="input-foto-{{ $item->id }}" accept="image/*" style="display: none;"
                                            onchange="previewFoto('{{ $item->id }}')">
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endforeach
                </div>
            @endif

            <div class="footer-fixed">
                @if ($kpiDaily->status == 'submitted')
                    <div class="row">
                        <div class="col-6">
                            <button type="button" class="btn-footer btn-reject" onclick="rejectAction()">
                                <ion-icon name="close-circle-outline"></ion-icon> Tolak / Revisi
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn-footer btn-approve" onclick="confirmApprove()">
                                <ion-icon name="checkmark-circle-outline"></ion-icon> Setujui
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-soft-secondary text-center m-0 p-2"
                        style="font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        @if ($kpiDaily->status == 'draft')
                            <ion-icon name="document-text-outline" style="font-size: 16px;"></ion-icon>
                            <span>Status masih <strong>Draft</strong> (Belum dikirim).</span>
                        @elseif ($kpiDaily->status == 'rejected')
                            <ion-icon name="alert-circle-outline" style="font-size: 16px; color: #c62828;"></ion-icon>
                            <span style="color: #c62828;">KPI ini sudah Anda <strong>Tolak/Revisi</strong>.</span>
                        @elseif ($kpiDaily->status == 'approved_by_atasan')
                            <ion-icon name="checkmark-done-circle-outline"
                                style="font-size: 16px; color: #27ae60;"></ion-icon>
                            <span style="color: #27ae60;">KPI ini sudah Anda <strong>Setujui</strong>.</span>
                        @elseif ($kpiDaily->status == 'approved_by_hr')
                            <ion-icon name="ribbon-outline" style="font-size: 16px; color: #2980b9;"></ion-icon>
                            <span style="color: #2980b9;">KPI ini sudah <strong>Final</strong> (Disetujui HR).</span>
                        @endif
                    </div>
                @endif
            </div>
        </form>

        {{-- Hidden Form for Reject --}}
        <form id="formReject" action="{{ route('kpi.atasan.reject', $kpiDaily->id) }}" method="POST"
            style="display: none;">
            @csrf
            <input type="hidden" name="alasan_reject" id="alasan_reject">
        </form>

    </div>
@endsection

@push('myscript')
    <style>
        /* Base Layout */
        body {
            background-color: #f4f7fa;
            font-family: 'Inter', sans-serif;
            padding-bottom: 90px;
        }

        .main-container {
            padding: 20px 15px;
        }

        /* Header Style */
        .presensi-header {
            background: rgba(27, 122, 111, 1);
            padding: 20px 15px;
            display: flex;
            align-items: center;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.25);
        }

        .header-title {
            flex: 1;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
            color: #ffffff;
        }

        .goBack ion-icon {
            font-size: 24px;
            color: #ffffff;
        }

        /* Alerts & Badges */
        .alert-soft-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 12px;
            font-size: 13px;
        }

        .alert-soft-secondary {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .date-badge {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 12px;
            text-align: center;
            min-width: 60px;
        }

        .date-badge .day {
            font-size: 18px;
            font-weight: 800;
            color: #334155;
            line-height: 1;
        }

        .date-badge .month {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 600;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            height: fit-content;
        }

        .status-primary {
            background: #e3f2fd;
            color: #1565c0;
        }

        .status-success {
            background: #e8f2fb;
            color: #063a6b;
        }

        .status-warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-secondary {
            background: #f1f5f9;
            color: #475569;
        }

        /* Card Sections */
        .card-section {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        .user-name {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .user-role {
            font-size: 13px;
            color: #64748b;
        }

        .user-dept {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #27ae60;
            background: #eef6fd;
            padding: 4px 8px;
            border-radius: 6px;
            width: fit-content;
        }

        /* Progress Bar */
        .section-label {
            font-weight: 600;
            font-size: 14px;
            color: #475569;
        }

        .progress-percent {
            font-weight: 800;
            color: #27ae60;
            font-size: 16px;
        }

        .custom-progress {
            height: 10px;
            background-color: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #27ae60;
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        /* Task Card */
        .task-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .task-card.checked {
            border-color: #27ae60;
            background-color: #eef6fd;
        }

        .task-header {
            display: flex;
            align-items: flex-start;
        }

        .task-title-wrapper {
            margin-left: 12px;
            flex: 1;
        }

        .task-title {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            line-height: 1.4;
            margin: 0;
            cursor: pointer;
        }

        /* Custom Checkbox */
        .custom-checkbox-container {
            display: block;
            position: relative;
            cursor: pointer;
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            user-select: none;
        }

        .custom-checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 24px;
            width: 24px;
            background-color: #e2e8f0;
            border-radius: 8px;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .custom-checkbox-container input:checked~.checkmark {
            background-color: #27ae60;
        }

        .custom-checkbox-container input:checked~.checkmark ion-icon {
            display: block;
        }

        .checkmark ion-icon {
            font-size: 16px;
            display: none;
        }

        .checkmark.disabled-mark {
            background-color: #cbd5e1;
            cursor: not-allowed;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            background: #f8fafc;
            padding: 10px;
            border-radius: 10px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 10px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-value {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .stat-item.active .stat-value {
            color: #27ae60;
        }

        /* Note Display */
        .note-preview {
            font-size: 12px;
            color: #64748b;
            background: #fffbeb;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px dashed #fcd34d;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }

        .note-preview ion-icon {
            font-size: 14px;
            color: #d97706;
            margin-top: 2px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-action:active {
            background: #f1f5f9;
            transform: scale(0.98);
        }

        /* Photo Preview */
        .existing-photo-card {
            position: relative;
            width: fit-content;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }

        .existing-img {
            max-height: 120px;
            display: block;
        }

        .photo-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            font-size: 10px;
            padding: 4px;
            text-align: center;
            backdrop-filter: blur(2px);
        }

        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: 0.3s;
        }

        .existing-photo-card:hover .photo-overlay {
            opacity: 1;
        }

        .btn-view {
            background: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }

        .photo-preview-box {
            position: relative;
            width: fit-content;
        }

        .preview-img {
            max-height: 120px;
            border-radius: 8px;
            border: 2px solid #27ae60;
        }

        .btn-remove-photo {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2;
        }

        /* Footer */
        .footer-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 15px 20px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.05);
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            z-index: 999;
        }

        .btn-footer {
            width: 100%;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-approve {
            background: linear-gradient(135deg, #2d6ea6 0%, #094b87 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
        }

        .btn-approve:active {
            background: linear-gradient(135deg, #094b87 0%, #063a6b 100%);
        }

        .btn-reject {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .btn-reject:active {
            background: #ffcdd2;
        }
    </style>

    <script>
        // 1. UPDATE PROGRESS UNTUK KPI HARIAN (KARYAWAN)
        function updateDailyProgress() {
            let totalBobot = 0;
            let bobotTercapai = 0;
            let totalItem = 0;
            let checkedItem = 0;

            const dailyChecks = document.querySelectorAll('.daily-check');
            dailyChecks.forEach(check => {
                let b = parseFloat(check.dataset.bobot) || 0;
                totalBobot += b;
                totalItem++;

                if (check.checked) {
                    bobotTercapai += b;
                    checkedItem++;
                }
            });

            let percent = totalBobot > 0 ? Math.round((bobotTercapai / totalBobot) * 100) : 0;
            if (percent > 100) percent = 100;

            document.getElementById('dailyProgressBar').style.width = percent + '%';
            document.getElementById('dailyProgressPercent').innerText = percent + '%';
            document.getElementById('dailyTaskCountText').innerText = checkedItem + ' / ' + totalItem;
        }

        // 2. UPDATE PROGRESS UNTUK KPI ATASAN
        function updateAtasanProgress() {
            let totalBobot = 0;
            let bobotTercapai = 0;

            const atasanChecks = document.querySelectorAll('.atasan-check');
            atasanChecks.forEach(check => {
                let b = parseFloat(check.dataset.bobot) || 0;
                totalBobot += b;
                const scoreInput = document.getElementById('score-' + check.dataset.id);
                const card = document.getElementById('card-' + check.dataset.id);

                if (check.checked) {
                    bobotTercapai += b;
                    scoreInput.value = b;
                    card.classList.add('checked');
                } else {
                    scoreInput.value = 0;
                    card.classList.remove('checked');
                }
            });

            let percent = totalBobot > 0 ? Math.round((bobotTercapai / totalBobot) * 100) : 0;
            if (percent > 100) percent = 100;

            document.getElementById('atasanProgressBar').style.width = percent + '%';
            document.getElementById('atasanProgressPercent').innerText = percent + '%';
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateDailyProgress(); // Hitung form Karyawan (Read Only)
        });

        // 3. ADD NOTE (SWEETALERT) UNTUK ATASAN
        function addNote(id, title) {
            const currentNote = document.getElementById('input-note-' + id).value;

            Swal.fire({
                title: 'Beri Catatan',
                html: '<div style="text-align:left; font-size:13px; color:#64748b; margin-bottom:5px;">Catatan (Opsional)</div>' +
                    '<textarea id="swal-input-note" class="swal2-textarea" placeholder="Tulis catatan di sini..." style="margin:0;">' +
                    currentNote + '</textarea>',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    return document.getElementById('swal-input-note').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const catatan = result.value;
                    document.getElementById('input-note-' + id).value = catatan;

                    // Update tampilan tombol text
                    const btnText = document.getElementById('btn-text-' + id);
                    if (catatan.trim() !== "") {
                        btnText.innerText = "Ubah Catatan";
                        btnText.parentElement.style.borderColor = "#27ae60";
                        btnText.parentElement.style.color = "#27ae60";
                    } else {
                        btnText.innerText = "Beri Catatan";
                        btnText.parentElement.style.borderColor = "#e2e8f0";
                        btnText.parentElement.style.color = "#64748b";
                    }

                    // Update UI Preview Note
                    const notePreviewBox = document.getElementById('note-display-' + id);
                    const noteTextEl = document.getElementById('note-preview-' + id);
                    if (catatan.trim() !== "") {
                        notePreviewBox.style.display = 'flex';
                        noteTextEl.innerText = catatan;
                    } else {
                        notePreviewBox.style.display = 'none';
                    }
                }
            });
        }

        // 4. FOTO LOGIC UNTUK ATASAN
        function previewFoto(id) {
            const input = document.getElementById('input-foto-' + id);
            const previewContainer = document.getElementById('preview-container-' + id);
            const previewImg = document.getElementById('preview-img-' + id);
            const existingFotoBox = document.getElementById('existing-foto-box-' + id);
            const btnText = document.getElementById('foto-text-' + id);

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                    if (existingFotoBox) existingFotoBox.style.display = 'none';
                    btnText.innerText = 'Ganti';
                    btnText.parentElement.style.borderColor = "#3b82f6";
                    btnText.parentElement.style.color = "#3b82f6";
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeFoto(event, id) {
            event.preventDefault();
            document.getElementById('input-foto-' + id).value = '';
            document.getElementById('preview-container-' + id).style.display = 'none';
            const existingFotoBox = document.getElementById('existing-foto-box-' + id);
            if (existingFotoBox) {
                existingFotoBox.style.display = 'block';
                document.getElementById('foto-text-' + id).innerText = 'Ganti Foto';
            } else {
                document.getElementById('foto-text-' + id).innerText = 'Upload Bukti';
            }
            const btnText = document.getElementById('foto-text-' + id);
            btnText.parentElement.style.borderColor = "#e2e8f0";
            btnText.parentElement.style.color = "#64748b";
        }

        // 5. SUBMIT & REJECT ACTIONS
        function rejectAction() {
            Swal.fire({
                title: 'Tolak / Revisi KPI',
                text: "Berikan alasan penolakan agar karyawan dapat merevisi.",
                input: 'textarea',
                inputPlaceholder: 'Tulis alasan revisi di sini...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Kirim Revisi',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Alasan revisi wajib diisi!'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('alasan_reject').value = result.value;
                    document.getElementById('formReject').submit();
                }
            });
        }

        function confirmApprove() {
            Swal.fire({
                title: 'Setujui KPI?',
                text: "Pastikan Anda sudah mengecek dan memberi nilai pada form jika ada.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Ya, Setujui',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formApproveKPI').submit();
                }
            });
        }
    </script>
@endpush

