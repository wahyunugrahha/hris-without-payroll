<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua respons web.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Halaman tidak boleh di-iframe situs lain (clickjacking pada tombol approve/hapus).
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // Kamera & lokasi dipakai absen (hanya oleh aplikasi ini).
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        return $response;
    }
}
