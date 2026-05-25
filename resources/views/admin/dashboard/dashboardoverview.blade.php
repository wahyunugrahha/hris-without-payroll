<!DOCTYPE html>

<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Dashboard Presensi DevHRIS</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}" sizes="32x32">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --tblr-font-sans-serif: 'Plus Jakarta Sans', sans-serif;
            --primary-color: #0054a6;
            --secondary-bg: #f8f9fc;
            --card-radius: 16px;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s ease;
        }

        body {
            font-family: var(--tblr-font-sans-serif);
            background-color: var(--secondary-bg);
            color: #334155;
            overflow-x: hidden;
        }

        /* --- MODERN SCROLLBAR --- */
        .scroll-area,
        .scroll-area-sm {
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .scroll-area {
            max-height: 500px;
        }

        .scroll-area-sm {
            max-height: 400px;
        }

        .scroll-area-5 {
            max-height: 220px;
            overflow-y: auto;
        }

        .scroll-area::-webkit-scrollbar,
        .scroll-area-sm::-webkit-scrollbar {
            width: 5px;
        }

        .scroll-area::-webkit-scrollbar-track,
        .scroll-area-sm::-webkit-scrollbar-track {
            background: transparent;
        }

        .scroll-area::-webkit-scrollbar-thumb,
        .scroll-area-sm::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }

        /* --- HERO HEADER --- */
        .dashboard-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 2rem 0 4rem 0;
            color: white;
            position: relative;
            margin-bottom: -3rem;
            border-bottom-left-radius: 30px;
            border-bottom-right-radius: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .clock-display {
            font-variant-numeric: tabular-nums;
            letter-spacing: -1px;
            font-weight: 800;
            font-size: 3.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            line-height: 1;
        }

        .date-display {
            font-weight: 500;
            opacity: 0.8;
            font-size: 1.1rem;
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- CARDS & WIDGETS --- */
        .card {
            border: none;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            background: #fff;
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        /* Stat Widget */
        .stat-widget {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-icon-box {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            line-height: 1;
            color: #1e293b;
        }

        .stat-content p {
            margin: 0;
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Color Themes for Stats */
        .theme-success .stat-icon-box {
            background: #dcfce7;
            color: #166534;
        }

        .theme-danger .stat-icon-box {
            background: #fee2e2;
            color: #991b1b;
        }

        .theme-primary .stat-icon-box {
            background: #dbeafe;
            color: #1e40af;
        }

        .theme-warning .stat-icon-box {
            background: #fef3c7;
            color: #92400e;
        }

        .theme-dark .stat-icon-box {
            background: #f1f5f9;
            color: #334155;
        }

        /* --- LIST STYLES --- */
        .card-header-modern {
            background: transparent;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-modern h4 {
            font-weight: 700;
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .list-item-modern {
            padding: 0.85rem 1.25rem;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            align-items: center;
            transition: background 0.2s;
        }

        .list-item-modern:last-child {
            border-bottom: none;
        }

        .list-item-modern:hover {
            background-color: #f8fafc;
        }

        .avatar-modern {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 2px solid white;
        }

        /* --- BADGES --- */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-pill {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .badge-soft-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-soft-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-soft-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-soft-primary {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-soft-purple {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .badge-soft-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        /* --- LEADERBOARD & RANKS --- */
        .rank-indicator {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
            margin-right: 12px;
        }

        .rank-gold {
            background: linear-gradient(135deg, #FFD700, #FDB931);
            color: #fff;
            box-shadow: 0 2px 6px rgba(253, 185, 49, 0.4);
        }

        .rank-silver {
            background: linear-gradient(135deg, #E0E0E0, #BDBDBD);
            color: #fff;
        }

        .rank-bronze {
            background: linear-gradient(135deg, #CD7F32, #A0522D);
            color: #fff;
        }

        .rank-normal {
            background: #f1f5f9;
            color: #64748b;
            font-weight: 600;
        }

        /* --- FILTER SECTION --- */
        .filter-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* --- BRANCH CARD --- */
        .branch-card-modern {
            height: 100%;
            border: 1px solid #f1f5f9;
        }

        .branch-header-modern {
            background: #f8fafc;
            padding: 1rem;
            font-weight: 700;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
        }
    </style>


</head>

<body>
    <div class="page-wrapper">

        <div class="dashboard-hero">
            <div class="container-xl">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-4">
                    <div class="text-left">
                        <div class="clock-display" id="clock">--:--:--</div>
                        <div class="date-display text-uppercase ls-1">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</div>
                    </div>    
                
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 p-2 rounded-3 shadow-sm">
                            <img src="{{ asset('assets/img/logo.png') }}" width="48" alt="Logo">
                        </div>
                        <div>
                            <h1 class="m-0 text-white fw-bold" style="font-size: 1.6rem; letter-spacing: -0.5px;">Live Monitor</h1>
                            <div class="text-white-50 small fw-medium">Dashboard Presensi Real-time</div>
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <div class="page-body mt-5">
            <div class="container-xl">

                <div class="row row-cards row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-5">
                    <div class="col">
                        <div class="card theme-success h-100">
                            <div class="stat-widget">
                                <div class="stat-icon-box">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-user-check" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                        <path d="M16 11l2 2l4 -4" />
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <h3>{{ $jmlhadir ?? 0 }}</h3>
                                    <p>Hadir</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card theme-danger h-100">
                            <div class="stat-widget">
                                <div class="stat-icon-box">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-clock-exclamation" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M20.986 12.502a9 9 0 1 0 -5.973 7.98" />
                                        <path d="M12 7v5l3 3" />
                                        <path d="M19 16v3" />
                                        <path d="M19 22v.01" />
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <h3>{{ $jmlterlambat ?? 0 }}</h3>
                                    <p>Terlambat</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card theme-primary h-100">
                            <div class="stat-widget">
                                <div class="stat-icon-box">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-calendar-user" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 21h-6a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v2" />
                                        <path d="M16 3v4" />
                                        <path d="M8 3v4" />
                                        <path d="M4 11h16" />
                                        <path d="M19 17h2" />
                                        <path
                                            d="M22 16v2a1 1 0 0 1 -1 1h-8a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1h8a1 1 0 0 1 1 1z" />
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <h3>{{ $rekapizin->jmlizin ?? 0 }}</h3>
                                    <p>Izin/Cuti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card theme-warning h-100">
                            <div class="stat-widget">
                                <div class="stat-icon-box">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-first-aid-kit" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M8 8v-2a4 4 0 0 1 8 0v2" />
                                        <path
                                            d="M4 8m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                        <path d="M10 14l4 0" />
                                        <path d="M12 12l0 4" />
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <h3>{{ $rekapizin->jmlsakit ?? 0 }}</h3>
                                    <p>Sakit</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card theme-dark h-100">
                            <div class="stat-widget">
                                <div class="stat-icon-box">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-user-x" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h3.5" />
                                        <path d="M22 22l-5 -5" />
                                        <path d="M17 22l5 -5" />
                                    </svg>
                                </div>
                                <div class="stat-content">
                                    <h3>{{ $jmltidakabsen ?? 0 }}</h3>
                                    <p>Alpha</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card filter-card p-3 shadow-sm">
                            <form method="GET" action="{{ route('overview') }}"
                                class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="bg-primary text-white rounded p-1"><svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-filter" width="16" height="16"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-5.414 5.414v7l-6 2v-8.5l-5.419 -5.423a2 2 0 0 1 -.581 -1.405v-2.172z" />
                                        </svg></span>
                                    <label class="fw-bold text-dark mb-0">Filter Periode:</label>
                                </div>
                                <select name="periode" id="periodeSelect"
                                    class="form-select border-0 bg-light fw-semibold" style="max-width: 250px;">
                                    <option value="">-- Pilih Bulan --</option>
                                    @for ($i = 0; $i < 12; $i++)
                                        @php
                                            $monthDate = \Carbon\Carbon::now()->subMonths($i)->day(26);
                                            $label =
                                                $monthDate->translatedFormat('d F') .
                                                ' - ' .
                                                $monthDate->copy()->addMonth()->day(25)->translatedFormat('d F Y');
                                            $value = $monthDate->format('Y-m-26');
                                        @endphp
                                        <option value="{{ $value }}"
                                            {{ request('periode') == $value ? 'selected' : '' }}>{{ $label }}
                                        </option>
                                    @endfor
                                </select>
                                <div class="ms-auto text-end d-none d-md-block">
                                    <span class="text-muted small text-uppercase fw-bold ls-1">Periode Aktif</span>
                                    <div class="fw-bold text-primary">{{ $periodeLabel }}</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row row-cards row-cols-1 row-cols-lg-5 g-3 mb-5">

                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-success"><span class="status-dot bg-success"></span> Presensi Masuk
                                </h4>
                                <span class="badge badge-soft-success">{{ count($dataPresensi) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area-sm">
                                <div class="list-group list-group-flush">
                                    @forelse($dataPresensi as $p)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($p->foto) ? asset('storage/uploads/karyawan/' . $p->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($p->nama_lengkap) . '&background=dcfce7&color=166534' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill overflow-hidden">
                                                <div class="text-dark fw-bold text-truncate">{{ $p->nama_lengkap }}
                                                </div>
                                                <div class="d-flex align-items-center mt-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-clock me-1 text-muted"
                                                        width="12" height="12" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                        <path d="M12 7v5l3 3" />
                                                    </svg>
                                                    <span
                                                        class="small fw-bold text-success">{{ $p->jam_in }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted small">Belum ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-warning"><span class="status-dot bg-warning"></span> Izin / Sakit / Cuti</h4>
                                <span class="badge badge-soft-warning">{{ count($dataIzinSakit) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area-sm">
                                <div class="list-group list-group-flush">
                                    @forelse($dataIzinSakit as $iz)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($iz->foto) ? asset('storage/uploads/karyawan/' . $iz->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($iz->nama_lengkap) . '&background=fef3c7&color=92400e' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill overflow-hidden">
                                                <div class="d-flex justify-content-between">
                                                    <div class="text-dark fw-bold text-truncate"
                                                        style="max-width: 85px;">{{ $iz->nama_lengkap }}</div>
                                                    @if ($iz->status_approved == 0)
                                                        <span class="badge badge-pill badge-soft-secondary">Wait</span>
                                                    @elseif($iz->status_approved == 1)
                                                        <span class="badge badge-pill badge-soft-success">ACC</span>
                                                    @else
                                                        <span class="badge badge-pill badge-soft-danger">Tolak</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted text-truncate fst-italic mt-1">
                                                    {{ $iz->keterangan }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted small">Tidak ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-primary"><span class="status-dot bg-primary"></span> Dinas Luar</h4>
                                <span class="badge badge-soft-primary">{{ count($dataDinasLuar) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area-sm">
                                <div class="list-group list-group-flush">
                                    @forelse($dataDinasLuar as $dl)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($dl->foto) ? asset('storage/uploads/karyawan/' . $dl->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($dl->nama_lengkap) . '&background=dbeafe&color=1e40af' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill overflow-hidden">
                                                <div class="d-flex justify-content-between">
                                                    <div class="text-dark fw-bold text-truncate"
                                                        style="max-width: 85px;">{{ $dl->nama_lengkap }}</div>
                                                    @if ($dl->status_acc == 'menunggu')
                                                        <span class="badge badge-pill badge-soft-secondary">Wait</span>
                                                    @elseif ($dl->status_acc == 'acc')
                                                        <span class="badge badge-pill badge-soft-primary">ACC</span>
                                                    @else
                                                        <span class="badge badge-pill badge-soft-danger">Tolak</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted text-truncate mt-1"><svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="icon icon-tabler icon-tabler-map-pin" width="10"
                                                        height="10" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                                        <path
                                                            d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" />
                                                    </svg> {{ $dl->lokasi_tujuan }}</div>
                                                <div class="small text-muted mt-1" style="font-size: 0.7rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar-event" width="10" height="10" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                                        <path d="M16 3l0 4" />
                                                        <path d="M8 3l0 4" />
                                                        <path d="M4 11l16 0" />
                                                        <path d="M8 15l2 2l4 -4" />
                                                    </svg>
                                                    {{ date('d/m', strtotime($dl->tgl_mulai)) }} - {{ date('d/m', strtotime($dl->tgl_selesai)) }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted small">Tidak ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 style="color: #6b21a8;"><span class="status-dot"
                                        style="background: #6b21a8;"></span> Data Lembur</h4>
                                <span class="badge badge-soft-purple">{{ count($dataLembur) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area-sm">
                                <div class="list-group list-group-flush">
                                    @forelse($dataLembur as $lb)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($lb->foto) ? asset('storage/uploads/karyawan/' . $lb->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($lb->nama_lengkap) . '&background=f3e8ff&color=6b21a8' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill overflow-hidden">
                                                <div class="d-flex justify-content-between">
                                                    <div class="text-dark fw-bold text-truncate"
                                                        style="max-width: 85px;">{{ $lb->nama_lengkap }}</div>
                                                    @if ($lb->status_approved == 1)
                                                        <span class="badge badge-pill badge-soft-purple">ACC</span>
                                                    @elseif($lb->status_approved == 2)
                                                        <span class="badge badge-pill badge-soft-danger">Tolak</span>
                                                    @else
                                                        <span class="badge badge-pill badge-soft-secondary">Wait</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    {{ date('H:i', strtotime($lb->jam_mulai)) }}-{{ date('H:i', strtotime($lb->jam_selesai)) }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted small">Tidak ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-danger"><span class="status-dot bg-danger"></span> Belum Absen</h4>
                                <span class="badge badge-soft-danger">{{ count($dataBelumPresensi) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area-sm">
                                <div class="list-group list-group-flush">
                                    @forelse($dataBelumPresensi as $ba)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($ba->foto) ? asset('storage/uploads/karyawan/' . $ba->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($ba->nama_lengkap) . '&background=fee2e2&color=991b1b' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill overflow-hidden">
                                                <div class="text-dark fw-bold text-truncate">{{ $ba->nama_lengkap }}
                                                </div>
                                                <div class="small text-muted mt-1">{{ $ba->jabatan_nama ?? '-' }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center">
                                            <span class="badge badge-soft-success w-100 py-2">Semua Hadir!</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row row-cards mb-5">
                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100 border-0">
                            <div class="card-header-modern bg-danger text-white rounded-top">
                                <h4 class="text-white"><svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-gavel" width="20" height="20"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M13 10l7.383 7.418c.823 .82 .823 2.148 0 2.967a2.11 2.11 0 0 1 -2.976 0l-7.407 -7.385" />
                                        <path d="M6 9l4 4" />
                                        <path d="M13 10l-4 -4" />
                                        <path d="M3 21h7" />
                                        <path
                                            d="M6.793 15.793l-3.586 -3.586a1 1 0 0 1 0 -1.414l2.293 -2.293l.5 .5l3 -3l-.5 -.5l2.293 -2.293a1 1 0 0 1 1.414 0l3.586 3.586a1 1 0 0 1 0 1.414l-2.293 2.293l-.5 -.5l-3 3l.5 .5l-2.293 2.293a1 1 0 0 1 -1.414 0z" />
                                    </svg> SANKSI AKTIF (SP)</h4>
                                <span class="badge bg-white text-danger">{{ count($karyawanSP) }}</span>
                            </div>
                            <div class="card-body p-0 scroll-area">
                                <div class="list-group list-group-flush">
                                    @forelse($karyawanSP as $sp)
                                        <div class="list-item-modern">
                                            <img src="{{ !empty($sp->foto) ? asset('storage/uploads/karyawan/' . $sp->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($sp->nama_lengkap) . '&background=random' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="fw-bold text-dark">{{ $sp->nama_lengkap }}</div>
                                                    <span class="badge bg-danger">SP {{ $sp->level }}</span>
                                                </div>
                                                <div class="small text-danger mt-1">
                                                    Exp:
                                                    {{ \Carbon\Carbon::parse($sp->sampai_tanggal)->translatedFormat('d M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="p-5 text-center text-muted">
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-mood-smile text-success mb-2"
                                                width="48" height="48" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor" fill="none">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                                <path d="M9 10l.01 0" />
                                                <path d="M15 10l.01 0" />
                                                <path d="M9.5 15a3.5 3.5 0 0 0 5 0" />
                                            </svg>
                                            <div class="fw-bold">Tidak ada sanksi aktif</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-primary"><svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-medal me-2" width="20" height="20"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M12 4l3.5 5h-7z" />
                                        <path d="M12 20l-3.5 -5h7z" />
                                        <path d="M15.5 9l4.5 -5h-3l-2.5 5z" />
                                        <path d="M8.5 9l-4.5 -5h3l2.5 5z" />
                                    </svg> Top Presensi Hari Ini</h4>
                            </div>
                            <div class="card-body p-0 scroll-area">
                                <div class="list-group list-group-flush">
                                    @forelse($globalDaily as $index => $d)
                                        <div class="list-item-modern">
                                            <div
                                                class="rank-indicator {{ $index == 0 ? 'rank-gold' : ($index == 1 ? 'rank-silver' : ($index == 2 ? 'rank-bronze' : 'rank-normal')) }}">
                                                {{ $index + 1 }}
                                            </div>
                                            <img src="{{ !empty($d->foto) ? asset('storage/uploads/karyawan/' . $d->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($d->nama_lengkap) . '&background=random' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill text-truncate">
                                                <div class="fw-bold text-dark">{{ $d->nama_lengkap }}</div>
                                                <div class="small text-muted">{{ $d->nama_cabang ?? $d->kode_cabang }}
                                                </div>
                                            </div>
                                            <div class="fw-bold text-primary">{{ $d->jam_in }}</div>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted">Belum ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-warning"><svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-building me-2" width="20"
                                        height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 21l18 0" />
                                        <path d="M9 8l1 0" />
                                        <path d="M9 12l1 0" />
                                        <path d="M9 16l1 0" />
                                        <path d="M14 8l1 0" />
                                        <path d="M14 12l1 0" />
                                        <path d="M14 16l1 0" />
                                        <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
                                    </svg> Top Office</h4>
                            </div>
                            <div class="card-body p-0 scroll-area">
                                <div class="list-group list-group-flush">
                                    @forelse($topOffice as $index => $m)
                                        <div class="list-item-modern">
                                            <div
                                                class="rank-indicator {{ $index == 0 ? 'rank-gold' : ($index == 1 ? 'rank-silver' : ($index == 2 ? 'rank-bronze' : 'rank-normal')) }}">
                                                {{ $index + 1 }}
                                            </div>
                                            <img src="{{ !empty($m->foto) ? asset('storage/uploads/karyawan/' . $m->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($m->nama_lengkap) . '&background=random' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill text-truncate">
                                                <div class="fw-bold text-dark">{{ $m->nama_lengkap }}</div>
                                                <div class="small text-muted">{{ $m->nama_cabang }}</div>
                                            </div>
                                            <span
                                                class="badge badge-soft-warning">{{ number_format($m->total_points) }}
                                                Pts</span>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted">Belum ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-header-modern">
                                <h4 class="text-warning"><svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-tractor me-2" width="20"
                                        height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M3 17h2v-6h10v6h2" />
                                        <path d="M5 17a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M15 17a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                        <path d="M5 11v-4h6" />
                                        <path d="M13 7h-2" />
                                    </svg> Top Site</h4>
                            </div>
                            <div class="card-body p-0 scroll-area">
                                <div class="list-group list-group-flush">
                                    @forelse($topSite as $index => $m)
                                        <div class="list-item-modern">
                                            <div
                                                class="rank-indicator {{ $index == 0 ? 'rank-gold' : ($index == 1 ? 'rank-silver' : ($index == 2 ? 'rank-bronze' : 'rank-normal')) }}">
                                                {{ $index + 1 }}
                                            </div>
                                            <img src="{{ !empty($m->foto) ? asset('storage/uploads/karyawan/' . $m->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($m->nama_lengkap) . '&background=random' }}"
                                                class="avatar-modern me-3">
                                            <div class="flex-fill text-truncate">
                                                <div class="fw-bold text-dark">{{ $m->nama_lengkap }}</div>
                                                <div class="small text-muted">{{ $m->nama_cabang }}</div>
                                            </div>
                                            <span
                                                class="badge badge-soft-warning">{{ number_format($m->total_points) }}
                                                Pts</span>
                                        </div>
                                    @empty
                                        <div class="p-4 text-center text-muted">Belum ada data</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="icon icon-tabler icon-tabler-building" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M3 21l18 0" />
                            <path d="M9 8l1 0" />
                            <path d="M9 12l1 0" />
                            <path d="M9 16l1 0" />
                            <path d="M14 8l1 0" />
                            <path d="M14 12l1 0" />
                            <path d="M14 16l1 0" />
                            <path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="m-0 fw-bold">Pantauan Cabang</h2>
                        <div class="text-muted small">Live presensi per lokasi</div>
                    </div>
                </div>

                <div class="row row-cards mb-5">
                    @forelse($leaderboardsByCabang as $kode => $lb)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card branch-card-modern shadow-sm h-100">
                                <div class="branch-header-modern d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width: 70%;">
                                        {{ $lb['cabang']->nama_cabang }}</div>
                                    <span class="badge bg-green text-white">{{ count($lb['rows']) }} Hadir</span>
                                </div>
                                <div class="card-body p-0 scroll-area-5">
                                    @forelse($lb['rows'] as $idx => $r)
                                        <div
                                            class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center text-truncate">
                                                <span class="text-muted small me-2 w-5">{{ $idx + 1 }}.</span>
                                                <span
                                                    class="text-dark small fw-semibold text-truncate">{{ $r->nama_lengkap }}</span>
                                            </div>
                                            <span class="text-success small fw-bold">{{ $r->jam_in }}</span>
                                        </div>
                                    @empty
                                        <div class="text-center py-3 text-muted small fst-italic">Belum ada absen</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted">Tidak ada data cabang.</div>
                    @endforelse
                </div>

                <div class="row">
                    <!-- KIRI: PRESENSI -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-4 mt-5">
                            <div class="bg-warning bg-opacity-10 text-warning p-2 rounded me-3">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="icon icon-tabler icon-tabler-star" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path
                                        d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="m-0 fw-bold">Top Presensi Cabang</h2>
                                <div class="text-muted small">Akumulasi poin bulan ini</div>
                            </div>
                        </div>

                        <div class="row row-cards mb-5">
                            @forelse($leaderboardsByCabangMonthly as $kode => $lb)
                                <div class="col-md-12 col-xl-6">
                                    <div class="card branch-card-modern shadow-sm h-100"
                                        style="border-top: 3px solid #f59f00;">
                                        <div class="branch-header-modern bg-white">
                                            <div class="text-truncate fw-bold">{{ $lb['cabang']->nama_cabang }}</div>
                                        </div>
                                        <div class="card-body p-0 scroll-area-5">
                                            @forelse($lb['rows'] as $idx => $r)
                                                <div
                                                    class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center text-truncate">
                                                        <span class="badge badge-soft-warning me-2"
                                                            style="font-size: 9px; padding: 2px 5px;">{{ $idx + 1 }}</span>
                                                        <span
                                                            class="text-dark small fw-semibold text-truncate">{{ $r->nama_lengkap }}</span>
                                                    </div>
                                                    <span class="text-warning small fw-bold">{{ number_format($r->total_points) }}</span>
                                                </div>
                                            @empty
                                                <div class="text-center py-3 text-muted small fst-italic">Belum ada data</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted">Tidak ada data presensi.</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- KANAN: KPI -->
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-4 mt-5">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="icon icon-tabler icon-tabler-target" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <circle cx="12" cy="12" r="9" />
                                    <circle cx="12" cy="12" r="5" />
                                    <circle cx="12" cy="12" r="1" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="m-0 fw-bold">Top KPI Cabang</h2>
                                <div class="text-muted small">Akumulasi nilai KPI bulan ini</div>
                            </div>
                        </div>

                        <div class="row row-cards mb-5">
                            @forelse($kpiLeaderboardsByCabangMonthly as $kode => $lb)
                                <div class="col-md-12 col-xl-6">
                                    <div class="card branch-card-modern shadow-sm h-100"
                                        style="border-top: 3px solid #0054a6;">
                                        <div class="branch-header-modern bg-white">
                                            <div class="text-truncate fw-bold">{{ $lb['cabang']->nama_cabang }}</div>
                                        </div>
                                        <div class="card-body p-0 scroll-area-5">
                                            @forelse($lb['rows'] as $idx => $r)
                                                <div
                                                    class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center text-truncate">
                                                        <span class="badge badge-soft-primary me-2"
                                                            style="font-size: 9px; padding: 2px 5px;">{{ $idx + 1 }}</span>
                                                        <span
                                                            class="text-dark small fw-semibold text-truncate">{{ $r->nama_lengkap }}</span>
                                                    </div>
                                                    <span class="text-primary small fw-bold">{{ number_format($r->total_points) }}</span>
                                                </div>
                                            @empty
                                                <div class="text-center py-3 text-muted small fst-italic">Belum ada data</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted">Tidak ada data KPI.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js">
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${h}:${m}:${s}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        setTimeout(() => window.location.reload(), 120000);

        const periodeSelect = document.getElementById('periodeSelect');
        if (periodeSelect) {
            periodeSelect.addEventListener('change', function() {
                if (this.value) this.closest('form').submit();
            });
        }


        document.addEventListener('DOMContentLoaded', () => {
            const scrollContainers = document.querySelectorAll('.scroll-area, .scroll-area-sm');
            scrollContainers.forEach((container) => {
                if (container.scrollHeight > container.clientHeight) {
                    startSmoothScroll(container);
                }
            });
        });

        function startSmoothScroll(container) {
            let scrollSpeed = 0.2;
            let direction = 1;
            let isPaused = false;
            let isHolding = false;

            function animate() {
                if (!isPaused && !isHolding) {
                    container.scrollTop += (scrollSpeed * direction);
                    if (container.scrollTop + container.clientHeight >= container.scrollHeight - 1) {
                        isPaused = true;
                        setTimeout(() => {
                            direction = -1;
                            isPaused = false;
                        }, 2000);
                    } else if (container.scrollTop <= 0) {
                        isPaused = true;
                        setTimeout(() => {
                            direction = 1;
                            isPaused = false;
                        }, 2000);
                    }
                }
                requestAnimationFrame(animate);
            }
            animate();
            container.addEventListener('mouseenter', () => isHolding = true);
            container.addEventListener('mouseleave', () => isHolding = false);
            container.addEventListener('touchstart', () => isHolding = true);
            container.addEventListener('touchend', () => isHolding = false);
        }
    </script>
</body>

</html>
