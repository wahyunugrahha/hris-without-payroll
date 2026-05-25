@extends('layouts.presensi')

@section('header')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>

    <div class="presensi-header">
        <a href="{{ route('kpi.user.index') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Input Workbook Harian</span>
        <div class="header-spacer" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="main-container">

        <form action="{{ route('kpi.user.store') }}" method="POST" id="formWorkbook" enctype="multipart/form-data">
            @csrf

            {{-- ALERT ERROR --}}
            @if (session('error'))
                <div class="alert alert-soft-danger d-flex align-items-center mb-3">
                    <ion-icon name="alert-circle-outline" style="font-size:24px; margin-right:10px;"></ion-icon>
                    <div>
                        <strong>Perhatian</strong><br>{{ session('error') }}
                    </div>
                </div>
            @endif

            {{-- USER INFO CARD --}}
            <div class="card-section mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="user-name">{{ $user->nama_lengkap }}</h4>
                        <span class="user-role">{{ $user->jabatanRel->nama_jabatan ?? '-' }}</span>
                        <div class="user-dept mt-1">
                            <ion-icon name="business-outline"></ion-icon>
                            {{ $user->departemen->nama_dept ?? '-' }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="date-badge">
                            <span class="d-block day">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</span>
                            <span class="d-block month">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('M') }}</span>
                        </div>
                    </div>
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
                    <div class="task-card" id="card-{{ $item->id }}">
                        {{-- Header Card: Checkbox & Title --}}
                        <div class="task-header mb-0 d-flex align-items-center">
                            <label class="custom-checkbox-container m-0">
                                <input class="task-check" type="checkbox" id="check-{{ $item->id }}"
                                    name="kpi[{{ $item->id }}][is_checked]" value="1"
                                    data-id="{{ $item->id }}" data-bobot="{{ $item->score_indikator }}">
                                <span class="checkmark">
                                    <ion-icon name="checkmark-outline"></ion-icon>
                                </span>
                            </label>
                            <div class="task-title-wrapper ms-3">
                                <label for="check-{{ $item->id }}" class="task-title m-0"
                                    style="cursor: pointer;">{{ $item->indikator }}</label>
                            </div>
                        </div>

                        {{-- Hidden Input untuk Score (Akan diisi oleh JS, namun di Backend nanti nilai aslinya tetap diambil dari DB untuk keamanan) --}}
                        <input type="hidden" name="kpi[{{ $item->id }}][score]" id="score-{{ $item->id }}"
                            value="0">

                        {{-- Note Preview --}}
                        <div class="note-preview mt-2" id="note-display-{{ $item->id }}" style="display:none;">
                            <ion-icon name="document-text-outline"></ion-icon>
                            <span id="note-preview-{{ $item->id }}"></span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="action-buttons mt-3 pt-3 border-top">
                            {{-- Input Note --}}
                            <div class="btn-action"
                                onclick="addNote('{{ $item->id }}', '{{ addslashes($item->indikator) }}')">
                                <ion-icon name="create-outline"></ion-icon>
                                <span id="btn-text-{{ $item->id }}">Masukkan Catatan</span>
                                <input type="hidden" name="kpi[{{ $item->id }}][catatan]"
                                    id="input-note-{{ $item->id }}" value="">
                            </div>

                            {{-- Upload Foto --}}
                            <div class="btn-action"
                                onclick="document.getElementById('input-foto-{{ $item->id }}').click()">
                                <ion-icon name="camera-outline"></ion-icon>
                                <span id="foto-text-{{ $item->id }}">Foto</span>
                                <input type="file" name="kpi[{{ $item->id }}][foto]"
                                    id="input-foto-{{ $item->id }}" accept="image/*" style="display: none;"
                                    onchange="previewFoto('{{ $item->id }}')">
                            </div>
                        </div>

                        {{-- Preview Container Foto --}}
                        <div id="preview-container-{{ $item->id }}" class="photo-preview-box" style="display: none;">
                            <img id="preview-img-{{ $item->id }}" src="" class="preview-img">
                            <button type="button" class="btn-remove-photo"
                                onclick="removeFoto(event, '{{ $item->id }}')">
                                <ion-icon name="close-outline"></ion-icon>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- KPI EXTRA BUTTONS --}}
            <div class="extra-section mt-4 mb-5">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="section-label">KPI Tambahan (Opsional)</span>
                </div>

                {{-- Container untuk menampung Item Extra --}}
                <div id="extra-kpi-container">
                    {{-- Item akan muncul di sini via JS --}}
                </div>

                {{-- Tombol Tambah (Akan hidden jika sudah ada item) --}}
                <button type="button" class="btn-add-extra" id="btnShowExtraModal" onclick="openExtraModal()">
                    <ion-icon name="add-circle-outline"></ion-icon>
                    Tambah Kegiatan KPI Tambahan
                </button>
            </div>

            {{-- FOOTER BUTTONS --}}
            <div class="footer-fixed">
                <div class="row">
                    <div class="col-6">
                        <button class="btn-footer btn-draft" type="submit" name="action_type" value="draft">
                            <ion-icon name="save-outline"></ion-icon> Draft
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn-footer btn-submit" type="submit" name="action_type" value="submitted">
                            <ion-icon name="send-outline"></ion-icon> Kirim
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>
@endsection

