<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password tidak boleh kosong.',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::guard('user')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->route('dashboard.admin');
        }

        return back()
            ->with('warning', 'Email atau Password Salah!')
            ->withInput($request->only('email', 'remember'));
    }

    public function logout(Request $request)
    {
        Auth::guard('user')->logout();
        $this->akhiriSesi($request, 'karyawan');

        // Halaman admin yang tersimpan di cache browser tidak boleh bisa dibuka lagi setelah logout.
        return redirect()->route('loginadmin')->header('Clear-Site-Data', '"cache"');
    }
}
