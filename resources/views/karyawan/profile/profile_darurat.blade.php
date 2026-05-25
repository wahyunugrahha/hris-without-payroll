@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="javascript:;" class="headerButton goBack back-button">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Kontak Darurat</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@section('content')
    <form method="POST" action="{{ route('karyawan.profile.updatedarurat') }}" class="pb-5" id="formDarurat">
        @csrf

        <div class="section mt-3 mb-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    {{-- Nama Darurat --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Nama Kontak Darurat</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="person-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('nama_darurat') is-invalid-custom @enderror"
                                name="nama_darurat" value="{{ old('nama_darurat', $karyawan->nama_darurat) }}" required>
                        </div>
                        @error('nama_darurat')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Hubungan Darurat --}}
                    <div class="form-group mb-3">
                        <label class="form-label text-muted small mb-1">Hubungan</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="people-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('hubungan_darurat') is-invalid-custom @enderror"
                                name="hubungan_darurat" value="{{ old('hubungan_darurat', $karyawan->hubungan_darurat) }}"
                                required placeholder="Contoh: Istri, Suami, Ayah, Ibu">
                        </div>
                        @error('hubungan_darurat')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- No HP Darurat --}}
                    <div class="form-group mb-4">
                        <label class="form-label text-muted small mb-1">No. Handphone Darurat</label>
                        <div class="input-group input-group-modern">
                            <span class="input-group-text bg-transparent border-end-0 text-primary">
                                <ion-icon name="call-outline"></ion-icon>
                            </span>
                            <input type="text"
                                class="form-control border-start-0 ps-0 @error('no_darurat') is-invalid-custom @enderror"
                                name="no_darurat" value="{{ old('no_darurat', $karyawan->no_darurat) }}" required
                                placeholder="08xx...">
                        </div>
                        @error('no_darurat')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
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

        .input-group-modern .form-control {
            background: transparent;
            border: none !important;
            box-shadow: none !important;
            height: 48px;
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
            padding-left: 5px;
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

            const form = document.getElementById('formDarurat');
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
