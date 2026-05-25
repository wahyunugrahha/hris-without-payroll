<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($guard === 'karyawan') {
                    return redirect(RouteServiceProvider::HOME);
                }

                if ($guard === 'user') {
                    return redirect(RouteServiceProvider::HOMEADMIN);
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        // Fallback: jika user sudah login dengan guard lain,
        // tetap arahkan ke halaman yang sesuai.
        if (Auth::guard('user')->check()) {
            return redirect(RouteServiceProvider::HOMEADMIN);
        }

        if (Auth::guard('karyawan')->check()) {
            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }
}
