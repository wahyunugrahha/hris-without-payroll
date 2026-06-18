<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618" sizes="32x32">
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/logo.png') }}?v=20260618">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
        }

        .bio-table {
            font-weight: bold;
            margin-bottom: 20px;
        }

        .main-table {
            border-collapse: collapse;
            margin-bottom: 20px;
            width: 100%;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle;
        }

        /* Warna Header Biru Muda */
        .header-blue {
            background-color: #B4C6E7;
            text-align: center;
            font-weight: bold;
        }

        /* Warna Baris Total / Kategori */
        .bg-grey {
            background-color: #D9D9D9;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>

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
        {{-- BIODATA --}}
        <table class="bio-table">
            <tr>
                <td width="150">Nama Lengkap</td>
                <td width="10">:</td>
                <td width="300">{{ $row->nama_lengkap }}</td>
            </tr>
            <tr>
                <td>NIK</td>
                <td>:</td>
                {{-- mso-number-format:'\@' memaksa Excel membaca angka sebagai teks (String) --}}
                <td style="mso-number-format:'\@';">{{ $row->nik }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $row->jabatan }}</td>
            </tr>
            <tr>
                <td>Departemen</td>
                <td>:</td>
                <td>{{ $row->departemen }}</td>
            </tr>
            <tr>
                <td>Cabang</td>
                <td>:</td>
                <td>{{ $row->cabang }}</td>
            </tr>
            <tr>
                <td>Periode</td>
                <td>:</td>
                <td>{{ $row->periode }}</td>
            </tr>
        </table>

        {{-- TABEL KPI UTAMA --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th width="15" class="header-blue">Point Indikator</th>
                    <th width="20" class="header-blue">Indikator KPI</th>
                    <th width="80" class="header-blue">Deskripsi</th>
                    <th width="20" class="header-blue">Workbook Daily</th>
                    <th width="20" class="header-blue">Hasil KPI</th>
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

                {{-- TOTAL KPI UTAMA --}}
                <tr class="bg-grey">
                    <td colspan="5">Hasil</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-center"><b>{{ $row->summary->total_bobot }}</b></td>
                    <td class="text-center"><b>{{ $row->summary->total_progress }}%</b></td>
                    <td class="text-center"><b>{{ $row->summary->total_hasil }}</b></td>
                </tr>

                {{-- SPASI ANTAR TABEL (Membiarkan border kosong agar di excel terlihat sebagai jarak) --}}
                <tr>
                    <td colspan="5" style="border: none; height: 15px;"></td>
                </tr>

                {{-- KEGIATAN TAMBAHAN --}}
                <tr class="bg-grey">
                    <td colspan="5">Kegiatan Tambahan</td>
                </tr>
                <tr>
                    <th class="header-blue text-center">Tanggal</th>
                    <th class="header-blue text-center" colspan="2">Kegiatan</th>
                    <th class="header-blue text-center">Catatan</th>
                    <th class="header-blue text-center">Point Tambahan</th>
                </tr>

                @php
                    $totalExtra = 0;
                @endphp
                @forelse ($row->extras as $ext)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($ext->tanggal)->translatedFormat('d M Y') }}
                        </td>
                        <td class="text-left" colspan="2">{{ $ext->kegiatan }}</td>
                        <td class="text-left">{{ $ext->catatan }}</td>
                        <td class="text-center">+{{ $ext->score }}</td>
                    </tr>
                    @php $totalExtra += $ext->score; @endphp
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">Tidak ada kegiatan tambahan</td>
                    </tr>
                @endforelse

                {{-- TOTAL EXTRA --}}
                <tr>
                    <td colspan="4" class="text-right"><b>Total Poin Tambahan</b></td>
                    <td class="text-center"><b>+{{ $totalExtra }}</b></td>
                </tr>

                {{-- TOTAL KESELURUHAN --}}
                <tr class="bg-grey">
                    <td colspan="4" class="text-right">TOTAL KESELURUHAN (Nilai Akhir)</td>
                    <td class="text-center"><b>{{ $row->summary->total_keseluruhan }}</b></td>
                </tr>
            </tbody>
        </table>

        {{-- Jarak antar karyawan jika mencetak banyak data di Excel --}}
        <br><br><br>
    @empty
        <h3>Tidak ada data KPI yang disetujui HRD pada periode ini.</h3>
    @endforelse
</body>

</html>
