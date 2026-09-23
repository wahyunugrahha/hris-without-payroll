<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Karyawan dengan password default (dibuat admin / import) hanya boleh membuka halaman ganti password.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $karyawan = auth('karyawan')->user();

        if ($karyawan?->must_change_password
            && ! $request->routeIs('karyawan.profile.edit', 'karyawan.profile.update', 'proseslogout')) {
            return redirect()
                ->route('karyawan.profile.edit')
                ->with('warning', 'Demi keamanan, silakan ganti password default Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
