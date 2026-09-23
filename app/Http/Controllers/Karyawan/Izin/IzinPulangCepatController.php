<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Models\Izin;
use App\Models\Presensi;
use Exception;
use Illuminate\Http\Request;

/**
 * Izin pulang cepat untuk hari ini: hanya setelah absen masuk dan sebelum absen pulang.
 */
class IzinPulangCepatController extends IzinController
{
    public function create()
    {
        return view('karyawan.pengajuanizin.createizinpulangcepat');
    }

    public function store(Request $request)
    {
        $request->validate(['keterangan' => 'nullable|string']);

        $nik = $this->karyawan()->nik;
        $hariIni = date('Y-m-d');

        $presensi = Presensi::where('nik', $nik)->whereDate('tgl_presensi', $hariIni)->first();

        if (! $presensi || empty($presensi->jam_in) || $presensi->jam_in == '00:00:00') {
            return $this->keDaftarIzin('error', 'Pengajuan pulang cepat hanya bisa dilakukan setelah absen masuk.');
        }

        if (! empty($presensi->jam_out) && $presensi->jam_out != '00:00:00') {
            return $this->keDaftarIzin('error', 'Anda sudah absen pulang hari ini.');
        }

        $sudahAda = Izin::milik($nik)
            ->jenis('p')
            ->whereIn('status_approved', [0, 1])
            ->whereDate('tgl_izin_dari', $hariIni)
            ->exists();

        if ($sudahAda) {
            return $this->keDaftarIzin('error', 'Pengajuan pulang cepat untuk hari ini sudah ada.');
        }

        // Izin terlambat (t) boleh berdampingan dengan pulang cepat (p) pada tanggal yang sama.
        $adaIzinLain = Izin::milik($nik)
            ->whereNotIn('status', ['p', 't'])
            ->whereIn('status_approved', [0, 1])
            ->whereDate('tgl_izin_dari', '<=', $hariIni)
            ->whereDate('tgl_izin_sampai', '>=', $hariIni)
            ->exists();

        if ($adaIzinLain) {
            return $this->keDaftarIzin('error', 'Tidak dapat mengajukan pulang cepat karena ada pengajuan izin lain pada tanggal yang sama.');
        }

        $kodeIzin = $this->izin->nextKodeIzin($hariIni);

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $nik,
                'tgl_izin_dari' => $hariIni,
                'tgl_izin_sampai' => $hariIni,
                'status' => 'p',
                'keterangan' => $request->keterangan,
            ]);

            return $this->keDaftarIzin('success', 'Pengajuan Pulang Cepat Berhasil Disimpan. Kode Izin: '.$kodeIzin);
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 'p');

        return $dataizin
            ? view('karyawan.pengajuanizin.editizinpulangcepat', compact('dataizin'))
            : $this->tidakBisaDiubah();
    }

    public function update(Request $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 'p');
        if (! $izin) {
            return $this->tidakBisaDiubah();
        }

        $request->validate(['keterangan' => 'nullable|string']);

        try {
            $izin->update([
                'tgl_izin_dari' => date('Y-m-d'),
                'tgl_izin_sampai' => date('Y-m-d'),
                'keterangan' => $request->keterangan,
            ]);

            return redirect('/pengajuanizin/index')->with('success', 'Data Izin Pulang Cepat Berhasil Diupdate.');
        } catch (Exception $e) {
            return redirect('/pengajuanizin/index')->with('error', $this->failMessage('Data Izin Pulang Cepat Gagal Diupdate.', $e));
        }
    }
}
