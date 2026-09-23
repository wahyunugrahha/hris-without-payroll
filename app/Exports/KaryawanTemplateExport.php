<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KaryawanTemplateExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        // Satu baris dummy "test" agar user punya contoh
        return new Collection([
            [
                'NIK' => "'1234567890123456",
                'Nama_Lengkap' => 'Test User',
                'Nama_Panggilan' => 'Test',
                'Jabatan' => 'Tester',
                'Kode_Dept' => 'TST',
                'Kode_Cabang' => 'TEST',
                'No_HP' => '081234567890',
                'Email' => 'test@example.com',
                'Jenis_Kelamin' => 'L',
                'Tempat_Lahir' => 'Test City',
                'Tanggal_Lahir' => '2000-01-01',
                'Agama' => 'Islam',
                'Status_Pernikahan' => 'Lajang',
                'Alamat' => 'Jl. Contoh No. 123',
                'Pendidikan_Terakhir' => 'S1',
                'Status_PTKP' => 'TK/0',
                'TMT_Join_Date' => '2026-01-01',
                'Awal_Kontrak' => '2026-01-01',
                'Status_Karyawan' => 'PKWT',
                'Status_Aktif' => 'Aktif',
                'Tanggal_Habis_Kontrak' => '2026-12-31',
                'History_Karyawan' => 'Riwayat contoh',
                'Statemen' => 'Pernyataan contoh',
                'Nama_Ibu_Kandung' => 'Ibu Test',
                'Nama_Kontak_Darurat' => 'Darurat Test',
                'Hubungan_Kontak_Darurat' => 'Saudara',
                'No_HP_Darurat' => '081234567891',
                'No_BPJS_Kesehatan' => '1234567890',
                'No_BPJS_Ketenagakerjaan' => '0987654321',
                'No_Rekening' => '111222333444',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Nama_Lengkap',
            'Nama_Panggilan',
            'Jabatan',
            'Kode_Dept',
            'Kode_Cabang',
            'No_HP',
            'Email',
            'Jenis_Kelamin',
            'Tempat_Lahir',
            'Tanggal_Lahir',
            'Agama',
            'Status_Pernikahan',
            'Alamat',
            'Pendidikan_Terakhir',
            'Status_PTKP',
            'TMT_Join_Date',
            'Awal_Kontrak',
            'Status_Karyawan',
            'Status_Aktif',
            'Tanggal_Habis_Kontrak',
            'History_Karyawan',
            'Statemen',
            'Nama_Ibu_Kandung',
            'Nama_Kontak_Darurat',
            'Hubungan_Kontak_Darurat',
            'No_HP_Darurat',
            'No_BPJS_Kesehatan',
            'No_BPJS_Ketenagakerjaan',
            'No_Rekening',
        ];
    }

    public function title(): string
    {
        return 'Template Import';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // NIK
            'B' => 30, // Nama Lengkap
            'C' => 20, // Nama Panggilan
            'D' => 25, // Jabatan
            'E' => 20, // Kode Dept
            'F' => 20, // Kode Cabang
            'G' => 15, // No HP
            'H' => 25, // Email
            'I' => 15, // JK
            'J' => 20, // Tempat Lahir
            'K' => 15, // Tanggal Lahir
            'L' => 15, // Agama
            'M' => 18, // Status Nikah
            'N' => 40, // Alamat
            'O' => 20, // Pendidikan
            'P' => 15, // Status PTKP
            'Q' => 15, // TMT
            'R' => 15, // Awal Kontrak
            'S' => 18, // Status Karyawan
            'T' => 15, // Status Aktif
            'U' => 18, // Tgl Habis Kontrak
            'V' => 30, // History
            'W' => 30, // Statemen
            'X' => 25, // Ibu Kandung
            'Y' => 25, // Nama Darurat
            'Z' => 20, // Hub Darurat
            'AA' => 15, // HP Darurat
            'AB' => 20, // BPJS Kes
            'AC' => 20, // BPJS TK
            'AD' => 25, // Rekening
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // NIK
            'G' => NumberFormat::FORMAT_TEXT, // No HP
            'AA' => NumberFormat::FORMAT_TEXT, // HP Darurat
            'AB' => NumberFormat::FORMAT_TEXT, // BPJS Kes
            'AC' => NumberFormat::FORMAT_TEXT, // BPJS TK
            'AD' => NumberFormat::FORMAT_TEXT, // Rekening
        ];
    }
}
