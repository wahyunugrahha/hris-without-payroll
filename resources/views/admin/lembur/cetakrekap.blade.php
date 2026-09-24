<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Lembur Seluruh Karyawan</title>
    <link rel="icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/7.0.0/normalize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/paper-css/0.4.1/paper.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 10mm 10mm 10mm;
        }

        body.A4.landscape .sheet {
            width: 297mm;
            height: auto;
            padding: 10mm !important;
        }

        html,
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        #title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }

        .tabellembur {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabellembur tr th {
            border: 1px solid #000;
            padding: 2px;
            background-color: #e9ecef;
            font-size: 8px;
            text-align: center;
        }

        .tabellembur tr td {
            border: 1px solid #000;
            padding: 2px 0;
            font-size: 7.5px;
            text-align: center;
            vertical-align: middle;
            height: 25px;
            line-height: 1.1;
        }

        .text-right {
            text-align: right;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        @media print {
            @page {
                margin: 10mm 10mm 10mm 10mm;
            }

            body,
            html {
                width: 297mm;
                height: auto;
                margin: 0;
                padding: 0;
            }

            body.A4.landscape .sheet {
                width: 297mm;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                padding: 10mm !important;
                margin: 0 !important;
                box-shadow: none !important;
            }

            .sheet {
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                box-shadow: none !important;
                page-break-after: auto;
            }

            .tabellembur thead {
                display: table-header-group;
            }

            .tabellembur tfoot {
                display: table-footer-group;
            }

            .tabellembur {
                page-break-inside: auto;
                border-collapse: collapse;
            }

            .tabellembur tbody tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .tabellembur td,
            .tabellembur th {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            /* Prevent orphan rows */
            .tabellembur tbody tr:last-child {
                page-break-after: auto;
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
        $bulan_int = (int) ($bulan ?? date('m'));
        $tahun_int = (int) ($tahun ?? date('Y'));
        if ($bulan_int == 1) {
            $bulan_awal = 12;
            $tahun_awal = $tahun_int - 1;
        } else {
            $bulan_awal = $bulan_int - 1;
            $tahun_awal = $tahun_int;
        }
        $start_date = "$tahun_awal-" . str_pad($bulan_awal, 2, '0', STR_PAD_LEFT) . '-26';
        $end_date = "$tahun_int-" . str_pad($bulan_int, 2, '0', STR_PAD_LEFT) . '-25';

        $formatJamMenit = function ($hoursFloat) {
            if ($hoursFloat === null) {
                return '-';
            }
            $totalMinutes = (int) round(((float) $hoursFloat) * 60);
            if ($totalMinutes < 0) {
                return '-';
            }
            $jam = intdiv($totalMinutes, 60);
            $menit = $totalMinutes % 60;
            return $jam . 'j' . $menit . 'm';
        };

        // Approval Data
        $user = Auth::user();
        $approverName = $user ? $user->name : 'Ahmad Yozi Alhidayah';
        $approverJabatan = $user && $user->jabatan ? strtoupper($user->jabatan->nama_jabatan) : 'HRD';
        $approvedAt = \Carbon\Carbon::now()->format('d F Y');
        $ttdText = "Telah di tanda tangani oleh :{$approverName} jabatan:{$approverJabatan} tanggal:{$approvedAt}";
        $qrImage = \App\Support\TandaTangan::qrImage($ttdText);
    @endphp
    <section class="sheet">
        <table style="width: 100%;">
            <tr>
                <td style="width: 60px;">
                    <x-brand-logo variant="print-square" alt="wndev" />
                </td>
                <td>
                    <div id="title">
                        REKAPITULASI LEMBUR KARYAWAN<br>
                        PERIODE {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d F Y') }} -
                        {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d F Y') }} <br>
                        {{ $cabang->nama_cabang ?? 'wndev' }}
                    </div>
                    <span style="font-size: 9px;">Jl. W.R. Suprtaman, RT 07 Rw 02, Kel. Bentiring, Kec Muara Bangkahulu,
                        Kota Bengkulu</span>
                </td>
            </tr>
        </table>
        <table class="tabellembur">
            <thead>
                <tr>
                    <th style="width: 65px;">NIK</th>
                    <th style="width: 110px;">Nama Karyawan</th>
                    <th style="width: 60px;">Departemen</th>
                    <th style="width: 60px;">Cabang</th>
                    <th style="width: 80px;">Tanggal Lembur</th>
                    <th style="width: 120px;">Pekerjaan</th>
                    <th style="width: 120px;">Tempat</th>
                    <th style="width: 120px;">Keterangan</th>
                    <th style="width: 60px;">Jam Mulai</th>
                    <th style="width: 60px;">Jam Selesai</th>
                    <th style="width: 50px;">Pembaruan</th>
                    <th style="width: 60px;">Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lembur as $nik => $items)
                    @php
                        $rowspan = count($items);
                        $first = true;
                        $total_jam = 0;
                    @endphp
                    @foreach ($items as $item)
                        @php $total_jam += $item->total_jam ?? 0; @endphp
                        <tr>
                            @if ($first)
                                <td rowspan="{{ $rowspan }}">{{ $item->nik }}</td>
                                <td rowspan="{{ $rowspan }}"
                                    style="text-align: left; padding-left: 3px; white-space: nowrap; overflow: hidden;">
                                    {{ $item->karyawan->nama_lengkap ?? '-' }}</td>
                                <td rowspan="{{ $rowspan }}">{{ $item->karyawan->departemen->nama_dept ?? '-' }}
                                </td>
                                <td rowspan="{{ $rowspan }}">{{ $item->karyawan->cabang->nama_cabang ?? '-' }}
                                </td>
                            @endif
                            <td>{{ \Carbon\Carbon::parse($item->tanggal_lembur)->translatedFormat('d F Y') }}</td>
                            <td style="text-align: left;">{{ $item->pekerjaan }}</td>
                            <td style="text-align: left;">{{ $item->tempat }}</td>
                            <td style="text-align: left;">{{ $item->keterangan }}</td>
                            <td>{{ $item->jam_mulai ?? '-' }}</td>
                            <td>{{ $item->jam_selesai ?? '-' }}</td>
                            <td style="font-size: 6px;">
                                @if ($item->update_count > 0)
                                    Ubah: {{ $item->update_count }}x<br>
                                    Awal: {{ $item->jam_selesai_awal }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $formatJamMenit($item->total_jam ?? null) }}</td>
                        </tr>
                        @php $first = false; @endphp
                    @endforeach
                    <tr style="background: #f3f3f3; font-weight: bold;">
                        <td colspan="8"></td>
                        <td colspan="3" style="text-align: center;">Total Jam Lembur</td>
                        <td>{{ $formatJamMenit($total_jam) }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
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
    </section>
</body>

</html>
