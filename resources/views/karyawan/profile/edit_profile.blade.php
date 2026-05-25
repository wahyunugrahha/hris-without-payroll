@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Edit Akun & Identitas Diri</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('karyawan.profile.update') }}" enctype="multipart/form-data" class="pb-5"
        id="formProfile">
        @csrf

        {{-- Section Foto Profil Modern --}}
        <div class="section mt-2 text-center">
            <div class="avatar-upload-container">
                <div class="avatar-preview">
                    @php
                        $fotoPath =
                            isset($karyawan->foto) && $karyawan->foto
                                ? asset('storage/uploads/karyawan/' . $karyawan->foto)
                                : asset('assets/img/nophoto.png');
                    @endphp
                    <div id="imagePreview" style="background-image: url('{{ $fotoPath }}');"></div>
                </div>
                <div class="avatar-edit">
                    <input type='file' name="foto" id="imageUpload" accept=".png, .jpg, .jpeg" />
                    <label for="imageUpload" class="btn-upload-icon">
                        <ion-icon name="camera"></ion-icon>
                    </label>
                </div>
            </div>
            <div class="mt-2 text-muted small">Tap ikon kamera untuk ubah foto</div>
            @error('foto')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Section Informasi Akun (Editable) --}}
        <div class="section mt-3">
            <div class="section-heading" style="margin-bottom: 10px; margin-left: 5px;">
                <h2 class="title" style="font-size: 16px; font-weight: 600; color: var(--primary-color);">Informasi Akun
                </h2>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Nama Panggilan --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Nama Panggilan</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="person-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('nama_panggilan') is-invalid-custom @enderror"
                                name="nama_panggilan" value="{{ old('nama_panggilan', $karyawan->nama_panggilan) }}"
                                required>
                        </div>
                        @error('nama_panggilan')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- No HP --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">No. Handphone (WhatsApp)</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="call-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('no_hp') is-invalid-custom @enderror"
                                name="no_hp" value="{{ old('no_hp', $karyawan->no_hp) }}" required placeholder="08xx...">
                        </div>
                        @error('no_hp')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Email</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="mail-outline"></ion-icon>
                            </span>
                            <input type="email"
                                class="form-control border-start-0 ps-0 @error('email') is-invalid-custom @enderror"
                                name="email" value="{{ old('email', $karyawan->email) }}" required>
                        </div>
                        @error('email')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group mb-4">
                        <label class="form-label text-muted small mb-1">Password Baru</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="lock-closed-outline"></ion-icon>
                            </span>
                            <input type="password"
                                class="form-control border-start-0 ps-0 @error('password') is-invalid-custom @enderror"
                                name="password" placeholder="Isi untuk mengubah password" autocomplete="off">
                        </div>
                        <small class="text-muted" style="font-size: 11px">*Kosongkan jika tidak ingin mengganti
                            password</small>
                        @error('password')
                            <br><small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Section Identitas Pribadi (Editable & Read-only) --}}
        <div class="section mt-4">
            <div class="section-heading" style="margin-bottom: 10px; margin-left: 5px;">
                <h2 class="title" style="font-size: 16px; font-weight: 600; color: var(--primary-color);">Identitas Pribadi
                </h2>
            </div>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Alamat --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Alamat Domisili</label>
                        <textarea class="form-control @error('alamat') is-invalid-custom @enderror" name="alamat" rows="2" required>{{ old('alamat', $karyawan->alamat) }}</textarea>
                        @error('alamat')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Agama --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Agama</label>
                        <select class="form-control @error('agama') is-invalid-custom @enderror" name="agama" required>
                            <option value="">Pilih Agama...</option>
                            @php $agamas = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']; @endphp
                            @foreach ($agamas as $agama)
                                <option value="{{ $agama }}"
                                    {{ old('agama', $karyawan->agama) == $agama ? 'selected' : '' }}>{{ $agama }}
                                </option>
                            @endforeach
                        </select>
                        @error('agama')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Status Pernikahan --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Status Pernikahan</label>
                        <select class="form-control @error('status_pernikahan') is-invalid-custom @enderror"
                            name="status_pernikahan" required>
                            <option value="">Pilih Status...</option>
                            @php $statuses = ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati']; @endphp
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}"
                                    {{ old('status_pernikahan', $karyawan->status_pernikahan) == $status ? 'selected' : '' }}>
                                    {{ $status }}</option>
                            @endforeach
                        </select>
                        @error('status_pernikahan')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Pendidikan Terakhir --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Pendidikan Terakhir</label>
                        <select class="form-control @error('pendidikan_terakhir') is-invalid-custom @enderror"
                            name="pendidikan_terakhir" required>
                            <option value="">Pilih Pendidikan...</option>
                            @php $pendidikans = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']; @endphp
                            @foreach ($pendidikans as $p)
                                <option value="{{ $p }}"
                                    {{ old('pendidikan_terakhir', $karyawan->pendidikan_terakhir) == $p ? 'selected' : '' }}>
                                    {{ $p }}</option>
                            @endforeach
                        </select>
                        @error('pendidikan_terakhir')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- No. Rekening (Editable) --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">No. Rekening</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="card-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('no_rekening') is-invalid-custom @enderror"
                                name="no_rekening" value="{{ old('no_rekening', $karyawan->no_rekening) }}"
                                placeholder="Masukkan Nomor Rekening">
                        </div>
                        @error('no_rekening')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="alert alert-info mt-4 mb-4" style="border-radius: 12px; font-size: 13px;">
                        <ion-icon name="information-circle-outline"
                            style="font-size: 18px; vertical-align: middle; margin-right: 4px;"></ion-icon>
                        Data di bawah ini tidak dapat diubah oleh karyawan. Hubungi HRD jika terdapat ketidaksesuaian.
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">NIK</label>
                        <input type="text" class="form-control bg-light" value="{{ $karyawan->nik ?? '-' }}" readonly
                            disabled>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Nama Sesuai KTP</label>
                        <input type="text" class="form-control bg-light" value="{{ $karyawan->nama_lengkap ?? '-' }}"
                            readonly disabled>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Tempat Lahir</label>
                        <input type="text" class="form-control bg-light" value="{{ $karyawan->tempat_lahir ?? '-' }}"
                            readonly disabled>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Tanggal Lahir</label>
                        <input type="text" class="form-control bg-light"
                            value="{{ $karyawan->tanggal_lahir ? \Carbon\Carbon::parse($karyawan->tanggal_lahir)->format('d F Y') : '-' }}"
                            readonly disabled>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Jenis Kelamin</label>
                        <input type="text" class="form-control bg-light"
                            value="{{ $karyawan->jenis_kelamin == 'L' ? 'Laki-Laki' : ($karyawan->jenis_kelamin == 'P' ? 'Perempuan' : '-') }}"
                            readonly disabled>
                    </div>

                </div>
            </div>
        </div>

        <div style="height: 90px;"></div>
        <div class="fixed-bottom bg-white p-3 border-top" style="box-shadow: 0 -4px 15px rgba(0,0,0,0.05); z-index: 1000; padding-bottom: calc(env(safe-area-inset-bottom) + 15px) !important;">
            <button type="submit" id="btnSimpan" class="btn btn-primary btn-block btn-lg rounded-pill shadow-sm w-100" style="height: 50px;">
                <span class="btn-text">Simpan Perubahan</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
        </div>

    </form>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .avatar-upload-container {
            position: relative;
            max-width: 120px;
            margin: 0 auto;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            position: relative;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .avatar-preview>div {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }

        .avatar-edit {
            position: absolute;
            right: -5px;
            bottom: 0px;
            z-index: 1;
        }

        .avatar-edit input {
            display: none;
        }

        .avatar-edit .btn-upload-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            margin-bottom: 0;
            border-radius: 50%;
            background: #1b7a6f;
            color: #fff;
            border: 3px solid #fff;
            box-shadow: 0 4px 10px rgba(27, 122, 111, 0.3);
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            font-size: 18px;
        }

        .avatar-edit .btn-upload-icon:hover {
            transform: scale(1.08);
            background: #156158;
        }

        /* Modern Inputs */
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

        .input-group-modern .form-control, .input-group-modern select {
            background: transparent;
            border: none !important;
            box-shadow: none !important;
            height: 48px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            padding-left: 5px;
        }

        .form-control.bg-light {
            background-color: #f1f5f9 !important;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #64748b;
            font-weight: 500;
        }
        
        textarea.form-control {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
            padding: 12px 15px;
        }
        textarea.form-control:focus {
            background: #fff;
            border-color: #1b7a6f;
            box-shadow: 0 0 0 4px rgba(27, 122, 111, 0.1);
        }

        .is-invalid-custom {
            border: 1px solid #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1b7a6f 0%, #2a8f85 100%);
            border: none;
            box-shadow: 0 8px 16px rgba(27, 122, 111, 0.2);
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #156158 0%, #1b7a6f 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(27, 122, 111, 0.3);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 1. HANDLE SESSION MESSAGES ---
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

            // --- 2. IMAGE PREVIEW & VALIDATION ---
            const imageUpload = document.getElementById("imageUpload");
            const imagePreview = document.getElementById("imagePreview");

            if (imageUpload) {
                imageUpload.addEventListener("change", function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                        if (!validTypes.includes(file.type)) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Format Salah',
                                text: 'Harap upload file JPG, JPEG, atau PNG.',
                                confirmButtonColor: '#f39c12'
                            });
                            this.value = ''; 
                            return;
                        }

                        // Updated max size to 3MB per user requirements
                        const maxSizeInMB = 3;
                        const maxSizeInBytes = maxSizeInMB * 1024 * 1024;

                        if (file.size > maxSizeInBytes) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ukuran File Terlalu Besar!',
                                html: `
                                    Ukuran file yang Anda pilih adalah <b>${(file.size / 1024 / 1024).toFixed(2)} MB</b>.
                                    <br>Maksimal ukuran yang diperbolehkan adalah <b>${maxSizeInMB} MB</b>.
                                `,
                                confirmButtonText: 'Pilih File Lain',
                                confirmButtonColor: '#d33'
                            });
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.style.backgroundImage = `url(${e.target.result})`;
                            imagePreview.style.opacity = '0';
                            imagePreview.style.borderColor = '#10b981'; // Success border
                            setTimeout(() => {
                                imagePreview.style.opacity = '1';
                            }, 100);
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }

            // --- 3. HANDLE FORM SUBMIT ---
            const form = document.getElementById('formProfile');
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