@push('myscript')
    <style>
        /* Base Layout */
        body {
            background-color: #f4f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            padding-bottom: 90px;
            /* Space for footer */
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

        /* Card Sections */
        .card-section {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
        }

        /* User Info */
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
            background: #ecfdf5;
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
            background-color: #f0fdf4;
        }

        .task-header {
            display: flex;
            align-items: flex-start;
            /* margin-bottom: 12px; Dihapus agar rapat */
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

        .checkmark ion-icon {
            font-size: 16px;
            display: none;
        }

        .custom-checkbox-container input:checked~.checkmark ion-icon {
            display: block;
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

        /* Photo Preview */
        .photo-preview-box {
            margin-top: 10px;
            position: relative;
            width: fit-content;
        }

        .preview-img {
            max-height: 120px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
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
        }

        .btn-draft {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);
        }

        .btn-submit:active {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
            background: #f0fdf4;
        }

        .extra-card {
            background: #fff;
            border-left: 4px solid #f59e0b;
            /* Warna Orange untuk pembeda */
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 10px;
            position: relative;
        }

        .extra-badge {
            background: #f59e0b;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 5px;
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
            const allChecks = document.querySelectorAll('.task-check');

            allChecks.forEach(check => {
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

            const percent = totalBobot > 0 ? Math.round((bobotTercapai / totalBobot) * 100) : 0;

            // Animasi Progress Bar
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').innerText = percent + '%';

            const checkedCount = document.querySelectorAll('.task-check:checked').length;
            document.getElementById('taskCountText').innerText = checkedCount + ' / ' + allChecks.length;
        }

        // 2. ADD NOTE (SWEETALERT)
        function addNote(id, title) {
            const currentNote = document.getElementById('input-note-' + id).value;

            Swal.fire({
                title: 'Input Kinerja',
                html: '<div style="text-align:left; font-size:13px; color:#64748b; margin-bottom:5px;">Catatan Tambahan</div>' +
                    '<textarea id="swal-input2" class="swal2-textarea" placeholder="Tulis kendala atau keterangan..." style="margin:0;">' +
                    currentNote + '</textarea>',
                showCancelButton: true,
                confirmButtonColor: '#27ae60',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Simpan Data',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                customClass: {
                    popup: 'rounded-xl', // Custom class if you want extra styling
                },
                preConfirm: () => {
                    return [
                        document.getElementById('swal-input2').value
                    ]
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Karena array yang dikembalikan hanya 1 value (catatan), maka assign ke catatan
                    const [catatan] = result.value;

                    document.getElementById('input-note-' + id).value = catatan;

                    // Update UI Button Text
                    const btnText = document.getElementById('btn-text-' + id);
                    if (catatan) {
                        btnText.innerText = "Edit Catatan";
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

        // 3. PREVIEW FOTO
        function previewFoto(id) {
            const input = document.getElementById('input-foto-' + id);
            const previewContainer = document.getElementById('preview-container-' + id);
            const previewImg = document.getElementById('preview-img-' + id);
            const btnText = document.getElementById('foto-text-' + id);

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';

                    // Style button berubah
                    btnText.innerText = 'Ganti';
                    btnText.parentElement.style.borderColor = "#3b82f6";
                    btnText.parentElement.style.color = "#3b82f6";
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 4. HAPUS FOTO
        function removeFoto(event, id) {
            event.preventDefault(); // Mencegah submit form
            const input = document.getElementById('input-foto-' + id);
            input.value = ''; // Reset input file

            document.getElementById('preview-container-' + id).style.display = 'none';

            const btnText = document.getElementById('foto-text-' + id);
            btnText.innerText = 'Foto';
            btnText.parentElement.style.borderColor = "#e2e8f0";
            btnText.parentElement.style.color = "#64748b";
        }

        // INIT LISTENERS
        document.querySelectorAll('.task-check').forEach(check => {
            check.addEventListener('change', updateProgress);
        });

        document.addEventListener('DOMContentLoaded', () => {
            updateProgress();
        });

        // 1. Fungsi Membuka Modal
        function openExtraModal(isEdit = false, id = null) {
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
                                value="${titleVal}"
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
                                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">${noteVal}</textarea>
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

        // 2. Render Tampilan Extra (Create) - Teks dirapikan
        function createExtraView(judul, catatan) {
            const container = document.getElementById('extra-kpi-container');
            const uniqueId = Date.now();

            // Saya tambahkan label kecil (text-muted) agar tampilannya seperti form output
            const html = `
                <div class="extra-card" id="extra_card_${uniqueId}">
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-bottom: 2px;">Kegiatan</div>
                    <div class="extra-title" id="display_judul_${uniqueId}">${judul}</div>
                    
                    <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; margin-top: 8px; margin-bottom: 2px;">Catatan</div>
                    <div class="extra-note" id="display_catatan_${uniqueId}">${catatan ? catatan : '-'}</div>
                    
                    <input type="hidden" name="extras[${uniqueId}][judul]" id="extra_judul_${uniqueId}" value="${judul}">
                    <input type="hidden" name="extras[${uniqueId}][catatan]" id="extra_catatan_${uniqueId}" value="${catatan}">

                    <div class="extra-actions" style="margin-top: 15px;">
                        <button type="button" class="btn-action text-primary" onclick="openExtraModal(true, '${uniqueId}')">
                            <ion-icon name="create-outline"></ion-icon> Edit
                        </button>
                        <button type="button" class="btn-action text-danger" onclick="deleteExtra('${uniqueId}')">
                            <ion-icon name="trash-outline"></ion-icon> Hapus
                        </button>
                    </div>
                </div>
            `;

            container.innerHTML = html;
            toggleAddButton(true);
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
            if (hide) {
                btn.style.display = 'none';
            } else {
                btn.style.display = 'flex';
            }
        }

        // 6. Cek Saat Load (Jika user sedang Edit Draft dan sudah ada extra)
        document.addEventListener('DOMContentLoaded', () => {
            // Load Existing Extras (PHP Injection)
            @if (isset($extras) && count($extras) > 0)
                @foreach ($extras as $ex)
                    createExtraView("{{ $ex->indikator_tambahan }}", "{{ $ex->catatan }}");
                @endforeach
            @endif

            updateProgress(); // Existing logic
        });
    </script>
@endpush
