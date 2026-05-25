<?php

namespace App\Exports;

use App\Models\Karyawan;
use App\Models\Cabang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KaryawanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithColumnFormatting, WithMultipleSheets, WithTitle
{
    protected $filters;
    protected $kode_cabang_sheet;
    protected $nama_cabang_sheet;
    protected $globalKodeCabang = 'CBNG0001';
    protected $globalCabangNama = null;

    public function __construct($filters = [], $kode_cabang_sheet = null, $nama_cabang_sheet = null)
    {
        if (!empty($filters['kode_cabang']) && strcasecmp($filters['kode_cabang'], 'Global') === 0) {
            $filters['kode_cabang'] = $this->globalKodeCabang;
        }
        $this->filters = $filters;
        $this->kode_cabang_sheet = $kode_cabang_sheet;
        $this->nama_cabang_sheet = $nama_cabang_sheet;
    }

    /**
     * LOGIKA KOORDINATOR (PARENT)
     */
    public function sheets(): array
    {
        if ($this->kode_cabang_sheet) {
            return [];
        }

        $sheets = [];

        if (!empty($this->filters['kode_cabang'])) {
            $cabangList = Cabang::where('kode_cabang', $this->filters['kode_cabang'])->get();
        } else {
            $cabangList = Cabang::whereRaw('lower(kode_cabang) != ?', ['global'])
                ->whereRaw('lower(nama_cabang) != ?', ['anywhere'])
                ->orderBy('nama_cabang')
                ->get();
        }

        foreach ($cabangList as $cabang) {
            $sheets[] = new self(
                $this->filters,
                $cabang->kode_cabang,
                $cabang->nama_cabang
            );
        }

        return $sheets;
    }

    /**
     * Judul Tab Excel
     */
    public function title(): string
    {
        // Excel membatasi nama sheet max 31 karakter
        $title = $this->nama_cabang_sheet ?? 'All Data';
        // Hapus karakter ilegal untuk nama sheet excel
        $cleanTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '', $title);

        return substr($cleanTitle, 0, 30);
    }

    /**
     * Query Data
     */
    public function collection()
    {
        // Jika ini adalah Parent (tidak punya kode cabang spesifik), 
        // jangan query data (karena data ada di dalam sheets anak).
        // Kecuali Anda ingin sheet pertama berisi semua data.
        if (!$this->kode_cabang_sheet) {
            // Opsional: return empty collection jika Parent hanya bertugas membungkus sheet
            // return collect([]); 
        }

        if ($this->globalCabangNama === null) {
            $this->globalCabangNama = Cabang::where('kode_cabang', $this->globalKodeCabang)->value('nama_cabang');
        }

        $query = Karyawan::select('karyawan.*', 'departemen.nama_dept', 'cabang.nama_cabang', 'jabatan.nama_jabatan as jabatan_nama')
            ->leftJoin('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept') // Gunakan Left Join untuk keamanan data null
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->orderBy('karyawan.nama_lengkap');

        $statusFilter = $this->filters['status_filter'] ?? Karyawan::STATUS_AKTIF;
        if (!in_array($statusFilter, Karyawan::FILTERABLE_STATUSES, true)) {
            $statusFilter = Karyawan::STATUS_AKTIF;
        }

        $query->filterStatus($statusFilter);

        // Filter Sheet (Child Logic)
        if ($this->kode_cabang_sheet) {
            if ($this->kode_cabang_sheet === $this->globalKodeCabang) {
                $query->whereIn('karyawan.kode_cabang', [$this->globalKodeCabang, 'Global']);
            } else {
                $query->where('karyawan.kode_cabang', $this->kode_cabang_sheet);
            }
        }

        // Filter User
        if (!empty($this->filters['nama_karyawan'])) {
            $query->where('karyawan.nama_lengkap', 'ilike', '%' . $this->filters['nama_karyawan'] . '%');
        }
        if (!empty($this->filters['kode_dept'])) {
            $query->where('karyawan.kode_dept', $this->filters['kode_dept']);
        }
        if (!empty($this->filters['jabatan_id'])) {
            $query->where('karyawan.jabatan_id', $this->filters['jabatan_id']);
        }
        if (!empty($this->filters['kode_cabang'])) {
            if ($this->filters['kode_cabang'] === $this->globalKodeCabang) {
                $query->whereIn('karyawan.kode_cabang', [$this->globalKodeCabang, 'Global']);
            } else {
                $query->where('karyawan.kode_cabang', $this->filters['kode_cabang']);
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'NIK',
            'Nama Lengkap',
            'Nama Panggilan',
            'Jabatan',
            'Departemen',
            'PT',
            'No. HP',
            'Email',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Status Pernikahan',
            'Alamat',
            'Pendidikan Terakhir',
            'Status PTKP',
            'TMT (Join Date)',
            'Tanggal Awal Kontrak',
            'Status Karyawan',
            'Status Aktif',
            'Tanggal Habis Kontrak',
            'History Karyawan',
            'Statemen',
            'Nama Ibu Kandung',
            'Nama Kontak Darurat',
            'Hubungan Kontak Darurat',
            'No. HP Darurat',
            'No. BPJS Kesehatan',
            'No. BPJS Ketenagakerjaan',
            'No. Rekening'
        ];
    }

    public function map($karyawan): array
    {
        // Helper untuk format tanggal
        $formatDate = fn($date) => $date ? date('d-m-Y', strtotime($date)) : '-';

        $kodeCabang = $karyawan->kode_cabang;
        $namaCabang = $karyawan->nama_cabang ?? '-';
        if ($kodeCabang === 'Global') {
            $kodeCabang = $this->globalKodeCabang;
            $namaCabang = $this->globalCabangNama ?? '-';
        }

        return [
            // Menggunakan explicit string conversion agar angka 0 di depan tidak hilang
            $karyawan->nik ? "'" . $karyawan->nik : '',
            $karyawan->nama_lengkap,
            $karyawan->nama_panggilan,
            $karyawan->jabatan_nama ?? '-',
            $karyawan->nama_dept ?? '-',
            $namaCabang,
            (string) ($karyawan->no_hp ?? ''),
            $karyawan->email ?? '-',
            $karyawan->jenis_kelamin ?? '-',
            $karyawan->tempat_lahir ?? '-',
            $formatDate($karyawan->tanggal_lahir),
            $karyawan->agama ?? '-',
            $karyawan->status_pernikahan ?? '-',
            $karyawan->alamat ?? '-',
            $karyawan->pendidikan_terakhir ?? '-',
            $karyawan->status_ptkp ?? '-',
            $formatDate($karyawan->tmt),
            $formatDate($karyawan->tanggal_awal_kontrak),
            $karyawan->status_karyawan ?? '-',
            $karyawan->status_aktif ?? '-',
            $formatDate($karyawan->tanggal_habis_kontrak),
            $karyawan->history_karyawan ?? '-',
            $karyawan->statemen ?? '-',
            $karyawan->nama_ibu_kandung ?? '-',
            $karyawan->nama_darurat ?? '-',
            $karyawan->hubungan_darurat ?? '-',
            (string) ($karyawan->no_darurat ?? ''),
            (string) ($karyawan->no_bpjs_kesehatan ?? ''),
            (string) ($karyawan->no_bpjs_ketenagakerjaan ?? ''),
            (string) ($karyawan->no_rekening ?? ''),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
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
            'E' => 25, // Departemen
            'F' => 30, // PT
            'G' => 15, // No HP
            'H' => 25, // Email
            'I' => 15, // JK
            'J' => 20, // Tmpt Lahir
            'K' => 15, // Tgl Lahir
            'L' => 15, // Agama
            'M' => 18, // Status Nikah
            'N' => 40, // Alamat
            'O' => 20, // Pendidikan
            'P' => 15, // Status PTKP
            'Q' => 15, // TMT
            'R' => 15, // Tanggal Awal Kontrak
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
            'G' => NumberFormat::FORMAT_TEXT, // HP
            'AA' => NumberFormat::FORMAT_TEXT, // HP Darurat
            'AB' => NumberFormat::FORMAT_TEXT, // BPJS
            'AC' => NumberFormat::FORMAT_TEXT, // BPJS
            'AD' => NumberFormat::FORMAT_TEXT, // Rekening
        ];
    }
}