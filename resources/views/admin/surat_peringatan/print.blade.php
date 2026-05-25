<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak SP - {{ $sp->karyawan->nama_lengkap ?? 'Karyawan' }}</title>
    <style>
        /* 1. RESET & BASIC SETUP */
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            background-color: #525659;
            /* Warna abu-abu seperti PDF viewer */
        }

        /* TOMBOL PRINT (HANYA TAMPIL DI LAYAR) */
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-family: Arial, sans-serif;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transition: background 0.3s;
        }

        .btn-print:hover {
            background-color: #0056b3;
        }

        /* 2. SIMULASI KERTAS A4 (TAMPILAN LAYAR) */
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 1.5cm 2cm;
            /* Margin Kertas */
            margin: 10mm auto;
            /* Posisi di tengah layar */
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            /* Efek bayangan kertas */
            position: relative;
        }

        /* 3. FORMATTING KONTEN */
        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img.logo {
            height: 70px;
            margin-bottom: 5px;
        }

        .header .company-contact {
            font-size: 9pt;
            color: #000;
        }

        .header a {
            text-decoration: none;
            color: #000;
        }

        .letter-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .letter-title h1 {
            font-size: 12pt;
            margin: 0;
            text-decoration: underline;
            text-transform: uppercase;
            font-weight: bold;
        }

        .letter-title h2 {
            font-size: 11pt;
            margin: 5px 0 0 0;
            font-weight: normal;
            text-transform: uppercase;
        }

        .content p {
            margin-bottom: 10px;
            margin-top: 0;
            text-align: justify;
            line-height: 1.3;
        }

        .bold {
            font-weight: bold;
        }

        .underline {
            text-decoration: underline;
        }

        /* TABEL DATA */
        table.custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0 20px 0;
            font-size: 11pt;
        }

        table.custom-table th,
        table.custom-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        table.custom-table th {
            text-align: center;
            background-color: #f2f2f2;
            font-weight: normal;
        }

        /* TANDA TANGAN */
        .signature-wrapper {
            margin-top: 40px;
            width: 100%;
        }

        table.signature-table {
            width: 100%;
            border: none;
        }

        table.signature-table td {
            vertical-align: top;
            padding: 0;
        }

        .stamp-space {
            height: 80px;
        }

        /* FOOTER ALAMAT */
        .footer-wrapper {
            margin-top: 50px;
            /* Footer akan otomatis turun mengikuti konten,
               tapi kita pastikan ada di bawah kertas jika konten sedikit */
            position: absolute;
            bottom: 1.5cm;
            /* Sesuai margin bawah kertas */
            left: 2cm;
            /* Sesuai margin kiri kertas */
            right: 2cm;
            /* Sesuai margin kanan kertas */
        }

        .address-box {
            border: 1px solid #000;
            display: table;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 8pt;
        }

        .address-col {
            display: table-cell;
            width: 50%;
            padding: 5px 8px;
            vertical-align: middle;
            line-height: 1.2;
        }

        .address-col:first-child {
            border-right: 1px solid #000;
        }

        /* 4. SETTING KHUSUS SAAT PRINT (CTRL+P) */
        @media print {

            /* Sembunyikan elemen dengan class no-print */
            .no-print {
                display: none !important;
            }

            body {
                background: none;
                margin: 0;
            }

            .page {
                width: 100%;
                margin: 0;
                padding: 0;
                /* Margin dihandle oleh @page */
                box-shadow: none;
                min-height: auto;
                position: static;
                /* Kembali ke flow normal saat print */
            }

            /* Atur margin printer asli */
            @page {
                size: A4;
                margin: 1.5cm 2cm;
            }

            /* Sembunyikan footer absolut saat print agar tidak menimpa jika konten panjang,
               atau gunakan fixed positioning jika ingin di setiap halaman */
            .footer-wrapper {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="btn-print no-print">🖨️ Cetak Surat</button>

    <div class="page">

        <div class="header">
            <img src="{{ asset('assets/img/logo.png') }}" class="logo" alt="DevHRIS">
            <div class="company-contact">
                Website : <a href="#" target="_blank">#</a> — email : <a
                    href="mailto:info@devhris.com">info@devhris.com</a>
            </div>
        </div>

        @php
            \Carbon\Carbon::setLocale('id');
            $levelText = match ($sp->level) {
                1 => 'KE-1 (SATU)',
                2 => 'KE-2 (DUA)',
                3 => 'KE-3 (TIGA)',
                default => 'KE-1 (SATU)',
            };
            $pasalText = match ($sp->level) {
                1 => 'PELANGGARAN TINGKAT RINGAN',
                2 => 'PELANGGARAN TINGKAT SEDANG',
                3 => 'PASAL 24 B DAN C',
                default => 'PERATURAN PERUSAHAAN',
            };
            $introText = match ($sp->level) {
                1 => 'Bahwa telah melakukan pelanggaran sesuai dengan Peraturan Perusahaan untuk Tingkat Ringan.',
                2 => 'Bahwa telah melakukan pelanggaran disiplin kerja yang berulang atau pelanggaran Tingkat Sedang.',
                3
                    => 'Bahwa telah melakukan pelanggaran sesuai dengan Peraturan Perusahaan Pasal 24 Point B dan Point C Tingkat Berat.',
                default => 'Bahwa telah melakukan pelanggaran peraturan perusahaan.',
            };
            $tglBerakhir = \Carbon\Carbon::parse($sp->expires_at)->translatedFormat('d F Y');
            $formattedIssued = \Carbon\Carbon::parse($sp->date ?? now())->translatedFormat('d F Y');

            $user = Auth::user();
            $approverName = $user ? $user->name : 'Ahmad Yozi Alhidaya';
            $approverJabatan = $user && $user->jabatan ? $user->jabatan->nama_jabatan : 'HR. Manager';
        @endphp

        <div class="letter-title">
            <h1>SURAT PERINGATAN {{ $levelText }}</h1>
            <h2>{{ $pasalText }}</h2>
        </div>

        <div class="content">
            <p>{{ $introText }} Maka karyawan yang diberikan Surat Peringatan {{ $levelText }} sebagai berikut :
            </p>
        </div>

        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nama Karyawan</th>
                    <th style="width: 20%;">Jabatan</th>
                    <th style="width: 50%;">Pelanggaran</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>
                        <span class="bold">{{ $sp->karyawan->nama_lengkap ?? 'Karyawan' }}</span><br>
                        <span style="font-size: 9pt;">NIK: {{ $sp->nik }}</span>
                    </td>
                    <td>{{ $sp->karyawan->jabatan_nama ?? '-' }}</td>
                    <td>
                        @if ($sp->note)
                            {!! nl2br(e($sp->note)) !!}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="content">
            <p>
                <span class="bold underline">Surat Peringatan {{ $levelText }}</span> ini berlaku sampai dengan
                tanggal
                <span class="bold">{{ $tglBerakhir }}</span>.
                @if ($sp->level == 3)
                    Apabila dikemudian hari ditemukan kesalahan/pelanggaran terhadap peraturan perusahaan maka akan
                    disampaikan <span class="bold">Pemutusan Hubungan Kerja (PHK)</span>.
                @else
                    Apabila dikemudian hari ditemukan kesalahan/pelanggaran kembali selama masa berlaku SP ini, maka
                    akan
                    dikenakan sanksi tingkat lanjut sesuai peraturan perusahaan.
                @endif
            </p>
            <p>Demikian disampaikan dan atas Perhatian terimakasih. Agar segera dilakukan perbaikan dan peningkatan
                kedisiplinan dikemudian hari.</p>
        </div>

        <div class="signature-wrapper">
            <table class="signature-table">
                <tr>
                    <td style="width: 50%;">
                        Ditetapkan di : Bengkulu<br>
                        Tanggal : {{ $formattedIssued }}
                    </td>
                    <td style="width: 50%;"></td>
                </tr>
                <tr>
                    <td style="padding-top: 15px;">
                        <span class="bold">DevHRIS</span>
                    </td>
                    <td style="padding-top: 15px; padding-left: 50px;">
                        Yang Menerima SP {{ $sp->level }},
                    </td>
                </tr>
                <tr>
                    <td class="stamp-space">
                    </td>
                    <td class="stamp-space"></td>
                </tr>
                <tr>
                    <td>
                        <div class="bold underline">{{ $approverName }}</div>
                        <div class="bold">{{ $approverJabatan }}</div>
                    </td>
                    <td style="padding-left: 50px;">
                        <div class="bold underline">{{ $sp->karyawan->nama_lengkap ?? '.........................' }}
                        </div>
                        <div>Karyawan</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-wrapper">
            <div class="address-box">
                <div class="address-col">
                    HO : SCBD. Treasury Tower Lt. 9 Unit M Lot. 28, Jl. Jend. sudirman Kav. 52-53, Senayan Kebayoran
                    baru
                    Jaksel
                </div>
                <div class="address-col">
                    BO : Jalan wr Supratman RT 007 RW 002 Kel. Bentiring Kec.Muara Bangkahulu Kota Bengkulu.
                </div>
            </div>
        </div>

    </div>
</body>

</html>
