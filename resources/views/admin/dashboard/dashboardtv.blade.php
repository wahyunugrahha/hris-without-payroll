<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300">
    <title>wndev TV Dashboard - Master Monitor</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

    <style>
        :root {
            /* --- COLOR PALETTE --- */
            --bg-app: #0f172a;
            --bg-header: rgba(15, 23, 42, 0.95);
            --bg-card: #1e293b;
            --border-color: rgba(255, 255, 255, 0.08);

            --text-main: #f1f5f9;
            --text-muted: #94a3b8;

            /* --- COMPACT SCALING (Agar muat 5 baris) --- */
            --fz-xl: clamp(2.4rem, 3.6vw, 4.5rem);
            /* Angka Stats */
            --fz-lg: clamp(1.2rem, 1.2vw, 2rem);
            /* Header H1 */
            --fz-md: clamp(0.95rem, 0.75vw, 1.4rem);
            /* Judul Card */
            --fz-base: clamp(0.8rem, 0.6vw, 1.1rem);
            /* Teks Utama (Nama) - Dikecilkan sedikit */
            --fz-sm: clamp(0.7rem, 0.45vw, 0.95rem);
            /* Subtext */

            --radius-card: 14px;
            --space-x: 2rem;
            --space-y: 1.5rem;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-app);
            color: var(--text-main);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        /* --- ZOOM WRAPPER 50% --- */
        .zoom-wrapper {
            width: 200vw;
            height: 200vh;
            transform: scale(0.5);
            transform-origin: 0 0;
            display: flex;
            flex-direction: column;
            background: radial-gradient(circle at top, #1e293b 0%, #0f172a 70%);
        }

        /* --- 1. HEADER (12vh) --- */
        header {
            flex: 0 0 auto;
            height: 12vh;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 var(--space-x);
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-header);
            backdrop-filter: blur(10px);
            z-index: 50;
        }

        .h-brand {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 25%;
        }

        .h-brand img {
            height: 4.5rem;
            width: auto;
        }

        .h-brand h1 {
            font-size: var(--fz-lg);
            margin: 0;
            font-weight: 800;
            line-height: 1.1;
            color: #fff;
        }

        .h-brand small {
            font-size: var(--fz-sm);
            color: #60a5fa;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            display: block;
        }

        .h-stats {
            flex: 1;
            display: flex;
            justify-content: center;
            gap: 1rem;
            height: 100%;
            align-items: center;
        }

        .hs-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            padding: 0 0.6rem;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .hs-item:last-child {
            border-right: none;
        }

        .hs-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: var(--fz-xl);
            font-weight: 700;
            line-height: 1;
        }

        .hs-lbl {
            font-size: var(--fz-sm);
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 5px;
        }

        .h-clock {
            width: 20%;
            text-align: right;
        }

        .time-d {
            font-family: 'JetBrains Mono', monospace;
            font-size: 3.5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }

        .date-d {
            font-size: var(--fz-base);
            font-weight: 500;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        /* --- MAIN LAYOUT --- */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: var(--space-y) var(--space-x);
            gap: 1.5rem;
            overflow: hidden;
        }

        /* --- MIDDLE GRID (DIPERBESAR jadi 50% tinggi layar) --- */
        .middle-grid {
            flex: 0 0 50%;
            /* Dibuat lebih tinggi agar muat 5 baris */
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 1.25rem;
        }

        /* --- BOTTOM GRID --- */
        .bottom-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
            min-height: 0;
        }

        /* --- CARD STYLING --- */
        .tv-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-card);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .card-head {
            flex: 0 0 auto;
            /* Header dibuat lebih compact */
            padding: 0.8rem 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.6rem;
        }

        .ch-title {
            font-size: clamp(0.85rem, 0.7vw, var(--fz-md));
            font-weight: 700;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ch-badge {
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            color: #0f172a;
            min-width: 30px;
            text-align: center;
        }

        .card-body {
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .scroll-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        /* --- LIST ITEMS (COMPACT MODE) --- */
        .li-row {
            display: flex;
            align-items: center;
            /* Padding diperkecil agar baris lebih rapat */
            padding: 0.6rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            gap: 0.5rem;
            height: auto;
            min-height: clamp(3rem, 2.8vw, 3.8rem);
            /* Tinggi minimal per baris ~60px (di 50%) */
        }

        .li-row:nth-child(even) {
            background: rgba(255, 255, 255, 0.015);
        }

        .u-avt {
            /* Avatar diperkecil */
            width: 2.4rem;
            height: 2.4rem;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-right: 0.8rem;
            object-fit: cover;
        }

        .u-info {
            flex: 1;
            min-width: 0;
        }

        .u-main {
            font-size: var(--fz-base);
            font-weight: 600;
            color: #f1f5f9;
            white-space: normal;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
            line-height: 1.2;
            word-break: break-word;
        }

        .u-sub {
            font-size: var(--fz-sm);
            /* Lebih kecil sedikit */
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        .u-meta {
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(0.78rem, 0.58vw, var(--fz-base));
            font-weight: 700;
            margin-left: 0.8rem;
            flex-shrink: 0;
        }

        /* --- FOOTER --- */
        footer {
            height: 5vh;
            background: #020617;
            display: flex;
            align-items: center;
            border-top: 1px solid var(--border-color);
        }

        .mq-tag {
            background: #ef4444;
            color: #fff;
            height: 100%;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: var(--fz-base);
            white-space: nowrap;
            z-index: 2;
        }

        .mq-wrap {
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
        }

        .mq-text {
            display: inline-block;
            padding-left: 100%;
            animation: marquee 35s linear infinite;
            font-size: var(--fz-base);
            font-weight: 400;
            color: #cbd5e1;
            line-height: 5vh;
        }

        @keyframes marquee {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        /* --- COLORS UTILS --- */
        .t-gold {
            color: #eab308 !important;
        }

        .b-gold {
            border-top: 4px solid #eab308;
        }

        .t-green {
            color: #3d87c9 !important;
        }

        .b-green {
            border-top: 4px solid #3d87c9;
        }

        .bg-green {
            background: #3d87c9;
        }

        .t-orange {
            color: #f97316 !important;
        }

        .b-orange {
            border-top: 4px solid #f97316;
        }

        .bg-orange {
            background: #f97316;
        }

        .t-purple {
            color: #d946ef !important;
        }

        .b-purple {
            border-top: 4px solid #d946ef;
        }

        .bg-purple {
            background: #d946ef;
        }

        .t-blue {
            color: #3b82f6 !important;
        }

        .b-blue {
            border-top: 4px solid #3b82f6;
        }

        .bg-blue {
            background: #3b82f6;
        }

        .t-red {
            color: #ef4444 !important;
        }

        .b-red {
            border-top: 4px solid #ef4444;
        }

        .bg-red {
            background: #ef4444;
            color: white;
        }

        .t-grey {
            color: #94a3b8 !important;
        }

        .b-grey {
            border-top: 4px solid #64748b;
        }

        .bg-grey {
            background: #64748b;
            color: white;
        }

        .split-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100%;
            gap: 0;
        }

        .split-col {
            display: flex;
            flex-direction: column;
            border-right: 3px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            min-height: 0;
        }

        .split-col:last-child {
            border-right: none;
        }

        .split-head {
            flex: 0 0 auto;
            position: sticky;
            top: 0;
            z-index: 2;
            text-align: center;
            font-size: clamp(0.7rem, 0.65vw, 1rem);
            font-weight: 800;
            padding: 0.6rem 0.4rem;
            background: rgba(0, 0, 0, 0.35);
            color: #fff;
            letter-spacing: 2px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--border-color);
        }

        .split-col-scroll {
            flex: 1;
            position: relative;
            overflow-y: hidden;
            overflow-x: hidden;
            min-height: 0;
            scrollbar-width: none;
        }

        .split-col-scroll::-webkit-scrollbar {
            display: none;
        }

        .split-col-scroll .scroll-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        .alpha-summary {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-family: 'JetBrains Mono', monospace;
            font-size: clamp(2.2rem, 4vw, 5rem);
            font-weight: 700;
            color: #94a3b8;
        }

        @media (max-width: 1366px) {
            .li-row {
                padding: 0.5rem 0.8rem;
            }

            .u-avt {
                width: 2rem;
                height: 2rem;
                margin-right: 0.5rem;
            }

            .ch-badge {
                font-size: 0.75rem;
                padding: 0.15rem 0.5rem;
            }
        }

        @media (min-width: 1900px) and (max-width: 1940px) and (min-height: 1060px) and (max-height: 1100px) {
            :root {
                --fz-xl: 4.2rem;
                --fz-lg: 1.9rem;
                --fz-md: 1.28rem;
                --fz-base: 1.02rem;
                --fz-sm: 0.86rem;
            }

            .main-wrapper {
                gap: 1.1rem;
                padding: 1.2rem 1.6rem;
            }

            .middle-grid,
            .bottom-grid {
                gap: 1rem;
            }

            .card-head {
                padding: 0.65rem 0.85rem;
            }

            .li-row {
                min-height: 3.35rem;
                padding: 0.48rem 0.8rem;
            }

            .u-avt {
                width: 2.1rem;
                height: 2.1rem;
            }

            .u-main {
                -webkit-line-clamp: 1;
            }

            .time-d {
                font-size: 3.2rem;
            }
        }
    </style>
</head>

<body>

    <div class="zoom-wrapper">

        <header>
            <div class="h-brand">
                <x-brand-logo variant="landscape" alt="wndev" />
                <div>
                    <h1>DASHBOARD</h1>
                    <small>Monitoring System</small>
                </div>
            </div>

            <div class="h-stats">
                <div class="hs-item">
                    <span class="hs-val" style="color:#a7cae8">{{ count($dataPresensi) }}</span>
                    <span class="hs-lbl">Hadir</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#f87171">{{ $jmlterlambat ?? 0 }}</span>
                    <span class="hs-lbl">Telat</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#fbbf24">{{ $jmlizin ?? ($rekapizin->jmlizin ?? 0) }}</span>
                    <span class="hs-lbl">Izin</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#fb923c">{{ $jmlsakit ?? ($rekapizin->jmlsakit ?? 0) }}</span>
                    <span class="hs-lbl">Sakit</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#f59e0b">{{ $jmlcuti ?? ($rekapizin->jmlcuti ?? 0) }}</span>
                    <span class="hs-lbl">Cuti</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#60a5fa">{{ count($dataDinasLuar) }}</span>
                    <span class="hs-lbl">Dinas Luar</span>
                </div>
                <div class="hs-item">
                    <span class="hs-val" style="color:#94a3b8">{{ count($dataBelumPresensi) }}</span>
                    <span class="hs-lbl">Belum Presensi</span>
                </div>
            </div>

            <div class="h-clock">
                <div class="time-d" id="clock">--:--</div>
                <div class="date-d">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</div>
            </div>
        </header>

        <div class="main-wrapper">

            @php
                $izinSakitRows = collect($dataIzinSakit ?? []);

                $izinRows = $izinSakitRows->filter(function ($item) {
                    return $item->status === 'i';
                })->values();

                $sakitRows = $izinSakitRows->filter(function ($item) {
                    return $item->status === 's';
                })->values();

                $cutiRows = $izinSakitRows->filter(function ($item) {
                    return $item->status === 'c';
                })->values();
            @endphp

            <div class="middle-grid">

                <div class="tv-card b-gold">
                    <div class="card-head">
                        <div class="ch-title t-gold"><i class="ti ti-trophy"></i> Top Office</div>
                    </div>
                    <div class="card-body" id="scroll-top-office">
                        <div class="scroll-content">
                            @forelse($topOffice as $idx => $m)
                                <div class="li-row">
                                    <div style="font-size:var(--fz-md); font-weight:700; color:#eab308; width:2rem">
                                        #{{ $idx + 1 }}</div>
                                    <div class="u-info">
                                        <div class="u-main">{{ $m->nama_lengkap }}</div>
                                        <div class="u-sub">{{ $m->nama_cabang }}</div>
                                    </div>
                                    <div class="u-meta t-gold">{{ number_format($m->total_points) }}</div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Kosong -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-blue">
                    <div class="card-head">
                        <div class="ch-title t-blue"><i class="ti ti-tractor"></i> Top Site</div>
                    </div>
                    <div class="card-body" id="scroll-top-site">
                        <div class="scroll-content">
                            @forelse($topSite as $idx => $m)
                                <div class="li-row">
                                    <div style="font-size:var(--fz-md); font-weight:700; color:#3b82f6; width:2rem">
                                        #{{ $idx + 1 }}</div>
                                    <div class="u-info">
                                        <div class="u-main">{{ $m->nama_lengkap }}</div>
                                        <div class="u-sub">{{ $m->nama_cabang }}</div>
                                    </div>
                                    <div class="u-meta t-blue">{{ number_format($m->total_points) }}</div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Kosong -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-green">
                    <div class="card-head">
                        <div class="ch-title t-green"><i class="ti ti-bolt"></i> Tercepat</div>
                    </div>
                    <div class="card-body" id="scroll-fastest">
                        <div class="split-grid">
                            <div class="split-col">
                                <div class="split-head t-green">OFFICE</div>
                                <div class="split-col-scroll" id="scroll-fastest-office">
                                    <div class="scroll-content">
                                        @forelse($fastestOffice as $fo)
                                            <div class="li-row">
                                                <div class="u-info">
                                                    <div class="u-main">{{ $fo->nama_lengkap }}</div>
                                                    <div class="u-sub">{{ $fo->nama_cabang }}</div>
                                                </div>
                                                <div class="u-meta t-green">{{ date('H:i', strtotime($fo->jam_in)) }}</div>
                                            </div>
                                        @empty
                                            <div style="padding:0.5rem; text-align:center; color:var(--text-muted); font-size:var(--fz-sm)">-</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="split-col">
                                <div class="split-head t-green">SITE</div>
                                <div class="split-col-scroll" id="scroll-fastest-site">
                                    <div class="scroll-content">
                                        @forelse($fastestSite as $fs)
                                            <div class="li-row">
                                                <div class="u-info">
                                                    <div class="u-main">{{ $fs->nama_lengkap }}</div>
                                                    <div class="u-sub">{{ $fs->nama_cabang }}</div>
                                                </div>
                                                <div class="u-meta t-green">{{ date('H:i', strtotime($fs->jam_in)) }}</div>
                                            </div>
                                        @empty
                                            <div style="padding:0.5rem; text-align:center; color:var(--text-muted); font-size:var(--fz-sm)">-</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tv-card b-red">
                    <div class="card-head">
                        <div class="ch-title t-red"><i class="ti ti-clock-exclamation"></i> Terlambat</div>
                    </div>
                    <div class="card-body" id="scroll-late">
                        <div class="split-grid">
                            <div class="split-col">
                                <div class="split-head t-red">OFFICE</div>
                                <div class="split-col-scroll" id="scroll-late-office">
                                    <div class="scroll-content">
                                        @forelse($lateOffice as $lo)
                                            <div class="li-row">
                                                <div class="u-info">
                                                    <div class="u-main">{{ $lo->nama_lengkap }}</div>
                                                    <div class="u-sub">{{ $lo->nama_cabang }}</div>
                                                </div>
                                                <div class="u-meta t-red">+{{ (int) ($lo->menit_terlambat ?? 0) }}m</div>
                                            </div>
                                        @empty
                                            <div style="padding:0.5rem; text-align:center; color:var(--text-muted); font-size:var(--fz-sm)">-</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="split-col">
                                <div class="split-head t-red">SITE</div>
                                <div class="split-col-scroll" id="scroll-late-site">
                                    <div class="scroll-content">
                                        @forelse($lateSite as $ls)
                                            <div class="li-row">
                                                <div class="u-info">
                                                    <div class="u-main">{{ $ls->nama_lengkap }}</div>
                                                    <div class="u-sub">{{ $ls->nama_cabang }}</div>
                                                </div>
                                                <div class="u-meta t-red">+{{ (int) ($ls->menit_terlambat ?? 0) }}m</div>
                                            </div>
                                        @empty
                                            <div style="padding:0.5rem; text-align:center; color:var(--text-muted); font-size:var(--fz-sm)">-</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tv-card b-orange">
                    <div class="card-head">
                        <div class="ch-title t-orange"><i class="ti ti-mood-sick"></i> Izin</div>
                        <span class="ch-badge bg-orange">{{ $izinRows->count() }}</span>
                    </div>
                    <div class="card-body" id="scroll-izin">
                        <div class="scroll-content">
                            @forelse($izinRows as $iz)
                                <div class="li-row">
                                    <img src="{{ !empty($iz->foto) ? asset('storage/uploads/karyawan/' . $iz->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($iz->nama_lengkap) . '&background=random&color=fff' }}"
                                        class="u-avt">
                                    <div class="u-info">
                                        <div class="u-main">{{ $iz->nama_lengkap }}</div>
                                        <div class="u-sub t-orange">{{ $iz->keterangan }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Nihil -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-orange">
                    <div class="card-head">
                        <div class="ch-title t-orange"><i class="ti ti-first-aid-kit"></i> Sakit</div>
                        <span class="ch-badge bg-orange">{{ $sakitRows->count() }}</span>
                    </div>
                    <div class="card-body" id="scroll-sakit">
                        <div class="scroll-content">
                            @forelse($sakitRows as $sk)
                                <div class="li-row">
                                    <img src="{{ !empty($sk->foto) ? asset('storage/uploads/karyawan/' . $sk->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($sk->nama_lengkap) . '&background=random&color=fff' }}"
                                        class="u-avt">
                                    <div class="u-info">
                                        <div class="u-main">{{ $sk->nama_lengkap }}</div>
                                        <div class="u-sub t-orange">{{ $sk->keterangan }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Nihil -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-orange">
                    <div class="card-head">
                        <div class="ch-title t-orange"><i class="ti ti-calendar-event"></i> Cuti</div>
                        <span class="ch-badge bg-orange">{{ $cutiRows->count() }}</span>
                    </div>
                    <div class="card-body" id="scroll-cuti">
                        <div class="scroll-content">
                            @forelse($cutiRows as $ct)
                                <div class="li-row">
                                    <img src="{{ !empty($ct->foto) ? asset('storage/uploads/karyawan/' . $ct->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($ct->nama_lengkap) . '&background=random&color=fff' }}"
                                        class="u-avt">
                                    <div class="u-info">
                                        <div class="u-main">{{ $ct->nama_lengkap }}</div>
                                        <div class="u-sub t-orange">{{ $ct->keterangan }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Nihil -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-purple">
                    <div class="card-head">
                        <div class="ch-title t-purple"><i class="ti ti-clock-bolt"></i> Lembur</div>
                        <span class="ch-badge bg-purple">{{ count($dataLembur) }}</span>
                    </div>
                    <div class="card-body" id="scroll-lembur">
                        <div class="scroll-content">
                            @forelse($dataLembur as $lb)
                                <div class="li-row">
                                    <div class="u-info">
                                        <div class="u-main">{{ $lb->nama_lengkap }}</div>
                                        <div class="u-sub">{{ \Carbon\Carbon::parse($lb->tanggal_lembur)->translatedFormat('l, d M') }} | {{ date('H:i', strtotime($lb->jam_mulai)) }} - {{ date('H:i', strtotime($lb->jam_selesai)) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Nihil -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-blue">
                    <div class="card-head">
                        <div class="ch-title t-blue"><i class="ti ti-plane-departure"></i> Dinas Luar</div>
                        <span class="ch-badge bg-blue">{{ count($dataDinasLuar) }}</span>
                    </div>
                    <div class="card-body" id="scroll-dl">
                        <div class="scroll-content">
                            @forelse($dataDinasLuar as $dl)
                                <div class="li-row">
                                    <div class="u-info">
                                        <div class="u-main">{{ $dl->nama_lengkap }}</div>
                                        <div class="u-sub t-blue">{{ $dl->lokasi_tujuan }} | {{ date('d/m', strtotime($dl->tgl_mulai)) }}-{{ date('d/m', strtotime($dl->tgl_selesai)) }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Nihil -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="tv-card b-red">
                    <div class="card-head">
                        <div class="ch-title t-red"><i class="ti ti-alert-triangle"></i> Surat Peringatan</div>
                        <span class="ch-badge bg-red">{{ count($karyawanSP) }}</span>
                    </div>
                    <div class="card-body" id="scroll-sp">
                        <div class="scroll-content">
                            @forelse($karyawanSP as $sp)
                                <div class="li-row">
                                    <div class="u-info">
                                        <div class="u-main">{{ $sp->nama_lengkap }}</div>
                                        <div class="u-sub t-red">Surat Peringatan {{ $sp->level }}</div>
                                    </div>
                                    <div class="u-meta" style="font-size:0.9rem; color:var(--text-muted)">
                                        {{ \Carbon\Carbon::parse($sp->sampai_tanggal)->format('d/m') }}
                                    </div>
                                </div>
                            @empty
                                <div class="li-row" style="justify-content:center; color:var(--text-muted)">- Bersih -
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            <div class="bottom-grid">
                @forelse($leaderboardsByCabang as $idx => $lb)
                    <div class="tv-card">
                        <div class="card-head" style="background: rgba(255,255,255,0.02)">
                            <div class="ch-title" style="color:white; font-size:var(--fz-base)">
                                <i class="ti ti-building"></i>
                                {{ $lb['cabang']->nama_cabang }}
                            </div>
                            <span class="ch-badge bg-green">{{ count($lb['rows']) }}</span>
                        </div>
                        <div class="card-body" id="scroll-cabang-{{ $idx }}">
                            <div class="scroll-content">
                                @forelse($lb['rows'] as $r)
                                    <div class="li-row">
                                        <div class="u-info">
                                            <div class="u-main">{{ $r->nama_lengkap }}</div>
                                        </div>
                                        <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.1rem">
                                            <div class="u-meta t-green">{{ date('H:i', strtotime($r->jam_in)) }}</div>
                                            <div class="u-meta t-red" style="font-size:var(--fz-sm)">{{ $r->jam_out && $r->jam_out !== '00:00:00' ? date('H:i', strtotime($r->jam_out)) : '-' }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="li-row" style="justify-content:center; color:var(--text-muted)">-
                                        Kosong -</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                @endforelse

                <div class="tv-card b-grey">
                    <div class="card-head">
                        <div class="ch-title t-grey"><i class="ti ti-user-x"></i> Alpha</div>
                        <span class="ch-badge bg-grey">{{ count($dataBelumPresensi) }}</span>
                    </div>
                    <div class="card-body">
                        <div class="alpha-summary">{{ count($dataBelumPresensi) }}</div>
                    </div>
                </div>

                <div class="tv-card b-purple">
                    <div class="card-head">
                        <div class="ch-title t-purple"><i class="ti ti-key"></i> Token Registrasi</div>
                    </div>
                    <div class="card-body" style="display: flex; align-items: center; justify-content: center; position: relative;">
                        <div class="alpha-summary t-purple" style="font-size: clamp(2.2rem, 4vw, 5.5rem); letter-spacing: 2px;">
                            {{ $regToken ? $regToken->formatted_token : '-' }}
                        </div>

                        <!-- Progress Bar & Timer at the bottom -->
                        <div style="position: absolute; bottom: 15px; left: 0; width: 100%; padding: 0 30px;">
                            <div style="height: 4px; background: rgba(255,255,255,0.05); border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                                <div id="token-progress" style="height: 100%; background: linear-gradient(90deg, #d946ef, #a855f7); width: 100%; transition: width 1s linear;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                <span style="font-size: 0.65rem; color: #64748b; font-family: 'JetBrains Mono';">REFRESH</span>
                                <span id="token-timer" style="font-size: 0.75rem; color: #d946ef; font-weight: 700; font-family: 'JetBrains Mono';">--:--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <footer>
            <div class="mq-tag">LIVE INFO</div>
            <div class="mq-wrap">
                <div class="mq-text">
                    Selamat Datang di Dashboard Monitoring wndev &nbsp;&bull;&nbsp;
                    Total Karyawan Hadir: {{ count($dataPresensi) }} &nbsp;&bull;&nbsp;
                    Disiplin dan Integritas &nbsp;&bull;&nbsp;
                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </footer>

    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clock').innerText = `${h}:${m}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function initScroll(id, speed = 35) {
            const wrap = document.getElementById(id);
            if (!wrap) return;
            const content = wrap.querySelector('.scroll-content');
            if (!content) return;

            if (content.offsetHeight <= wrap.offsetHeight) return;

            const clone = content.cloneNode(true);
            wrap.appendChild(clone);
            clone.style.top = content.offsetHeight + 'px';

            let pos = 0;
            let lastTime = 0;

            function anim(time) {
                if (!lastTime) lastTime = time;
                const delta = time - lastTime;
                lastTime = time;
                pos += (speed * delta / 1000);
                if (pos >= content.offsetHeight) pos = 0;
                wrap.scrollTop = pos;
                requestAnimationFrame(anim);
            }
            requestAnimationFrame(anim);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initScroll('scroll-top-office', 16);
            initScroll('scroll-top-site', 16);
            initScroll('scroll-fastest-office', 16);
            initScroll('scroll-fastest-site', 16);
            initScroll('scroll-late-office', 16);
            initScroll('scroll-late-site', 16);
            initScroll('scroll-izin', 14);
            initScroll('scroll-sakit', 14);
            initScroll('scroll-cuti', 14);
            initScroll('scroll-lembur', 14);
            initScroll('scroll-dl', 14);
            initScroll('scroll-sp', 14);

            document.querySelectorAll('.card-body[id^="scroll-cabang-"]').forEach(el => {
                initScroll(el.id, 14 + Math.random() * 4);
            });

            // --- Token Refresh Logic ---
            @if($regToken)
            const tokenStart = new Date("{{ $regToken->created_at }}").getTime();
            const tokenEnd = new Date("{{ $regToken->expires_at }}").getTime();
            
            function updateTokenProgress() {
                const now = new Date().getTime();
                const total = tokenEnd - tokenStart;
                const remaining = tokenEnd - now;
                
                if (remaining <= 0) {
                    document.getElementById('token-progress').style.width = '0%';
                    document.getElementById('token-timer').innerText = 'EXPIRED';
                    setTimeout(() => location.reload(), 1500);
                    return;
                }
                
                const pct = Math.max(0, (remaining / total) * 100);
                document.getElementById('token-progress').style.width = pct + '%';
                
                const seconds = Math.floor((remaining / 1000) % 60);
                const minutes = Math.floor((remaining / 1000 / 60) % 60);
                document.getElementById('token-timer').innerText = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            }
            
            setInterval(updateTokenProgress, 1000);
            updateTokenProgress();
            @endif
        });
    </script>
</body>

</html>
