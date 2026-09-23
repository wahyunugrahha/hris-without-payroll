<?php

use App\Http\Middleware\CheckWajibPresensi;
use App\Http\Middleware\EnsurePasswordChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::middleware(['web', 'auth:user'])
                ->group(base_path('routes/admin.php'));

            Route::middleware(['web', 'auth:karyawan', 'password_changed'])
                ->group(base_path('routes/karyawan.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman admin diarahkan ke login admin, sisanya ke login karyawan.
        $middleware->redirectGuestsTo(fn (Request $request) => in_array('auth:user', $request->route()?->gatherMiddleware() ?? [], true)
            ? route('loginadmin')
            : route('login'));

        // Yang sudah login dan membuka halaman login diarahkan ke dashboard guard-nya.
        $middleware->redirectUsersTo(fn () => Auth::guard('user')->check()
            ? route('dashboard.admin')
            : route('dashboard.karyawan'));

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'wajib_presensi' => CheckWajibPresensi::class,
            'password_changed' => EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
