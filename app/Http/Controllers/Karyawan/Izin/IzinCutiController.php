<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Http\Requests\Karyawan\Izin\IzinCutiRequest;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\MasterCuti;
use App\Models\SuratPeringatan;
use App\Support\CutiDatesMeta;
use Exception;

/**
 * Cuti: tanggal pilihan (bisa tidak berurutan), dibatasi sisa jatah per jenis cuti dan SP aktif.
 */
class IzinCutiController extends IzinController
{
    public function create()
    {
        return view('karyawan.pengajuanizin.createizincuti', $this->dataForm() + ['submissionType' => 'cuti']);
    }

    public function store(IzinCutiRequest $request)
    {
        $karyawan = $this->karyawan();
        $this->tolakJikaAdaSpAktif($karyawan->nik, 'mengajukan');

        $tanggal = $request->tanggalDipilih();
        // Hari libur & libur shift otomatis dikeluarkan dari pengajuan.
        $hariKerja = $this->izin->tanggalHariKerja($karyawan, $tanggal);

        if ($hariKerja->isEmpty()) {
            $this->tolak('selected_dates', 'Seluruh tanggal yang dipilih bertepatan dengan libur / shift libur.');
        }

        if ($this->izin->tanggalBentrok($karyawan->nik, $hariKerja)) {
            $this->tolak('selected_dates', 'Sebagian tanggal yang diajukan sudah memiliki presensi / izin lain.');
        }

        $this->tolakJikaMelebihiJatah($request->kode_cuti, $hariKerja->count(), 'hari efektif yang diajukan');

        $kodeIzin = $this->izin->nextKodeIzin($tanggal->first());

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $karyawan->nik,
                'tgl_izin_dari' => $hariKerja->first(),
                'tgl_izin_sampai' => $hariKerja->last(),
                'status' => 'c',
                'kode_cuti' => $request->kode_cuti,
                'keterangan' => CutiDatesMeta::append($request->keterangan(), $hariKerja),
            ]);

            return $this->keDaftarIzin('success', 'Pengajuan cuti berhasil disimpan.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 'c')?->load('masterCuti');
        if (! $dataizin) {
            return $this->tidakBisaDiubah();
        }

        return view('karyawan.pengajuanizin.editizincuti', $this->dataForm($kode_izin) + [
            'dataizin' => $dataizin,
            'kode_izin' => $kode_izin,
            'initialSelectedDates' => $dataizin->tanggalDiajukan(),
            'keterangan_plain' => CutiDatesMeta::strip($dataizin->keterangan),
            'submissionType' => 'cuti',
        ]);
    }

    public function update(IzinCutiRequest $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 'c');
        if (! $izin) {
            return $this->tidakBisaDiubah();
        }

        $karyawan = $this->karyawan();
        $this->tolakJikaAdaSpAktif($karyawan->nik, 'mengupdate');

        $tanggal = $request->tanggalDipilih();

        // ponytail: berbeda dengan store (libur dikeluarkan otomatis), update menolak tanggal libur.
        // Perilaku lama dipertahankan; samakan jika bisnis menginginkan.
        $libur = $tanggal->intersect(HariLibur::tanggalBerlaku($karyawan->kode_cabang, $karyawan->kode_dept, $tanggal->first(), $tanggal->last()));
        if ($libur->isNotEmpty()) {
            $this->tolak('selected_dates', 'Tanggal '.$libur->join(', ').' adalah hari libur dan tidak dapat diajukan cuti.');
        }

        if ($this->izin->tanggalBentrok($karyawan->nik, $tanggal, $kode_izin)) {
            $this->tolak('selected_dates', 'Sebagian tanggal yang dipilih bentrok dengan presensi/pengajuan izin lain.');
        }

        $this->tolakJikaMelebihiJatah($request->kode_cuti, $tanggal->count(), 'hari yang diajukan', $kode_izin);

        try {
            $izin->update([
                'tgl_izin_dari' => $tanggal->first(),
                'tgl_izin_sampai' => $tanggal->last(),
                'kode_cuti' => $request->kode_cuti,
                'keterangan' => CutiDatesMeta::append($request->keterangan(), $tanggal),
            ]);

            return $this->keDaftarIzin('success', 'Data Pengajuan Cuti Berhasil Diupdate.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Pengajuan Cuti Gagal Diupdate.', $e));
        }
    }

    /**
     * Data dropdown jenis cuti beserta sisa jatahnya (tanpa menghitung pengajuan yang sedang diedit).
     */
    private function dataForm(?string $kodeIzinDiedit = null): array
    {
        $mastercuti = MasterCuti::orderBy('kode_cuti')->get();
        $sisa_cuti_map = $this->izin->sisaCutiMap($this->karyawan(), $mastercuti, $kodeIzinDiedit);
        $kodeCutiTahunan = $this->izin->kodeCutiTahunan();
        $sisa_cuti = $sisa_cuti_map[$kodeCutiTahunan] ?? null;

        return compact('mastercuti', 'sisa_cuti_map', 'kodeCutiTahunan', 'sisa_cuti');
    }

    private function tolakJikaAdaSpAktif(string $nik, string $aksi): void
    {
        if (SuratPeringatan::aktif()->where('nik', $nik)->exists()) {
            $this->tolak('kode_cuti', "Maaf, Anda memiliki Surat Peringatan (SP) yang masih aktif sehingga tidak dapat {$aksi} cuti.");
        }
    }

    private function tolakJikaMelebihiJatah(string $kodeCuti, int $jumlahHari, string $keteranganHari, ?string $kodeIzinDiedit = null): void
    {
        $masterCuti = MasterCuti::find($kodeCuti);
        $sisa = $this->izin->sisaCuti($this->karyawan(), $masterCuti, $kodeIzinDiedit);

        if ($sisa !== null && $jumlahHari > $sisa) {
            $this->tolak('jmlhari', "Sisa jatah cuti untuk jenis {$masterCuti->nama_cuti} Anda ({$sisa} hari) tidak mencukupi untuk {$jumlahHari} {$keteranganHari}.");
        }
    }
}
