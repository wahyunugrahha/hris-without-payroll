@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="{{ route('dashboard.karyawan') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Riwayat KPI Saya</span>
        <div class="header-spacer" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    <div class="row" style="margin-top: 20px; padding-bottom: 80px;">
        <div class="col">

            {{-- FORM PENCARIAN --}}
            <div class="card search-card mb-3">
                <div class="card-body p-3">
                    <form action="{{ route('kpi.user.index') }}" method="GET">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <select name="bulan" id="bulan" class="form-control custom-input"
                                        onchange="this.form.submit()">
                                        @foreach ($periodeList as $val => $teks)
                                            <option value="{{ $val }}"
                                                {{ intval($reqBulan) == $val ? 'selected' : '' }}>
                                                {{ $teks }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <select name="tahun" id="tahun" class="form-control custom-input"
                                        onchange="this.form.submit()">
                                        @for ($t = $tahunSekarang; $t >= $tahunMulai; $t--)
                                            <option value="{{ $t }}"
                                                {{ intval($reqTahun) == $t ? 'selected' : '' }}>
                                                {{ $t }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <button class="btn btn-theme btn-block">
                                    <ion-icon name="search-outline"></ion-icon> Cari Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ALERT JIKA ADA BAWAHAN BELUM ISI KPI --}}
            @if (isset($bawahanBelumIsi) && $bawahanBelumIsi->isNotEmpty() && !$kpiHariIni)
                <div class="alert mb-3"
                    style="background-color: #fff8e1; border: 1px dashed #f57f17; border-radius: 12px; font-size: 13px; color: #d84315;">
                    <div class="d-flex align-items-center mb-1">
                        <ion-icon name="warning" style="font-size: 18px; margin-right: 6px;"></ion-icon>
                        <strong style="font-size: 14px;">Tindakan Diperlukan!</strong>
                    </div>
                    Anda belum bisa menginput KPI hari ini karena bawahan berikut belum membuat laporan KPI mereka:
                    <ul class="mb-0 mt-1" style="padding-left: 20px;">
                        @foreach ($bawahanBelumIsi as $bawahan)
                            <li><b>{{ $bawahan->nama_lengkap }}</b></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- TOMBOL INPUT HARI INI --}}
            @if (!$kpiHariIni)
                <div class="mb-3">
                    @if (isset($bawahanBelumIsi) && $bawahanBelumIsi->isNotEmpty())
                        {{-- Kondisi 1: Karyawan adalah Atasan, tapi bawahannya belum ngisi --}}
                        <a href="#" onclick="alertTungguBawahan(event)"
                            class="btn btn-outline-theme btn-block dashed-btn" style="opacity: 0.6;">
                            <ion-icon name="lock-closed-outline" style="font-size: 18px;"></ion-icon>
                            Input KPI Terkunci
                        </a>
                    @elseif (isset($isConfigured) && $isConfigured)
                        {{-- Kondisi 2: Karyawan biasa / Atasan yang bawahannya sudah ngisi semua --}}
                        <a href="{{ route('kpi.user.create') }}" class="btn btn-outline-theme btn-block dashed-btn">
                            <ion-icon name="add-circle-outline" style="font-size: 18px;"></ion-icon>
                            Input KPI Hari Ini
                        </a>
                    @else
                        {{-- Kondisi 3: Master KPI belum diatur HRD --}}
                        <a href="#" onclick="blockAkses(event)" class="btn btn-outline-theme btn-block dashed-btn">
                            <ion-icon name="add-circle-outline" style="font-size: 18px;"></ion-icon>
                            Input KPI Hari Ini
                        </a>
                    @endif
                </div>
            @endif

            {{-- LIST RIWAYAT KPI --}}
            <div class="row mt-1">
                <div class="col-12" id="historiList">
                    <h6 class="section-title mb-3">Histori Pengajuan</h6>

                    @forelse ($riwayatKPI as $item)
                        <a href="{{ route('kpi.user.edit', $item->id) }}" class="card-kpi text-decoration-none">
                            <div class="card-body histori-item">
                                <div class="d-flex align-items-center">
                                    {{-- Icon --}}
                                    <div class="icon-container mr-3">
                                        <ion-icon name="document-text-outline"></ion-icon>
                                    </div>

                                    {{-- Tambahkan min-width: 0; agar elemen tidak melebar merusak layout utama --}}
                                    <div class="info-col flex-grow-1" style="min-width: 0;">
                                        <h6 class="kpi-date mb-0">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                        </h6>

                                        {{-- Hapus d-flex, biarkan berjalan normal sebagai inline-block --}}
                                        <div class="mt-1">

                                            {{-- 1. Status Utama --}}
                                            @if ($item->status == 'rejected')
                                                <span class="badge badge-soft-danger"
                                                    style="margin-bottom: 4px; display: inline-block;">Perlu Revisi</span>
                                            @elseif ($item->status == 'submitted')
                                                <span class="badge badge-soft-warning"
                                                    style="margin-bottom: 4px; display: inline-block;">Menunggu
                                                    Approval</span>
                                            @elseif ($item->status == 'draft')
                                                <span class="badge badge-soft-secondary"
                                                    style="margin-bottom: 4px; display: inline-block;">Draft</span>
                                            @endif

                                            {{-- 2. Status Persetujuan --}}
                                            {{-- Tambahkan white-space: normal agar teks bisa turun baris jika kepanjangan --}}
                                            @if (!empty($item->approve_atasan))
                                                <span class="badge badge-soft-success"
                                                    style="margin-bottom: 4px; display: inline-block; white-space: normal; text-align: left; line-height: 1.4;">
                                                    Disetujui Atasan: {{ $item->nama_atasan_display }}
                                                </span>
                                            @endif

                                            @if (!empty($item->approve_hr))
                                                <span class="badge badge-soft-primary"
                                                    style="margin-bottom: 4px; display: inline-block; white-space: normal; text-align: left; line-height: 1.4;">
                                                    Disetujui HR: {{ $item->nama_hr_display }} (Final)
                                                </span>
                                            @endif

                                            @if (isset($item->daily_points) && $item->daily_points != 0)
                                                <span class="badge"
                                                    style="margin-bottom: 4px; display: inline-block; font-weight: bold; color: {{ $item->daily_points > 0 ? '#094b87' : '#dc2626' }}; background: {{ $item->daily_points > 0 ? '#e8f5e9' : '#ffebee' }};">
                                                    {{ $item->daily_points > 0 ? '+' : '' }}{{ number_format($item->daily_points) }}
                                                </span>
                                            @endif

                                        </div>
                                    </div>

                                    {{-- Arrow --}}
                                    <div class="arrow-col">
                                        <ion-icon name="chevron-forward-outline"></ion-icon>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="empty-state">
                            <div class="icon-empty">
                                <ion-icon name="file-tray-outline"></ion-icon>
                            </div>
                            <p>Belum ada data KPI pada periode ini.</p>
                            <small class="text-muted">Silakan input KPI atau cari bulan lain.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // 1. Alert Akses Ditolak (Dipanggil saat tombol diklik)
        function blockAkses(e) {
            e.preventDefault(); // Mencegah pindah halaman

            Swal.fire({
                title: 'Akses Ditolak!',
                html: `
                    <div style="text-align: center;">
                        <div style="font-size: 50px; color: #e74c3c; margin-bottom: 10px;">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                        </div>
                        <h5 style="font-weight: 700; color: #333; margin-bottom: 15px;">KPI Belum Tersedia</h5>
                        <p style="font-size: 13px; color: #666; line-height: 1.6;">
                            Mohon maaf, Anda belum dapat melakukan input KPI karena penilaian KPI belum diatur oleh HRD.<br>
                            <hr style="margin: 10px 0; border-top: 1px dashed #ddd;">
                            <div style="text-align: left; background: #f9f9f9; padding: 10px; border-radius: 8px; font-size: 12px;">
                                <b>Jabatan:</b> {{ Auth::guard('karyawan')->user()->jabatanRel->nama_jabatan ?? '-' }}<br>
                                <b>Departemen:</b> {{ Auth::guard('karyawan')->user()->departemen->nama_dept ?? '-' }}<br>
                                <b>Cabang:</b> {{ Auth::guard('karyawan')->user()->cabang->nama_cabang ?? '-' }}
                            </div>
                        </p>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#e74c3c',
                allowOutsideClick: false
            });
        }

        // 2. Alert jika akses via URL (Redireksi dari Controller)
        @if (Session::get('error_template'))
            Swal.fire({
                title: 'Akses Ditolak!',
                text: 'Konfigurasi Template KPI tidak ditemukan untuk akun Anda.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        @endif

        // 3. Notifikasi Sukses / Gagal standar
        @if (Session::get('success'))
            Swal.fire({
                title: 'Berhasil!',
                text: "{{ Session::get('success') }}",
                icon: 'success',
                confirmButtonColor: '#27ae60'
            });
        @endif
        @if (Session::get('error'))
            Swal.fire({
                title: 'Gagal!',
                text: "{{ Session::get('error') }}",
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        @endif

        function alertTungguBawahan(e) {
            e.preventDefault();

            @php
                $names = isset($bawahanBelumIsi) ? $bawahanBelumIsi->pluck('nama_lengkap')->toArray() : [];
                $listHtml = '<div style="margin-top: 15px; text-align: left; background: #fffcf0; padding: 15px; border-radius: 12px; border: 1px solid #ffeaa7;">';
                $listHtml .= '<div style="display: flex; align-items: center; margin-bottom: 10px; color: #d84315; font-weight: 700; font-size: 13px;">';
                $listHtml .= '<ion-icon name="list-outline" style="margin-right: 8px; font-size: 18px;"></ion-icon> DAFTAR ANTRIAN KPI';
                $listHtml .= '</div>';
                $listHtml .= '<div style="max-height: 200px; overflow-y: auto; padding-right: 5px;">';
                foreach ($names as $n) {
                    $listHtml .= '<div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,0.05); color: #444; font-size: 13px;">';
                    $listHtml .= '<div style="width: 32px; height: 32px; background: #fef3c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 12px; flex-shrink: 0;">';
                    $listHtml .= '<ion-icon name="person" style="color: #d97706; font-size: 16px;"></ion-icon>';
                    $listHtml .= '</div>';
                    $listHtml .= '<span style="font-weight: 600;">' . e($n) . '</span>';
                    $listHtml .= '</div>';
                }
                $listHtml .= '</div></div>';
            @endphp
            // Disisipkan sebagai string JSON, bukan langsung ke template literal (cegah XSS / injeksi ${...}).
            const listHtml = @json($listHtml);

            Swal.fire({
                title: '<span style="color: #d84315; font-weight: 800; font-size: 20px;">Akses Terkunci!</span>',
                html: `
                    <div style="text-align: center;">
                        <div style="background: #fff8e1; width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; transform: rotate(-10deg); box-shadow: 0 10px 20px rgba(245, 127, 23, 0.1);">
                            <ion-icon name="lock-closed" style="font-size: 35px; color: #f57f17;"></ion-icon>
                        </div>
                        <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 0; padding: 0 10px;">
                            Harap pastikan semua bawahan Anda telah mengirimkan KPI mereka hari ini sebelum Anda dapat memproses laporan Anda sendiri.
                        </p>
                        ${listHtml}
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 20px; font-weight: 500;">
                           Sistem mewajibkan alur pelaporan berjenjang.
                        </p>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonText: 'Saya Mengerti',
                confirmButtonColor: '#f57f17',
                padding: '2rem',
                borderRadius: '25px'
            });
        }
    </script>

    <style>
        /* Base Style */
        body {
            background-color: #f4f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Header Style */
        .presensi-header {
            background: linear-gradient(135deg, #0d5eaa 0%, #094b87 100%);
            padding: 20px 15px;
            display: flex;
            align-items: center;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 15px rgba(9, 75, 135, 0.25);
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

        /* Form Search Styling */
        .search-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            background: white;
            overflow: hidden;
        }

        .custom-input {
            border: 1px solid #f0f0f0;
            background-color: #f8f9fa;
            border-radius: 10px;
            height: 45px;
            padding-left: 15px;
            font-size: 14px;
            color: #333;
            transition: all 0.3s;
        }

        .custom-input:focus {
            border-color: #27ae60;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }

        .btn-theme {
            background: linear-gradient(135deg, #2d6ea6 0%, #094b87 100%);
            color: white;
            border-radius: 10px;
            font-weight: 500;
            padding: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(39, 174, 96, 0.2);
            transition: all 0.3s;
        }

        .btn-theme:active {
            transform: scale(0.98);
            background: linear-gradient(135deg, #094b87 0%, #063a6b 100%);
        }

        /* Tombol Input Hari Ini (Dashed Style) */
        .dashed-btn {
            border: 2px dashed #27ae60;
            color: #27ae60;
            background: rgba(39, 174, 96, 0.05);
            font-weight: 600;
            padding: 12px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .dashed-btn:hover,
        .dashed-btn:active {
            background: linear-gradient(135deg, #2d6ea6 0%, #094b87 100%);
            color: white;
            border-style: solid;
        }

        /* List Items Styling */
        .section-title {
            font-size: 13px;
            color: #888;
            font-weight: 600;
            margin-left: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-kpi {
            display: block;
            background: white;
            border-radius: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            border: 1px solid #f1f1f1;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-kpi:active {
            transform: scale(0.98);
            background-color: #fafafa;
        }

        .histori-item {
            padding: 16px !important;
        }

        .icon-container {
            width: 45px;
            height: 45px;
            background-color: #e8f5e9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #27ae60;
            font-size: 22px;
        }

        .kpi-date {
            font-weight: 700;
            color: #2c3e50;
            font-size: 15px;
            margin: 0;
            margin-bottom: 4px;
        }

        .arrow-col ion-icon {
            font-size: 20px;
            color: #d1d5db;
        }

        /* Modern Badges (Soft Colors) */
        .badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .badge-soft-warning {
            background-color: #fff8e1;
            color: #f57f17;
        }

        .badge-soft-success {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .badge-soft-primary {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .badge-soft-danger {
            background-color: #ffebee;
            color: #c62828;
        }

        .badge-soft-secondary {
            background-color: #f5f5f5;
            color: #757575;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 15px;
            border: 1px dashed #ddd;
        }

        .icon-empty {
            font-size: 50px;
            color: #e0e0e0;
            margin-bottom: 10px;
        }

        .empty-state p {
            margin: 0;
            font-weight: 600;
            color: #555;
        }

        .swal2-popup {
            font-size: 0.9rem !important;
            border-radius: 15px !important;
        }

        .swal2-title {
            font-size: 1.2rem !important;
        }
    </style>
@endpush
