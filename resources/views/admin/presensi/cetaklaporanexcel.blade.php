<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Cetak Laporan Presensi Karyawan</title>
    <link rel="icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset_v('assets/img/logo.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_v('assets/img/logo.png') }}">
    <style>
        html,
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        #title {
            font-size: 16px;
            font-weight: bold;
        }

        .tabeldatakaryawan {
            margin-top: 10px;
            font-size: 12px;
        }

        .tabeldatakaryawan td {
            padding: 2px 4px;
        }

        .tabelpresensi {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
            font-size: 11px;
        }

        .tabelpresensi th,
        .tabelpresensi td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-30 {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    @php
        function selisih($jam_masuk, $jam_keluar, $jam_pulang_resmi = '17:00:00')
        {
            if (empty($jam_keluar) || empty($jam_masuk) || $jam_keluar === '-' || $jam_masuk === '00:00:00') {
                return '-';
            }
            try {
                $dtAwal = new \DateTimeImmutable('2000-01-01 ' . $jam_masuk);
                $dtAkhir = new \DateTimeImmutable('2000-01-01 ' . $jam_keluar);
                $dtPulangResmi = new \DateTimeImmutable('2000-01-01 ' . $jam_pulang_resmi);

                if ($dtAkhir < $dtAwal) {
                    $dtAkhir = $dtAkhir->modify('+1 day');
                    $dtPulangResmi = $dtPulangResmi->modify('+1 day');
                }
                if ($dtAkhir > $dtPulangResmi) {
                    $dtAkhir = $dtPulangResmi;
                }
                if ($dtAkhir < $dtAwal) {
                    return '-';
                }
                $dtSelisih = $dtAwal->diff($dtAkhir);
                return $dtSelisih->format('%H:%I');
            } catch (\Exception $e) {
                return '-';
            }
        }

        function formatJamMenit($hhmm)
        {
            if ($hhmm === '-' || empty($hhmm) || strpos($hhmm, ':') === false) {
                return '-';
            }
            [$hh, $mm] = explode(':', $hhmm);
            $jam = (int) $hh;
            $menit = (int) $mm;
            return $jam . 'j' . $menit . 'm';
        }
    @endphp

    @if (empty($karyawan))
        <h3 style="color: red; text-align: center; margin-top: 30px;">
            Data Karyawan Tidak Ditemukan (NIK: {{ $nik ?? '' }})
        </h3>
    @else
        <table class="tabeldatakaryawan" width="100%">
            <tr>
                <td style="width:120px;">NIK</td>
                <td style="width:10px;">:</td>
                <td>'{{ $karyawan->nik }}</td>
            </tr>
            <tr>
                <td>Nama Karyawan</td>
                <td>:</td>
                <td>{{ $karyawan->nama_lengkap }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $karyawan->jabatan_nama }}</td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>:</td>
                <td>{{ $karyawan->departemen->nama_dept ?? '-' }}</td>
            </tr>
            <tr>
                <td>Cabang</td>
                <td>:</td>
                <td>{{ $karyawan->cabang->nama_cabang ?? '-' }}</td>
            </tr>
            <tr>
                <td>No. HP</td>
                <td>:</td>
                <td>'{{ $karyawan->no_hp }}</td>
            </tr>
            @if(isset($bonusBulanan) && $bonusBulanan > 0)
            <tr>
                <td>Bonus Performa</td>
                <td>:</td>
                <td>Rp. {{ number_format($bonusBulanan, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if(isset($kenaikanGaji))
            <tr>
                <td>Kenaikan Gaji</td>
                <td>:</td>
                <td style="font-weight: bold; color: #094b87;">+{{ $kenaikanGaji->persentase }}% (Disetujui {{ date('d-m-Y', strtotime($kenaikanGaji->approved_at)) }})</td>
            </tr>
            @endif
        </table>

        <table class="tabelpresensi">
            <thead>
                <tr>
                    <th style="width: 25px;">No.</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th style="width: 80px;">Jam Masuk</th>
                    <th style="width: 80px;">Jam Pulang</th>
                    <th style="width: 120px;">Keterangan</th>
                    <th style="width: 70px;">Total Jam Kerja</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presensi as $d)
                    @php
                        $batas_waktu_masuk = $d->jam_masuk ?? '09:10:00';
                        $jam_in_waktu = $d->jam_in ?? '00:00:00';
                        $jam_out_waktu = $d->jam_out ?? '00:00:00';

                        // Menentukan Keterangan Teks
                        $status_map = [
                            'l' => 'Libur',
                            'i' => 'Izin',
                            's' => 'Sakit',
                            'c' => 'Cuti',
                            'r' => 'Roster',
                            'd' => 'Dinas Luar',
                            'h' => 'Hadir',
                        ];
                        $keterangan_teks = $status_map[$d->status] ?? 'Hadir';

                        // Menentukan Jam Pulang Resmi
                        $jam_pulang_resmi = $d->jam_pulang ?: ($karyawan->jam_pulang ?: '17:00:00');

                        // Menghitung Jumlah Jam Kerja
                        $jumlah_jam_kerja_display = '-';
                        if ($d->status == 'h' && $jam_in_waktu != '00:00:00' && $jam_out_waktu != '00:00:00') {
                            $jumlah_jam_kerja = selisih($jam_in_waktu, $jam_out_waktu, $jam_pulang_resmi);
                            $jumlah_jam_kerja_display = formatJamMenit($jumlah_jam_kerja);
                        } elseif ($d->status != 'h') {
                            $jumlah_jam_kerja_display = '0j 0m';
                        }
                    @endphp

                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($d->tgl_presensi)->translatedFormat('d F Y') }}</td>
                        <td class="{{ $jam_in_waktu > $batas_waktu_masuk ? 'text-danger' : '' }}">
                            {{ $jam_in_waktu == '00:00:00' ? '-' : $jam_in_waktu }}
                        </td>
                        <td>{{ $jam_out_waktu == '00:00:00' ? '-' : $jam_out_waktu }}</td>
                        <td>{{ $keterangan_teks }}</td>
                        <td>{{ $jumlah_jam_kerja_display }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">
                            Data presensi untuk periode ini tidak ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>

</html>
