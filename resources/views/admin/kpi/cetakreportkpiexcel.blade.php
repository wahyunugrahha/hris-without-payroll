<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
        }

        .title-header {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
        }

        .main-table {
            border-collapse: collapse;
            width: 100%;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }

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
    </style>
</head>

<body>

    @forelse ($rekap as $row)
        {{-- Jarak Antar Laporan (jika print > 1 karyawan sekaligus) --}}
        <table>
            <tr>
                <td colspan="9" class="title-header">Performance Appraisal Form</td>
            </tr>
            <tr>
                <td colspan="9" class="title-header">DevHRIS</td>
            </tr>
            <tr>
                <td colspan="9"></td>
            </tr> {{-- Spacer --}}

            {{-- Biodata Rata Kanan --}}
            <tr>
                <td colspan="4"></td>
                <td colspan="2" style="border: 1px solid #000;">Nomor Register</td>
                <td colspan="3" style="border: 1px solid #000; mso-number-format:'\@';">{{ $row->nik }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td colspan="2" style="border: 1px solid #000;">Employee Name</td>
                <td colspan="3" style="border: 1px solid #000;">{{ $row->nama_lengkap }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td colspan="2" style="border: 1px solid #000;">Position</td>
                <td colspan="3" style="border: 1px solid #000;">{{ $row->jabatan }}</td>
            </tr>
            <tr>
                <td colspan="4"></td>
                <td colspan="2" style="border: 1px solid #000;">Period</td>
                <td colspan="3" style="border: 1px solid #000;">{{ $row->periode }}</td>
            </tr>
            <tr>
                <td colspan="9"></td>
            </tr> {{-- Spacer --}}
        </table>

        {{-- Tabel Utama --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th class="bg-blue" rowspan="2" width="5">No</th>
                    <th class="bg-blue" rowspan="2" width="25">Strategic Objective</th>
                    <th class="bg-blue" rowspan="2" width="30">KPI/Deliverable</th>
                    <th class="bg-blue" rowspan="2" width="10">Weight</th>
                    <th class="bg-blue" colspan="4">Scoring</th>
                </tr>
                <tr>
                    <th class="bg-blue" width="10">Target</th>
                    <th class="bg-blue" width="10">Achievement</th>
                    <th class="bg-blue" width="10">Score</th>
                    <th class="bg-blue" width="10">Total Score</th>
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
                    <td colspan="3" class="text-right font-bold">Weight Total</td>
                    <td class="text-center font-bold">{{ $row->summary->weight_total }}</td>
                    <td colspan="3" class="text-right font-bold">Overall Score</td>
                    <td class="text-center font-bold">{{ $row->summary->overall_score }}</td>
                </tr>

                {{-- Spacer untuk ttd --}}
                <tr>
                    <td colspan="9" style="border: none; height: 30px;"></td>
                </tr>
                <tr>
                    <td colspan="3" style="border: none;">Assesment By :</td>
                    <td colspan="3" style="border: none;">Acknowledge By :</td>
                    <td colspan="3" style="border: none;" class="text-right">Approve by</td>
                </tr>
                <tr>
                    <td colspan="9" style="border: none; height: 40px;"></td>
                </tr>
            </tbody>
        </table>
        <br><br>
    @empty
        <h3>Tidak ada data KPI yang disetujui HRD pada periode ini.</h3>
    @endforelse
</body>

</html>
