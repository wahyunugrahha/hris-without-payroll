<?php

namespace App\Http\Controllers\Karyawan\Izin;

use App\Http\Controllers\Controller;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Services\IzinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Dasar controller per jenis pengajuan izin (absen, sakit, terlambat, pulang cepat, cuti, roster).
 */
abstract class IzinController extends Controller
{
    public function __construct(protected IzinService $izin) {}

    protected function karyawan(): Karyawan
    {
        return Auth::guard('karyawan')->user();
    }

    /**
     * Pengajuan milik karyawan yang login, masih pending, dan sesuai jenisnya.
     * Semua edit/update wajib lewat sini agar tidak bisa mengubah milik orang lain / yang sudah diverifikasi.
     */
    protected function pendingMilikSendiri(string $kodeIzin, string $jenis): ?Izin
    {
        return Izin::milik($this->karyawan()->nik)->pending()->jenis($jenis)->find($kodeIzin);
    }

    protected function tidakBisaDiubah(): RedirectResponse
    {
        return back()->with('error', 'Pengajuan tidak ditemukan atau sudah diverifikasi sehingga tidak bisa diubah.');
    }

    protected function keDaftarIzin(string $type, string $message): RedirectResponse
    {
        return redirect('/presensi/izin')->with($type, $message);
    }

    protected function tolak(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
