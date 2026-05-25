<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Cek jika request ke halaman admin
            if ($request->is('panel') || $request->is('panel/*') || 
                $request->is('karyawan*') || $request->is('departemen*') || 
                $request->is('presensi/monitoring*') || $request->is('presensi/laporan*') || 
                $request->is('presensi/rekap*') || $request->is('presensi/izinsakit*') ||
                $request->is('cabang*') || $request->is('konfigurasi*') || 
                $request->is('cuti*') || $request->is('harilibur*')) {
                return route('loginadmin');
            } 
            
            return route('login');
        }

        return null;
    }
}
