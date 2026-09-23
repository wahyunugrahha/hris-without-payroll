<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Http\Requests\Karyawan\Izin\IzinMultiTanggalRequest;
use App\Models\Izin;
use App\Support\CutiDatesMeta;
use Exception;

/**
 * Roster: tanggal pilihan (bisa tidak berurutan) tanpa jatah; hari libur & libur shift dikeluarkan otomatis.
 */
class IzinRosterController extends IzinController
{
    private const FORM_KOSONG = [
        'sisa_cuti' => null,
        'kodeCutiTahunan' => null,
        'sisa_cuti_map' => [],
        'submissionType' => 'roster',
    ];

    public function create()
    {
        return view('karyawan.pengajuanizin.createizincuti', ['mastercuti' => collect()] + self::FORM_KOSONG);
    }

    public function store(IzinMultiTanggalRequest $request)
    {
        $karyawan = $this->karyawan();
        $hariKerja = $this->hariKerjaTanpaBentrok($request);

        $kodeIzin = $this->izin->nextKodeIzin($hariKerja->first());

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $karyawan->nik,
                'tgl_izin_dari' => $hariKerja->first(),
                'tgl_izin_sampai' => $hariKerja->last(),
                'status' => 'r',
                'kode_cuti' => null,
                'keterangan' => CutiDatesMeta::append($request->keterangan(), $hariKerja),
            ]);

            return $this->keDaftarIzin('success', 'Pengajuan roster berhasil disimpan.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 'r');
        if (! $dataizin) {
            return $this->tidakBisaDiubah();
        }

        return view('karyawan.pengajuanizin.editizincuti', ['mastercuti' => collect()] + self::FORM_KOSONG + [
            'dataizin' => $dataizin,
            'kode_izin' => $kode_izin,
            'initialSelectedDates' => $dataizin->tanggalDiajukan(),
            'keterangan_plain' => CutiDatesMeta::strip($dataizin->keterangan),
        ]);
    }

    public function update(IzinMultiTanggalRequest $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 'r');
        if (! $izin) {
            return $this->tidakBisaDiubah();
        }

        $hariKerja = $this->hariKerjaTanpaBentrok($request, $kode_izin);

        try {
            $izin->update([
                'tgl_izin_dari' => $hariKerja->first(),
                'tgl_izin_sampai' => $hariKerja->last(),
                'keterangan' => CutiDatesMeta::append($request->keterangan(), $hariKerja),
            ]);

            return $this->keDaftarIzin('success', 'Data Pengajuan Roster Berhasil Diupdate.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Pengajuan Roster Gagal Diupdate.', $e));
        }
    }

    private function hariKerjaTanpaBentrok(IzinMultiTanggalRequest $request, ?string $kodeIzinDiedit = null)
    {
        $karyawan = $this->karyawan();
        $hariKerja = $this->izin->tanggalHariKerja($karyawan, $request->tanggalDipilih());

        if ($hariKerja->isEmpty()) {
            $this->tolak('selected_dates', 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.');
        }

        if ($this->izin->tanggalBentrok($karyawan->nik, $hariKerja, $kodeIzinDiedit)) {
            $this->tolak('selected_dates', $kodeIzinDiedit
                ? 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.'
                : 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.');
        }

        return $hariKerja;
    }
}
