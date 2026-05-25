<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap KPI Harian Karyawan</title>
    <style>
        /* === SETUP KERTAS & LAYAR === */
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #525659;
            margin: 0;
            padding: 20px 0;
            color: #000;
        }

        .main-container {
            width: 210mm;
            min-height: 297mm;
            background: #ffffff;
            margin: 0 auto 20px auto;
            padding: 20px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        @media print {
            body {
                background-color: transparent;
                padding: 0;
            }

            .main-container {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                page-break-after: always;
            }

            .main-container:last-child {
                page-break-after: auto;
            }

            .no-print {
                display: none !important;
            }

            tr {
                page-break-inside: avoid;
            }
        }

        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
            z-index: 9999;
        }

        .bio-table {
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
            width: 60%;
            font-weight: bold;
        }

        .bio-table td {
            padding: 4px 8px 4px 0;
        }

        .bio-table .label {
            width: 120px;
        }

        .bio-table .colon {
            width: 10px;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 10px;
            /* Jarak tabel utama ke bawah */
        }

        /* Warna Header disamakan untuk semua tabel (B4C6E7) */
        .content-table th {
            background-color: #B4C6E7;
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .content-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        /* Baris Hasil/Total Utama */
        .bg-grey {
            background-color: #D9D9D9;
            font-weight: bold;
            text-align: center;
        }

        /* Tabel Ekstra (Jarak dari tabel atas) */
        .table-extra {
            margin-top: 20px;
        }

        /* === FOOTER === */
        .footer-container {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .footer-left {
            width: 80%;
            padding-right: 20px;
        }

        .footer-left strong {
            display: block;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .footer-left ol {
            margin: 0;
            padding-left: 15px;
            text-align: justify;
            line-height: 1.5;
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="no-print btn-print">🖨️ Cetak Laporan</button>

    @php
        function toRoman($number)
        {
            $map = [
                'M' => 1000,
                'CM' => 900,
                'D' => 500,
                'CD' => 400,
                'C' => 100,
                'XC' => 90,
                'L' => 50,
                'XL' => 40,
                'X' => 10,
                'IX' => 9,
                'V' => 5,
                'IV' => 4,
                'I' => 1,
            ];
            $returnValue = '';
            while ($number > 0) {
                foreach ($map as $roman => $int) {
                    if ($number >= $int) {
                        $number -= $int;
                        $returnValue .= $roman;
                        break;
                    }
                }
            }
            return $returnValue;
        }
    @endphp

    @forelse ($rekap as $row)
        <div class="main-container">
            {{-- KOP SURAT --}}
            <table style="width: 100%; margin-bottom: 10px; border:none;">
                <tr>
                    <td style="width: 80px; vertical-align: top; border:none; padding:0;">
                        <img src="{{ asset('assets/img/logo.png') }}" width="70" alt="Logo"
                            onerror="this.style.display='none'">
                    </td>
                    <td style="vertical-align: top; text-align:left; border:none; padding:0;">
                        <span id="title">
                            LAPORAN REKAPITULASI WORKBOOK DAILY<br>
                            PERIODE {{ strtoupper($row->periode) }}<br>
                            <span style="font-size: 14px; font-weight: bold;">{{ strtoupper($row->cabang) }}</span>
                        </span><br>
                        <span style="font-size: 9px; line-height: 1.4; display: block; margin-top: 4px;">
                            <i>Sistem Penilaian Kinerja Karyawan (DevHRIS)</i>
                        </span>
                    </td>
                </tr>
            </table>

            <hr style="border: 1px solid #000; margin: 10px 0 20px 0;">

            {{-- BIODATA --}}
            <div class="bio-section">
                <table class="bio-table">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td>{{ $row->nama_lengkap }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIK</td>
                        <td class="colon">:</td>
                        <td>{{ $row->nik }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td>{{ $row->jabatan }}</td>
                    </tr>
                    <tr>
                        <td class="label">Departemen</td>
                        <td class="colon">:</td>
                        <td>{{ $row->departemen }}</td>
                    </tr>
                </table>
            </div>

            {{-- TABEL KPI UTAMA --}}
            <table class="content-table">
                <thead>
                    <tr>
                        <th width="10%">Point Indikator</th>
                        <th width="10%">Indikator KPI</th>
                        <th width="50%">Deskripsi</th>
                        <th width="15%">Workbook Daily</th>
                        <th width="15%">Hasil KPI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($row->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ toRoman($index + 1) }}</td>
                            <td class="text-center">{{ $item->indikator }}</td>
                            <td class="text-left">{{ $item->description }}</td>
                            <td class="text-center">{{ $item->progress }}</td>
                            <td class="text-center">{{ $item->hasil }}</td>
                        </tr>
                    @endforeach

                    {{-- TOTAL BARIS BAWAH KPI UTAMA --}}
                    <tr class="bg-grey">
                        <td colspan="5">Hasil</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-center"><b>{{ $row->summary->total_bobot }}</b></td>
                        <td class="text-center"><b>{{ $row->summary->total_progress }}%</b></td> {{-- Tambah % agar jelas --}}
                        <td class="text-center"><b>{{ $row->summary->total_hasil }}</b></td>
                    </tr>
                </tbody>
            </table>

            {{-- TABEL KPI KEGIATAN TAMBAHAN --}}
            <table class="content-table table-extra">
                <thead>
                    <tr class="bg-grey">
                        <td colspan="4" style="padding: 6px;">Kegiatan Tambahan</td>
                    </tr>
                    <tr>
                        <th width="15%">Tanggal</th>
                        <th width="45%">Kegiatan</th>
                        <th width="25%">Catatan</th>
                        <th width="15%">Point Tambahan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalExtra = 0;
                    @endphp
                    @forelse ($row->extras as $ext)
                        <tr>
                            <td class="text-center">
                                {{ \Carbon\Carbon::parse($ext->tanggal)->translatedFormat('d M Y') }}</td>
                            <td class="text-left">{{ $ext->kegiatan }}</td>
                            <td class="text-left">{{ $ext->catatan }}</td>
                            <td class="text-center">+{{ $ext->score }}</td>
                        </tr>
                        @php $totalExtra += $ext->score; @endphp
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted fst-italic">Tidak ada kegiatan tambahan di
                                bulan ini</td>
                        </tr>
                    @endforelse

                    {{-- TOTAL EXTRA --}}
                    <tr>
                        <td colspan="3" class="text-right" style="text-align: right; padding-right: 15px;"><b>Total
                                Poin Tambahan</b></td>
                        <td class="text-center"><b>+{{ $totalExtra }}</b></td>
                    </tr>

                    {{-- TOTAL KESELURUHAN (KPI Utama + Extra) --}}
                    <tr class="bg-grey" style="font-size: 12px;">
                        <td colspan="3" class="text-right" style="text-align: right; padding-right: 15px;"><b>TOTAL
                                KESELURUHAN (Nilai Akhir)</b></td>
                        <td class="text-center" style="font-size: 12px;"><b>{{ $row->summary->total_keseluruhan }}</b>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>
    @empty
        <div class="main-container" style="display: flex; justify-content: center; align-items: center;">
            <h3 style="color: #555;">Tidak ada data KPI yang disetujui HRD pada periode ini.</h3>
        </div>
    @endforelse

</body>

</html>
