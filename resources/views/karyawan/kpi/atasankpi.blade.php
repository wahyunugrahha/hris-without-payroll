@extends('layouts.presensi')

@section('header')
    <div class="presensi-header">
        <a href="{{ route('dashboard.karyawan') }}" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
        <span class="header-title">Approval KPI Karyawan</span>
        <div class="header-spacer" style="width: 24px;"></div>
    </div>
@endsection

@section('content')
    {{-- Container utama diberi padding bawah agar tidak tertutup menu bawah (jika ada) --}}
    <div class="row" style="margin-top: 20px; padding-bottom: 80px;">
        <div class="col">

            {{-- FORM PENCARIAN --}}
            <div class="card search-card mb-3">
                <div class="card-body p-3">
                    <form action="{{ route('kpi.atasan.index') }}" method="GET">
                        {{-- Dropdown NIK --}}
                        <div class="form-group mb-2">
                            <div class="input-wrapper">
                                <ion-icon name="search-outline" class="input-icon"></ion-icon>
                                <select name="nik" id="nik" class="form-control custom-input" autofocus>
                                    <option value="">Pilih Karyawan</option>
                                    @foreach ($bawahan as $d)
                                        <option value="{{ $d->nik }}"
                                            {{ request('nik') == $d->nik ? 'selected' : '' }}>
                                            {{ strtoupper($d->nama_lengkap) }} -
                                            {{ $d->jabatanRel->nama_jabatan ?? 'Belum diketahui' }} ({{ $d->nik }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <select name="bulan" id="bulan" class="form-control custom-input">
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
                                    <select name="tahun" id="tahun" class="form-control custom-input">
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

                        <button type="submit" class="btn btn-theme btn-block mt-2">
                            <ion-icon name="search-outline"></ion-icon> Tampilkan Data
                        </button>
                    </form>
                </div>
            </div>

            {{-- LIST RIWAYAT KPI BAWAHAN --}}
            <div class="row mt-1">
                <div class="col-12">
                    <h6 class="section-title mb-3">Daftar Pengajuan</h6>
                    @forelse ($riwayatKPI as $item)
                        @php
                            // Perbaikan Logika Progress di sini:
                            $skorUtama = $item->kpiDailyDetail->sum('score') ?? 0;
                            $skorExtra = $item->kpiDailyExtra->sum('score') ?? 0;
                            $totalSkorMentah = floatval($skorUtama + $skorExtra);

                            // Konversi ke persentase berdasarkan maksimal 40 skor per hari
                            $persenProgress = ($totalSkorMentah / 40) * 100;
                            if ($persenProgress > 100) {
                                $persenProgress = 100;
                            }
                        @endphp

                        <a href="{{ route('kpi.atasan.detail', $item->id) }}" class="card-kpi text-decoration-none">
                            <div class="card-body histori-item">
                                <div class="d-flex align-items-center">
                                    {{-- Icon Container --}}
                                    <div class="icon-container mr-3">
                                        <ion-icon name="document-text-outline"></ion-icon>
                                    </div>

                                    {{-- Info --}}
                                    <div class="info-col flex-grow-1" style="min-width: 0;">
                                        <h6 class="kpi-date mb-0">
                                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                        </h6>

                                        <div class="mt-1">
                                            {{-- Status Utama --}}
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

                                            {{-- Status Persetujuan --}}
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

                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <div class="progress flex-grow-1"
                                                    style="height: 4px; background-color: #e2e8f0; border-radius: 10px;">
                                                    <div class="progress-bar bg-success"
                                                        style="width: {{ $persenProgress }}%" role="progressbar"></div>
                                                </div>
                                                <span
                                                    style="font-size: 11px; font-weight: 700; color: #27ae60; min-width: 35px; text-align: right;">
                                                    {{ round($persenProgress) }}%
                                                </span>
                                            </div>
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
                            @if (empty(request('nik')))
                                <p>Silakan pilih karyawan untuk melihat data.</p>
                                <small class="text-muted">Pilih nama karyawan pada form pencarian di atas.</small>
                            @else
                                <p>Tidak ada data KPI ditemukan.</p>
                                <small class="text-muted">Karyawan ini belum menginput KPI pada bulan tersebut.</small>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <style>
        /* Base Style */
        body {
            background-color: #f4f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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

        /* Form Search Styling */
        .search-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.03);
            background: white;
            overflow: hidden;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 18px;
            z-index: 2;
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

        /* Khusus input nik yang ada iconnya, padding kiri lebih besar */
        #nik.custom-input {
            padding-left: 40px;
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
            background-color: #f1f5f9;
            color: #64748b;
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
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nikInput = document.getElementById('nik');
            if (nikInput && nikInput.value === "") {
                nikInput.focus();
            }

            // Memunculkan pesan error/success dari Controller jika ada
            @if (Session::has('error'))
                Swal.fire({
                    title: 'Peringatan!',
                    text: @json(Session::get('error')),
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            @endif

            @if (Session::has('success'))
                Swal.fire({
                    title: 'Berhasil!',
                    text: @json(Session::get('success')),
                    icon: 'success',
                    confirmButtonColor: '#094b87'
                });
            @endif
        });

        function alertPilihKaryawan(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Pilih Karyawan',
                text: "Silakan pilih nama karyawan pada form pencarian di atas terlebih dahulu sebelum membuat penilaian.",
                icon: 'info',
                confirmButtonColor: '#094b87',
                confirmButtonText: 'Mengerti'
            }).then((result) => {
                if (result.isConfirmed || result.isDismissed) {
                    const nikSelect = document.getElementById('nik');
                    if (nikSelect) {
                        nikSelect.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        setTimeout(() => {
                            nikSelect.focus();
                            const originalBoxShadow = nikSelect.style.boxShadow;
                            const originalBorder = nikSelect.style.border;

                            nikSelect.style.boxShadow = '0 0 0 4px rgba(39, 174, 96, 0.4)';
                            nikSelect.style.borderColor = '#27ae60';

                            setTimeout(() => {
                                nikSelect.style.boxShadow = originalBoxShadow;
                                nikSelect.style.borderColor = originalBorder;
                            }, 1500);
                        }, 300);
                    }
                }
            });
        }
    </script>
@endpush

