<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * File foto karyawan (profil & kartu BPJS) di disk public. Nama file selalu diawali NIK dan diberi
 * timestamp, sehingga ganti foto / ganti NIK langsung terlihat (tidak tertahan cache browser).
 */
class FotoKaryawanService
{
    private const JENIS = [
        'foto' => ['folder' => 'uploads/karyawan', 'sisipan' => '_'],
        'foto_bpjs_kesehatan' => ['folder' => 'uploads/karyawan/bpjs', 'sisipan' => '_bpjs_kes_'],
        'foto_bpjs_ketenagakerjaan' => ['folder' => 'uploads/karyawan/bpjs', 'sisipan' => '_bpjs_ket_'],
    ];

    /**
     * Nama file yang akan disimpan di kolom: file upload baru, file lama yang di-rename karena NIK berubah,
     * atau nama lama apa adanya.
     */
    public function namaBaru(string $jenis, ?UploadedFile $upload, ?string $namaLama, string $nikLama, string $nikBaru): ?string
    {
        if ($upload) {
            return $this->buatNama($jenis, $nikBaru, $upload->extension());
        }

        if ($namaLama && $nikLama !== $nikBaru) {
            return $this->buatNama($jenis, $nikBaru, pathinfo($namaLama, PATHINFO_EXTENSION) ?: 'jpg');
        }

        return $namaLama;
    }

    /**
     * Terapkan perubahan ke disk: simpan upload baru / pindahkan file lama, lalu buang file lama yang tak terpakai.
     */
    public function terapkan(string $jenis, ?UploadedFile $upload, ?string $namaLama, ?string $namaBaru): void
    {
        $folder = self::JENIS[$jenis]['folder'];
        $disk = Storage::disk('public');

        if ($upload) {
            $upload->storeAs($folder, $namaBaru, 'public');

            if ($namaLama && $namaLama !== $namaBaru) {
                $disk->delete($folder.'/'.$namaLama);
            }

            return;
        }

        if ($namaLama && $namaBaru && $namaLama !== $namaBaru && $disk->exists($folder.'/'.$namaLama)) {
            $disk->move($folder.'/'.$namaLama, $folder.'/'.$namaBaru);
        }
    }

    /**
     * Simpan upload baru untuk karyawan & buang file lamanya. Mengembalikan nama file baru untuk kolom.
     */
    public function ganti(string $jenis, UploadedFile $upload, ?string $namaLama, string $nik): string
    {
        $namaBaru = $this->namaBaru($jenis, $upload, $namaLama, $nik, $nik);
        $this->terapkan($jenis, $upload, $namaLama, $namaBaru);

        return $namaBaru;
    }

    public function hapus(string $jenis, ?string $nama): void
    {
        if ($nama) {
            Storage::disk('public')->delete(self::JENIS[$jenis]['folder'].'/'.$nama);
        }
    }

    private function buatNama(string $jenis, string $nik, string $ekstensi): string
    {
        return $nik.self::JENIS[$jenis]['sisipan'].time().'.'.$ekstensi;
    }
}
