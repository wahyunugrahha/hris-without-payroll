<?php

namespace App\Support;

/**
 * Warna kategori (kelas CSS .hue-*) per jenis data, agar setiap jenis
 * tampil konsisten di semua halaman admin.
 */
final class WarnaJenis
{
    /** Kode status ketidakhadiran/presensi → warna. */
    public const KETIDAKHADIRAN = [
        'i' => 'blue',     // izin
        's' => 'pink',     // sakit
        'c' => 'teal',     // cuti
        'r' => 'violet',   // roster
        'd' => 'indigo',   // dinas luar
        't' => 'orange',   // izin terlambat
        'p' => 'amber',    // izin pulang cepat
        'l' => 'slate',    // libur
    ];

    public const HARI_LIBUR = [
        'nasional' => 'red',
        'cuti_bersama' => 'amber',
        'lokal' => 'blue',
    ];

    public const PELANGGARAN_SP = [
        'late' => 'orange',
        'absent' => 'red',
        'discipline' => 'violet',
        'other' => 'slate',
    ];

    public const PANEL_AKSES = [
        'user' => 'indigo',
        'karyawan' => 'teal',
    ];

    public static function ketidakhadiran(?string $kode): string
    {
        return self::KETIDAKHADIRAN[$kode] ?? 'slate';
    }
}
