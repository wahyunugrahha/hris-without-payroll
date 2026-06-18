<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi - {{ $karyawan->nama_lengkap ?? 'Karyawan' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
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
            margin: 0 auto;
            padding: 20px 30px;
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
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* === KOP SURAT === */
        #title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
            /* Memaksa semua teks di dalam title menjadi kapital */
        }

        /* === BIODATA === */
        .bio-section {
            display: flex;
            margin-bottom: 20px;
            gap: 20px;
            font-size: 11px;
            align-items: center;
        }

        .foto-box {
            width: 70px;
            height: 90px;
            border: 1px solid #000;
            object-fit: cover;
            padding: 2px;
        }

        .bio-table {
            border-collapse: collapse;
            font-size: 11px;
        }

        .bio-table td {
            padding: 3px 8px 3px 0;
            vertical-align: middle;
        }

        .bio-table .label {
            width: 110px;
            font-weight: bold;
        }

        .bio-table .colon {
            width: 10px;
            font-weight: bold;
        }

        /* === TABEL PRESENSI === */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 20px;
        }

        .content-table th {
            background-color: #f2f2f2;
            border: 1px solid #000;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .content-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .img-presensi {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ccc;
            display: block;
            margin: 2px auto;
        }

        /* === FOOTER & TANDA TANGAN === */
        .footer-container {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .footer-left {
            width: 60%;
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

        .footer-right {
            width: 35%;
            text-align: center;
            line-height: 1.4;
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

<body>

    <button onclick="window.print()" class="no-print btn-print">🖨️ Cetak Laporan</button>

    <div class="main-container">

        <table>
            <tr>
                <td style="width: 80px; vertical-align: top;">
                    <x-brand-logo variant="print-square" alt="Logo"
                        onerror="this.style.display='none'" />
                </td>
                <td style="vertical-align: top; text-align:left;">
                    <span id="title">
                        LAPORAN PRESENSI KARYAWAN<br>
                        PERIODE {{ strtoupper(\Carbon\Carbon::parse($tgl_awal)->translatedFormat('d F Y')) }} -
                        {{ strtoupper(\Carbon\Carbon::parse($tgl_akhir)->translatedFormat('d F Y')) }}<br>
                        <span
                            style="font-size: 14px; font-weight: bold;">{{ $cabang->nama_cabang ?? 'wndev' }}</span>
                    </span><br>
                    <span style="font-size: 9px; line-height: 1.4; display: block; margin-top: 4px;">
                        <i>Jl. W.R. Supratman RT 07 Rw 02 Kel. Bentiring Kec. Muara Bangkahulu Kota Bengkulu</i>
                    </span>
                </td>
            </tr>
        </table>

        <hr style="border: 1px solid #000; margin: 10px 0 20px 0;">

        <div class="bio-section">
            <div>
                <img src="{{ $karyawan->foto_url }}" class="foto-box" alt="Foto Karyawan">
            </div>

            <div style="flex: 1;">
                <table class="bio-table">
                    <tr>
                        <td class="label">Nama Lengkap</td>
                        <td class="colon">:</td>
                        <td>{{ $karyawan->nama_lengkap }}</td>
                    </tr>
                    <tr>
                        <td class="label">NIK</td>
                        <td class="colon">:</td>
                        <td>{{ $karyawan->nik }}</td>
                    </tr>
                    <tr>
                        <td class="label">Jabatan</td>
                        <td class="colon">:</td>
                        <td>{{ $karyawan->jabatan_nama }}</td>
                    </tr>
                    <tr>
                        <td class="label">Departemen</td>
                        <td class="colon">:</td>
                        <td>{{ $karyawan->departemen->nama_dept ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Cabang</td>
                        <td class="colon">:</td>
                        <td>{{ $cabang->nama_cabang ?? 'wndev' }}</td>
                    </tr>
                    @if(isset($bonusBulanan) && $bonusBulanan > 0)
                    <tr>
                        <td class="label">Bonus Performa</td>
                        <td class="colon">:</td>
                        <td style="font-weight: bold; color: green;">Rp. {{ number_format($bonusBulanan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(isset($kenaikanGaji))
                    <tr>
                        <td class="label">Kenaikan Gaji</td>
                        <td class="colon">:</td>
                        <td style="font-weight: bold; color: #094b87;">+{{ $kenaikanGaji->persentase }}% (Disetujui {{ date('d-m-Y', strtotime($kenaikanGaji->approved_at)) }})</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <table class="content-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="15%">Tanggal</th>
                    <th width="10%">Masuk</th>
                    <th width="10%">Foto Masuk</th>
                    <th width="10%">Pulang</th>
                    <th width="10%">Foto Pulang</th>
                    <th width="15%">Status</th>
                    <th width="15%">Total Jam</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presensiFinal as $d)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ date('d M Y', strtotime($d->tgl_presensi)) }}</td>
                        <td>{{ $d->jam_in == '00:00:00' || $d->jam_in == '-' ? '-' : date('H:i', strtotime($d->jam_in)) }}
                        </td>
                        <td>
                            @if ($d->foto_in_url)
                                <img src="{{ $d->foto_in_url }}" class="img-presensi" alt="In">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $d->jam_out == '00:00:00' || $d->jam_out == '-' ? '-' : date('H:i', strtotime($d->jam_out)) }}
                        </td>
                        <td>
                            @if ($d->foto_out_url)
                                <img src="{{ $d->foto_out_url }}" class="img-presensi" alt="Out">
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $d->keterangan }}</td>
                        <td style="font-weight:bold;">{{ $d->total_jam_kerja }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">Data Presensi tidak ditemukan untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-container">
            <div class="footer-left">
                <strong>Catatan :</strong>
                <ol>
                    <li>Laporan ini dihasilkan secara otomatis oleh sistem <i>wndev.com</i>.</li>
                    <li>Total jam kerja dihitung berdasarkan selisih jam masuk dan jam pulang.</li>
                    <li>Jika terdapat ketidaksesuaian data presensi, harap segera menghubungi bagian HRD.</li>
                </ol>
            </div>

            <div class="footer-right">
                <div>Bengkulu, {{ $approvedAt }}</div>
                <div style="margin-top:5px; font-weight:bold;">Mengetahui,</div>
                <div style="margin-top:2px;">wndev</div>

                <div class="qr-box">
                    <img src="{{ $qrImage }}" alt="QR Code Validasi">
                </div>

                <div class="sign-name">{{ $approverName }}</div>
                <div>{{ $approverJabatan }}</div>
            </div>
        </div>

    </div>

</body>

</html>
