<?php

namespace App\Imports;

use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\Cabang;
use App\Models\Jabatan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;

class KaryawanImport implements ToModel, WithHeadingRow, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    /**
     * Transform Excel row menjadi Model Karyawan
     */
    public function model(array $row)
    {
        // Skip baris kosong
        if (empty($row['nik']) || empty($row['nama_lengkap'])) {
            $this->skipCount++;
            return null;
        }

        try {
            // Validasi NIK
            $nik = trim($row['nik']);
            if (Karyawan::where('nik', $nik)->exists()) {
                $this->errors[] = "Baris NIK {$nik}: NIK sudah terdaftar.";
                $this->skipCount++;
                return null;
            }

            // Validasi Departemen
            $kodeDept = !empty($row['kode_dept']) ? trim($row['kode_dept']) : null;
            if ($kodeDept && !Departemen::where('kode_dept', $kodeDept)->exists()) {
                $this->errors[] = "Baris NIK {$nik}: Kode Departemen '{$kodeDept}' tidak ditemukan.";
                $this->skipCount++;
                return null;
            }

            // Validasi Cabang
            $kodeCabang = !empty($row['kode_cabang']) ? trim($row['kode_cabang']) : null;
            if ($kodeCabang && strcasecmp($kodeCabang, 'Global') === 0) {
                $kodeCabang = 'CBNG0001';
            }
            if ($kodeCabang && !Cabang::where('kode_cabang', $kodeCabang)->exists()) {
                $this->errors[] = "Baris NIK {$nik}: Kode Cabang '{$kodeCabang}' tidak ditemukan.";
                $this->skipCount++;
                return null;
            }

            // Validasi Jabatan - cari by nama_jabatan dari Excel
            $jabatanId = null;
            if (!empty($row['jabatan'])) {
                $jabatanNama = trim($row['jabatan']);
                $jabatan = Jabatan::where('nama_jabatan', $jabatanNama)->first();
                if (!$jabatan) {
                    $this->errors[] = "Baris NIK {$nik}: Jabatan '{$jabatanNama}' tidak ditemukan di master jabatan.";
                    $this->skipCount++;
                    return null;
                }
                $jabatanId = $jabatan->id;
            }

            // Parse tanggal
            $tmt = $this->parseDate($row['tmt_join_date'] ?? null);
            $tanggalAwalKontrak = $this->parseDate($row['awal_kontrak'] ?? null);
            $tanggalLahir = $this->parseDate($row['tanggal_lahir'] ?? null);
            $tanggalHabisKontrak = $this->parseDate($row['tanggal_habis_kontrak'] ?? null);

            $this->successCount++;

            return new Karyawan([
                'nik' => $nik,
                'nama_lengkap' => trim($row['nama_lengkap']),
                'nama_panggilan' => !empty($row['nama_panggilan']) ? trim($row['nama_panggilan']) : trim($row['nama_lengkap']),
                'password' => Hash::make('123456'), // Default password, wajib diganti saat login pertama
                'must_change_password' => true,
                'jabatan_id' => $jabatanId,
                'kode_dept' => $kodeDept,
                'kode_cabang' => $kodeCabang,
                'no_hp' => !empty($row['no_hp']) ? trim($row['no_hp']) : '0',
                'email' => !empty($row['email']) ? trim($row['email']) : null,
                
                // Data Pribadi
                'jenis_kelamin' => !empty($row['jenis_kelamin']) ? trim($row['jenis_kelamin']) : null,
                'tempat_lahir' => !empty($row['tempat_lahir']) ? trim($row['tempat_lahir']) : null,
                'tanggal_lahir' => $tanggalLahir,
                'agama' => !empty($row['agama']) ? trim($row['agama']) : null,
                'status_pernikahan' => !empty($row['status_pernikahan']) ? trim($row['status_pernikahan']) : null,
                'alamat' => !empty($row['alamat']) ? trim($row['alamat']) : null,
                'pendidikan_terakhir' => !empty($row['pendidikan_terakhir']) ? trim($row['pendidikan_terakhir']) : null,
                'status_ptkp' => !empty($row['status_ptkp']) ? trim($row['status_ptkp']) : null,
                
                // Kepegawaian
                'tmt' => $tmt,
                'tanggal_awal_kontrak' => $tanggalAwalKontrak,
                'status_karyawan' => !empty($row['status_karyawan']) ? trim($row['status_karyawan']) : 'PKWT',
                'status_aktif' => !empty($row['status_aktif']) ? trim($row['status_aktif']) : 'Aktif',
                'tanggal_habis_kontrak' => $tanggalHabisKontrak,
                'history_karyawan' => !empty($row['history_karyawan']) ? trim($row['history_karyawan']) : null,
                'statemen' => !empty($row['statemen']) ? trim($row['statemen']) : null,
                
                // Keluarga & Darurat
                'nama_ibu_kandung' => !empty($row['nama_ibu_kandung']) ? trim($row['nama_ibu_kandung']) : null,
                'nama_darurat' => !empty($row['nama_kontak_darurat']) ? trim($row['nama_kontak_darurat']) : null,
                'hubungan_darurat' => !empty($row['hubungan_kontak_darurat']) ? trim($row['hubungan_kontak_darurat']) : null,
                'no_darurat' => !empty($row['no_hp_darurat']) ? trim($row['no_hp_darurat']) : null,
                
                // Legal & Finance
                'no_bpjs_kesehatan' => !empty($row['no_bpjs_kesehatan']) ? trim($row['no_bpjs_kesehatan']) : null,
                'no_bpjs_ketenagakerjaan' => !empty($row['no_bpjs_ketenagakerjaan']) ? trim($row['no_bpjs_ketenagakerjaan']) : null,
                'no_rekening' => !empty($row['no_rekening']) ? trim($row['no_rekening']) : null,
            ]);

        } catch (\Exception $e) {
            $this->errors[] = "Baris NIK {$nik}: " . $e->getMessage();
            $this->skipCount++;
            return null;
        }
    }

    /**
     * Parse tanggal dari berbagai format
     */
    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Jika numeric (Excel serial date)
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }
            
            // Parse string date
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get import summary
     */
    public function getSummary()
    {
        return [
            'success' => $this->successCount,
            'skipped' => $this->skipCount,
            'errors' => $this->errors,
        ];
    }
}
