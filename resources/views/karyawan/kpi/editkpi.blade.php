@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

    @php
        $details = $details ?? collect([]);
    @endphp

    <div class="presensi-header">
        <a href="{{ route('kpi.user.index') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Edit Workbook Harian</span>
        <div class="header-spacer" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="main-container">

        <form action="{{ route('kpi.user.update', $kpiDaily->id) }}" method="POST" id="formWorkbook"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ALERT ERROR --}}
            @if (session('error'))
                <div class="alert alert-soft-danger d-flex align-items-center mb-3">
                    <ion-icon name="alert-circle-outline" style="font-size:24px; margin-right:10px;"></ion-icon>
                    <div>
                        <strong>Perhatian</strong><br>{{ session('error') }}
                    </div>
                </div>
            @endif

            {{-- USER INFO & STATUS CARD --}}
            <div class="card-section mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h4 class="user-name">{{ $user->nama_lengkap }}</h4>
                        <span class="user-role">{{ $user->jabatanRel->nama_jabatan ?? '-' }}</span>
                        <div class="user-dept mt-1">
                            <ion-icon name="business-outline"></ion-icon>
                            {{ $user->departemen->nama_dept ?? '-' }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="date-badge mb-2">
                            <span class="d-block day">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</span>
                            <span class="d-block month">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('M') }}</span>
                        </div>
                    </div>
                </div>

                {{-- STATUS BAR --}}
                <div class="mt-2 pt-2 border-top">
                    {{-- Container flex agar pill bisa bersebelahan --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center">

                        {{-- 1. Status Utama (Draft / Submitted / Rejected) --}}
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

                        {{-- 2. Tampilkan semua riwayat Approval yang ada --}}
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

                    {{-- 3. Catatan Reject (Diletakkan di bawah pill agar rapi) --}}
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

            {{-- PROGRESS BAR CARD --}}
            <div class="card-section mb-3">
                <div class="d-flex justify-content-between align-items-end mb-2">
                    <span class="section-label">Progress Capaian</span>
                    <span id="progressPercent" class="progress-percent">0%</span>
                </div>
                <div class="progress custom-progress">
                    <div id="progressBar" class="progress-bar" style="width:0%;"></div>
                </div>
                <div class="text-right mt-2">
                    <small class="text-muted">Item Selesai: <span id="taskCountText" class="text-dark font-weight-bold">0 /
                            {{ count($indikators) }}</span></small>
                </div>
            </div>

            {{-- LIST INDIKATOR --}}
            <div class="list-wrapper">
                @foreach ($indikators as $item)
                    @php
                        $detailItem = $details->get($item->id);

                        $isChecked = $detailItem && $detailItem->is_checked == 1;
                        $catatan = $detailItem ? $detailItem->catatan : '';
                        $buktiFoto = $detailItem ? $detailItem->bukti_foto : null;
                        $score = $detailItem ? $detailItem->score : 0;

                        // Mode Read Only
                        $disabled = $isApproved ? 'disabled' : '';
                        $pointerEvent = $isApproved ? 'none' : 'auto';
                    @endphp

                    <div class="task-card {{ $isChecked ? 'checked' : '' }}" id="card-{{ $item->id }}">
                        {{-- Header: Checkbox & Title --}}
                        <div class="task-header mb-0 d-flex align-items-center">
                            <label class="custom-checkbox-container m-0" style="pointer-events: {{ $pointerEvent }}">
                                <input class="task-check" type="checkbox" id="check-{{ $item->id }}"
                                    name="kpi[{{ $item->id }}][is_checked]" value="1"
                                    data-id="{{ $item->id }}" data-bobot="{{ $item->score_indikator }}"
                                    {{ $isChecked ? 'checked' : '' }} {{ $disabled }}>
                                <span class="checkmark {{ $isApproved ? 'disabled-mark' : '' }}">
                                    <ion-icon name="checkmark-outline"></ion-icon>
                                </span>
                            </label>
                            <div class="task-title-wrapper ms-3">
                                <label for="check-{{ $item->id }}" class="task-title m-0"
                                    style="cursor: pointer;">{{ $item->indikator }}</label>
                            </div>
                        </div>

                        {{-- Hidden Input untuk Score --}}
                        <input type="hidden" name="kpi[{{ $item->id }}][score]" id="score-{{ $item->id }}"
                            value="{{ $score }}">

                        {{-- LOGIKA TAMPILAN BERDASARKAN STATUS APPROVAL --}}
                        @if ($isApproved)
                            {{-- Hanya tampilkan catatan/foto JIKA ADA isinya --}}
                            @if (!empty($catatan))
                                <div class="note-preview mt-3 d-flex">
                                    <ion-icon name="document-text-outline"></ion-icon>
                                    <span>{{ $catatan }}</span>
                                </div>
                            @endif

                            @if ($buktiFoto)
                                <div class="photo-area mt-3">
                                    <div class="existing-photo-card mb-0">
                                        <img src="{{ asset_v('storage/' . $buktiFoto) }}" class="existing-img"
                                            onerror="this.onerror=null;this.src='{{ asset_v('assets/img/nophoto.png') }}';">
                                        <div class="photo-badge"><ion-icon name="image"></ion-icon> Tersimpan</div>
                                        <div class="photo-overlay">
                                            <a href="{{ asset_v('storage/' . $buktiFoto) }}" target="_blank"
                                                class="btn-view">Lihat</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @else
                            {{-- TAMPILAN MODE EDIT (Bisa diubah oleh karyawan) --}}
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
                                        <div class="photo-overlay">
                                            <a href="{{ asset_v('storage/' . $buktiFoto) }}" target="_blank"
                                                class="btn-view">Lihat</a>
                                        </div>
                                    </div>
                                @endif

                                <div id="preview-container-{{ $item->id }}" class="photo-preview-box mb-0"
                                    style="display: none;">
                                    <img id="preview-img-{{ $item->id }}" src="" class="preview-img">
                                    <button type="button" class="btn-remove-photo"
                                        onclick="removeFoto(event, '{{ $item->id }}')">
                                        <ion-icon name="close-outline"></ion-icon>
                                    </button>
                                    <div class="photo-badge new-badge">Baru</div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="action-buttons mt-3 pt-3 border-top">
                                <div class="btn-action"
                                    onclick="addNote('{{ $item->id }}', '{{ addslashes($item->indikator) }}')">
                                    <ion-icon name="create-outline"></ion-icon>
                                    <span id="btn-text-{{ $item->id }}">
                                        {{ $catatan ? 'Ubah Catatan' : 'Masukkan Catatan' }}
                                    </span>
                                    <input type="hidden" name="kpi[{{ $item->id }}][catatan]"
                                        id="input-note-{{ $item->id }}" value="{{ $catatan }}">
                                </div>

                                <div class="btn-action"
                                    onclick="document.getElementById('input-foto-{{ $item->id }}').click()">
                                    <ion-icon name="camera-outline"></ion-icon>
                                    <span id="foto-text-{{ $item->id }}">
                                        {{ $buktiFoto ? 'Ganti Foto' : 'Upload Foto' }}
                                    </span>
                                    <input type="file" name="kpi[{{ $item->id }}][foto]"
                                        id="input-foto-{{ $item->id }}" accept="image/*" style="display: none;"
                                        onchange="previewFoto('{{ $item->id }}')">
                                </div>
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>

            {{-- KPI EXTRA BUTTONS --}}
            <div class="extra-section mt-4 mb-5">
                @if (!$isApproved)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="section-label">KPI Tambahan (Opsional)</span>
                    </div>
                @endif

                {{-- Container untuk menampung Item Extra --}}
                <div id="extra-kpi-container">
                    {{-- Item akan muncul di sini via JS --}}
                </div>

                {{-- Tombol Tambah --}}
                @if (!$isApproved)
                    <button type="button" class="btn-add-extra" id="btnShowExtraModal" onclick="openExtraModal()">
                        <ion-icon name="add-circle-outline"></ion-icon>
                        Tambah Kegiatan KPI Tambahan
                    </button>
                @endif
            </div>

            {{-- FOOTER BUTTONS --}}
            <div class="footer-fixed">
                @if (!$isApproved)
                    <div class="row">
                        <div class="col-12">
                            <button class="btn-footer btn-submit" type="submit" name="action_type" value="submitted">
                                <ion-icon name="save-outline"></ion-icon> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-soft-secondary text-center m-0 p-2"
                        style="font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <ion-icon name="lock-closed-outline" style="font-size: 16px;"></ion-icon>
                        @if ($kpiDaily->status == 'approved_by_atasan')
                            <span>Data sudah <strong>Disetujui Atasan</strong>, tidak dapat diubah.</span>
                        @elseif ($kpiDaily->status == 'approved_by_hr')
                            <span>Data sudah <strong>Disetujui HR (Final)</strong>, tidak dapat diubah.</span>
                        @else
                            <span>Data sudah disetujui, tidak dapat diubah.</span>
                        @endif
                    </div>
                @endif
            </div>
        </form>
    </div>
@endsection

@push('myscript')
    <style>
        /* Base Layout (Sama dengan Input Page) */
        body {
            background-color: #f4f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            padding-bottom: 90px;
        }

        .main-container {
            padding: 20px 15px;
        }

        /* Header */
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
            letter-spacing: 0.5px;
        }

        .goBack ion-icon {
            font-size: 24px;
            color: #ffffff;
        }

        /* Alerts */
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

        /* Card Sections */
        .card-section {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        /* User Info & Status Pill */
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

        /* Status Pills */
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
            /* margin-bottom: 12px; */
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

        .checkmark.disabled-mark {
            background-color: #cbd5e1;
            cursor: not-allowed;
        }

        .custom-checkbox-container input:checked~.checkmark {
            background-color: #27ae60;
        }

        .checkmark ion-icon {
            font-size: 16px;
            display: none;
        }

        .custom-checkbox-container input:checked~.checkmark ion-icon {
            display: block;
        }

        /* Stats Grid */
        /* .stats-grid {
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
                    } */

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

        /* PHOTO STYLING (EDIT PAGE SPECIAL) */
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

        .photo-badge.new-badge {
            background: #27ae60;
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
            margin-top: 10px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            z-index: 2;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
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

        /* Fixed Footer */
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

        .btn-submit {
            width: 100%;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #2d6ea6 0%, #094b87 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
        }

        .btn-submit:active {
            background: linear-gradient(135deg, #094b87 0%, #063a6b 100%);
        }

        .btn-add-extra {
            width: 100%;
            background: #fff;
            border: 2px dashed #cbd5e1;
            color: #64748b;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-add-extra:hover {
            border-color: #27ae60;
            color: #27ae60;
            background: #eef6fd;
        }

        .extra-card {
            background: #fff;
            border-left: 4px solid #f59e0b;
            /* Orange */
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 10px;
            position: relative;
        }

        .extra-title {
            font-weight: 700;
            color: #334155;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .extra-note {
            font-size: 12px;
            color: #64748b;
            font-style: italic;
        }

        .extra-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }
    </style>

    <script>
        // 1. UPDATE PROGRESS
        function updateProgress() {
            let totalBobot = 0;
            let bobotTercapai = 0;
            let totalItem = 0;
            let checkedItem = 0;

            const allChecks = document.querySelectorAll('.task-check');

            allChecks.forEach(check => {
                let b = parseFloat(check.dataset.bobot) || 0;
                totalBobot += b;
                totalItem++;
                const scoreInput = document.getElementById('score-' + check.dataset.id);
                const card = document.getElementById('card-' + check.dataset.id);

                if (check.checked) {
                    bobotTercapai += b;
                    checkedItem++;
                    scoreInput.value = b;
                    card.classList.add('checked');
                } else {
                    scoreInput.value = 0;
                    card.classList.remove('checked');
                }
            });

            const percent = totalBobot > 0 ? Math.round((bobotTercapai / totalBobot) * 100) : 0;
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').innerText = percent + '%';

            document.getElementById('taskCountText').innerText = checkedItem + ' / ' + allChecks.length;
        }

        // 2. ADD NOTE (SWEETALERT)
        function addNote(id, title) {
            const currentNote = document.getElementById('input-note-' + id).value;

            Swal.fire({
                title: 'Ubah Data Kinerja',
                html: '<div style="text-align:left; font-size:13px; color:#64748b; margin-bottom:5px;">Catatan Tambahan</div>' +
                    '<textarea id="swal-input2" class="swal2-textarea" placeholder="Tulis kendala atau keterangan..." style="margin:0;">' +
                    currentNote + '</textarea>',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Update Data',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    return [
                        document.getElementById('swal-input2').value
                    ]
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const [catatan] = result.value;

                    // Set Value ke Hidden Input
                    document.getElementById('input-note-' + id).value = catatan;

                    // Update UI Text
                    const btnText = document.getElementById('btn-text-' + id);
                    if (catatan) {
                        btnText.innerText = "Ubah Catatan";
                        btnText.parentElement.style.borderColor = "#27ae60";
                        btnText.parentElement.style.color = "#27ae60";
                    } else {
                        btnText.innerText = "Masukkan Catatan";
                        btnText.parentElement.style.borderColor = "#e2e8f0";
                        btnText.parentElement.style.color = "#64748b";
                    }

                    // Update Preview Box Catatan
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

        // 3. PREVIEW FOTO (LOGIC KHUSUS EDIT)
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

                    // Sembunyikan foto lama jika ada
                    if (existingFotoBox) existingFotoBox.style.display = 'none';

                    // Ubah style tombol
                    btnText.innerText = 'Ganti';
                    btnText.parentElement.style.borderColor = "#3b82f6";
                    btnText.parentElement.style.color = "#3b82f6";
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 4. HAPUS FOTO
        function removeFoto(event, id) {
            event.preventDefault();
            const input = document.getElementById('input-foto-' + id);
            input.value = '';

            // Sembunyikan preview baru
            document.getElementById('preview-container-' + id).style.display = 'none';

            // Munculkan kembali foto lama jika ada
            const existingFotoBox = document.getElementById('existing-foto-box-' + id);
            if (existingFotoBox) {
                existingFotoBox.style.display = 'block';
                document.getElementById('foto-text-' + id).innerText = 'Ganti Foto';
            } else {
                document.getElementById('foto-text-' + id).innerText = 'Upload Foto';
            }

            // Reset style tombol
            const btnText = document.getElementById('foto-text-' + id);
            btnText.parentElement.style.borderColor = "#e2e8f0";
            btnText.parentElement.style.color = "#64748b";
        }

        // INIT LISTENERS
        document.querySelectorAll('.task-check').forEach(check => {
            check.addEventListener('change', updateProgress);
        });

        // Variabel Status Approved dari PHP
        const isApproved = {{ $isApproved ? 'true' : 'false' }};

        // 1. Fungsi Membuka Modal
        function openExtraModal(isEdit = false, id = null) {
            if (isApproved) return; // Cegah akses jika approved

            let titleVal = '';
            let noteVal = '';

            // Jika Mode Edit, ambil data dari hidden input yang ada
            if (isEdit) {
                titleVal = document.getElementById(`extra_judul_${id}`).value;
                noteVal = document.getElementById(`extra_catatan_${id}`).value;
            }

            Swal.fire({
                title: isEdit ? 'Edit KPI Tambahan' : 'Tambah KPI Tambahan',
                html: `
                    <div style="padding: 5px 0; font-family: 'Inter', sans-serif;">
                        <div style="text-align: left; margin-bottom: 16px;">
                            <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 8px;">
                                Nama Kegiatan <span style="color: #ef4444;">*</span>
                            </label>
                            <input id="swal-extra-judul" type="text" 
                                style="width: 100%; padding: 12px 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #1e293b; box-sizing: border-box; outline: none; transition: 0.2s;" 
                                placeholder="Contoh: Membantu rekap data..." 
                                value="${escHtml(titleVal)}"
                                onfocus="this.style.borderColor='#f59e0b'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.1)';"
                                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">
                        </div>
                        <div style="text-align: left;">
                            <label style="font-size: 13px; font-weight: 600; color: #334155; display: block; margin-bottom: 8px;">
                                Catatan / Detail <span style="color: #94a3b8; font-weight: 400; font-size: 11px;">(Opsional)</span>
                            </label>
                            <textarea id="swal-extra-catatan" 
                                style="width: 100%; padding: 12px 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #1e293b; box-sizing: border-box; outline: none; transition: 0.2s; min-height: 100px; resize: vertical;" 
                                placeholder="Tuliskan rincian kegiatan di sini..."
                                onfocus="this.style.borderColor='#f59e0b'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.1)';"
                                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">${escHtml(noteVal)}</textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#f59e0b', // Tetap menggunakan orange untuk identitas 'Extra'
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<ion-icon name="save-outline" style="vertical-align: -2px; margin-right: 4px;"></ion-icon> Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                customClass: {
                    popup: 'rounded-xl',
                    title: 'fs-5 text-dark',
                    confirmButton: 'px-4 py-2',
                    cancelButton: 'px-4 py-2'
                },
                preConfirm: () => {
                    const judul = document.getElementById('swal-extra-judul').value;
                    const catatan = document.getElementById('swal-extra-catatan').value;
                    if (!judul.trim()) {
                        Swal.showValidationMessage('Nama Kegiatan wajib diisi!');
                    }
                    return {
                        judul: judul,
                        catatan: catatan
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const data = result.value;
                    if (isEdit) {
                        updateExtraView(id, data.judul, data.catatan);
                    } else {
                        createExtraView(data.judul, data.catatan);
                    }
                }
            });
        }

        // 2. Render Tampilan Extra (Dipercantik & Support isApproved)
        // Escape input teks sebelum masuk ke innerHTML / atribut value (cegah XSS).
        function escHtml(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
        }

        function createExtraView(judul, catatan) {
            judul = escHtml(judul);
            catatan = catatan ? escHtml(catatan) : '';
            const container = document.getElementById('extra-kpi-container');
            const uniqueId = Date.now() + Math.floor(Math.random() * 1000); // ID Unik

            // Logic Button: Sembunyikan tombol Edit/Hapus jika Approved
            let actionButtons = '';
            if (!isApproved) {
                actionButtons = `
                    <div class="extra-actions" style="margin-top: 15px;">
                        <button type="button" class="btn-action text-primary" onclick="openExtraModal(true, '${uniqueId}')">
                            <ion-icon name="create-outline"></ion-icon> Edit
                        </button>
                        <button type="button" class="btn-action text-danger" onclick="deleteExtra('${uniqueId}')">
                            <ion-icon name="trash-outline"></ion-icon> Hapus
                        </button>
                    </div>`;
            }

            // HTML Card yang sudah dirapikan teksnya
            const html = `
                <div class="extra-card" id="extra_card_${uniqueId}">
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Kegiatan</div>
                    <div class="extra-title" id="display_judul_${uniqueId}">${judul}</div>
                    
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: 8px; margin-bottom: 2px;">Catatan</div>
                    <div class="extra-note" id="display_catatan_${uniqueId}">${catatan ? catatan : '-'}</div>
                    
                    <input type="hidden" name="extras[${uniqueId}][judul]" id="extra_judul_${uniqueId}" value="${judul}">
                    <input type="hidden" name="extras[${uniqueId}][catatan]" id="extra_catatan_${uniqueId}" value="${catatan}">

                    ${actionButtons}
                </div>
            `;

            container.innerHTML = html; // Replace isi container
            toggleAddButton(true); // Sembunyikan tombol tambah
        }

        // 3. Update Tampilan Extra (Edit) - Penyesuaian output text
        function updateExtraView(id, judul, catatan) {
            document.getElementById(`display_judul_${id}`).innerText = judul;
            document.getElementById(`display_catatan_${id}`).innerText = catatan ? catatan : '-';

            document.getElementById(`extra_judul_${id}`).value = judul;
            document.getElementById(`extra_catatan_${id}`).value = catatan;
        }

        // 4. Hapus Extra
        function deleteExtra(id) {
            Swal.fire({
                title: 'Hapus KPI Tambahan?',
                text: "Data ini akan dihapus dari draft",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) {
                    const card = document.getElementById(`extra_card_${id}`);
                    card.remove();
                    toggleAddButton(false); // Munculkan kembali tombol tambah
                }
            })
        }

        // 5. Toggle Tombol Tambah
        function toggleAddButton(hide) {
            const btn = document.getElementById('btnShowExtraModal');
            if (btn) { // Cek existensi tombol (karena di mode approved tombol gak ada)
                if (hide) {
                    btn.style.display = 'none';
                } else {
                    btn.style.display = 'flex';
                }
            }
        }

        // 6. INIT SAAT LOAD
        document.addEventListener('DOMContentLoaded', () => {
            updateProgress(); // Fungsi existing

            // --- LOAD DATA EXTRA DARI DATABASE ---
            @if (isset($extras) && count($extras) > 0)
                @foreach ($extras as $ex)
                    // Panggil fungsi create untuk menampilkan data yg sudah ada
                    // Kita gunakan logic blade untuk escape string agar aman dari error syntax JS
                    createExtraView(
                        @json($ex->indikator_tambahan),
                        @json($ex->catatan)
                    );
                @endforeach
            @endif
        });
    </script>
@endpush
