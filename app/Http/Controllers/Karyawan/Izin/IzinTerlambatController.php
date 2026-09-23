<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Models\Izin;
use Exception;
use Illuminate\Http\Request;

/**
 * Izin terlambat selalu untuk hari ini (tanggal server), satu kali per hari.
 */
class IzinTerlambatController extends IzinController
{
    public function create()
    {
        return view('karyawan.pengajuanizin.createizinterlambat');
    }

    public function store(Request $request)
    {
        $request->validate(['keterangan' => 'nullable|string']);

        $nik = $this->karyawan()->nik;
        $hariIni = date('Y-m-d');

        if (Izin::milik($nik)->jenis('t')->whereDate('tgl_izin_dari', $hariIni)->exists()) {
            return $this->keDaftarIzin('error', 'Anda sudah mengajukan izin terlambat hari ini.');
        }

        $kodeIzin = $this->izin->nextKodeIzin($hariIni);

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $nik,
                'tgl_izin_dari' => $hariIni,
                'tgl_izin_sampai' => $hariIni,
                'status' => 't',
                'keterangan' => $request->keterangan,
            ]);

            return $this->keDaftarIzin('success', 'Pengajuan Izin Terlambat Berhasil Disimpan. Kode Izin: '.$kodeIzin);
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 't');

        return $dataizin
            ? view('karyawan.pengajuanizin.editizinterlambat', compact('dataizin'))
            : $this->tidakBisaDiubah();
    }

    public function update(Request $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 't');
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

            return redirect('/pengajuanizin/index')->with('success', 'Data Izin Terlambat Berhasil Diupdate.');
        } catch (Exception $e) {
            return redirect('/pengajuanizin/index')->with('error', $this->failMessage('Data Izin Terlambat Gagal Diupdate.', $e));
        }
    }
}
