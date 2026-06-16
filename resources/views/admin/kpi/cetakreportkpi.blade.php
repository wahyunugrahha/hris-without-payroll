<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Appraisal Form</title>
    <style>
        /* === SETUP KERTAS & LAYAR === */
        @page {
            size: A4 landscape;
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
            font-size: 11px;
        }

        .main-container {
            width: 297mm;
            min-height: 210mm;
            background: #ffffff;
            margin: 0 auto 20px auto;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
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

            .no-print {
                display: none !important;
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

        /* HEADER JUDUL */
        .title-header {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .title-header span {
            text-decoration: underline;
        }

        /* TABEL PROFIL KANAN */
        .profile-table {
            width: 40%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .profile-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        /* TABEL UTAMA */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
        }

        /* WARNA KOLOM */
        .bg-blue {
            background-color: #8EAADB;
            font-weight: bold;
            text-align: center;
        }

        .bg-yellow {
            background-color: #FFFF00;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        /* FOOTER SIGNATURE */
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-table td {
            border: none;
            padding: 0;
            vertical-align: bottom;
            height: 80px;
        }
    </style>
</head>

<body>
    <button onclick="window.print()" class="no-print btn-print">🖨️ Cetak Laporan</button>

    @forelse ($rekap as $row)
        <div class="main-container">

            <div class="title-header">
                <span>Performance Appraisal Form</span><br>
                <span>wndev</span>
            </div>

            <table class="profile-table">
                <tr>
                    <td width="35%">Nomor Register</td>
                    <td width="65%" style="mso-number-format:'\@';">{{ $row->nik }}</td>
                </tr>
                <tr>
                    <td>Employee Name</td>
                    <td>{{ $row->nama_lengkap }}</td>
                </tr>
                <tr>
                    <td>Position</td>
                    <td>{{ $row->jabatan }}</td>
                </tr>
                <tr>
                    <td>Period</td>
                    <td>{{ $row->periode }}</td>
                </tr>
            </table>

            <table class="main-table">
                <thead>
                    <tr>
                        <th class="bg-blue" rowspan="2" width="3%">No</th>
                        <th class="bg-blue" rowspan="2" width="20%">Strategic Objective</th>
                        <th class="bg-blue" rowspan="2" width="25%">KPI/Deliverable</th>
                        <th class="bg-blue" rowspan="2" width="7%">Weight</th>
                        <th class="bg-blue" colspan="4">Scoring</th>
                    </tr>
                    <tr>
                        <th class="bg-blue" width="8%">Target</th>
                        <th class="bg-blue" width="8%">Achievement</th>
                        <th class="bg-blue" width="8%">Score</th>
                        <th class="bg-blue" width="8%">Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($row->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->objective }}</td>
                            <td>{{ $item->deliverable }}</td>
                            <td class="text-center">{{ $item->weight }}</td>
                            <td class="text-center">{{ $item->target }}</td>
                            <td class="text-center">{{ $item->achievement }}</td>
                            <td class="bg-yellow">{{ $item->score }}</td>
                            <td class="bg-yellow">{{ $item->total_score }}</td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="3" class="text-right font-bold" style="padding-right: 15px;">Weight Total</td>
                        <td class="text-center font-bold">{{ $row->summary->weight_total }}</td>
                        <td colspan="3" class="text-right font-bold" style="padding-right: 15px;">Overall Score</td>
                        <td class="text-center font-bold">{{ $row->summary->overall_score }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="signature-table" style="width: 100%; margin-top: 40px;">
                <tr>
                    <td width="33%" style="border: none; height: 80px; vertical-align: top;">
                        <div>Assessment By :</div>
                        <div style="height: 45px;"></div>
                        <div class="font-bold" style="text-decoration: underline;">{{ $row->nama_lengkap }}</div>
                        <div style="font-size: 10px; color: #555;">Karyawan</div>
                    </td>
                    <td width="33%" style="border: none; height: 80px; vertical-align: top;">
                        <div>Acknowledge By :</div>
                        <div style="height: 45px;"></div>
                        <div class="font-bold" style="text-decoration: underline;">Atasan Langsung</div>
                        <div style="font-size: 10px; color: #555;">Supervisor / Kabag</div>
                    </td>
                    <td width="33%" style="border: none; height: 80px; vertical-align: top; text-align: right; padding-right: 50px;">
                        <div>Approve By :</div>
                        <div style="height: 45px;"></div>
                        <div class="font-bold" style="text-decoration: underline;">{{ Auth::user()->name ?? 'Administrator' }}</div>
                        <div style="font-size: 10px; color: #555;">{{ Auth::user() && Auth::user()->jabatan ? Auth::user()->jabatan->nama_jabatan : 'HRD' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div class="main-container" style="display: flex; justify-content: center; align-items: center;">
            <h3 style="color: #555;">Tidak ada data KPI yang disetujui HRD pada periode ini.</h3>
        </div>
    @endforelse

</body>

</html>
