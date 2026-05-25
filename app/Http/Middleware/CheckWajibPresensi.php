<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWajibPresensi
{   
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $karyawan = auth('karyawan')->user();

        if ($karyawan && (int) $karyawan->is_whitelist === 1) {
            return redirect()
                ->route('dashboard.karyawan')
                ->with('error', 'Akun Anda dikecualikan dari sistem presensi harian.');
        }

        return $next($request);
    }
}
