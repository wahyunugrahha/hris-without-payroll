<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman cetak laporan dibuka lewat GET (bisa di-refresh / dibuka ulang). Bila dibuka tanpa
 * periode (mis. alamat diketik langsung atau tab dipulihkan), arahkan kembali ke form laporannya.
 *
 *   ->middleware('laporan_periode:presensi.rekap')
 */
class LaporanPerluPeriode
{
    public function handle(Request $request, Closure $next, string $routeForm): Response
    {
        if ($request->isMethod('GET') && (! $request->filled('bulan') || ! $request->filled('tahun'))) {
            return redirect()->route($routeForm)->with('warning', 'Pilih periode laporan terlebih dahulu.');
        }

        return $next($request);
    }
}
