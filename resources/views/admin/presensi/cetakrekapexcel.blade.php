<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Rekap Seluruh Presensi Karyawan</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <style>
        html,
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
        }

        #title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.2;
        }

        .tabelpresensi {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabelpresensi tr th {
            border: 1px solid #000;
            padding: 2px;
            background-color: #e9ecef;
            font-size: 8px;
            text-align: center;
        }

        .tabelpresensi tr td {
            border: 1px solid #000;
            padding: 2px 0;
            font-size: 7.5px;
            text-align: center;
            vertical-align: middle;
            height: 22px;
            line-height: 1.1;
        }

        .bg-holiday {
            background-color: #fff4e6;
        }

        .keterangan-container {
            margin-top: 12px;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 8px;
        }

        .keterangan-container span {
            margin-right: 12px;
            display: inline-block;
        }

        .mt-20 {
            margin-top: 20px;
        }
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

        $begin = new DateTime($start_date);
        $end = new DateTime($end_date);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($begin, $interval, $end);

        $list_tgl = [];
        foreach ($period as $dt) {
            $list_tgl[] = $dt->format('Y-m-d');
        }

        $jml_kolom = count($list_tgl);
        $hariLiburNasional = $hariLiburNasional ?? [];
        $namaHariIdx = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

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

    <table class="tabelpresensi" border="1">
        <thead>
            <tr>
                <th rowspan="2" style="width: 65px;">NIK</th>
                <th rowspan="2" style="width: 110px;">Nama Karyawan</th>
                <th colspan="{{ $jml_kolom }}">
                    {{ date('d', strtotime($start_date)) }} {{ strtoupper(substr($namabulan[$bulan_awal], 0, 3)) }} {{ $tahun_awal }}
                    -
                    {{ date('d', strtotime($end_date)) }} {{ strtoupper(substr($namabulan[$bulan_int], 0, 3)) }} {{ $tahun_int }}
                </th>
                <th colspan="6" style="width: 110px;">Rekapitulasi</th>
            </tr>
            <tr>
                @foreach ($list_tgl as $tgl)
                    @php
                        $d = date('d', strtotime($tgl));
                        $isOff = date('w', strtotime($tgl)) == 0 || in_array($tgl, $hariLiburNasional);
                    @endphp
                    <th style="{{ $isOff ? 'color:red;' : '' }}">{{ $d }}</th>
                @endforeach
                <th style="width: 18px;">H</th>
                <th style="width: 18px;">I</th>
                <th style="width: 18px;">S</th>
                <th style="width: 18px;">C</th>
                <th style="width: 18px;">A</th>
                <th style="width: 24px;">JK</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rekap as $d)
                @php
                    $total_h = 0;
                    $total_i = 0;
                    $total_s = 0;
                    $total_c = 0;
                    $total_a = 0;
                    $total_jk = 0;
                @endphp
                <tr>
                    <td style="mso-number-format:'\@';">{{ $d->nik }}</td>
                    <td style="text-align: left; vertical-align: middle;">
                        {{ $d->nama_lengkap }}
                        @if(!empty($d->kenaikan_gaji))
                            <br><small style="color: #094b87; font-weight: bold; font-size: 8px;">(KG: +{{ $d->kenaikan_gaji }}%)</small>
                        @endif
                    </td>

                    @foreach ($list_tgl as $tgl)
                        @php
                            $tglField = 'tgl_' . date('Ymd', strtotime($tgl));
                            $rawData = isset($d->$tglField) ? $d->$tglField : null;
                            $isSunday = date('w', strtotime($tgl)) == 0;
                            $holidayDates = $hariLiburByNik[$d->nik] ?? ($hariLiburNasional ?? []);
                            $isHoliday = in_array($tgl, $holidayDates);
                            $display = '';
                            $color = '';

                            if (!empty($rawData)) {
                                $parts = explode('|', $rawData);
                                $status = $parts[0] ?? 'h';
                                $jamIn = $parts[1] ?? '00:00:00';
                                $jamOut = $parts[2] ?? '00:00:00';
                                $jamMasukJadwal = $parts[3] ?? ($d->jam_masuk ?? null);

                                $forcedLibur = ($status == 'l') || (($isSunday || $isHoliday) && $status == 'a');

                                if ($status == '#') {
                                    $display = '-';
                                    $color = '';
                                } elseif ($forcedLibur) {
                                    $display = 'L';
                                    $color = 'green';
                                } elseif ($status == 'h') {
                                    $total_h++;
                                    if (is_terlambat($jamMasukJadwal, $jamIn)) {
                                        $color = 'red';
                                    }

                                    if ($jamIn != '00:00:00' && $jamOut != '00:00:00') {
                                        // Default jam pulang resmi: dari data presensi, jika tidak ada ambil dari data karyawan, jika tidak ada juga fallback ke 17:00:00
                                        if (!empty($d->jam_pulang)) {
                                            $jam_pulang_resmi = $d->jam_pulang;
                                        } elseif (!empty($allKaryawan[$d->nik]->jam_pulang)) {
                                            $jam_pulang_resmi = $allKaryawan[$d->nik]->jam_pulang;
                                        } else {
                                            $jam_pulang_resmi = '17:00:00';
                                        }
                                        $jamInActual = strtotime($jamIn);
                                        $jamOutActual = strtotime($jamOut);
                                        $jamPulangResmiActual = strtotime($jam_pulang_resmi);
                                        if ($jamOutActual > $jamPulangResmiActual) {
                                            $jamOutActual = $jamPulangResmiActual;
                                        }
                                        $jamKerja = round(($jamOutActual - $jamInActual) / 3600, 2);
                                        if ($jamKerja > 0) {
                                            $total_jk += $jamKerja;
                                        }
                                    }
                                    $in = $jamIn != '00:00:00' ? date('H:i', strtotime($jamIn)) : '-';
                                    $out = $jamOut != '00:00:00' ? date('H:i', strtotime($jamOut)) : '-';
                                    $display = $in . '<br>' . $out;
                                } elseif ($status == 'i') {
                                    $total_i++;
                                    $display = 'I';
                                    $color = 'blue';
                                } elseif ($status == 's') {
                                    $total_s++;
                                    $display = 'S';
                                    $color = 'blue';
                                } elseif ($status == 'c') {
                                    $total_c++;
                                    $display = 'C';
                                    $color = 'blue';
                                } elseif ($status == 'r') {
                                    $display = 'R';
                                    $color = 'blue';
                                } elseif ($status == 'd') {
                                    $display = 'DL';
                                    $color = 'blue';
                                } elseif ($status == 'a') {
                                    $total_a++;
                                    $display = 'A';
                                    $color = 'red';
                                }
                            } else {
                                if ($isSunday || $isHoliday) {
                                    $display = 'L';
                                    $color = 'green';
                                } elseif ($tgl < date('Y-m-d')) {
                                    $total_a++;
                                    $display = 'A';
                                    $color = 'red';
                                }
                            }
                        @endphp
                        <td class="{{ $isSunday || $isHoliday ? 'bg-holiday' : '' }}" style="color: {{ $color }}; font-weight: bold;">
                            {!! $display !!}
                        </td>
                    @endforeach

                    <td style="font-weight: bold;">{{ $total_h }}</td>
                    <td>{{ $total_i }}</td>
                    <td>{{ $total_s }}</td>
                    <td>{{ $total_c }}</td>
                    <td style="{{ $total_a > 0 ? 'color: #FF0000;' : '' }}">{{ $total_a }}</td>
                    <td style="font-weight: bold;">{{ $formatJamMenit($total_jk) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="keterangan-container">
        <span><b>Keterangan:</b></span>
        <span><b>H</b>: Hadir</span>
        <span style="color:blue;"><b>I</b>: Izin</span>
        <span style="color:blue;"><b>S</b>: Sakit</span>
        <span style="color:blue;"><b>C</b>: Cuti</span>
        <span style="color:blue;"><b>R</b>: Roster</span>
        <span style="color:blue;"><b>DL</b>: Dinas Luar</span>
        <span style="color:red;"><b>A</b>: Alpha</span>
        <span style="color:green;"><b>L</b>: Libur/Minggu</span>
        <span style="color:blue;"><b>JK</b>: Total Jam Kerja (Jam Menit)</span>
    </div>
</body>

</html>
