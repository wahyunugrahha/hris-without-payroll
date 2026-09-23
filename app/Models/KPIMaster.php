<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPIMaster extends Model
{
    protected $table = 'kpi_master';

    protected $guarded = ['id'];

    public function kpiMasterDetail()
    {
        return $this->hasMany(KPIMasterDetail::class, 'kode_master', 'kode_master');
    }

    public function kpiMasterAtasan()
    {
        return $this->hasMany(KPIMasterAtasan::class, 'kode_master', 'kode_master');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'kode_dept', 'kode_dept');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }

    /**
     * Master KPI aktif yang berlaku untuk karyawan, dari yang paling spesifik:
     * jabatan+dept+cabang, jabatan+dept, jabatan saja; jika tidak ada, master jabatan "Staff"
     * dengan urutan cakupan yang sama.
     */
    public static function untukKaryawan(Karyawan $karyawan): ?self
    {
        $cakupan = [
            [$karyawan->kode_dept, $karyawan->kode_cabang],
            [$karyawan->kode_dept, null],
            [null, null],
        ];

        foreach ([false, true] as $fallbackStaff) {
            foreach ($cakupan as [$kodeDept, $kodeCabang]) {
                $master = self::where('is_active', true)
                    ->where('kode_dept', $kodeDept)
                    ->where('kode_cabang', $kodeCabang)
                    ->when(
                        $fallbackStaff,
                        fn ($q) => $q->whereHas('jabatan', fn ($j) => $j->where('nama_jabatan', 'ilike', '%staff%')),
                        fn ($q) => $q->where('jabatan_id', $karyawan->jabatan_id)
                    )
                    ->first();

                if ($master) {
                    return $master;
                }
            }
        }

        return null;
    }
}
