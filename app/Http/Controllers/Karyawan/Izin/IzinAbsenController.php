<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Http\Requests\Karyawan\Izin\IzinRentangRequest;
use App\Models\Izin;
use Exception;

class IzinAbsenController extends IzinController
{
    public function create()
    {
        return view('karyawan.pengajuanizin.createizinabsen');
    }

    public function store(IzinRentangRequest $request)
    {
        $kodeIzin = $this->izin->nextKodeIzin($request->dari);

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $this->karyawan()->nik,
                'tgl_izin_dari' => $request->dari,
                'tgl_izin_sampai' => $request->sampai,
                'status' => 'i',
                'keterangan' => $request->keterangan,
            ]);

            return $this->keDaftarIzin('success', 'Data Izin Berhasil Disimpan. Kode Izin: '.$kodeIzin);
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 'i');

        return $dataizin
            ? view('karyawan.pengajuanizin.editizinabsen', compact('dataizin'))
            : $this->tidakBisaDiubah();
    }

    public function update(IzinRentangRequest $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 'i');
        if (! $izin) {
            return $this->tidakBisaDiubah();
        }

        try {
            $izin->update([
                'tgl_izin_dari' => $request->dari,
                'tgl_izin_sampai' => $request->sampai,
                'keterangan' => $request->keterangan,
            ]);

            return $this->keDaftarIzin('success', 'Data Izin Absen Berhasil Diupdate.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Izin Absen Gagal Diupdate.', $e));
        }
    }
}
