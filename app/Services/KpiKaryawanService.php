<?php

namespace App\Services;

use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\KPIDaily;
use App\Models\KPIMaster;
use App\Models\KPIMasterAtasan;
use App\Models\KPIMasterDetail;
use App\Models\Presensi;
use App\Models\User;
use App\Support\PeriodeKerja;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Aturan KPI harian dari sisi karyawan: indikator, hierarki atasan-bawahan, dan batas waktu pengisian.
 */
class KpiKaryawanService
{
    /** Urutan jabatan: angka kecil = lebih tinggi. Atasan menilai bawahan dengan level lebih besar. */
    private const HIERARKI = ['spv' => 1, 'pjo' => 2, 'kepala divisi' => 3, 'staff' => 4];

    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    /**
     * @param  'self'|'atasan'  $peran  indikator diisi karyawan sendiri atau penilaian atasan
     */
    public function indikator(?KPIMaster $master, string $peran = 'self'): Collection
    {
        if (! $master) {
            return collect();
        }

        $model = $peran === 'atasan' ? KPIMasterAtasan::class : KPIMasterDetail::class;

        return $model::where('kode_master', $master->kode_master)->where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Bawahan langsung: satu cabang & departemen, level jabatan di bawahnya, dan punya master KPI.
     */
    public function bawahan(Karyawan $karyawan): Collection
    {
        $karyawan->loadMissing('jabatanRel');

        $namaRole = Role::where('id', $karyawan->jabatanRel?->role_id)->value('name');
        $level = self::HIERARKI[strtolower($namaRole ?? '')] ?? null;

        if ($level === null) {
            return collect();
        }

        $roleBawahan = array_keys(array_filter(self::HIERARKI, fn ($l) => $l > $level));
        $roleIds = Role::whereIn(DB::raw('LOWER(name)'), $roleBawahan)->pluck('id');

        return Karyawan::with('jabatanRel')
            ->where('kode_cabang', $karyawan->kode_cabang)
            ->where('kode_dept', $karyawan->kode_dept)
            ->where('nik', '!=', $karyawan->nik)
            ->whereHas('jabatanRel', fn ($q) => $q->whereIn('role_id', $roleIds))
            ->orderBy('nama_lengkap')
            ->get()
            ->filter(fn ($k) => KPIMaster::untukKaryawan($k) !== null)
            ->values();
    }

    /**
     * @return 'self'|'atasan'|null peran $pengakses terhadap KPI milik $pemilik
     */
    public function peran(Karyawan $pengakses, Karyawan $pemilik): ?string
    {
        if ($pengakses->nik === $pemilik->nik) {
            return 'self';
        }

        return $this->bawahan($pengakses)->contains('nik', $pemilik->nik) ? 'atasan' : null;
    }

    /**
     * Bawahan yang wajib KPI pada tanggal tsb (hadir / izin terlambat / dinas luar) tapi belum mengirimnya.
     */
    public function bawahanBelumIsi(Karyawan $karyawan, string $tanggal): Collection
    {
        $bawahan = $this->bawahan($karyawan);
        if ($bawahan->isEmpty()) {
            return collect();
        }

        $nik = $bawahan->pluck('nik')->all();

        $wajib = array_unique(array_merge(
            Presensi::whereIn('nik', $nik)->whereDate('tgl_presensi', $tanggal)->where('status', 'h')->pluck('nik')->all(),
            Izin::whereIn('nik', $nik)->where('status', 't')->where('status_approved', '1')
                ->whereDate('tgl_izin_dari', '<=', $tanggal)->whereDate('tgl_izin_sampai', '>=', $tanggal)->pluck('nik')->all(),
            DinasLuar::whereIn('nik', $nik)->where('status_acc', 'acc')
                ->whereDate('tgl_mulai', '<=', $tanggal)->whereDate('tgl_selesai', '>=', $tanggal)->pluck('nik')->all(),
        ));

        if (empty($wajib)) {
            return collect();
        }

        $sudahIsi = KPIDaily::whereIn('nik', $wajib)->whereDate('tanggal', $tanggal)->where('status', '!=', 'draft')->pluck('nik')->all();

        return $bawahan->filter(fn ($k) => in_array($k->nik, $wajib) && ! in_array($k->nik, $sudahIsi))->values();
    }

    /**
     * Atasan baru boleh memproses KPI-nya setelah semua bawahan yang wajib KPI mengirim KPI.
     */
    public function peringatanBawahan(Karyawan $karyawan, string $tanggal): ?string
    {
        if ($karyawan->is_whitelist) {
            return null;
        }

        $belum = $this->bawahanBelumIsi($karyawan, $tanggal);

        return $belum->isEmpty()
            ? null
            : "Anda belum bisa memproses KPI ini. Bawahan berikut belum mengisi KPI pada tanggal $tanggal: ".$belum->pluck('nama_lengkap')->implode(', ');
    }

    /**
     * KPI hari ini baru boleh diisi mulai 2 jam sebelum jadwal pulang.
     */
    public function peringatanBatasWaktu(Karyawan $karyawan, string $tanggal): ?string
    {
        if ($tanggal !== now()->toDateString() || $karyawan->is_whitelist) {
            return null;
        }

        $kodeJamKerja = Presensi::where('nik', $karyawan->nik)->whereDate('tgl_presensi', $tanggal)->value('kode_jam_kerja');
        $jamKerja = $kodeJamKerja
            ? JamKerja::find($kodeJamKerja)
            : $this->jadwalKerja->untukHari(
                $karyawan->nik,
                $karyawan->kode_dept,
                $karyawan->kode_cabang,
                $this->jadwalKerja->namaHari(date('D', strtotime($tanggal)))
            )[0];

        if (! $jamKerja || empty($jamKerja->jam_pulang)) {
            return null;
        }

        $batasMulai = Carbon::parse($tanggal.' '.$jamKerja->jam_pulang)->subHours(2);

        return now()->lt($batasMulai)
            ? 'KPI hari ini baru dapat diisi mulai pukul '.$batasMulai->format('H:i').' (2 jam sebelum jadwal pulang Anda pukul '.date('H:i', strtotime($jamKerja->jam_pulang)).').'
            : null;
    }

    /**
     * Tambahkan nama_atasan_display & nama_hr_display untuk ditampilkan.
     */
    public function tandaiNamaApprover(KPIDaily $kpi): KPIDaily
    {
        $kpi->nama_atasan_display = $kpi->approve_atasan
            ? (Karyawan::where('nik', $kpi->approve_atasan)->value('nama_lengkap') ?? $kpi->approve_atasan)
            : '-';
        $kpi->nama_hr_display = $kpi->approve_hr
            ? (User::whereKey($kpi->approve_hr)->value('name') ?? $kpi->approve_hr)
            : '-';

        return $kpi;
    }

    /**
     * Filter periode di halaman KPI: bulan/tahun terpilih (default periode berjalan) & daftar label periode.
     */
    public function periode($reqBulan = null, $reqTahun = null): array
    {
        $periodeIni = PeriodeKerja::dari();
        $bulan = $reqBulan ?: $periodeIni->bulanKe();
        $tahun = $reqTahun ?: $periodeIni->tahun();

        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $periodeList = [];
        for ($i = 1; $i <= 12; $i++) {
            $periodeList[$i] = '26 '.$namabulan[$i === 1 ? 12 : $i - 1].' - 25 '.$namabulan[$i];
        }

        [$tglAwal, $tglAkhir] = PeriodeKerja::bulan($bulan, $tahun)->range();

        return compact('bulan', 'tahun', 'periodeList', 'tglAwal', 'tglAkhir', 'namabulan');
    }
}
