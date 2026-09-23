<?php

use App\Models\KonfigurasiUmum;
use Illuminate\Support\Facades\Cache;

function is_terlambat($jadwal_jam_masuk, $jam_presensi): bool
{
    try {
        if (empty($jadwal_jam_masuk) || empty($jam_presensi) || $jam_presensi === '00:00:00') {
            return false;
        }

        $jadwal = new DateTime($jadwal_jam_masuk);
        $presensi = new DateTime($jam_presensi);

        return $presensi > $jadwal;
    } catch (Exception $e) {
        Log::error('Error is_terlambat: '.$e->getMessage(), [
            'jadwal_jam_masuk' => $jadwal_jam_masuk,
            'jam_presensi' => $jam_presensi,
        ]);

        return false;
    }
}

function hitungjamterlambat($jadwal_jam_masuk, $jam_presensi)
{
    try {
        if (! is_terlambat($jadwal_jam_masuk, $jam_presensi)) {
            return '00:00';
        }

        // Parse DateTime
        $j1 = new DateTime($jadwal_jam_masuk);
        $j2 = new DateTime($jam_presensi);

        // Hitung selisih
        $diff = $j1->diff($j2);

        // Total jam termasuk hari
        $jamterlambat = ($diff->days * 24) + $diff->h;
        $menitterlambat = $diff->i;

        // Format output
        $jterlambat = str_pad($jamterlambat, 2, '0', STR_PAD_LEFT);
        $mterlambat = str_pad($menitterlambat, 2, '0', STR_PAD_LEFT);

        return $jterlambat.':'.$mterlambat;
    } catch (Exception $e) {
        // Log error untuk debugging
        Log::error('Error hitungjamterlambat: '.$e->getMessage(), [
            'jadwal_jam_masuk' => $jadwal_jam_masuk,
            'jam_presensi' => $jam_presensi,
        ]);

        return '00:00';
    }
}

function hitungjamterlambatdesimal($jam_masuk, $jam_presensi)
{
    try {
        if (! is_terlambat($jam_masuk, $jam_presensi)) {
            return 0;
        }

        // Parse DateTime
        $j1 = new DateTime($jam_masuk);
        $j2 = new DateTime($jam_presensi);

        // Hitung selisih
        $diff = $j1->diff($j2);

        // Total jam termasuk hari
        $jamterlambat = ($diff->days * 24) + $diff->h;
        $menitterlambat = $diff->i;

        // Konversi ke desimal: jam + (menit/60)
        $desimalterlambat = $jamterlambat + round(($menitterlambat / 60), 2);

        return $desimalterlambat;
    } catch (Exception $e) {
        // Log error untuk debugging
        Log::error('Error hitungjamterlambatdesimal: '.$e->getMessage(), [
            'jam_masuk' => $jam_masuk,
            'jam_presensi' => $jam_presensi,
        ]);

        return 0;
    }
}

function hitunghari($tanggal_mulai, $tanggal_akhir)
{
    $tanggal_1 = date_create($tanggal_mulai);
    $tanggal_2 = date_create($tanggal_akhir);
    $diff = date_diff($tanggal_1, $tanggal_2);

    return $diff->days + 1;
}

function buatkode($nomor_terakhir, $kunci, $jumlah_karakter = 0)
{
    /* mencari nomor baru dengan memecah nomor terakhir dan menambahkan 1
    string nomor baru dibawah ini harus dengan format XXX000000
    untuk penggunaan dalam format lain anda harus menyesuaikan sendiri */
    $nomor_baru = intval(substr($nomor_terakhir, strlen($kunci))) + 1;
    //    menambahkan nol didepan nomor baru sesuai panjang jumlah karakter
    $nomor_baru_plus_nol = str_pad($nomor_baru, $jumlah_karakter, '0', STR_PAD_LEFT);
    //    menyusun kunci dan nomor baru
    $kode = $kunci.$nomor_baru_plus_nol;

    return $kode;
}

if (! function_exists('get_setting')) {
    /**
     * Get a setting value from konfigurasi_umums table.
     * Uses cache to prevent repeated database queries.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function get_setting($key, $default = null)
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = KonfigurasiUmum::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }
}

if (! function_exists('asset_v')) {
    /**
     * URL aset di folder public dengan versi dari waktu modifikasi file,
     * sehingga browser otomatis memuat ulang CSS/JS setelah file berubah (tanpa hard refresh).
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
