@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Data Administrasi</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <div class="pb-5">
        <form method="POST" action="{{ route('karyawan.profile.updateadministrasi') }}" enctype="multipart/form-data"
            id="formAdministrasi">
            @csrf
            <div class="section mt-3 mb-5">
                <div class="card border-0 shadow-sm rounded-4" style="background: #ffffff; border: 1px solid #eef2f7 !important;">
                    <div class="card-body p-4">

                        {{-- BPJS Kesehatan --}}
                        <div class="mb-4">
                            <h4 class="section-title mb-3">
                                <ion-icon name="medkit-outline"></ion-icon>
                                BPJS Kesehatan
                            </h4>

                            <div class="form-group mt-3 mb-3">
                                <label class="form-label form-label-modern">Nomor BPJS Kesehatan</label>
                                <input type="text"
                                    class="form-control @error('no_bpjs_kesehatan') is-invalid-custom @enderror"
                                    name="no_bpjs_kesehatan"
                                    value="{{ old('no_bpjs_kesehatan', $karyawan->no_bpjs_kesehatan) }}"
                                    placeholder="Masukkan Nomor BPJS Kesehatan">
                                @error('no_bpjs_kesehatan')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label form-label-modern">Upload/Ganti Foto Kartu BPJS
                                    Kesehatan</label>
                                <div class="upload-box @error('foto_bpjs_kesehatan') is-invalid-custom @enderror" id="uploadBoxBpjsKesehatan">
                                    <input type="file" class="upload-input" id="foto_bpjs_kesehatan"
                                        name="foto_bpjs_kesehatan" accept=".png, .jpg, .jpeg">
                                    <label for="foto_bpjs_kesehatan" class="upload-label mb-0">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                        <span class="upload-title">Pilih file foto kartu</span>
                                        <span class="upload-subtitle">Klik untuk upload (JPG, JPEG, PNG - Maks 3MB)</span>
                                    </label>
                                </div>
                                <div id="selectedFileName" class="selected-file-name d-none"></div>
                                @error('foto_bpjs_kesehatan')
                                    <br><small class="text-danger">{{ $message }}</small>
                                @enderror

                                @if (!empty($karyawan->foto_bpjs_kesehatan))
                                    <div class="photo-preview mt-3 text-center">
                                        <div class="small text-muted mb-2">Foto Saat Ini</div>
                                        <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_kesehatan) }}"
                                            alt="BPJS Kesehatan"
                                            style="max-width: 100%; border-radius: 6px; max-height: 150px;">
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- BPJS Ketenagakerjaan --}}
                        <div class="mb-4 mt-5">
                            <h4 class="section-title mb-3" style="color: #334155;">
                                <ion-icon name="briefcase-outline"></ion-icon>
                                BPJS Ketenagakerjaan</h4>

                            <div class="form-group mt-3 mb-3">
                                <label class="form-label form-label-modern">Nomor BPJS Ketenagakerjaan</label>
                                <input type="text" class="form-control bg-light"
                                    value="{{ $karyawan->no_bpjs_ketenagakerjaan ?? '-' }}" readonly disabled>
                                <small class="helper-text">*Data ini diatur oleh HRD</small>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label form-label-modern">Foto Kartu BPJS Ketenagakerjaan</label>

                                @if (!empty($karyawan->foto_bpjs_ketenagakerjaan))
                                    <div class="photo-preview mt-2 text-center">
                                        <img src="{{ asset('storage/uploads/karyawan/bpjs/' . $karyawan->foto_bpjs_ketenagakerjaan) }}"
                                            alt="BPJS Ketenagakerjaan"
                                            style="max-width: 100%; border-radius: 6px; max-height: 150px;">
                                    </div>
                                @else
                                    <div class="empty-photo-state mt-2">
                                        <ion-icon name="image-outline"></ion-icon>
                                        <span>Belum ada foto kartu BPJS Ketenagakerjaan.</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3">
                                @if ($pengajuanBpjsSudahDikirim)
                                    <button type="button" class="btn btn-secondary w-100 btn-modern" disabled>
                                        Pengajuan Sudah Dikirim (Maksimal 1x)
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-outline-primary w-100 btn-modern"
                                        formaction="{{ route('karyawan.profile.ajukanbpjs') }}" formmethod="POST"
                                        style="font-weight: 600;">
                                        Ajukan BPJS
                                    </button>
                                @endif

                                @if (!empty($pengajuanBpjsTerakhir))
                                    @php
                                        $statusTerakhir = strtolower($pengajuanBpjsTerakhir->status ?? 'pending');
                                        $statusClass = 'status-pill status-pending';
                                        $statusLabel = 'PENDING';
                                        if (in_array($statusTerakhir, ['processed', 'approved'])) {
                                            $statusClass = 'status-pill status-processed';
                                            $statusLabel = 'PROCESSED';
                                        } elseif ($statusTerakhir === 'rejected') {
                                            $statusClass = 'status-pill status-rejected';
                                            $statusLabel = 'REJECTED';
                                        }
                                    @endphp
                                    <div class="mt-2 status-info-wrap">
                                        <span class="text-muted">Status pengajuan terakhir:</span>
                                        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if (!empty($pengajuanBpjsTerakhir->requested_at))
                                            <span class="status-time">{{ \Carbon\Carbon::parse($pengajuanBpjsTerakhir->requested_at)->format('d-m-Y H:i') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div style="height: 90px;"></div>
            <div class="fixed-bottom bg-white p-3 border-top"
                style="box-shadow: 0 -4px 15px rgba(0,0,0,0.05); z-index: 1000; padding-bottom: calc(env(safe-area-inset-bottom) + 15px) !important;">
                <button type="submit" id="btnSimpan" class="btn btn-primary btn-block btn-lg rounded-pill shadow-sm w-100"
                    style="height: 50px;">
                    <span class="btn-text">Simpan Perubahan</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>

        </form>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .input-group-modern {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            align-items: center;
        }

        .input-group-modern:focus-within {
            background: #fff;
            border-color: #1b7a6f;
            box-shadow: 0 0 0 4px rgba(27, 122, 111, 0.1);
        }

        .input-group-modern .input-group-text {
            border: none;
            background: transparent;
            color: #1b7a6f;
            font-size: 20px;
            padding: 10px 10px 10px 15px;
        }

        .form-control {
            height: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: #fff;
            border-color: #1b7a6f;
            box-shadow: 0 0 0 4px rgba(27, 122, 111, 0.1);
        }

        .form-control.bg-light {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }

        .is-invalid-custom {
            border: 1px solid #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #1b7a6f;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 8px 12px;
            border-radius: 10px;
            background: #f0fdfa;
        }

        .form-label-modern {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .helper-text {
            font-size: 11px;
            color: #94a3b8;
        }

        .upload-box {
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.25s ease;
            overflow: hidden;
        }

        .upload-box:hover {
            border-color: #1b7a6f;
            background: #f0fdfa;
        }

        .upload-box:focus-within {
            border-color: #1b7a6f;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(27, 122, 111, 0.1);
        }

        .upload-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        .upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            padding: 16px 12px;
            cursor: pointer;
        }

        .upload-label ion-icon {
            font-size: 22px;
            color: #1b7a6f;
        }

        .upload-title {
            font-size: 13px;
            color: #334155;
            font-weight: 700;
        }

        .upload-subtitle {
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }

        .selected-file-name {
            margin-top: 8px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0f766e;
            font-size: 12px;
            border-radius: 8px;
            padding: 8px 10px;
            word-break: break-word;
        }

        .photo-preview {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            background: #f8fafc;
        }

        .empty-photo-state {
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .empty-photo-state ion-icon {
            font-size: 18px;
            color: #94a3b8;
        }

        .btn-modern {
            border-radius: 12px;
            height: 44px;
            font-weight: 600;
        }

        .status-info-wrap {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .status-processed {
            background: #dcfce7;
            color: #15803d;
        }

        .status-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .status-time {
            color: #94a3b8;
            font-size: 11px;
        }

        .btn-primary {
            background: #1b7a6f;
            border: none;
            box-shadow: 0 4px 10px rgba(27, 122, 111, 0.2);
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .btn-primary:hover {
            background: #156158;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(27, 122, 111, 0.25);
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            @if (session('success'))
                Toast.fire({
                    icon: 'success',
                    title: '{{ session('success') }}'
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'Coba Lagi',
                    confirmButtonColor: '#d33'
                });
            @endif

            // Validation for file input Max Size 3MB
            const fileInput = document.querySelector('input[name="foto_bpjs_kesehatan"]');
            const selectedFileName = document.getElementById('selectedFileName');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const maxSizeInMB = 3;
                        const maxSizeInBytes = maxSizeInMB * 1024 * 1024;
                        if (file.size > maxSizeInBytes) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ukuran File Terlalu Besar!',
                                html: `Maksimal ukuran yang diperbolehkan adalah <b>${maxSizeInMB} MB</b>.`,
                                confirmButtonText: 'Tutup',
                                confirmButtonColor: '#d33'
                            });
                            this.value = '';
                            if (selectedFileName) {
                                selectedFileName.classList.add('d-none');
                                selectedFileName.textContent = '';
                            }
                            return;
                        }

                        if (selectedFileName) {
                            selectedFileName.classList.remove('d-none');
                            selectedFileName.textContent = `File dipilih: ${file.name}`;
                        }
                    } else if (selectedFileName) {
                        selectedFileName.classList.add('d-none');
                        selectedFileName.textContent = '';
                    }
                });
            }

            const form = document.getElementById('formAdministrasi');
            const btnSimpan = document.getElementById('btnSimpan');
            const btnText = btnSimpan.querySelector('.btn-text');
            const btnSpinner = btnSimpan.querySelector('.spinner-border');

            form.addEventListener('submit', function() {
                btnSimpan.disabled = true;
                btnText.textContent = 'Menyimpan...';
                btnSpinner.classList.remove('d-none');
            });
        });
    </script>
@endpush
