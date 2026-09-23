<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Seluruh Presensi Karyawan</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        body.A4.landscape .sheet {
            padding: 5mm 10mm !important;
        }

        html,
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
        }

        #title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.3;
            margin-bottom: 5px;
        }

        .tabelpresensi {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 10px;
        }

        .tabelpresensi th {
            border: 1px solid #000;
            padding: 4px 2px;
            background-color: #f2f2f2;
            font-size: 8px;
            text-align: center;
        }

        .tabelpresensi td {
            border: 1px solid #000;
            font-size: 7.5px;
            text-align: center;
            vertical-align: middle;
            height: 28px;
            word-wrap: break-word;
        }

        .bg-holiday {
            background-color: #ffebeb !important;
        }

        .text-red {
            color: red;
        }

        .text-blue {
            color: blue;
        }

        .text-green {
            color: green;
        }

        .keterangan-container {
            margin-top: 15px;
            font-size: 8.5px;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .ket-label {
            font-weight: bold;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .sheet {
                page-break-after: always;
            }
        }

        .qr-box {
            margin: 10px 0;
        }

        .qr-box img {
            width: 80px;
            height: 80px;
        }

        .sign-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 5px;
        }
    </style>
</head>

<body class="A4 landscape">
    @php
        // 1. Inisialisasi Periode
        $bulan_int = (int) ($bulan ?? date('m'));
        $tahun_int = (int) ($tahun ?? date('Y'));

        $tgl_akhir_obj = \Carbon\Carbon::create($tahun_int, $bulan_int, 25)->startOfDay();
        $tgl_awal_obj = $tgl_akhir_obj->copy()->subMonth()->addDay();

        $tgl_awal = $tgl_awal_obj->format('Y-m-d');
        $tgl_akhir = $tgl_akhir_obj->format('Y-m-d');

        $list_tgl = [];
        $period = \Carbon\CarbonPeriod::create($tgl_awal, $tgl_akhir);

        foreach ($period as $date) {
            $list_tgl[] = $date->format('Y-m-d');
        }

        $jml_kolom = count($list_tgl);

        // 2. Pagination (Data Chunking)
        $rekapCollection = is_array($rekap) ? collect($rekap) : $rekap;
        $pages = $rekapCollection->chunk(18);

        // 3. Helper Format Jam
        $formatJamMenit = function ($hoursFloat) {
            if (!$hoursFloat || $hoursFloat <= 0) {
                return '-';
            }
            $totalMinutes = (int) round($hoursFloat * 60);
            return intdiv($totalMinutes, 60) . 'j ' . $totalMinutes % 60 . 'm';
        };

        // Approval Data
        $user = Auth::user();
        $approverName = $user ? $user->name : 'Ahmad Yozi Alhidayah';
        $approverJabatan = $user && $user->jabatan ? strtoupper($user->jabatan->nama_jabatan) : 'HRD';
        $approvedAt = \Carbon\Carbon::now()->format('d F Y');
        $ttdText = "Telah di tanda tangani oleh :{$approverName} jabatan:{$approverJabatan} tanggal:{$approvedAt}";
        $qrImage = \App\Support\TandaTangan::qrImage($ttdText);

        // Fallback nama cabang untuk lembar template kosong.
        $defaultBranch = $cabang->nama_cabang ?? 'wndev';
    @endphp

    @foreach ($pages as $pageIndex => $pageData)
        <section class="sheet">
            <table style="width: 100%; border:none;">
                <tr>
                    <td style="width: 80px; border:none;">
                        <x-brand-logo variant="print-square" alt="wndev" />
                    </td>
                    <td style="border:none; text-align: left; vertical-align: top;">
                        <div id="title">
                            REKAPITULASI PRESENSI KARYAWAN<br>
                            PERIODE {{ \Carbon\Carbon::parse($tgl_awal)->translatedFormat('d F Y') }} -
                            {{ \Carbon\Carbon::parse($tgl_akhir)->translatedFormat('d F Y') }} <br>
                            {{ $cabang->nama_cabang ?? 'wndev' }}
                        </div>
                        <span style="font-size: 9px;">Jl. W.R. Suprtaman, RT 07 Rw 02, Kel. Bentiring, Kec Muara
                            Bangkahulu, Kota Bengkulu</span>
                    </td>
                </tr>
            </table>

            <table class="tabelpresensi">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 70px;">NIK</th>
                        <th rowspan="2" style="width: 130px;">Nama Karyawan</th>
                        <th colspan="{{ $jml_kolom }}">Tanggal</th>
                        <th colspan="6" style="width: 120px;">Total</th>
                    </tr>
                    <tr>
                        @foreach ($list_tgl as $tgl)
                            @php
                                $isSun = date('w', strtotime($tgl)) == 0;
                                $isHol = in_array($tgl, $hariLiburNasional ?? []);
                            @endphp
                            <th style="width: 20px; {{ $isSun || $isHol ? 'color:red;' : '' }}">
                                {{ date('d', strtotime($tgl)) }}
                            </th>
                        @endforeach
                        <th>H</th>
                        <th>I</th>
                        <th>S</th>
                        <th>C</th>
                        <th>A</th>
                        <th>JK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pageData as $d)
                        @php
                            $total_h = $total_i = $total_s = $total_c = $total_a = $total_jk = 0;
                        @endphp
                        <tr>
                            <td>{{ $d->nik }}</td>
                            <td style="text-align: left; padding-left: 4px; line-height: 1.2;">
                                {{ substr($d->nama_lengkap, 0, 20) }}
                                @if (!empty($d->kenaikan_gaji))
                                    <div style="font-size: 7px; color: #094b87; font-weight: bold; margin-top: 2px;">KG:
                                        +{{ $d->kenaikan_gaji }}%</div>
                                @endif
                            </td>

                            @foreach ($list_tgl as $tgl)
                                @php
                                    $tglField = 'tgl_' . date('Ymd', strtotime($tgl));
                                    $rawData = $d->$tglField ?? null;
                                    $isSunday = date('w', strtotime($tgl)) == 0;
                                    $isHoliday = in_array($tgl, $hariLiburByNik[$d->nik] ?? ($hariLiburNasional ?? []));

                                    $display = '';
                                    $colorClass = '';

                                    if (!empty($rawData)) {
                                        $parts = explode('|', $rawData);
                                        $status = $parts[0] ?? 'h';
                                        $jamIn = $parts[1] ?? '00:00:00';
                                        $jamOut = $parts[2] ?? '00:00:00';
                                        $jamMasukJadwal = $parts[3] ?? ($d->jam_masuk ?? null);

                                        if ($status == '#') {
                                            $display = '-';
                                            $colorClass = '';
                                        } elseif ($status == 'l' || (($isSunday || $isHoliday) && $status == 'a')) {
                                            $display = 'L';
                                            $colorClass = 'text-green';
                                        } elseif ($status == 'h') {
                                            $total_h++;
                                            $in = $jamIn != '00:00:00' ? date('H:i', strtotime($jamIn)) : '-';
                                            $out = $jamOut != '00:00:00' ? date('H:i', strtotime($jamOut)) : '-';
                                            $display = "$in<br>$out";

                                            // Warna merah jika terlambat (pakai jadwal per-hari, tidak tergantung jam pulang)
                                            if (is_terlambat($jamMasukJadwal, $jamIn)) {
                                                $colorClass = 'text-red';
                                            }

                                            // Perhitungan Jam Kerja (Logic Simplified)
                                            if ($jamIn != '00:00:00' && $jamOut != '00:00:00') {
                                                $start = strtotime($jamIn);
                                                $end_time = strtotime($jamOut);
                                                // Jika ada limit jam pulang di data karyawan, gunakan itu
                                                $limit = !empty($d->jam_pulang)
                                                    ? strtotime($d->jam_pulang)
                                                    : strtotime('17:00:00');
                                                $actual_end = $end_time > $limit ? $limit : $end_time;
                                                $diff = ($actual_end - $start) / 3600;
                                                if ($diff > 0) {
                                                    $total_jk += $diff;
                                                }
                                            }
                                        } else {
                                            $display = strtoupper($status);
                                            $colorClass = $status == 'a' ? 'text-red' : 'text-blue';
                                            if ($status == 'i') {
                                                $total_i++;
                                            }
                                            if ($status == 's') {
                                                $total_s++;
                                            }
                                            if ($status == 'c') {
                                                $total_c++;
                                            }
                                            if ($status == 'r') {
                                                $display = 'R';
                                                $colorClass = 'text-blue';
                                            }
                                            if ($status == 'a') {
                                                $total_a++;
                                            }
                                        }
                                    } else {
                                        if ($isSunday || $isHoliday) {
                                            $display = 'L';
                                            $colorClass = 'text-green';
                                        } else {
                                            $display = 'A';
                                            $colorClass = 'text-red';
                                            $total_a++;
                                        }
                                    }
                                @endphp
                                <td class="{{ $isSunday || $isHoliday ? 'bg-holiday' : '' }} {{ $colorClass }}"
                                    style="font-weight: bold;">
                                    {!! $display !!}
                                </td>
                            @endforeach

                            <td style="font-weight: bold;">{{ $total_h }}</td>
                            <td>{{ $total_i }}</td>
                            <td>{{ $total_s }}</td>
                            <td>{{ $total_c }}</td>
                            <td class="text-red" style="font-weight: bold;">{{ $total_a }}</td>
                            <td style="font-weight: bold; font-size: 7px;">{{ $formatJamMenit($total_jk) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($loop->last)
                <div class="keterangan-container">
                    <span class="ket-label">Ket:</span>
                    <span>H: Hadir</span>
                    <span class="text-blue">I: Izin</span>
                    <span class="text-blue">S: Sakit</span>
                    <span class="text-blue">C: Cuti</span>
                    <span class="text-blue">R: Roster</span>
                    <span class="text-red">A: Alpha</span>
                    <span class="text-green">L: Libur</span>
                    <span>JK: Total Jam Kerja</span>
                </div>

                <table width="100%" style="margin-top: 30px; border:none;">
                    <tr>
                        <td width="60%"></td>

                        <td width="40%" style="text-align: center;">
                            <div>Bengkulu, {{ $approvedAt }}</div>
                            <div style="margin-top:5px; font-weight:bold;">Mengetahui,</div>
                            <div style="margin-top:2px;">wndev</div>

                            <div class="qr-box">
                                <img src="{{ $qrImage }}" alt="QR Code TTD">
                            </div>

                            <div class="sign-name">{{ $approverName }}</div>
                            <div>{{ $approverJabatan }}</div>
                        </td>
                    </tr>
                </table>
            @endif
        </section>
    @endforeach

    @php
        $karyawanNaikGaji = collect($rekap)->filter(function ($item) {
            return !empty($item->kenaikan_gaji);
        });

        // Urutkan berdasarkan nama cabang, lalu NIK
        $karyawanNaikGaji = $karyawanNaikGaji->sortBy([['nama_cabang', 'asc'], ['nik', 'asc']])->values();

        // Chunk per 10 karyawan per halaman untuk mencegah overlapping/pemotongan layout Landscape
        $naikGajiChunks = $karyawanNaikGaji->chunk(10);
    @endphp

    @if ($karyawanNaikGaji->isNotEmpty())
        @foreach ($naikGajiChunks as $chunkIndex => $employeesGroup)
            <section class="sheet" style="padding: 5mm 10mm !important;">
                @if ($loop->first)
                    <table style="width: 100%; border:none;">
                        <tr>
                            <td style="width: 80px; border:none;">
                                <x-brand-logo variant="print-square" alt="wndev" />
                            </td>
                            <td style="border:none; text-align: left; vertical-align: top;">
                                <div id="title" style="font-size: 13px;">
                                    LAMPIRAN REKAPITULASI PRESENSI<br>
                                    DAFTAR KARYAWAN YANG MENDAPATKAN KENAIKAN GAJI<br>
                                    PERIODE {{ \Carbon\Carbon::parse($tgl_awal)->translatedFormat('d F Y') }} -
                                    {{ \Carbon\Carbon::parse($tgl_akhir)->translatedFormat('d F Y') }} <br>
                                    {{ $cabang->nama_cabang ?? 'wndev' }}
                                </div>
                                <span style="font-size: 9px;">Jl. W.R. Supratman, RT 07 Rw 02, Kel. Bentiring, Kec Muara
                                    Bangkahulu, Kota Bengkulu</span>
                            </td>
                        </tr>
                    </table>
                    <hr style="border: 1.5px solid #000; margin: 15px 0 10px 0;">
                @else
                    <table style="width: 100%; border:none;">
                        <tr>
                            <td style="border:none; text-align: left; vertical-align: top;">
                                <span style="font-weight: bold; font-size: 11px; text-transform: uppercase;">
                                    LAMPIRAN DAFTAR KENAIKAN GAJI (LANJUTAN)
                                </span>
                                <span style="font-size: 9px; float: right; color: #64748b; font-weight: bold;">
                                    Halaman {{ $loop->iteration }} dari {{ $loop->count }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <hr style="border: 1.5px solid #000; margin: 5px 0 15px 0;">
                @endif

                @if ($loop->first)
                    <div style="font-size: 10px; font-weight: bold; margin-bottom: 12px; line-height: 1.4;">
                        Berdasarkan evaluasi kehadiran, kinerja berdasarkan KPI, dan kedisiplinan selama periode
                        penilaian
                        berjalan, berikut adalah daftar karyawan yang telah sah mendapatkan persetujuan Kenaikan Gaji:
                    </div>
                @endif

                <table class="tabelpresensi" style="table-layout: auto; margin-bottom: 10px; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40px; padding: 6px; font-size: 9px;">No</th>
                            <th style="width: 110px; padding: 6px; font-size: 9px;">NIK</th>
                            <th style="padding: 6px; text-align: left; padding-left: 8px; font-size: 9px;">Nama Lengkap
                            </th>
                            <th style="width: 150px; padding: 6px; font-size: 9px;">Cabang</th>
                            <th style="width: 130px; padding: 6px; font-size: 9px;">Departemen</th>
                            <th style="width: 90px; padding: 6px; font-size: 9px;">Kenaikan</th>
                            <th style="width: 120px; padding: 6px; font-size: 9px;">Tanggal Disetujui</th>
                            <th style="width: 130px; padding: 6px; font-size: 9px;">Disetujui Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employeesGroup as $kng)
                            @php
                                $globalNo = $chunkIndex * 10 + $loop->iteration;
                            @endphp
                            <tr>
                                <td style="padding: 6px; font-size: 8.5px;">{{ $globalNo }}</td>
                                <td style="padding: 6px; font-size: 8.5px;">{{ $kng->nik }}</td>
                                <td
                                    style="padding: 6px; text-align: left; padding-left: 8px; font-weight: bold; font-size: 8.5px;">
                                    {{ $kng->nama_lengkap }}</td>
                                <td style="padding: 6px; font-size: 8.5px;">{{ $kng->nama_cabang ?? '-' }}</td>
                                <td style="padding: 6px; font-size: 8.5px;">{{ $kng->nama_dept ?? '-' }}</td>
                                <td style="padding: 6px; font-weight: bold; color: green; font-size: 9.5px;">
                                    +{{ $kng->kenaikan_gaji }}%</td>
                                <td style="padding: 6px; font-size: 8.5px;">
                                    {{ $kng->kenaikan_gaji_tgl ? date('d-m-Y', strtotime($kng->kenaikan_gaji_tgl)) : '-' }}
                                </td>
                                <td style="padding: 6px; font-weight: bold; font-size: 8.5px;">
                                    {{ $kng->kenaikan_gaji_oleh ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($loop->last)
                    <table width="100%" style="margin-top: 40px; border:none; page-break-inside: avoid;">
                        <tr>
                            <td width="60%"></td>
                            <td width="40%" style="text-align: center; font-size: 9px;">
                                <div>Bengkulu, {{ $approvedAt }}</div>
                                <div style="margin-top:5px; font-weight:bold;">Mengetahui & Mensahkan,</div>
                                <div style="margin-top:2px;">wndev</div>

                                <div class="qr-box">
                                    <img src="{{ $qrImage }}" alt="QR Code TTD"
                                        style="width: 75px; height: 75px;">
                                </div>

                                <div class="sign-name" style="font-size: 9px;">{{ $approverName }}</div>
                                <div>{{ $approverJabatan }}</div>
                            </td>
                        </tr>
                    </table>
                @endif
            </section>
        @endforeach
    @endif

    @php
        // Menggabungkan Top 3 dari SETIAP cabang ke dalam satu koleksi
        $karyawanDapatBonus = collect();

        $rekapPerCabang = collect($rekap)->groupBy(function ($item) {
            return $item->nama_cabang ?? 'wndev';
        });

        foreach ($rekapPerCabang as $branchName => $branchEmployees) {
            $topThreeBranch = collect($branchEmployees)
                ->filter(function ($item) {
                    // Hanya karyawan yang mendapatkan bonus DAN memiliki poin bulanan lebih besar dari 0
                    return !empty($item->bonus_bulanan) &&
                        $item->bonus_bulanan > 0 &&
                        isset($item->total_poin_bulanan) &&
                        $item->total_poin_bulanan > 0;
                })
                ->sortByDesc('bonus_bulanan')
                ->take(3)
                ->values();

            // Assign peringkat relatif secara dinamis sebelum dimerge
            foreach ($topThreeBranch as $index => $item) {
                $item->peringkat_cabang = $index + 1;
            }

            $karyawanDapatBonus = $karyawanDapatBonus->merge($topThreeBranch);
        }

        // Urutkan berdasarkan nama cabang, lalu nominal bonus terbesar (yang setara dengan peringkat)
        $karyawanDapatBonus = $karyawanDapatBonus
            ->sortBy([['nama_cabang', 'asc'], ['bonus_bulanan', 'desc']])
            ->values();

        // Mengelompokkan berdasarkan nama cabang
        $groupedByBranch = $karyawanDapatBonus->groupBy('nama_cabang');

        // Chunk per 3 cabang per halaman agar pas dan tidak terpotong pada layout A4 Portrait
        $branchChunks = $groupedByBranch->chunk(3);
        $globalIteration = 1;
    @endphp

    @if ($karyawanDapatBonus->isNotEmpty())
        @foreach ($branchChunks as $chunkIndex => $branchGroup)
            <section class="sheet" style="padding: 5mm 10mm !important;">
                @if ($loop->first)
                    <table style="width: 100%; border:none;">
                        <tr>
                            <td style="width: 80px; border:none;">
                                <x-brand-logo variant="print-square" alt="wndev" />
                            </td>
                            <td style="border:none; text-align: left; vertical-align: top;">
                                <div id="title" style="font-size: 13px;">
                                    LAMPIRAN REKAPITULASI PRESENSI<br>
                                    DAFTAR KARYAWAN YANG MENDAPATKAN BONUS PERFORMA PER CABANG<br>
                                    PERIODE {{ \Carbon\Carbon::parse($tgl_awal)->translatedFormat('d F Y') }} -
                                    {{ \Carbon\Carbon::parse($tgl_akhir)->translatedFormat('d F Y') }} <br>
                                    {{ $cabang->nama_cabang ?? 'wndev' }}
                                </div>
                                <span style="font-size: 9px;">Jl. W.R. Supratman, RT 07 Rw 02, Kel. Bentiring, Kec
                                    Muara Bangkahulu, Kota Bengkulu</span>
                            </td>
                        </tr>
                    </table>

                    <hr style="border: 1px solid #000; margin: 15px 0 10px 0;">

                    <div style="font-size: 10px; font-weight: bold; margin-bottom: 12px; line-height: 1.4;">
                        Berdasarkan evaluasi kehadiran, kedisiplinan dan akumulasi poin harian selama periode penilaian
                        berjalan, berikut adalah daftar karyawan dengan performa terbaik per cabang yang mendapatkan
                        penghargaan Bonus Performa:
                    </div>
                @else
                    <table style="width: 100%; border:none; margin-bottom: 10px;">
                        <tr>
                            <td style="border:none; text-align: left; vertical-align: top;">
                                <span
                                    style="font-size: 11px; font-weight: bold; text-transform: uppercase; color: #1e293b;">
                                    LAMPIRAN REKAPITULASI PRESENSI - BONUS PERFORMA (LANJUTAN)
                                </span>
                                <span style="font-size: 9px; float: right; color: #64748b; font-weight: bold;">
                                    Halaman {{ $loop->iteration }} dari {{ $loop->count }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <hr style="border: 1.5px solid #000; margin: 5px 0 15px 0;">
                @endif

                @foreach ($branchGroup as $namaCabangGroup => $employeesGroup)
                    <div style="margin-top: 15px; margin-bottom: 5px;">
                        <span style="font-weight: bold; font-size: 10px; color: #1e293b; text-transform: uppercase;">
                            🏢 Cabang: {{ $namaCabangGroup }}
                        </span>
                    </div>
                    <table class="tabelpresensi" style="table-layout: auto; margin-bottom: 10px; width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px; padding: 6px; font-size: 9px;">No</th>
                                <th style="width: 120px; padding: 6px; font-size: 9px;">NIK</th>
                                <th style="padding: 6px; text-align: left; padding-left: 8px; font-size: 9px;">Nama
                                    Lengkap</th>
                                <th style="width: 120px; padding: 6px; font-size: 9px;">Departemen</th>
                                <th style="width: 100px; padding: 6px; font-size: 9px;">Poin Kehadiran</th>
                                <th style="width: 120px; padding: 6px; font-size: 9px;">Bonus Performa</th>
                                <th style="width: 120px; padding: 6px; font-size: 9px;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employeesGroup as $kdb)
                                @php
                                    $keterangan = 'Peringkat ' . ($kdb->peringkat_cabang ?? 1) . ' Terbaik';
                                @endphp
                                <tr>
                                    <td style="padding: 6px; font-size: 8.5px;">{{ $loop->iteration }}</td>
                                    <td style="padding: 6px; font-size: 8.5px;">{{ $kdb->nik }}</td>
                                    <td
                                        style="padding: 6px; text-align: left; padding-left: 8px; font-weight: bold; font-size: 8.5px;">
                                        {{ $kdb->nama_lengkap }}</td>
                                    <td style="padding: 6px; font-size: 8.5px;">{{ $kdb->nama_dept ?? '-' }}</td>
                                    <td style="padding: 6px; font-weight: bold; font-size: 8.5px;">
                                        {{ number_format($kdb->total_poin_bulanan) }} pts</td>
                                    <td style="padding: 6px; font-weight: bold; color: green; font-size: 9.5px;">Rp
                                        {{ number_format($kdb->bonus_bulanan, 0, ',', '.') }}</td>
                                    <td style="padding: 6px; font-weight: bold; font-size: 8.5px; color: #1e293b;">
                                        {{ $keterangan }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                {{-- TTD hanya di halaman terakhir dari rangkaian chunk --}}
                @if ($loop->last)
                    <table width="100%" style="margin-top: 40px; border:none; page-break-inside: avoid;">
                        <tr>
                            <td width="60%"></td>
                            <td width="40%" style="text-align: center; font-size: 9px;">
                                <div>Bengkulu, {{ $approvedAt }}</div>
                                <div style="margin-top:5px; font-weight:bold;">Mengetahui & Mensahkan,</div>
                                <div style="margin-top:2px;">wndev</div>

                                <div class="qr-box">
                                    <img src="{{ $qrImage }}" alt="QR Code TTD"
                                        style="width: 75px; height: 75px;">
                                </div>

                                <div class="sign-name" style="font-size: 9px;">{{ $approverName }}</div>
                                <div>{{ $approverJabatan }}</div>
                            </td>
                        </tr>
                    </table>
                @endif
            </section>
        @endforeach
    @else
        {{-- Lembar kosong template jika belum di-generate --}}
        <section class="sheet" style="padding: 5mm 10mm !important;">
            <table style="width: 100%; border:none;">
                <tr>
                    <td style="width: 80px; border:none;">
                        <x-brand-logo variant="print-square" alt="wndev" />
                    </td>
                    <td style="border:none; text-align: left; vertical-align: top;">
                        <div id="title" style="font-size: 13px;">
                            LAMPIRAN REKAPITULASI PRESENSI<br>
                            DAFTAR KARYAWAN YANG MENDAPATKAN BONUS PERFORMA PER CABANG<br>
                            PERIODE {{ \Carbon\Carbon::parse($tgl_awal)->translatedFormat('d F Y') }} -
                            {{ \Carbon\Carbon::parse($tgl_akhir)->translatedFormat('d F Y') }} <br>
                            {{ $cabang->nama_cabang ?? 'wndev' }}
                        </div>
                        <span style="font-size: 9px;">Jl. W.R. Supratman, RT 07 Rw 02, Kel. Bentiring, Kec Muara
                            Bangkahulu, Kota Bengkulu</span>
                    </td>
                </tr>
            </table>

            <hr style="border: 1px solid #000; margin: 15px 0 10px 0;">

            <div style="font-size: 10px; font-weight: bold; margin-bottom: 12px; line-height: 1.4;">
                Berdasarkan evaluasi kehadiran, kedisiplinan dan akumulasi poin harian, selama periode penilaian
                berjalan, berikut adalah daftar karyawan dengan performa terbaik per cabang yang mendapatkan penghargaan
                Bonus Performa:
            </div>

            <div style="margin-top: 15px; margin-bottom: 5px;">
                <span style="font-weight: bold; font-size: 10px; color: #1e293b; text-transform: uppercase;">
                    🏢 Cabang: {{ $defaultBranch }}
                </span>
            </div>
            <table class="tabelpresensi" style="table-layout: auto; margin-bottom: 10px; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 40px; padding: 6px; font-size: 9px;">No</th>
                        <th style="width: 120px; padding: 6px; font-size: 9px;">NIK</th>
                        <th style="padding: 6px; text-align: left; padding-left: 8px; font-size: 9px;">Nama Lengkap
                        </th>
                        <th style="width: 120px; padding: 6px; font-size: 9px;">Departemen</th>
                        <th style="width: 100px; padding: 6px; font-size: 9px;">Poin Kehadiran</th>
                        <th style="width: 110px; padding: 6px; font-size: 9px;">Rerata Jam Masuk</th>
                        <th style="width: 120px; padding: 6px; font-size: 9px;">Bonus Performa</th>
                        <th style="width: 120px; padding: 6px; font-size: 9px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 12px; font-size: 8.5px;">1</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">
                            .......................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc; text-align: left; padding-left: 8px;">
                            .............................................................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 9.5px; font-weight: bold; color: green;">Rp 150.000</td>
                        <td style="padding: 12px; font-size: 8.5px; font-weight: bold;">Peringkat 1 Terbaik</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; font-size: 8.5px;">2</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">
                            .......................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc; text-align: left; padding-left: 8px;">
                            .............................................................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 9.5px; font-weight: bold; color: green;">Rp 100.000</td>
                        <td style="padding: 12px; font-size: 8.5px; font-weight: bold;">Peringkat 2 Terbaik</td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; font-size: 8.5px;">3</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">
                            .......................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc; text-align: left; padding-left: 8px;">
                            .............................................................................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 8.5px; color: #ccc;">.......................</td>
                        <td style="padding: 12px; font-size: 9.5px; font-weight: bold; color: green;">Rp 50.000</td>
                        <td style="padding: 12px; font-size: 8.5px; font-weight: bold;">Peringkat 3 Terbaik</td>
                    </tr>
                </tbody>
            </table>

            <table width="100%" style="margin-top: 40px; border:none; page-break-inside: avoid;">
                <tr>
                    <td width="60%"></td>
                    <td width="40%" style="text-align: center; font-size: 9px;">
                        <div>Bengkulu, {{ $approvedAt }}</div>
                        <div style="margin-top:5px; font-weight:bold;">Mengetahui & Mensahkan,</div>
                        <div style="margin-top:2px;">wndev</div>

                        <div class="qr-box">
                            <img src="{{ $qrImage }}" alt="QR Code TTD" style="width: 75px; height: 75px;">
                        </div>

                        <div class="sign-name" style="font-size: 9px;">{{ $approverName }}</div>
                        <div>{{ $approverJabatan }}</div>
                    </td>
                </tr>
            </table>
        </section>
    @endif
</body>

</html>
