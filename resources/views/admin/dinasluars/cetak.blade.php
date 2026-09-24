<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak FPD Internal - {{ $dinasLuar->karyawan->nama_lengkap ?? 'Karyawan' }}</title>
    <link rel="icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/logo.png') }}">
    <style>
        /* === SETUP KERTAS === */
        @page {
            size: A4 portrait;
            margin: 10mm 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 20px 0;
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

        .main-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            border: 2px solid #000;
        }

        /* === HEADER === */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding: 10px 15px;
            background-color: #e0e0e0;
            min-height: 75px;
        }

        .logo-horizontal {
            max-height: 55px;
            width: auto;
            display: block;
        }

        .doc-info {
            text-align: right;
            font-size: 10px;
        }

        .doc-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .doc-subtitle {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 2px;
        }

        /* === BIODATA === */
        .bio-section {
            padding: 5px 10px;
            border-bottom: 1px solid #000;
        }

        .bio-row {
            display: flex;
            margin-bottom: 2px;
            align-items: flex-end;
        }

        .bio-label {
            width: 80px;
            font-weight: bold;
            font-size: 11px;
        }

        .bio-separator {
            width: 10px;
        }

        .bio-value {
            flex: 1;
            border-bottom: 1px solid #000;
            padding-left: 5px;
            font-size: 11px;
        }

        /* === TABEL === */
        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        /* HEADER TABEL FIXED (RATA TENGAH) */
        .content-table th {
            text-align: center;
            vertical-align: middle;
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            padding: 8px 5px;
        }

        .row-content {
            height: 300px;
        }

        /* === TRANSPORTASI CHECKBOX STYLE === */
        .transport-cell {
            padding: 10px 5px;
            vertical-align: top;
        }

        .check-row {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 11px;
        }

        /* Kotak Checkbox */
        .checkbox-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 8px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
        }

        /* === FOOTER === */
        .footer-container {
            display: flex;
            border-top: 1px solid #000;
        }

        .footer-left {
            flex: 2.5;
            padding: 10px;
            border-right: 1px solid #000;
        }

        .footer-right {
            flex: 1.5;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .catatan-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .catatan-list {
            margin: 0;
            padding-left: 20px;
        }

        .catatan-list li {
            margin-bottom: 3px;
        }

        .ttd-place-date {
            margin-bottom: 10px;
            /* DIKURANGI AGAR LEBIH RAPAT DENGAN QR */
            font-size: 11px;
        }

        .ttd-signature {
            text-align: center;
            margin-top: 0px;
            /* DIHILANGKAN MARGIN TOP */
            font-weight: bold;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            /* GAP DITIPISKAN */
        }

        /* Style Khusus QR Container */
        .qr-box {
            margin-top: -20px;
            /* MENARIK QR CODE KE ATAS */
            margin-bottom: 5px;
        }

        .qr-box img {
            width: 110px;
            height: 110px;
            object-fit: contain;
        }

        .bottom-note {
            font-size: 9px;
            padding: 5px 10px;
            border-top: 1px solid #000;
            font-style: italic;
        }
    </style>
</head>

<body>

    <button onclick="window.print()" class="no-print btn-print">🖨️ Cetak Formulir</button>

    @php
        // Array Nama Bulan Indonesia
        $bulanIndo = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        $tgl_cetak = date('d');
        $bln_cetak = $bulanIndo[(int) date('m')];
        $thn_cetak = date('Y');

        // Logic Romawi Header
        $romawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bln_romawi = $romawi[date('n', strtotime($dinasLuar->created_at)) - 1];

        $approverName = optional($dinasLuar->approver)->name ?? '-';
        $approverJabatan = optional(optional($dinasLuar->approver)->jabatan)->nama_jabatan ?? '-';
        $approvedAt = $dinasLuar->approved_at ? \Carbon\Carbon::parse($dinasLuar->approved_at) : now();
        $approvedDate = $approvedAt->format('d M Y');
        $approvedTime = $approvedAt->format('H:i');
        $ttdText = "telah di tanda tangani oleh :{$approverName} jabatan:{$approverJabatan} jam:{$approvedTime} tanggal:{$approvedDate}";
        $qrImage = \App\Support\TandaTangan::qrImage($ttdText);
    @endphp

    <div class="main-container">
        <div class="header">
            <div class="logo-section-container">
                <x-brand-logo variant="print-square" alt="wndev Logo" class="logo-horizontal" />
            </div>

            <div class="doc-info">
                <div class="doc-title">Form Perjalanan Dinas Internal</div>
                <div class="doc-subtitle">FPD Internal</div>
                <div>Registered No.
                    {{ str_pad($dinasLuar->id, 3, '0', STR_PAD_LEFT) }}/FM/SPPD/SJP/{{ $bln_romawi }}/{{ date('Y', strtotime($dinasLuar->created_at)) }}
                                    {{ str_pad($dinasLuar->id, 3, '0', STR_PAD_LEFT) }}/FM/SPPD/wndev/{{ $bln_romawi }}/{{ date('Y', strtotime($dinasLuar->created_at)) }}
                </div>
            </div>
        </div>

        <div class="bio-section">
            <div class="bio-row">
                <div class="bio-label">Nama</div>
                <div class="bio-separator">:</div>
                <div class="bio-value">{{ $dinasLuar->karyawan->nama_lengkap ?? '' }}</div>
            </div>
            <div class="bio-row">
                <div class="bio-label">Jabatan</div>
                <div class="bio-separator">:</div>
                <div class="bio-value">{{ $dinasLuar->karyawan->jabatan_nama ?? '' }}</div>
            </div>
            <div class="bio-row">
                <div class="bio-label">Entitas</div>
                <div class="bio-separator">:</div>
                <div class="bio-value">{{ $dinasLuar->karyawan->cabang->nama_cabang ?? 'wndev' }}
                </div>
            </div>
        </div>

        <table class="content-table">
            <thead>
                <tr>
                    <th colspan="2" width="20%">Tanggal</th>
                    <th rowspan="2" width="35%">Keperluan</th>
                    <th rowspan="2" width="15%">Dasar<br>Perjalanan</th>
                    <th rowspan="2" width="15%">Transportasi</th>
                    <th rowspan="2" width="15%">Dana yang diajukan</th>
                </tr>
                <tr>
                    <th>Berangkat</th>
                    <th>Pulang</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-content">
                    <td align="center">
                        <strong>{{ date('d/m', strtotime($dinasLuar->tgl_mulai)) }}</strong><br>
                        <span style="font-size: 10px;">{{ date('Y', strtotime($dinasLuar->tgl_mulai)) }}</span>
                    </td>
                    <td align="center">
                        <strong>{{ date('d/m', strtotime($dinasLuar->tgl_selesai)) }}</strong><br>
                        <span style="font-size: 10px;">{{ date('Y', strtotime($dinasLuar->tgl_selesai)) }}</span>
                    </td>

                    <td>
                        <div style="font-weight: bold; margin-bottom: 5px;">{{ $dinasLuar->alasan }}</div>
                        @if ($dinasLuar->lokasi_tujuan)
                            <div>Lokasi: {{ $dinasLuar->lokasi_tujuan }}</div>
                        @endif
                        @if ($dinasLuar->keterangan)
                            <div style="margin-top: 10px; font-style: italic;">"{{ $dinasLuar->keterangan }}"</div>
                        @endif
                    </td>

                    <td align="center">
                        {{ $dinasLuar->dasar_perjalanan ?? '' }}
                    </td>

                    <td class="transport-cell">
                        @php $t = strtolower(trim($dinasLuar->transportasi ?? '')); @endphp

                        <div class="check-row">
                            <div class="checkbox-box">
                                {!! $t == 'darat' ? '✓' : '&nbsp;' !!}
                            </div>
                            Darat
                        </div>
                        <div class="check-row">
                            <div class="checkbox-box">
                                {!! $t == 'laut' ? '✓' : '&nbsp;' !!}
                            </div>
                            Laut
                        </div>
                        <div class="check-row">
                            <div class="checkbox-box">
                                {!! $t == 'udara' ? '✓' : '&nbsp;' !!}
                            </div>
                            Udara
                        </div>
                    </td>

                    <td style="vertical-align: top; font-weight: bold; padding: 10px;">
                        Rp. {{ number_format($dinasLuar->dana_diajukan, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="footer-container">
            <div class="footer-left">
                <div class="catatan-title">Catatan :</div>
                <ol class="catatan-list">
                    <li>Biaya Perjalanan dinas berdasarkan SOP & PP wndev</li>
                    <li>Dana diluar Form ini wajib menyertakan nota asli.</li>
                </ol>
            </div>

            <div class="footer-right">
                <div class="ttd-place-date">
                    {{ 'Bengkulu' }}, {{ $tgl_cetak }}
                    {{ $bln_cetak }} {{ $thn_cetak }}<br>
                    wndev
                </div>

                <div class="ttd-signature">
                    <div class="qr-box">
                        <img src="{{ $qrImage }}" alt="QR TTD">
                    </div>
                    <div style="font-weight: normal; font-size: 9px; text-align: center;">
                        Telah ditandatangani oleh: <b>{{ $approverName }}</b><br>
                        Jabatan: {{ $approverJabatan }}<br>
                        Jam: {{ $approvedTime }} Tanggal: {{ $approvedDate }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-note">
            Asli: Form ini digunakan untuk keperluan Perjalanan dinas Internal, HO/ BO/ Site/ Dalam Kota.
        </div>

    </div>

</body>

</html>
