<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Lembur Seluruh Karyawan</title>
    <link rel="icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/logo.png') }}">
    <style>
        html, body { font-family: 'Arial', sans-serif; font-size: 10px; color: #000; }
        #title { font-size: 14px; font-weight: bold; line-height: 1.2; }
        .tabellembur { width: 100%; margin-top: 10px; border-collapse: collapse; table-layout: fixed; }
        .tabellembur tr th { border: 1px solid #000; padding: 2px; background-color: #e9ecef; font-size: 8px; text-align: center; }
        .tabellembur tr td { border: 1px solid #000; padding: 2px 0; font-size: 7.5px; text-align: center; vertical-align: middle; height: 22px; line-height: 1.1; }
        .mt-20 { margin-top: 20px; }
    </style>
</head>
<body>
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
@endphp
<table class="tabellembur" border="1">
    <thead>
        <tr>
            <th style="width: 65px;">NIK</th>
            <th style="width: 110px;">Nama Karyawan</th>
            <th style="width: 60px;">Departemen</th>
            <th style="width: 60px;">Cabang</th>
            <th style="width: 80px;">Tanggal Lembur</th>
            <th style="width: 60px;">Jam Mulai</th>
            <th style="width: 60px;">Jam Selesai</th>
            <th style="width: 60px;">Total Jam</th>
            <th style="width: 80px;">Riwayat</th>
            <th style="width: 120px;">Pekerjaan</th>
            <th style="width: 120px;">Tempat</th>
            <th style="width: 120px;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lembur as $nik => $items)
            @php $rowspan = count($items); $first = true; $total_jam = 0; @endphp
            @foreach ($items as $item)
                @php $total_jam += $item->total_jam ?? 0; @endphp
                <tr>
                    @if ($first)
                        <td rowspan="{{ $rowspan }}">{{ $item->nik }}</td>
                        <td rowspan="{{ $rowspan }}" style="text-align: left; padding-left: 3px; white-space: nowrap; overflow: hidden;">{{ $item->karyawan->nama_lengkap ?? '-' }}</td>
                        <td rowspan="{{ $rowspan }}">{{ $item->karyawan->departemen->nama_dept ?? '-' }}</td>
                        <td rowspan="{{ $rowspan }}">{{ $item->karyawan->cabang->nama_cabang ?? '-' }}</td>
                    @endif
                    <td>{{ date('d/m/Y', strtotime($item->tanggal_lembur)) }}</td>
                    <td>{{ $item->jam_mulai ?? '-' }}</td>
                    <td>{{ $item->jam_selesai ?? '-' }}</td>
                    <td>{{ $formatJamMenit($item->total_jam ?? null) }}</td>
                    <td>{{ $item->update_count > 0 ? "Ubah: {$item->update_count}x (Awal: {$item->jam_selesai_awal})" : '-' }}</td>
                    <td style="text-align: left;">{{ $item->pekerjaan }}</td>
                    <td style="text-align: left;">{{ $item->tempat }}</td>
                    <td style="text-align: left;">{{ $item->keterangan }}</td>
                </tr>
                @php $first = false; @endphp
            @endforeach
            <tr style="background: #f3f3f3; font-weight: bold;">
                <td colspan="7" style="text-align: right;">Total Jam Lembur</td>
                <td>{{ $formatJamMenit($total_jam) }}</td>
                <td colspan="4"></td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
