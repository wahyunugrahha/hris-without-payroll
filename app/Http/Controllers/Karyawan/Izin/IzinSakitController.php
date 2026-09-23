<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Http\Requests\Karyawan\Izin\IzinSakitRequest;
use App\Models\Izin;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class IzinSakitController extends IzinController
{
    private const FOLDER_SURAT = 'uploads/sid';

    public function create()
    {
        return view('karyawan.pengajuanizin.createizinsakit');
    }

    public function store(IzinSakitRequest $request)
    {
        $kodeIzin = $this->izin->nextKodeIzin($request->dari);

        try {
            Izin::create([
                'kode_izin' => $kodeIzin,
                'nik' => $this->karyawan()->nik,
                'tgl_izin_dari' => $request->dari,
                'tgl_izin_sampai' => $request->sampai,
                'status' => 's',
                'keterangan' => $request->keterangan,
                'doc_sid' => $request->hasFile('sid') ? $this->simpanSurat($request->file('sid'), $kodeIzin) : null,
            ]);

            return $this->keDaftarIzin('success', 'Data Izin Sakit Berhasil Disimpan. Kode Izin: '.$kodeIzin);
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Izin Sakit Gagal Disimpan.', $e));
        }
    }

    public function edit(string $kode_izin)
    {
        $dataizin = $this->pendingMilikSendiri($kode_izin, 's');

        return $dataizin
            ? view('karyawan.pengajuanizin.editizinsakit', compact('dataizin'))
            : $this->tidakBisaDiubah();
    }

    public function update(IzinSakitRequest $request, string $kode_izin)
    {
        $izin = $this->pendingMilikSendiri($kode_izin, 's');
        if (! $izin) {
            return $this->tidakBisaDiubah();
        }

        $data = [
            'tgl_izin_dari' => $request->dari,
            'tgl_izin_sampai' => $request->sampai,
            'keterangan' => $request->keterangan,
        ];

        try {
            if ($request->hasFile('sid')) {
                if (! empty($izin->doc_sid) && $izin->doc_sid !== '-') {
                    Storage::disk('public')->delete(self::FOLDER_SURAT.'/'.$izin->doc_sid);
                }
                $data['doc_sid'] = $this->simpanSurat($request->file('sid'), $kode_izin);
            }

            $izin->update($data);

            return $this->keDaftarIzin('success', 'Data Izin Sakit Berhasil Diupdate.');
        } catch (Exception $e) {
            return $this->keDaftarIzin('error', $this->failMessage('Data Izin Sakit Gagal Diupdate.', $e));
        }
    }

    /**
     * Simpan surat dokter dengan ekstensi hasil deteksi isi file (bukan dari nama file klien).
     */
    private function simpanSurat(UploadedFile $file, string $kodeIzin): string
    {
        $fileName = $kodeIzin.'.'.$file->extension();
        $file->storeAs(self::FOLDER_SURAT, $fileName, 'public');

        return $fileName;
    }
}
