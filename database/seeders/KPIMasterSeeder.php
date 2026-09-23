<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\KPIMaster;
use App\Models\KPIMasterDetail;
use Illuminate\Database\Seeder;

class KPIMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil ID Jabatan
        $jabatanSPV = Jabatan::where('nama_jabatan', 'ILIKE', '%Supervisor%')->first();
        $jabatanMKN = Jabatan::where('nama_jabatan', 'ILIKE', '%Crew Mekanik%')->first(); // Atau sesuaikan dengan 'Mekanik'

        $idSPV = $jabatanSPV ? $jabatanSPV->id : null;
        $idMKN = $jabatanMKN ? $jabatanMKN->id : null;

        // 2. Definisi Master KPI (Header)
        $masters = [
            [
                'kode_master' => 'KPI-SPV-MKN',
                'nama_kpi' => 'KPI Kinerja Supervisor',
                'kode_dept' => 'MKN',
                'kode_cabang' => 'CBNG0001',
                'jabatan_id' => $idSPV,
            ],
            [
                'kode_master' => 'KPI-MKN',
                'nama_kpi' => 'KPI Kinerja Crew Mekanik (Workbook Daily)',
                'kode_dept' => 'MKN',
                'kode_cabang' => 'CBNG0001',
                'jabatan_id' => $idMKN,
            ],
        ];

        foreach ($masters as $master) {
            KPIMaster::updateOrCreate(
                ['kode_master' => $master['kode_master']],
                array_merge($master, ['is_active' => true])
            );
        }

        // === 3. DETAIL KPI SUPERVISOR ===
        $spvMaster = KPIMaster::where('kode_master', 'KPI-SPV-MKN')->first();

        if ($spvMaster) {
            $spvDetails = [
                ['indikator' => 'Monitoring & Verifikasi Laporan Crew', 'score_indikator' => 20, 'target' => 1],
                ['indikator' => 'Pengawasan Kepatuhan SOP & K3', 'score_indikator' => 10, 'target' => 1],
                ['indikator' => 'Koordinasi Kerusakan & Perbaikan Unit', 'score_indikator' => 10, 'target' => 1],
            ];

            foreach ($spvDetails as $detail) {
                KPIMasterDetail::updateOrCreate(
                    [
                        'kode_master' => $spvMaster->kode_master,
                        'indikator' => $detail['indikator'],
                    ],
                    array_merge($detail, [
                        'is_active' => true,
                    ])
                );
            }
        }

        // === 4. DETAIL KPI CREW MEKANIK (Disesuaikan dengan Gambar Workbook) ===
        $mknMaster = KPIMaster::where('kode_master', 'KPI-MKN')->first();

        if ($mknMaster) {
            $mknDetails = [
                ['indikator' => 'Menganalisa dan mengatasi gangguan (trouble shooting) mesin unit', 'score_indikator' => 4, 'target' => 1],
                ['indikator' => 'Memeriksa dan menganalisa kerusakan komponen', 'score_indikator' => 4, 'target' => 1],
                ['indikator' => 'Melaksanakan pemeliharaan, perbaikan dan pemeriksaan teknis peralatan', 'score_indikator' => 4, 'target' => 1],
                ['indikator' => 'Melaksanakan pemeliharaan, perbaikan dan pemeriksaan teknis peralatan', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Menyiapkan buku panduan atau shop manual yang sesuai', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Membongkar komponen yang akan diperbaiki', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Memeriksa kesesuaian dan memasang suku cadang yang diperlukan', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Menguji hasil perbaikan', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Mempergunakan perkakas kerja (tools) dengan benar sesuai kapasitas dan aplikasi', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Selalu menjaga kebersihan benda kerja, perkakas kerja (tools) dan tempat kerja', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Bekerja dengan peralatan pengaman, memprioritaskan keamanan diri dan lingkungan (K3)', 'score_indikator' => 3, 'target' => 1],
                ['indikator' => 'Berpikir cermat sebelum bertindak, memastikan solusi tepat dan terbaik', 'score_indikator' => 2, 'target' => 1],
                ['indikator' => 'Segera memberikan laporan kepada atasan terhadap masalah yang tidak bisa diatasi segera', 'score_indikator' => 2, 'target' => 1],
            ];

            foreach ($mknDetails as $detail) {
                KPIMasterDetail::updateOrCreate(
                    [
                        'kode_master' => $mknMaster->kode_master,
                        'indikator' => $detail['indikator'],
                    ],
                    array_merge($detail, [
                        'is_active' => true,
                    ])
                );
            }
        }
    }
}
