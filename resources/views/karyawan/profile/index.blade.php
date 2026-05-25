@extends('layouts.presensi')
@section('header')
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Profile Karyawan</span>
        <div class="header-spacer"></div>
    </div>
@endsection

@push('myscript')
    <style>
        .simple-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .simple-modal[aria-hidden="false"] {
            visibility: visible;
            opacity: 1;
        }

        .simple-modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }

        .simple-modal-panel {
            position: relative;
            background: white;
            width: 90%;
            max-width: 360px;
            z-index: 10000;
            transform: translateY(30px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .simple-modal[aria-hidden="false"] .simple-modal-panel {
            transform: translateY(0);
        }

        .settings-item {
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .settings-item:active {
            background-color: #f8fafc !important;
            transform: scale(0.97);
        }

        .badge {
            letter-spacing: 0.3px;
        }
    </style>
    <script>
        (function() {
            const logoutBtn = document.getElementById('logoutBtn');
            const logoutModal = document.getElementById('logoutModal');
            const logoutCancel = document.getElementById('logoutCancel');
            const logoutCancelBackdrop = document.getElementById('logoutCancelBackdrop');
            const logoutConfirm = document.getElementById('logoutConfirm');
            const logoutForm = document.getElementById('logoutForm');

            function openModal() {
                logoutModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                logoutModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (logoutBtn) {
                logoutBtn.addEventListener('click', openModal);
            }
            if (logoutCancel) {
                logoutCancel.addEventListener('click', closeModal);
            }
            if (logoutCancelBackdrop) {
                logoutCancelBackdrop.addEventListener('click', closeModal);
            }
            if (logoutConfirm) {
                logoutConfirm.addEventListener('click', function() {
                    logoutForm.submit();
                });
            }
        })();
    </script>
@endpush

@section('content')
    <div id="appCapsule" class="pb-5">

        {{-- Profile Header Modern --}}
        <div class="section mt-3 px-3">
            <div class="card shadow-sm border-0 mb-3" style="border-radius: 16px; background: white; overflow: hidden;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center" style="gap: 16px;">
                        {{-- Avatar on Left --}}
                        <div class="avatar-container" style="width: 80px; height: 80px; flex-shrink: 0;">
                            @if (isset($karyawan->foto) && $karyawan->foto)
                                <img src="{{ asset('storage/uploads/karyawan/' . $karyawan->foto) }}"
                                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.05);"
                                    alt="Foto Profil" loading="lazy">
                            @else
                                <img src="{{ asset('assets/img/nophoto.png') }}"
                                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.05);"
                                    alt="Foto Default" loading="lazy">
                            @endif
                        </div>

                        {{-- Info on Right --}}
                        <div class="profile-info flex-grow-1" style="min-width: 0;">
                            <h3 class="fw-bold mb-0" style="font-size: 16px; color: #1f2937; line-height: 1.2;">
                                {{ $karyawan->nama_lengkap ?? '-' }}
                            </h3>
                            @if (!empty($karyawan->nama_panggilan))
                                <div class="text-muted" style="font-weight:400; font-size:12px; margin-bottom: 2px;">
                                    ({{ $karyawan->nama_panggilan }})</div>
                            @else
                                <div style="margin-bottom: 2px;"></div>
                            @endif
                            <div class="text-muted mb-2" style="font-size: 11px; letter-spacing: 0.5px; font-weight:600;">
                                {{ $karyawan->nik ?? '-' }}</div>

                            {{-- Badges --}}
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-light text-primary d-flex align-items-center"
                                    style="font-weight: 600; font-size: 9px; padding: 4px 8px; border-radius: 6px; border: 1px solid #e0e7ff;">
                                    <ion-icon name="briefcase" class="me-1"></ion-icon>
                                    {{ $karyawan->jabatanRel->nama_jabatan ?? '-' }}
                                </span>
                                <span class="badge bg-light text-success d-flex align-items-center"
                                    style="font-weight: 600; font-size: 9px; padding: 4px 8px; border-radius: 6px; border: 1px solid #dcfce7;">
                                    <ion-icon name="business" class="me-1"></ion-icon>
                                    {{ $karyawan->departemen->nama_dept ?? '-' }}
                                </span>
                                <span class="badge bg-light text-info d-flex align-items-center"
                                    style="font-weight: 600; font-size: 9px; padding: 4px 8px; border-radius: 6px; border: 1px solid #e0f2fe;">
                                    <ion-icon name="location" class="me-1"></ion-icon>
                                    {{ $karyawan->cabang->nama_cabang ?? '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Minimalist Info List --}}
        <div class="section px-3 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 16px; background: white;">
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush" style="border-radius: 16px;">
                        {{-- WhatsApp --}}
                        <li class="list-group-item d-flex align-items-center py-3 border-bottom px-3"
                            style="border-color: #f3f4f6 !important;">
                            <div class="icon-box mr-2"
                                style="width: 36px; height: 36px; min-width: 36px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;">
                                <ion-icon name="logo-whatsapp" style="font-size: 18px;"></ion-icon>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"
                                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">No. HP
                                    (WhatsApp)</p>
                                <p class="mb-0 fw-bold" style="font-size: 13px; color: #1f2937;">
                                    {{ $karyawan->no_hp ?? '-' }}</p>
                            </div>
                        </li>
                        {{-- Email --}}
                        <li class="list-group-item d-flex align-items-center py-3 border-bottom px-3"
                            style="border-color: #f3f4f6 !important;">
                            <div class="icon-box mr-2"
                                style="width: 36px; height: 36px; min-width: 36px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                                <ion-icon name="mail" style="font-size: 18px;"></ion-icon>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"
                                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Email</p>
                                <p class="mb-0 fw-bold" style="font-size: 13px; color: #1f2937;">
                                    {{ $karyawan->email ?? '-' }}</p>
                            </div>
                        </li>
                        {{-- Kontak Darurat --}}
                        <li class="list-group-item d-flex align-items-center py-3 border-bottom px-3"
                            style="border-color: #f3f4f6 !important;">
                            <div class="icon-box mr-2"
                                style="width: 36px; height: 36px; min-width: 36px; border-radius: 10px; background: #fff1f2; color: #e11d48; display: flex; align-items: center; justify-content: center;">
                                <ion-icon name="heart" style="font-size: 18px;"></ion-icon>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-0 text-muted"
                                    style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Kontak
                                    Darurat</p>
                                <p class="mb-0 fw-bold" style="font-size: 13px; color: #1f2937; line-height: 1.2;">
                                    {{ $karyawan->nama_darurat ?? '-' }}
                                    @if (!empty($karyawan->hubungan_darurat))
                                        <span class="text-muted fw-normal"
                                            style="font-size: 11px;">({{ $karyawan->hubungan_darurat }})</span>
                                    @endif
                                </p>
                                <p class="mb-0 fw-bold" style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    {{ $karyawan->no_darurat ?? '-' }}</p>
                            </div>
                        </li>
                        {{-- Sisa Kontrak --}}
                        @if (isset($karyawan->sisa_kontrak))
                            <li class="list-group-item d-flex align-items-center py-3 px-3">
                                <div class="icon-box mr-2"
                                    style="width: 36px; height: 36px; min-width: 36px; border-radius: 10px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center;">
                                    <ion-icon name="time" style="font-size: 18px;"></ion-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-muted"
                                        style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Sisa
                                        Kontrak</p>
                                    <p class="mb-0 fw-bold" style="font-size: 13px; color: #1f2937;">
                                        {{ $karyawan->sisa_kontrak }}</p>
                                </div>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- Settings Menu Modern --}}
        <div class="section px-3" style="margin-top: 24px;">
            <div class="settings-list">

                <a href="{{ route('karyawan.profile.edit') }}"
                    class="settings-item d-flex align-items-center p-3 mb-2 shadow-sm"
                    style="background: white; border-radius: 12px; text-decoration: none; color: inherit;">
                    <div class="icon-box me-3"
                        style="width: 38px; height: 38px; border-radius: 10px; background: #f8fafc; color: #1b7a6f; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <ion-icon name="person-circle-outline" style="font-size: 22px;"></ion-icon>
                    </div>
                    <div class="settings-label flex-grow-1 fw-bold" style="font-size: 14px; color: #1e293b;">Akun &
                        Identitas Diri</div>
                    <div class="settings-chevron text-muted"> <ion-icon name="chevron-forward-outline"></ion-icon> </div>
                </a>

                <a href="{{ route('karyawan.profile.administrasi') }}"
                    class="settings-item d-flex align-items-center p-3 mb-2 shadow-sm"
                    style="background: white; border-radius: 12px; text-decoration: none; color: inherit;">
                    <div class="icon-box me-3"
                        style="width: 38px; height: 38px; border-radius: 10px; background: #f0f9ff; color: #0369a1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <ion-icon name="document-text-outline" style="font-size: 22px;"></ion-icon>
                    </div>
                    <div class="settings-label flex-grow-1 fw-bold" style="font-size: 14px; color: #1e293b;">Data
                        Administrasi</div>
                    <div class="settings-chevron text-muted"> <ion-icon name="chevron-forward-outline"></ion-icon> </div>
                </a>

                <a href="{{ route('karyawan.profile.darurat') }}"
                    class="settings-item d-flex align-items-center p-3 mb-2 shadow-sm"
                    style="background: white; border-radius: 12px; text-decoration: none; color: inherit;">
                    <div class="icon-box me-3"
                        style="width: 38px; height: 38px; border-radius: 10px; background: #fff1f2; color: #be123c; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <ion-icon name="heart-outline" style="font-size: 22px;"></ion-icon>
                    </div>
                    <div class="settings-label flex-grow-1 fw-bold" style="font-size: 14px; color: #1e293b;">Kontak
                        Darurat</div>
                    <div class="settings-chevron text-muted"> <ion-icon name="chevron-forward-outline"></ion-icon> </div>
                </a>

                <a href="{{ route('karyawan.kenaikan_gaji.index') }}"
                    class="settings-item d-flex align-items-center p-3 mb-2 shadow-sm"
                    style="background: white; border-radius: 12px; text-decoration: none; color: inherit;">
                    <div class="icon-box me-3"
                        style="width: 38px; height: 38px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <ion-icon name="trending-up-outline" style="font-size: 22px;"></ion-icon>
                    </div>
                    <div class="settings-label flex-grow-1 fw-bold" style="font-size: 14px; color: #1e293b;">Ajukan Kenaikan Gaji</div>
                    <div class="settings-chevron text-muted"> <ion-icon name="chevron-forward-outline"></ion-icon> </div>
                </a>

                <form method="POST" action="{{ route('proseslogout') }}" style="margin:0" id="logoutForm">
                    @csrf
                    <button type="button" id="logoutBtn"
                        class="settings-item d-flex align-items-center p-3 mb-2 shadow-sm w-100"
                        style="background: white; border-radius: 12px; text-decoration: none; color: #ef4444; border: none; outline: none; justify-content: flex-start !important; text-align: left !important;">
                        <div class="icon-box me-3"
                            style="width: 38px; height: 38px; border-radius: 10px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <ion-icon name="log-out-outline" style="font-size: 22px;"></ion-icon>
                        </div>
                        <div class="settings-label fw-bold" style="font-size: 14px; text-align: left !important; flex-grow: 1;">Log Out</div>
                        <div style="width: 20px;"></div> {{-- Spacer matching chevron width --}}
                    </button>
                </form>

            </div>
        </div>

        {{-- Custom Logout Modal --}}
        <div id="logoutModal" class="simple-modal" aria-hidden="true">
            <div class="simple-modal-backdrop" id="logoutCancelBackdrop"></div>
            <div class="simple-modal-panel shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="p-4 text-center">
                    <div class="mb-3" style="font-size: 50px; color: #ef4444;">
                        <ion-icon name="log-out-outline"></ion-icon>
                    </div>
                    <h3 class="fw-bold mb-2" style="font-size: 18px; color: #1f2937;">Konfirmasi Logout</h3>
                    <p class="text-muted" style="font-size: 14px;">Apakah Anda yakin ingin keluar dari aplikasi?</p>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light flex-grow-1 fw-bold py-3 mr-2" id="logoutCancel"
                            style="border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;">Batal</button>
                        <button type="button" class="btn btn-danger flex-grow-1 fw-bold py-3" id="logoutConfirm"
                            style="border-radius: 12px; font-size: 14px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">Logout</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
