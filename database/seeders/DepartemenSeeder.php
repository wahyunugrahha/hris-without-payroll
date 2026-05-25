<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartemenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['kode_dept' => 'HRD', 'nama_dept' => 'Human Resources Development'],
            ['kode_dept' => 'MKT', 'nama_dept' => 'Marketing'],
            ['kode_dept' => 'DEV', 'nama_dept' => 'Information Technology'],
            ['kode_dept' => 'PRC', 'nama_dept' => 'Purchasing'],
            ['kode_dept' => 'LGL', 'nama_dept' => 'Legal'],
            ['kode_dept' => 'GDG', 'nama_dept' => 'Gudang'],
            ['kode_dept' => 'FRM', 'nama_dept' => 'Foreman'],
            ['kode_dept' => 'MKN', 'nama_dept' => 'Mekanik'],
            ['kode_dept' => 'RCP', 'nama_dept' => 'Resepsionis'],
            ['kode_dept' => 'ADM', 'nama_dept' => 'Admin'],
            ['kode_dept' => 'LGS', 'nama_dept' => 'Logistik'],
            ['kode_dept' => 'ACT', 'nama_dept' => 'Accounting'],
            ['kode_dept' => 'ETR', 'nama_dept' => 'Estimator'],
            ['kode_dept' => 'DSR', 'nama_dept' => 'Designer'],
            ['kode_dept' => 'HUL', 'nama_dept' => 'Hauling'],
            ['kode_dept' => 'STU', 'nama_dept' => 'Staff Umum'],
            ['kode_dept' => 'OPT', 'nama_dept' => 'Operator Excavator'],
            ['kode_dept' => 'DRV', 'nama_dept' => 'Driver DT'],
            ['kode_dept' => 'TNY', 'nama_dept' => 'Driver Tonly'],
            ['kode_dept' => 'PGS', 'nama_dept' => 'Pengawas Lapangan'],
            ['kode_dept' => 'KSP', 'nama_dept' => 'KSP'],
            ['kode_dept' => 'OWN', 'nama_dept' => 'Owner'],
            ['kode_dept' => 'DRO', 'nama_dept' => 'Driver Operasional'],
            ['kode_dept' => 'OWL', 'nama_dept' => 'OP While Loader'],
            ['kode_dept' => 'CKR', 'nama_dept' => 'Checker'],
            ['kode_dept' => 'OPS', 'nama_dept' => 'Op Serap'],
            ['kode_dept' => 'OTB', 'nama_dept' => 'Op Timbangan'],
            ['kode_dept' => 'KAM', 'nama_dept' => 'Keamanan'],
            ['kode_dept' => 'MGM', 'nama_dept' => 'Management'],
            ['kode_dept' => 'HLS', 'nama_dept' => 'Helper Las'],
            ['kode_dept' => 'HRG', 'nama_dept' => 'Human Resource and General Affairs'],
            ['kode_dept' => 'HSE', 'nama_dept' => 'Health, Safety and Environment'],
            ['kode_dept' => 'PJO', 'nama_dept' => 'Penanggung Jawab Operasional'],
            ['kode_dept' => 'ENG', 'nama_dept' => 'Enggineering'],
            ['kode_dept' => 'PLT', 'nama_dept' => 'Plant'],
            ['kode_dept' => 'OPR', 'nama_dept' => 'Operation'],
            ['kode_dept' => 'OGR', 'nama_dept' => 'Operator Grader'],
            ['kode_dept' => 'ODZ', 'nama_dept' => 'Operator Dozzer'],
            ['kode_dept' => 'PMP', 'nama_dept' => 'Pompa'],
            ['kode_dept' => 'DRS', 'nama_dept' => 'Driver sarana'],
        ];

        foreach ($departments as $dept) {
            DB::table('departemen')->updateOrInsert(
                ['kode_dept' => $dept['kode_dept']],
                [
                    'nama_dept' => $dept['nama_dept'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        $this->command->info('Data 40 Departemen berhasil diproses.');
    }
}