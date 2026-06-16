@extends('layouts.presensi')
@section('content')
    @php
        $user = Auth::guard('karyawan')->user();
        $deptName = $userWithDept?->departemen?->nama_dept ?? 'DEVELOPER';
        $cabangName = $userWithDept?->cabang?->nama_cabang ?? '-';
    @endphp

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}?v={{ filemtime(public_path('assets/css/style.css')) }}">

    <style>
        .modern-header {
            border-radius: 0 0 18px 18px;
            background: linear-gradient(135deg, #2f8f85 0%, #2a988d 55%, #34a39a 100%);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.14);
            overflow: hidden;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .header-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            min-width: 0;
        }

        .header-info h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
        }

        .header-role {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.2;
            color: rgba(255, 255, 255, 0.9);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .header-company {
            margin: 0;
            font-size: 11px;
            line-height: 1.25;
            opacity: 0.7;
            letter-spacing: 0.2px;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .header-role ion-icon,
        .header-company ion-icon {
            font-size: 13px;
            flex-shrink: 0;
        }

        .header-right {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: center;
            gap: 7px;
        }

        .header-badges-row {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            width: 100%;
        }

        .header-badge {
            background-color: rgba(202, 233, 228, 0.88);
            color: #324b4a;
            padding: 5px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            box-shadow:
                0 3px 8px rgba(2, 6, 23, 0.12),
                0 0 0 1px rgba(255, 255, 255, 0.7) inset;
            border: none;
            line-height: 1;
            white-space: nowrap;
        }

        .header-badge--clickable {
            cursor: pointer;
        }

        .header-badge ion-icon {
            margin-right: 4px;
            font-size: 13px;
        }

        .header-badge--dept {
            width: 100%;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            padding: 5px 10px;
        }

        .sp-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
            backdrop-filter: blur(4px);
        }

        .sp-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .sp-modal-box {
            background: #fff;
            width: 85%;
            max-width: 380px;
            border-radius: 20px;
            overflow: hidden;
            transform: scale(0.8);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .sp-modal-overlay.show .sp-modal-box {
            transform: scale(1);
        }

        .sp-header {
            background: #e74c3c;
            padding: 20px;
            text-align: center;
            color: white;
        }

        .sp-header ion-icon {
            font-size: 48px;
            margin-bottom: 5px;
        }

        .sp-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: white;
        }

        .sp-body {
            padding: 20px 20px 10px 20px;
            color: #333;
            font-size: 13px;
            line-height: 1.5;
        }

        .sp-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }

        .sp-table td {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .sp-table td:first-child {
            color: #777;
            width: 40%;
        }

        .sp-table td:last-child {
            font-weight: 600;
            color: #333;
            text-align: right;
        }

        .sp-note {
            background: #fff5f5;
            border: 1px solid #ffcccc;
            border-radius: 8px;
            padding: 12px;
            color: #c0392b;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .sp-footer {
            padding: 0 20px 20px 20px;
        }

        .btn-sp-confirm {
            width: 100%;
            background: #2c3e50;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-sp-confirm:hover {
            background: #34495e;
        }

        .btn-sp-confirm:active {
            transform: scale(0.98);
        }

        .menu-icon.cs {
            background: linear-gradient(135deg, #2a988d, #1e7169);
            color: #fff;
        }

        .cs-modal .sp-modal-box {
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cs-modal .sp-header {
            background: linear-gradient(135deg, #2a988d 0%, #1e7169 100%);
            position: relative;
            overflow: hidden;
        }

        .sp-header-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 60%);
            pointer-events: none;
            transform: rotate(30deg);
        }

        .cs-notice {
            background: #e6f4f2 !important;
            border: 1px solid #b5e2dd !important;
            color: #1e7169 !important;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .cs-notice ion-icon {
            font-size: 18px;
            flex-shrink: 0;
        }

        .cs-agent-list {
            margin-top: 10px;
        }

        .cs-agent-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01);
        }

        .cs-agent-card:hover {
            transform: translateY(-2px);
            background: #ffffff;
            border-color: #2a988d;
            box-shadow: 0 6px 16px rgba(42, 152, 141, 0.08);
        }

        .cs-agent-info {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex-grow: 1;
        }

        .cs-agent-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #e6f4f2;
            color: #2a988d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: inset 0 2px 4px rgba(42, 152, 141, 0.06);
            letter-spacing: 0.5px;
        }

        .cs-agent-details {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .cs-agent-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cs-agent-status {
            font-size: 11px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 1px;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #10b981;
            display: inline-block;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
            animation: statusPulse 2s infinite ease-in-out;
        }

        @keyframes statusPulse {

            0%,
            100% {
                opacity: 0.6;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }

        .cs-agent-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }

        .cs-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 16px;
            color: #ffffff !important;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .cs-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .cs-btn:active {
            transform: scale(0.95);
        }

        .cs-btn-phone {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
        }

        .cs-btn-phone:hover {
            background: linear-gradient(135deg, #0284c7, #0369a1);
        }

        .cs-btn-wa {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .cs-btn-wa:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .cs-modal .btn-sp-confirm {
            background: #1e293b;
            transition: all 0.2s ease;
        }

        .cs-modal .btn-sp-confirm:hover {
            background: #0f172a;
        }

        /* Card Bonus Performa - Cohesive Theme */
        .performance-bonus-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 16px 20px;
            margin: 15px 0 20px 0;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #2a8f85;
        }

        .performance-bonus-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at top right, rgba(42, 143, 133, 0.03) 0%, transparent 60%);
            pointer-events: none;
            z-index: 1;
        }

        .performance-bonus-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(42, 143, 133, 0.08);
        }

        .bonus-card-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            z-index: 2;
        }

        .bonus-left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .bonus-trophy {
            width: 44px;
            height: 44px;
            background: rgba(245, 158, 11, 0.12);
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.08);
            transition: all 0.3s ease;
        }

        .performance-bonus-card:hover .bonus-trophy {
            transform: scale(1.1) rotate(5deg);
            background: rgba(245, 158, 11, 0.18);
        }

        .bonus-text {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .bonus-text h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
            color: #1f2937;
            letter-spacing: 0.1px;
            line-height: 1.3;
        }

        .bonus-text p {
            margin: 0;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.3;
            font-weight: 500;
        }

        .bonus-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 3px;
            padding: 0 0 0 12px;
            flex-shrink: 0;
        }

        .bonus-amount {
            font-size: 16px;
            font-weight: 800;
            color: #10b981;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }

        .bonus-period {
            color: #6b7280 !important;
            font-weight: 600;
            font-size: 10px !important;
            line-height: 1;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .bonus-amount-placeholder {
            font-size: 10px;
            color: #9ca3af !important;
            font-weight: 600;
            font-style: italic;
            text-align: right;
            line-height: 1.3;
            max-width: 110px;
        }

        @media (max-width: 480px) {
            .performance-bonus-card {
                padding: 14px 16px;
            }

            .bonus-card-content {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .bonus-left {
                width: 100%;
                gap: 12px;
            }

            .bonus-trophy {
                width: 40px;
                height: 40px;
                font-size: 20px;
                border-radius: 10px;
            }

            .bonus-text h4 {
                font-size: 13px;
                line-height: 1.25;
            }

            .bonus-text p {
                font-size: 10px;
                line-height: 1.3;
            }

            .bonus-right {
                width: 100%;
                background: rgba(42, 143, 133, 0.04);
                border: 1px solid rgba(42, 143, 133, 0.08);
                border-radius: 12px;
                padding: 10px 14px;
                margin: 0;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                box-sizing: border-box;
            }

            .bonus-amount {
                font-size: 14px;
                line-height: 1;
            }

            .bonus-period {
                font-size: 9px !important;
                line-height: 1;
                margin: 0;
            }

            .bonus-amount-placeholder {
                width: 100%;
                text-align: center;
                max-width: none !important;
                font-size: 10px;
            }
        }
    </style>

    <div id="appCapsule">
        <div class="modern-header">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-avatar">
                        @if (!empty($user?->foto))
                            @php
                                $path = asset('storage/uploads/karyawan/' . $user->foto);
                            @endphp
                            <img src="{{ $path }}" alt="avatar">
                        @else
                            <img src="{{ asset('assets/img/nophoto.png') }}" alt="avatar">
                        @endif
                    </div>
                    <div class="header-info">
                        <h2>{{ $user?->nama_lengkap ?? 'Team wndev' }}</h2>
                        <p class="header-role">
                            <ion-icon name="person"></ion-icon>
                            {{ $user?->jabatanRel->nama_jabatan ?? 'DEVELOPER' }}
                        </p>
                        <p class="header-company">
                            <ion-icon name="business"></ion-icon>
                            {{ $cabangName }}
                        </p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-badges-row">
                        <span class="header-badge header-badge--clickable"
                            onclick="alert('Poin Absensi: Poin ini adalah total akumulasi presensi Anda sejak tanggal 26 bulan ini. Point Presensi anda sekarang: {{ $userPoints ?? 0 }}')">
                            <ion-icon name="star" style="color: #f59e0b;"></ion-icon>
                            Presensi: <b>{{ $userPoints ?? 0 }}</b>
                        </span>
                        <span class="header-badge header-badge--clickable"
                            onclick="alert('Poin Kinerja: Poin ini adalah total akumulasi poin kinerja KPI Anda sejak tanggal 26 bulan ini. Point KPI anda sekarang: {{ number_format($kpiUserPoints ?? 0) }}')">
                            <ion-icon name="flash" style="color: #3b82f6;"></ion-icon>
                            KPI: <b>{{ number_format($kpiUserPoints ?? 0) }}</b>
                        </span>
                    </div>
                    <span class="header-badge header-badge--dept">{{ $deptName }}</span>
                </div>
            </div>
        </div>

        <div class="container-modern">
            @if ((int) ($user?->is_whitelist ?? 0) === 1)
                <div class="alert alert-info mb-2" style="border-radius: 8px" role="alert">
                    Akun Anda dikecualikan dari sistem presensi harian.
                </div>
            @endif

            @if ($isProfileIncomplete)
                <div class="card shadow-sm mb-3"
                    style="border-radius: 16px; border: 1px solid #fed7aa; background-color: #fffedd;">
                    <div class="card-body p-3 d-flex align-items-center">
                        <div class="mr-2" style="color: #ea580c; display: flex; align-items: center;">
                            <ion-icon name="warning" style="font-size: 26px;"></ion-icon>
                        </div>
                        <div class="flex-grow-1 pe-2">
                            <h4 class="mb-1 fw-bold" style="font-size: 13px; color: #9a3412;">Profil Belum Lengkap</h4>
                            <p class="mb-0" style="font-size: 11px; color: #c2410c; line-height: 1.3;">Mohon lengkapi data
                                pribadi, administrasi & kontak darurat Anda.</p>
                        </div>
                        <a href="{{ route('karyawan.profile.show') }}" class="btn btn-sm rounded-pill px-3 shadow-sm"
                            style="background: #ea580c; color: white; font-size: 11px; font-weight: 600; white-space: nowrap;">
                            Lengkapi
                        </a>
                    </div>
                </div>
            @endif

            <div class="menu-grid">
                <a href="{{ route('karyawan.profile.show') }}" class="menu-card">
                    <div class="menu-icon profil">
                        <ion-icon name="person-sharp"></ion-icon>
                    </div>
                    <span class="menu-name">Profil</span>
                </a>
                <a href="{{ route('karyawan.izin.index') }}" class="menu-card">
                    <div class="menu-icon izin">
                        <ion-icon name="calendar-number"></ion-icon>
                    </div>
                    <span class="menu-name">Izin/Cuti</span>
                </a>
                <a href="{{ route('karyawan.presensi.history') }}" class="menu-card">
                    <div class="menu-icon histori">
                        <ion-icon name="document-text"></ion-icon>
                    </div>
                    <span class="menu-name">Histori</span>
                </a>
                <a href="{{ route('lembur.index') }}" class="menu-card">
                    <div class="menu-icon lembur">
                        <ion-icon name="hourglass-outline"></ion-icon>
                    </div>
                    <span class="menu-name">Lembur</span>
                </a>
                <a href="{{ route('dinasluars.index') }}" class="menu-card">
                    <div class="menu-icon dinasLuar">
                        <ion-icon name="briefcase-outline"></ion-icon>
                    </div>
                    <span class="menu-name">Dinas Luar</span>
                </a>
                @can('kpi-input-karyawan')
                    <a href="{{ route('kpi.user.index') }}" class="menu-card">
                        <div class="menu-icon kpi">
                            <ion-icon name="document-text-outline"></ion-icon>
                        </div>
                        <span class="menu-name">KPI Saya</span>
                    </a>
                @endcan
                @can('kpi-approve-karyawan')
                    <a href="{{ route('kpi.atasan.index') }}" class="menu-card">
                        <div class="menu-icon approve">
                            <ion-icon name="checkbox-outline"></ion-icon>
                        </div>
                        <span class="menu-name">KPI Karyawan</span>
                    </a>
                @endcan
                @can('kenaikan_gaji-view-karyawan')
                    <a href="{{ route('karyawan.kenaikan_gaji.index') }}" class="menu-card">
                        <div class="menu-icon" style="background: linear-gradient(135deg, #10b981, #047857); color: #fff;">
                            <ion-icon name="trending-up-outline"></ion-icon>
                        </div>
                        <span class="menu-name" style="font-size: 10px;">Kenaikan Gaji</span>
                    </a>
                @endcan
                <a href="{{ route('karyawan.profile.administrasi') }}" class="menu-card">
                    <div class="menu-icon" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff;">
                        <ion-icon name="card-outline"></ion-icon>
                    </div>
                    <span class="menu-name">BPJS</span>
                </a>
                <a href="#" class="menu-card" id="menu-cs">
                    <div class="menu-icon cs">
                        <ion-icon name="headset-outline"></ion-icon>
                    </div>
                    <span class="menu-name">Customer Service</span>
                </a>
            </div>

            <div class="cs-modal">
                <div class="sp-modal-overlay" id="cs-modal-overlay">
                    <div class="sp-modal-box">
                        <div class="sp-header">
                            <div class="sp-header-glow"></div>
                            <ion-icon name="headset-outline"></ion-icon>
                            <h3>Layanan Customer Service</h3>
                            <small style="color: rgba(255,255,255,0.85);">Butuh bantuan? Hubungi kami</small>
                        </div>
                        <div class="sp-body">
                            <div class="sp-note cs-notice">
                                <ion-icon name="information-circle"></ion-icon>
                                <span>Tim siap membantu pada jam kerja operasional.</span>
                            </div>
                            <div class="cs-agent-list">
                                <div class="cs-agent-card">
                                    <div class="cs-agent-info">
                                        <div class="cs-agent-avatar">LL</div>
                                        <div class="cs-agent-details">
                                            <span class="cs-agent-name">Liovi Egi Likardo</span>
                                            <span class="cs-agent-status"><span class="status-dot"></span>Staff IT</span>
                                        </div>
                                    </div>
                                    <div class="cs-agent-actions">
                                        <a href="tel:+6283826793210" class="cs-btn cs-btn-phone" title="Telepon">
                                            <ion-icon name="call"></ion-icon>
                                        </a>
                                        <a href="https://wa.me/6283826793210" target="_blank" class="cs-btn cs-btn-wa"
                                            title="WhatsApp">
                                            <ion-icon name="logo-whatsapp"></ion-icon>
                                        </a>
                                    </div>
                                </div>
                                <div class="cs-agent-card">
                                    <div class="cs-agent-info">
                                        <div class="cs-agent-avatar">MK</div>
                                        <div class="cs-agent-details">
                                            <span class="cs-agent-name">Muhammad Nurul Karim</span>
                                            <span class="cs-agent-status"><span class="status-dot"></span>Staff IT</span>
                                        </div>
                                    </div>
                                    <div class="cs-agent-actions">
                                        <a href="tel:+6285163648981" class="cs-btn cs-btn-phone" title="Telepon">
                                            <ion-icon name="call"></ion-icon>
                                        </a>
                                        <a href="https://wa.me/6285163648981" target="_blank" class="cs-btn cs-btn-wa"
                                            title="WhatsApp">
                                            <ion-icon name="logo-whatsapp"></ion-icon>
                                        </a>
                                    </div>
                                </div>
                                <div class="cs-agent-card">
                                    <div class="cs-agent-info">
                                        <div class="cs-agent-avatar">WN</div>
                                        <div class="cs-agent-details">
                                            <span class="cs-agent-name">Wahyu Nugraha</span>
                                            <span class="cs-agent-status"><span class="status-dot"></span>Staff IT</span>
                                        </div>
                                    </div>
                                    <div class="cs-agent-actions">
                                        <a href="tel:+6281262534217" class="cs-btn cs-btn-phone" title="Telepon">
                                            <ion-icon name="call"></ion-icon>
                                        </a>
                                        <a href="https://wa.me/6281262534217" target="_blank" class="cs-btn cs-btn-wa"
                                            title="WhatsApp">
                                            <ion-icon name="logo-whatsapp"></ion-icon>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sp-footer">
                            <button type="button" class="btn-sp-confirm" id="btn-cs-close">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="presence-cards-row">
                <div class="presence-card card-in">
                    <div class="presence-content">
                        @php
                            $hasPhotoIn =
                                isset($presensihariini) && $presensihariini !== null && $presensihariini->status == 'h';
                        @endphp
                        @if ($hasPhotoIn)
                            <div class="presence-img presence-img-disabled">
                                @php
                                    $path = asset('storage/uploads/absensi/' . $presensihariini->foto_in);
                                @endphp
                                <img src="{{ $path }}" alt="Presensi Masuk">
                            </div>
                        @elseif ((int) ($user?->is_whitelist ?? 0) === 0)
                            <a href="{{ route('karyawan.presensi.create') }}"
                                class="presence-img presence-img-clickable">
                                <ion-icon name="camera"></ion-icon>
                            </a>
                        @else
                            <div class="presence-img presence-img-disabled">
                                <ion-icon name="ban-outline"></ion-icon>
                            </div>
                        @endif
                        <div class="presence-detail">
                            <h4>Masuk</h4>
                            <span>
                                @if (isset($presensihariini) && $presensihariini->status == 'x')
                                    <span class="text-danger fw-bold">Dianulir</span>
                                @else
                                    {{ $presensihariini?->jam_in != '00:00:00' && $presensihariini?->jam_in != null ? $presensihariini?->jam_in : ($presensihariini?->status == 'i' ? 'Izin' : ($presensihariini?->status == 's' ? 'Sakit' : ($presensihariini?->status == 'c' ? 'Cuti' : ($presensihariini?->status == 'r' ? 'Roster' : 'Belum Absen')))) }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="presence-card card-out">
                    <div class="presence-content">
                        @php
                            $hasPhotoOut =
                                isset($presensihariini) &&
                                $presensihariini !== null &&
                                $presensihariini->jam_out !== null &&
                                $presensihariini->jam_out !== '00:00:00' &&
                                $presensihariini->status == 'h';
                        @endphp
                        @if ($hasPhotoOut)
                            <div class="presence-img presence-img-disabled">
                                @php
                                    $path = asset('storage/uploads/absensi/' . $presensihariini->foto_out);
                                @endphp
                                <img src="{{ $path }}" alt="Presensi Pulang">
                            </div>
                        @elseif ((int) ($user?->is_whitelist ?? 0) === 0)
                            <a href="/presensi/create" class="presence-img presence-img-clickable">
                                <ion-icon name="camera"></ion-icon>
                            </a>
                        @else
                            <div class="presence-img presence-img-disabled">
                                <ion-icon name="ban-outline"></ion-icon>
                            </div>
                        @endif
                        <div class="presence-detail">
                            <h4>Pulang</h4>
                            <span>
                                @if (isset($presensihariini) && $presensihariini->status == 'x')
                                    <span class="text-danger fw-bold">Dianulir</span>
                                @else
                                    {{ $presensihariini?->jam_out != '00:00:00' && $presensihariini?->jam_out != null ? $presensihariini?->jam_out : ($presensihariini?->status != 'h' ? 'Tidak Ada' : 'Belum Absen') }}
                                @endif
                                @if (!empty($isPulangCepatHariIni) && $presensihariini?->jam_out != '00:00:00' && $presensihariini?->jam_out != null)
                                    (Pulang Cepat)
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rekap-section">
                <h3 class="rekap-title">
                    Rekap Presensi
                    @if (isset($bulanini) && isset($namabulan[$bulanini]))
                        <span>{{ $namabulan[$bulanini] }}</span>
                    @else
                        <span>Bulan Tidak Dikenal</span>
                    @endif
                    Tahun {{ $tahunini }}
                </h3>
                <div class="rekap-grid">
                    <div class="rekap-card">
                        <span class="rekap-badge">{{ $rekappresensi?->jmlhadir ?? 0 }}</span>
                        <div class="rekap-icon hadir">
                            <ion-icon name="accessibility-outline"></ion-icon>
                        </div>
                        <div class="rekap-label">Hadir</div>
                    </div>

                    <div class="rekap-card">
                        <span class="rekap-badge">{{ $rekapizin?->jmlizin ?? 0 }}</span>
                        <div class="rekap-icon izin">
                            <ion-icon name="newspaper-outline"></ion-icon>
                        </div>
                        <div class="rekap-label">Izin</div>
                    </div>

                    <div class="rekap-card">
                        <span class="rekap-badge">{{ $rekapizin?->jmlsakit ?? 0 }}</span>
                        <div class="rekap-icon sakit">
                            <ion-icon name="medkit-outline"></ion-icon>
                        </div>
                        <div class="rekap-label">Sakit</div>
                    </div>

                    <div class="rekap-card">
                        <span class="rekap-badge">{{ $rekapizin?->jmlcuti ?? 0 }}</span>
                        <div class="rekap-icon cuti">
                            <ion-icon name="airplane-outline"></ion-icon>
                        </div>
                        <div class="rekap-label">Cuti</div>
                    </div>

                    <div class="rekap-card">
                        <span class="rekap-badge">{{ $rekappresensi?->jmlterlambat ?? 0 }}</span>
                        <div class="rekap-icon telat">
                            <ion-icon name="alarm-outline"></ion-icon>
                        </div>
                        <div class="rekap-label">Telat</div>
                    </div>
                </div>
            </div>

            @if (isset($userRank))
                <div class="performance-bonus-card">
                    <div class="bonus-card-content">
                        <div class="bonus-left">
                            <div class="bonus-trophy">
                                <ion-icon name="trophy"></ion-icon>
                            </div>
                            <div class="bonus-text">
                                <h4>Saat ini, Anda peringkat {{ $userRank }}!</h4>
                                <p>Top Presensi {{ $cabangName }}.</p>
                            </div>
                        </div>
                        <div class="bonus-right">
                            @if (!empty($rekapBulananUser) && isset($rekapBulananUser->bonus_bulanan))
                                <span class="bonus-period">Bonus Performa {{ $bonusPeriodText }}</span>
                                <span class="bonus-amount">Rp
                                    {{ number_format($rekapBulananUser->bonus_bulanan, 0, ',', '.') }}</span>
                            @else
                                <span class="bonus-amount-placeholder">Menunggu Hasil Rekap Bulanan</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            <div class="tab-modern">
                <div class="tab-nav mb-2">
                    <div class="tab-nav-item active" data-tab="home" style="font-size: 13px; padding: 10px 5px;">
                        Presensi</div>
                    <div class="tab-nav-item" data-tab="profile" style="font-size: 13px; padding: 10px 5px;">Top Presensi
                    </div>
                    <div class="tab-nav-item" data-tab="kpi-lb" style="font-size: 13px; padding: 10px 5px;">Top KPI</div>
                </div>

                <div class="tab-content-pane active" id="home">
                    @foreach ($historibulanini as $d)
                        <div class="card mb-2" style="border-radius: 10px;">
                            <div class="card-body py-2 px-3">
                                <div class="historiscontent d-flex align-items-center">
                                    <div class="iconpresensi me-3 d-flex align-items-center" style="padding-right: 12px;">
                                        @if ($d->status == 'h')
                                            @if (!empty($d->is_pulang_cepat))
                                                <ion-icon name="log-out-outline" style="font-size: 40px;"
                                                    class="text-primary"></ion-icon>
                                            @else
                                                <ion-icon name="finger-print-outline" style="font-size: 40px;"
                                                    class="text-success"></ion-icon>
                                            @endif
                                        @elseif ($d->status == 'i')
                                            <ion-icon name="document-outline" style="font-size: 40px;"
                                                class="text-warning"></ion-icon>
                                        @elseif ($d->status == 's')
                                            <ion-icon name="medkit-outline" style="font-size: 40px;"
                                                class="text-danger"></ion-icon>
                                        @elseif ($d->status == 'c')
                                            <ion-icon name="airplane-outline" style="font-size: 40px;"
                                                class="text-info"></ion-icon>
                                        @elseif ($d->status == 'r')
                                            <ion-icon name="calendar-clear-outline" style="font-size: 40px;"
                                                class="text-primary"></ion-icon>
                                        @elseif ($d->status == 'x')
                                            <ion-icon name="close-circle-outline" style="font-size: 40px;"
                                                class="text-danger"></ion-icon>
                                        @elseif ($d->status == 'a')
                                            <ion-icon name="close-circle-outline" style="font-size: 40px;"
                                                class="text-danger"></ion-icon>
                                        @endif
                                    </div>

                                    <div class="datapresensi flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-baseline">
                                            <h4 style="margin:0px; font-size: 14px; font-weight: bold;">
                                                @if ($d->status == 'h')
                                                    @if (!empty($d->dinas_luar_id))
                                                        Dinas Luar
                                                    @elseif (!empty($d->is_pulang_cepat))
                                                        Pulang Cepat
                                                    @else
                                                        {{ $d->nama_jam_kerja ?? 'NON SHIFT' }}
                                                    @endif
                                                @else
                                                    @if ($d->status == 'i')
                                                        Izin
                                                    @elseif ($d->status == 's')
                                                        Sakit
                                                    @elseif ($d->status == 'c')
                                                        Cuti
                                                    @elseif ($d->status == 'r')
                                                        Roster
                                                    @elseif ($d->status == 'x')
                                                        Dianulir HR
                                                    @elseif ($d->status == 'a' || $d->status == 'd')
                                                        {{ $d->keterangan ?? 'Alpha / Dinas' }}
                                                    @endif
                                                @endif
                                            </h4>
                                            <div class="text-right d-flex flex-column align-items-end">
                                                <span
                                                    style="font-size: 12px; color: #6c757d;">{{ date('d-m-Y', strtotime($d->tgl_presensi)) }}</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-baseline"
                                            style="line-height: 1.2;">
                                            <span style="font-size: 14px;">
                                                @if ($d->status == 'h')
                                                    @if ($d->jam_in != null && $d->jam_in != '00:00:00')
                                                        {{ date('H:i', strtotime($d->jam_in)) }}
                                                    @else
                                                        <span class="text-danger">Belum Scan</span>
                                                    @endif
                                                @else
                                                    <span class="text-secondary">Belum Scan</span>
                                                @endif

                                                @if ($d->status == 'h' && $d->jam_out != null && $d->jam_out != '00:00:00')
                                                    - {{ date('H:i', strtotime($d->jam_out)) }}
                                                @endif
                                            </span>

                                            <div class="d-flex align-items-center" style="gap: 5px;">
                                                @if ($d->status != 'h')
                                                    <span class="text-danger" style="font-size: 13px;">
                                                        @if ($d->status == 'i')
                                                            Izin
                                                        @elseif ($d->status == 's')
                                                            Sakit
                                                        @elseif ($d->status == 'c')
                                                            Cuti
                                                        @elseif ($d->status == 'r')
                                                            Roster
                                                        @elseif ($d->status == 'x')
                                                            Dianulir
                                                        @elseif ($d->status == 'a')
                                                            {{-- Text Alpha/Mangkir dihapus sesuai request, hanya menyisakan angka poin di bawahnya --}}
                                                        @elseif ($d->status == 'd')
                                                            Dinas Luar
                                                        @endif
                                                    </span>
                                                @elseif (!empty($d->is_pulang_cepat))
                                                    <span class="text-primary" style="font-size: 13px;">Pulang
                                                        Cepat</span>
                                                @endif

                                                @if (isset($d->daily_points) && !empty($d->daily_points->point_details))
                                                    @foreach ($d->daily_points->point_details as $pts)
                                                        @if ($pts != 0)
                                                            @php
                                                                $label = 'Poin Kehadiran';
                                                                if ($pts == -10) {
                                                                    $label = 'Poin Terlambat';
                                                                } elseif ($pts == -5) {
                                                                    $label = 'Poin Lupa Absen Pulang';
                                                                } elseif ($pts == -20) {
                                                                    $label = 'Poin Mangkir / Alpha';
                                                                } elseif ($pts == 25) {
                                                                    $label =
                                                                        (isset($d->status) && $d->status == 'd') ||
                                                                        !empty($d->dinas_luar_id)
                                                                            ? 'Poin Dinas Luar'
                                                                            : 'Poin Datang Lebih Awal (>30 Menit)';
                                                                } elseif ($pts == 5) {
                                                                    $label = 'Poin Izin Terlambat';
                                                                } elseif ($pts == 35) {
                                                                    $label = 'Poin Datang Sangat Awal (>60 Menit)';
                                                                } elseif ($pts == 15) {
                                                                    $label = 'Poin Datang Awal (>15 Menit)';
                                                                } elseif ($pts == 1) {
                                                                    $label = 'Poin Tepat Waktu';
                                                                } elseif ($pts > 0) {
                                                                    $label =
                                                                        'Poin Datang Lebih Awal (' . $pts . ' Menit)';
                                                                }
                                                            @endphp
                                                            <span class="badge"
                                                                onclick="alert('{{ $label }}: {{ $pts > 0 ? '+' : '' }}{{ $pts }}')"
                                                                title="{{ $label }}"
                                                                style="cursor: pointer; font-size: 11px; font-weight: bold; color: {{ $pts > 0 ? '#059669' : '#dc2626' }}; background: {{ $pts > 0 ? '#e8f5e9' : '#ffebee' }}; margin-left: 4px;">
                                                                {{ $pts > 0 ? '+' : '' }}{{ number_format($pts) }}
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </div>

                                            <div id="keterangan" class="mt-0" style="font-size: 13px;">
                                                @if ($d->status == 'h')
                                                    @if (!empty($d->dinas_luar_id))
                                                        @if ($d->jam_in != '00:00:00' && !empty($d->jam_in))
                                                            <span style="color: #8b5cf6; font-weight: 500;">Dinas Luar -
                                                                Hadir</span>
                                                        @else
                                                            <span class="text-danger" style="font-weight: 500;">Tidak
                                                                Absen</span>
                                                        @endif
                                                    @elseif (!empty($d->is_pulang_cepat))
                                                        <span class="text-primary" style="font-weight: 500;">Pulang sesuai
                                                            jadwal via approval admin</span>
                                                    @else
                                                        @php
                                                            $jam_in = $d->jam_in;
                                                            $jam_masuk = $d->jam_masuk;
                                                            $tgl_presensi = date('Y-m-d', strtotime($d->tgl_presensi));

                                                            if (strpos($jam_in, ' ') !== false) {
                                                                $jam_in = end(explode(' ', $jam_in));
                                                            }
                                                            if (strpos($jam_masuk, ' ') !== false) {
                                                                $jam_masuk = end(explode(' ', $jam_masuk));
                                                            }

                                                            $jadwal_jam_masuk = $tgl_presensi . ' ' . $jam_masuk;
                                                            $jam_presensi = $tgl_presensi . ' ' . $jam_in;
                                                        @endphp

                                                        @if ($d->jam_in != '00:00:00' && !empty($jam_in) && !empty($jam_masuk) && $jam_in > $jam_masuk)
                                                            @php
                                                                $datetime1 = new DateTime($jadwal_jam_masuk);
                                                                $datetime2 = new DateTime($jam_presensi);
                                                                $interval = $datetime1->diff($datetime2);
                                                                $jam_terlambat = $interval->h + $interval->days * 24;
                                                                $menit_terlambat = $interval->i;
                                                                $total_menit = $jam_terlambat * 60 + $menit_terlambat;
                                                            @endphp
                                                            <span class="text-danger" style="font-weight: 500;">
                                                                Terlambat {{ $jam_terlambat }} Jam {{ $menit_terlambat }}
                                                                Menit ({{ $total_menit }} Menit)
                                                            </span>
                                                        @elseif ($d->jam_in != '00:00:00' && !empty($jam_in))
                                                            <span style="color: green; font-weight: 500;">Tepat
                                                                Waktu</span>
                                                        @else
                                                            <span class="text-danger" style="font-weight: 500;">Tidak
                                                                Absen
                                                                Masuk</span>
                                                        @endif
                                                    @endif
                                                @elseif ($d->status == 'x')
                                                    <span class="text-danger" style="font-weight: 500;">Dianulir (Silakan
                                                        Scan Masuk Ulang)</span>
                                                @else
                                                    <span class="text-secondary">Status Non-Hadiran.</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if (isset($d->is_selected) && $d->is_selected)
                                        <div style="height: 5px; background-color: #9370DB; border-radius: 0 0 10px 10px;">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="tab-content-pane" id="profile">
                    <div style="padding: 0 15px;">
                        @foreach ($leaderboard as $idx => $karyawan)
                            <div class="leaderboard-item">
                                <div class="leaderboard-avatar">
                                    @if (!empty($karyawan->foto))
                                        <img src="{{ asset('storage/uploads/karyawan/' . $karyawan->foto) }}"
                                            alt="avatar">
                                    @else
                                        <img src="{{ asset('assets/img/nophoto.png') }}" alt="avatar">
                                    @endif
                                </div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name" style="font-weight:700; color:#334155;">
                                        <span class="badge"
                                            style="background-color:#fffbeb; color:#d97706; font-size:9px; padding:2px 4px; border-radius:4px; border:1px solid #fde68a;">#{{ $idx + 1 }}</span>
                                        {{ $karyawan->nama_lengkap ?? $karyawan->nik }}
                                    </div>
                                    <div class="leaderboard-role">
                                        {{ $karyawan->jabatan_nama ?? 'Tidak Ada Jabatan' }}
                                    </div>
                                </div>
                                <span class="leaderboard-badge"
                                    style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight:700;">
                                    {{ number_format($karyawan->total_points) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="tab-content-pane" id="kpi-lb">
                    <div style="padding: 0 15px;">
                        @forelse ($kpiLeaderboard as $idx => $karyawan)
                            <div class="leaderboard-item">
                                <div class="leaderboard-avatar">
                                    @if (!empty($karyawan->foto))
                                        <img src="{{ asset('storage/uploads/karyawan/' . $karyawan->foto) }}"
                                            alt="avatar">
                                    @else
                                        <img src="{{ asset('assets/img/nophoto.png') }}" alt="avatar">
                                    @endif
                                </div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name" style="font-weight:700; color:#334155;">
                                        <span class="badge"
                                            style="background-color:#e0f2fe; color:#0369a1; font-size:9px; padding:2px 4px; border-radius:4px; border:1px solid #bae6fd;">#{{ $idx + 1 }}</span>
                                        {{ $karyawan->nama_lengkap ?? $karyawan->nik }}
                                    </div>
                                    <div class="leaderboard-role">
                                        {{ $karyawan->jabatan_nama ?? 'Tidak Ada Jabatan' }}
                                    </div>
                                </div>
                                <span class="leaderboard-badge"
                                    style="background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; font-weight:700;">
                                    {{ number_format($karyawan->total_points) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-muted"
                                style="padding: 30px 0; font-size: 14px; font-style:italic;">Belum ada data KPI klasemen.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (isset($cekSP) && !empty($cekSP))
        <div class="sp-modal-overlay" id="sp-modal-overlay">
            <div class="sp-modal-box">
                <div class="sp-header">
                    <ion-icon name="warning-outline"></ion-icon>
                    <h3>STATUS SP AKTIF</h3>
                    <small style="color: rgba(255,255,255,0.8);">Surat Peringatan Tingkat {{ $cekSP->level }}</small>
                </div>
                <div class="sp-body">
                    <p>Halo <b>{{ $user->nama_lengkap }}</b>, sistem mencatat adanya surat peringatan yang masih berlaku
                        pada akun Anda.</p>

                    <table class="sp-table">
                        <tr>
                            <td>Pelanggaran</td>
                            <td>
                                @if ($cekSP->violation_type == 'late')
                                    <span style="color:#d35400;">Terlambat Beruntun</span>
                                @elseif($cekSP->violation_type == 'absent')
                                    <span style="color:#c0392b;">Alpha / Tanpa Keterangan</span>
                                @else
                                    {{ strtoupper($cekSP->violation_type) }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Tanggal Terbit</td>
                            <td>{{ date('d/m/Y', strtotime($cekSP->issued_at)) }}</td>
                        </tr>
                        <tr>
                            <td>Berlaku S.d</td>
                            <td style="color:#c0392b;">{{ date('d/m/Y', strtotime($cekSP->expires_at)) }}</td>
                        </tr>
                    </table>

                    <div class="sp-note">
                        <b>Catatan:</b><br>
                        "{{ $cekSP->note }}"
                    </div>

                    <div style="text-align: center; font-size: 11px; color: #999; margin-bottom: 5px;">
                        Notifikasi ini muncul 1x seminggu.
                    </div>
                </div>
                <div class="sp-footer">
                    <button type="button" class="btn-sp-confirm" id="btn-sp-close">Saya Mengerti & Akan
                        Memperbaiki</button>
                </div>
            </div>
        </div>
    @endif
    <div class="bottom-nav">
        <a href="{{ route('dashboard.karyawan') }}" class="nav-item active">
            <div class="nav-icon"><ion-icon name="home"></ion-icon></div>
            <span>Home</span>
        </a>
        <a href="{{ route('karyawan.presensi.history') }}" class="nav-item">
            <div class="nav-icon"><ion-icon name="document-text"></ion-icon></div>
            <span>Histori</span>
        </a>
        <a href="{{ route('karyawan.izin.index') }}" class="nav-item">
            <div class="nav-icon"><ion-icon name="calendar"></ion-icon></div>
            <span>Izin/Cuti</span>
        </a>
        <a href="{{ route('karyawan.profile.show') }}" class="nav-item">
            <div class="nav-icon"><ion-icon name="person"></ion-icon></div>
            <span>Profil</span>
        </a>
    </div>

    <script>
        document.querySelectorAll('.tab-nav-item').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-nav-item').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content-pane').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(tabName).classList.add('active');
            });
        });

        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

        function setCookie(cname, cvalue, exdays) {
            const d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            let expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) {
            let name = cname + "=";
            let decodedCookie = decodeURIComponent(document.cookie);
            let ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        document.addEventListener("DOMContentLoaded", function() {
            @if (isset($cekSP) && !empty($cekSP))
                // ID Unik berdasarkan ID SP dan User NIK
                const spId = "{{ $cekSP->id }}";
                const userNik = "{{ $user->nik }}";
                const cookieName = "sp_custom_alert_v1_" + spId + "_" + userNik;

                const modalOverlay = document.getElementById('sp-modal-overlay');
                const btnClose = document.getElementById('btn-sp-close');

                // Cek Cookie
                if (getCookie(cookieName) === "") {
                    // Jika belum ada cookie, tampilkan modal
                    if (modalOverlay) {
                        // Delay sedikit agar transisi smooth saat load
                        setTimeout(() => {
                            modalOverlay.classList.add('show');
                        }, 500);
                    }
                }

                // Event Listener Tombol Tutup
                if (btnClose) {
                    btnClose.addEventListener('click', function() {
                        // Sembunyikan Modal
                        modalOverlay.classList.remove('show');
                        // Set Cookie 7 Hari
                        setCookie(cookieName, "read", 7);
                    });
                }
            @endif
        });

        // CS Modal
        const csCard = document.getElementById('menu-cs');
        const csOverlay = document.getElementById('cs-modal-overlay');
        const csClose = document.getElementById('btn-cs-close');
        if (csCard && csOverlay) {
            csCard.addEventListener('click', function(e) {
                e.preventDefault();
                csOverlay.classList.add('show');
            });
        }
        if (csClose) {
            csClose.addEventListener('click', function() {
                csOverlay.classList.remove('show');
            });
        }
    </script>
@endsection

@if (isset($pengumuman) && $pengumuman->count() > 0)
    <style>
        .modal-banner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
            backdrop-filter: blur(2px);
        }

        .modal-banner-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-banner-box {
            background: #fff;
            width: 90%;
            max-width: 400px;
            max-height: 85vh;
            border-radius: 16px;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transform: scale(0.8);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
        }

        .modal-banner-overlay.show .modal-banner-box {
            transform: scale(1);
        }

        .btn-close-banner {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            font-size: 24px;
            line-height: 1;
            color: #333;
            cursor: pointer;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .btn-close-banner:active {
            transform: scale(0.9);
        }

        #banner-slides {
            width: 100%;
            flex-grow: 1;
            overflow-y: auto;
            position: relative;
            background-color: #f8f9fa;
        }

        .banner-slide {
            width: 100%;
            display: none;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .banner-img {
            width: 100%;
            height: auto;
            max-height: 350px;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid #eee;
        }

        .banner-content {
            padding: 20px 24px;
            text-align: center;
        }

        .banner-title {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 12px;
            color: #1e293b;
            line-height: 1.3;
        }

        .banner-text {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
            white-space: pre-line;
        }

        .banner-nav-btn {
            position: absolute;
            top: 45%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 40;
            font-size: 24px;
            transition: background 0.2s;
            user-select: none;
        }

        .banner-nav-btn:hover {
            background: rgba(0, 0, 0, 0.6);
        }

        .banner-prev {
            left: 10px;
        }

        .banner-next {
            right: 10px;
        }

        .banner-indicators {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 12px 0 16px 0;
            background: #fff;
            border-top: 1px solid #f1f5f9;
        }

        .banner-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #cbd5e1;
            cursor: pointer;
            transition: all 0.3s;
        }

        .banner-dot.active {
            background: #0f172a;
            transform: scale(1.3);
        }
    </style>

    <div class="modal-banner-overlay" id="banner-overlay">
        <div class="modal-banner-box">
            <button class="btn-close-banner" id="banner-close">&times;</button>
            <div id="banner-slides">
                @foreach ($pengumuman as $key => $p)
                    <div class="banner-slide" data-index="{{ $key }}">
                        @if (!empty($p->gambar))
                            <img src="{{ asset('storage/' . $p->gambar) }}" class="banner-img" alt="Pengumuman">
                        @endif
                        @if (!empty($p->judul) || !empty($p->isi))
                            <div class="banner-content">
                                @if (!empty($p->judul))
                                    <div class="banner-title">{{ $p->judul }}</div>
                                @endif
                                @if (!empty($p->isi))
                                    <div class="banner-text">{{ $p->isi }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($pengumuman->count() > 1)
                <button class="banner-nav-btn banner-prev" id="btn-prev">&#8249;</button>
                <button class="banner-nav-btn banner-next" id="btn-next">&#8250;</button>
                <div class="banner-indicators" id="banner-indicators">
                    @foreach ($pengumuman as $key => $p)
                        <div class="banner-dot {{ $loop->first ? 'active' : '' }}"
                            onclick="goToSlide({{ $key }})"></div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const latestId = "{{ $pengumuman->max('id') }}";
            const sessionKey = 'seen_banner_v3_' + latestId;
            const overlay = document.getElementById('banner-overlay');
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.banner-dot');
            let currentIndex = 0;
            let slideInterval;

            if (!sessionStorage.getItem(sessionKey)) {
                setTimeout(() => {
                    overlay.classList.add('show');
                    showSlide(0);
                    startAutoPlay();
                }, 800);
            }

            window.showSlide = function(index) {
                if (index >= slides.length) currentIndex = 0;
                else if (index < 0) currentIndex = slides.length - 1;
                else currentIndex = index;
                slides.forEach(slide => slide.style.display = 'none');
                dots.forEach(dot => dot.classList.remove('active'));
                slides[currentIndex].style.display = 'block';
                if (dots.length > 0) dots[currentIndex].classList.add('active');
            }

            window.goToSlide = function(index) {
                showSlide(index);
                resetAutoPlay();
            }

            function nextSlide() {
                showSlide(currentIndex + 1);
            }

            function prevSlide() {
                showSlide(currentIndex - 1);
            }

            function startAutoPlay() {
                if (slides.length > 1) slideInterval = setInterval(nextSlide, 5000);
            }

            function resetAutoPlay() {
                clearInterval(slideInterval);
                startAutoPlay();
            }

            const btnNext = document.getElementById('btn-next');
            const btnPrev = document.getElementById('btn-prev');
            if (btnNext) btnNext.addEventListener('click', (e) => {
                e.stopPropagation();
                nextSlide();
                resetAutoPlay();
            });
            if (btnPrev) btnPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                prevSlide();
                resetAutoPlay();
            });

            document.getElementById('banner-close').addEventListener('click', function() {
                overlay.classList.remove('show');
                sessionStorage.setItem(sessionKey, 'true');
                clearInterval(slideInterval);
            });

            let touchStartX = 0;
            let touchEndX = 0;
            const swipeBox = document.querySelector('.modal-banner-box');
            if (slides.length > 1) {
                swipeBox.addEventListener('touchstart', e => {
                    touchStartX = e.changedTouches[0].screenX;
                }, {
                    passive: true
                });
                swipeBox.addEventListener('touchend', e => {
                    touchEndX = e.changedTouches[0].screenX;
                    if (touchEndX < touchStartX - 50) {
                        nextSlide();
                        resetAutoPlay();
                    }
                    if (touchEndX > touchStartX + 50) {
                        prevSlide();
                        resetAutoPlay();
                    }
                }, {
                    passive: true
                });
            }
        });
    </script>
@endif
