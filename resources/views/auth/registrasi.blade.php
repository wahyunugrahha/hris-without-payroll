<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#2d6ea6">
    <title>Registrasi Karyawan | Sistem Absensi wndev</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="manifest" href="{{ asset('__manifest.json') }}?v=20260618">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset_v('assets/css/style.css') }}">

    <style>
        :root {
            --theme-color: #2a988d;
            --theme-hover: #2f8f85;
            --theme-light: rgba(42, 152, 141, 0.1);
            --bg-body: #e8eef4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            padding-bottom: 40px;
            min-height: 100vh;
            margin: 0;
        }

        /* Desktop Container Wrapper */
        .form-wrapper {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Header Styling — same as style.css .presensi-header */
        .presensi-header {
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            background: linear-gradient(135deg, #1b7a6f 0%, #2a8f85 100%);
            padding: 24px 20px;
            border-radius: 0 0 20px 20px;
            color: white;
            box-shadow: 0 8px 24px rgba(27, 122, 111, 0.2) !important;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
        }

        .presensi-header .header-title {
            flex: 1;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        /* Section Title */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding-left: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Card Customization */
        .card {
            border-radius: 18px !important;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06) !important;
            border: 1px solid #e8eef4 !important;
            background: #fff !important;
        }

        /* Form Labels */
        .form-label {
            font-size: 11px !important;
            font-weight: 700 !important;
            color: #64748b !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Form Inputs */
        .form-group .form-control,
        .form-group .form-select,
        .form-group textarea.form-control {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 14px;
            color: #1e293b;
            transition: all 0.25s;
        }

        .form-group .form-control,
        .form-group .form-select {
            height: 48px;
        }

        .form-group textarea.form-control {
            height: auto;
            padding-top: 12px;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #fff;
            border-color: var(--theme-color);
            box-shadow: 0 0 0 3px var(--theme-light);
            outline: none;
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #ef4444;
            background-color: #fff5f5;
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
        }

        .form-control.border-start-0 {
            border-radius: 0 10px 10px 0;
        }

        .text-theme {
            color: var(--theme-color) !important;
        }

        /* File input */
        .form-control[type="file"] {
            height: auto !important;
            padding: 10px 12px;
            cursor: pointer;
        }

        /* Avatar Upload */
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
            box-shadow: 0 6px 20px rgba(42, 152, 141, 0.2);
            overflow: hidden;
            background-color: #f1f5f9;
        }

        .avatar-preview>div {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            transition: transform 0.3s ease;
        }

        .avatar-preview:hover>div {
            transform: scale(1.05);
        }

        .avatar-edit {
            position: absolute;
            right: 0;
            bottom: 4px;
            z-index: 1;
        }

        .avatar-edit input {
            display: none;
        }

        .avatar-edit .btn-upload-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--theme-color);
            color: #fff;
            border: 3px solid #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            cursor: pointer;
            transition: all 0.2s;
        }

        .avatar-edit .btn-upload-icon:hover {
            transform: scale(1.1);
            background: var(--theme-hover);
        }

        /* Submit Button */
        .btn-theme {
            background: linear-gradient(135deg, #2f8f85 0%, #2a988d 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.5px;
            height: 54px;
            font-size: 16px;
            border-radius: 14px !important;
            transition: all 0.25s;
            box-shadow: 0 4px 14px rgba(42, 152, 141, 0.35);
        }

        .btn-theme:hover,
        .btn-theme:focus {
            opacity: 0.93;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(42, 152, 141, 0.4);
        }

        .btn-theme:active {
            transform: translateY(0);
        }

        .btn-theme.disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Responsive tweaks */
        @media (max-width: 576px) {
            .presensi-header {
                padding: 14px 16px 18px;
            }

            .header-title {
                font-size: 16px;
            }

            .card {
                border-radius: 14px !important;
            }

            .form-wrapper {
                padding: 0 8px;
            }

            .btn-theme {
                height: 50px;
                font-size: 15px;
            }
        }

        /* SweetAlert */
        .swal2-popup {
            border-radius: 20px !important;
        }

        .swal2-confirm {
            border-radius: 12px !important;
            padding: 12px 30px !important;
            font-weight: 600 !important;
        }
    </style>
</head>

<body>

    <div class="presensi-header">
        <span class="header-title">Registrasi</span>
    </div>

    <div class="container form-wrapper mt-4">
        <form method="POST" action="{{ route('registrasi.store') }}" enctype="multipart/form-data" id="formRegister">
            @csrf

            <div class="section mb-4">
                <div class="section-title">
                    Identitas Pribadi Karyawan
                </div>
                <div class="card p-2">
                    <div class="card-body p-3 p-md-4">
                        <div class="mb-4 text-center">
                            <div class="avatar-upload-container">
                                <div class="avatar-preview">
                                    <div id="imagePreview"
                                        style="background-image: url('{{ asset('assets/img/nophoto.png') }}');"></div>
                                </div>
                                <div class="avatar-edit">
                                    <input type='file' name="foto" id="imageUpload" accept=".png, .jpg, .jpeg" />
                                    <label for="imageUpload" class="btn-upload-icon" title="Pilih Foto Profil">
                                        <ion-icon name="camera" class="fs-5"></ion-icon>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-3 text-muted small fw-medium">Upload Foto Profil Terbaru</div>
                            @error('foto')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Nomor Induk Kependudukan (Sesuai KTP)</label>
                                    <input type="text" class="form-control @error('nik') is-invalid @enderror"
                                        name="nik" value="{{ old('nik') }}" placeholder="17710xxxxxxxx" required>
                                    @error('nik')
                                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Nama Lengkap (Sesuai KTP)</label>
                                    <input type="text"
                                        class="form-control @error('nama_lengkap') is-invalid @enderror"
                                        name="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
                                    @error('nama_lengkap')
                                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Nama Panggilan</label>
                                    <input type="text"
                                        class="form-control @error('nama_panggilan') is-invalid @enderror"
                                        name="nama_panggilan" value="{{ old('nama_panggilan') }}" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Jenis Kelamin</label>
                                    <select name="jenis_kelamin"
                                        class="form-select @error('jenis_kelamin') is-invalid @enderror" required>
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>
                                            Laki-laki
                                        </option>
                                        <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>
                                            Perempuan
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Tempat Lahir</label>
                                    <input type="text"
                                        class="form-control @error('tempat_lahir') is-invalid @enderror"
                                        name="tempat_lahir" value="{{ old('tempat_lahir') }}" placeholder="Bengkulu"
                                        required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Tanggal Lahir</label>
                                    <input type="date"
                                        class="form-control @error('tanggal_lahir') is-invalid @enderror"
                                        name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" required>
                                </div>
                            </div>
                        </div>



                        <div class="form-group mb-3">
                            <label class="form-label small mb-1">Alamat Lengkap</label>
                            <textarea class="form-control @error('alamat') is-invalid @enderror" name="alamat" rows="3"
                                placeholder="Masukkan alamat domisili saat ini..." required>{{ old('alamat') }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label small mb-1">Nama Ibu Kandung</label>
                            <input type="text" class="form-control @error('nama_ibu_kandung') is-invalid @enderror"
                                name="nama_ibu_kandung" value="{{ old('nama_ibu_kandung') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Agama</label>
                                    <select class="form-select @error('agama') is-invalid @enderror" name="agama"
                                        required>
                                        <option value="">Pilih Agama</option>
                                        @foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $agm)
                                            <option value="{{ $agm }}"
                                                {{ old('agama') == $agm ? 'selected' : '' }}>{{ $agm }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Pendidikan Terakhir</label>
                                    <select class="form-select @error('pendidikan_terakhir') is-invalid @enderror"
                                        name="pendidikan_terakhir" required>
                                        <option value="">Pilih Pendidikan</option>
                                        @foreach (['SD', 'SMP', 'SMA/SMK', 'D3', 'S1', 'S2'] as $p)
                                            <option value="{{ $p }}"
                                                {{ old('pendidikan_terakhir') == $p ? 'selected' : '' }}>
                                                {{ $p }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Pernikahan</label>
                                    <select class="form-select @error('status_pernikahan') is-invalid @enderror"
                                        name="status_pernikahan" required>
                                        <option value="">Pilih Status</option>
                                        @foreach (['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'] as $sp)
                                            <option value="{{ $sp }}"
                                                {{ old('status_pernikahan') == $sp ? 'selected' : '' }}>
                                                {{ $sp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Status Tanggungan Keluarga</label>
                                    <select class="form-select @error('status_ptkp') is-invalid @enderror"
                                        name="status_ptkp" required>
                                        <option value="TK" {{ old('status_ptkp') == 'TK' ? 'selected' : '' }}>Tak
                                            Kawin (TK)</option>
                                        @for ($i = 0; $i <= 10; $i++)
                                            <option value="K/{{ $i }}"
                                                {{ old('status_ptkp') == "K/$i" ? 'selected' : '' }}>
                                                Kawin, {{ $i }} Tanggungan (K/{{ $i }})
                                            </option>
                                        @endfor
                                    </select>
                                    @error('status_ptkp')
                                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Email Aktif (Opsional)</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email') }}" placeholder="email@gmail.com">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">No. Handphone (WhatsApp)</label>
                                    <input type="number" class="form-control @error('no_hp') is-invalid @enderror"
                                        name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"
                                        required>
                                </div>
                            </div>
                        </div>



                    </div>
                </div>
            </div>

            <div class="section mb-4">
                <div class="section-title">
                    Administrasi & Darurat
                </div>
                <div class="card p-2">
                    <div class="card-body p-3 p-md-4">
                        {{-- Kontak Darurat --}}
                        <div class="form-group mb-3">
                            <label class="form-label small mb-1">No. HP Darurat</label>
                            <input type="number" class="form-control @error('no_darurat') is-invalid @enderror"
                                name="no_darurat" value="{{ old('no_darurat') }}" placeholder="08xxxxxxxxxx"
                                required>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group mb-3">
                                    <label class="form-label small mb-1">Nama Kontak Darurat</label>
                                    <input type="text"
                                        class="form-control @error('nama_darurat') is-invalid @enderror"
                                        name="nama_darurat" value="{{ old('nama_darurat') }}"
                                        placeholder="Nama keluarga/kerabat" required>
                                </div>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Hubungan</label>
                                    <input type="text"
                                        class="form-control @error('hubungan_darurat') is-invalid @enderror"
                                        name="hubungan_darurat" value="{{ old('hubungan_darurat') }}"
                                        placeholder="Contoh: Istri/Ayah" required>
                                </div>
                            </div>
                        </div>

                        {{-- Rekening & BPJS --}}
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">Nomor Rekening (Opsional)</label>
                                    <input type="text"
                                        class="form-control @error('no_rekening') is-invalid @enderror"
                                        name="no_rekening" value="{{ old('no_rekening') }}"
                                        placeholder="Nomor Rekening">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="form-label small mb-1">No. BPJS Kesehatan (Opsional)</label>
                                    <input type="text"
                                        class="form-control @error('no_bpjs_kesehatan') is-invalid @enderror"
                                        name="no_bpjs_kesehatan" value="{{ old('no_bpjs_kesehatan') }}"
                                        placeholder="Nomor BPJS">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label small mb-1">Upload Kartu BPJS Kesehatan (Opsional)</label>
                            <input type="file"
                                class="form-control p-2 @error('foto_bpjs_kesehatan') is-invalid @enderror"
                                name="foto_bpjs_kesehatan" accept=".png, .jpg, .jpeg">
                            <small class="text-muted d-block mt-2"><ion-icon
                                    name="information-circle-outline"></ion-icon> Format: JPG, JPEG, PNG (Maks
                                3MB)</small>
                            @error('foto_bpjs_kesehatan')
                                <small class="text-danger mt-1 d-block">{{ $message }}</small>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>

            <div class="section mb-5">
                <button type="submit" id="btnSimpan"
                    class="btn btn-theme w-100 rounded-pill d-flex align-items-center justify-content-center gap-2">
                    <span class="btn-text">Daftar Sekarang</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <div class="mt-4 text-center small text-muted">
                    Sudah punya akun? <a href="{{ route('login') }}"
                        class="text-theme fw-bold text-decoration-none">Masuk di sini</a>
                </div>
                <div class="mt-3 text-center">
                    Copyright &copy; 2026
                    <a href="#" class="link-secondary">wndev</a>.
                    All rights reserved.
                </div>
            </div>

        </form>
    </div>

    <script src="{{ asset_v('assets/js/lib/jquery-3.4.1.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toast Error Alert
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true,
            });

            @if ($errors->any())
                Toast.fire({
                    icon: 'error',
                    title: 'Mohon periksa kembali form Anda.'
                });
            @endif

            // Image Preview Profil
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
                                confirmButtonColor: '#2d6ea6'
                            });
                            this.value = '';
                            return;
                        }

                        if (file.size > 3 * 1024 * 1024) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Ukuran Terlalu Besar!',
                                text: 'Maksimal ukuran file adalah 3MB.',
                                confirmButtonColor: '#d33'
                            });
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.style.backgroundImage = `url(${e.target.result})`;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Form Submit Loading
            const form = document.getElementById('formRegister');
            const btnSimpan = document.getElementById('btnSimpan');
            const btnText = btnSimpan.querySelector('.btn-text');
            const btnSpinner = btnSimpan.querySelector('.spinner-border');

            form.addEventListener('submit', function() {
                btnSimpan.classList.add('disabled');
                btnSimpan.style.pointerEvents = 'none';
                btnText.textContent = 'Memproses...';
                btnSpinner.classList.remove('d-none');
            });
        });
    </script>
</body>

</html>
