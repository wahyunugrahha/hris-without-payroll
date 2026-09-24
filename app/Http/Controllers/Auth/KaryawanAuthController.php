<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\RegistrationToken;
use App\Services\FotoKaryawanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class KaryawanAuthController extends Controller
{
    private function findValidRegistrationToken(?string $tokenStr): ?RegistrationToken
    {
        if (! $tokenStr) {
            return null;
        }

        return RegistrationToken::where('token', $tokenStr)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ], [
            'nik.required' => 'NIK tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
        ]);

        $karyawan = Karyawan::where('nik', $request->nik)->first();

        // Cek status terlebih dahulu sebelum password (sesuai permintaan user untuk kemudahan info)
        if ($karyawan && $karyawan->status_aktif !== Karyawan::STATUS_AKTIF) {
            if ($karyawan->status_aktif === Karyawan::STATUS_MENUNGGU_APPROVAL) {
                return back()
                    ->with('warning', 'Akun Anda sedang menunggu approval HRD.')
                    ->withInput($request->only('nik'));
            } else {
                return back()
                    ->with('error_swal', 'Akun Anda telah dinonaktifkan karena pelanggaran berkelanjutan atau alasan lain. Silakan temui HRD.')
                    ->withInput($request->only('nik'));
            }
        }

        $credentials = $request->only('nik', 'password');
        $remember = $request->has('remember');

        if (Auth::guard('karyawan')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->route('dashboard.karyawan');
        }

        return back()
            ->with('warning', 'NIK atau Password Salah!')
            ->withInput($request->only('nik'));
    }

    // --- Self Registration ---
    public function preregistrasi()
    {
        // Jika sudah login, arahkan ke dashboard
        if (Auth::guard('karyawan')->check()) {
            return redirect()->route('dashboard.karyawan');
        }

        return view('auth.preregistrasi');
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ], [
            'token.required' => 'Token registrasi harus diisi.',
        ]);

        // Bersihkan token dari spasi (misal user input "123 456")
        $tokenInput = str_replace(' ', '', $request->token);

        $token = $this->findValidRegistrationToken($tokenInput);

        if (! $token) {
            return back()->with('warning', 'Token registrasi tidak valid atau sudah kadaluwarsa.');
        }

        // Simpan ke session
        session(['registration_token' => $tokenInput]);

        return redirect()->route('registrasi');
    }

    public function registrasi()
    {
        // Cek apakah token ada di session
        if (! session()->has('registration_token')) {
            return redirect()->route('registrasi.pretoken')->with('warning', 'Silakan masukkan token registrasi terlebih dahulu.');
        }

        // Validasi ulang token di DB untuk mencegah bypass saat token kadaluarsa/nonaktif
        $tokenStr = session('registration_token');
        $token = $this->findValidRegistrationToken($tokenStr);

        if (! $token) {
            session()->forget('registration_token');

            return redirect()->route('registrasi.pretoken')->with('warning', 'Token Anda sudah tidak berlaku.');
        }

        return view('auth.registrasi');
    }

    public function storeRegistrasi(Request $request, FotoKaryawanService $foto)
    {
        // Re-validate token on store
        $tokenStr = session('registration_token');
        $token = $this->findValidRegistrationToken($tokenStr);
        if (! $token) {
            session()->forget('registration_token');

            return redirect()->route('registrasi.pretoken')->with('warning', 'Token registrasi tidak valid atau sudah kadaluwarsa.');
        }
        $validated = $request->validate([
            // Identitas
            'nik' => 'required|alpha_num|max:50|unique:karyawan,nik',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nama_ibu_kandung' => 'required|string|max:255',

            // Kontak & Info
            'nama_panggilan' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'alamat' => 'required|string',
            'agama' => 'required|string|max:255',
            'status_pernikahan' => 'required|string|max:255',
            'status_ptkp' => 'required|string|max:10',
            'pendidikan_terakhir' => 'required|string|max:255',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',

            // Darurat & Bank
            'nama_darurat' => 'required|string|max:255',
            'no_darurat' => 'required|string|max:255',
            'hubungan_darurat' => 'required|string|max:255',
            'no_rekening' => 'nullable|string|max:255',
            'no_bpjs_kesehatan' => 'nullable|string|max:255',
            'foto_bpjs_kesehatan' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',

        ]);

        // Hanya field tervalidasi yang disimpan, agar field sensitif lain di $fillable tidak bisa diisi dari form.
        $data = Arr::except($validated, ['foto', 'foto_bpjs_kesehatan']);
        $data['password'] = Hash::make('123456'); // Default password

        // Auto-filled data
        $data['status_aktif'] = Karyawan::STATUS_MENUNGGU_APPROVAL;
        $data['is_whitelist'] = false;
        $data['tmt'] = Carbon::today();
        $data['kode_dept'] = null;
        $data['kode_cabang'] = null;
        $data['jabatan_id'] = null;

        foreach (['foto', 'foto_bpjs_kesehatan'] as $jenis) {
            if ($request->hasFile($jenis)) {
                $data[$jenis] = $foto->ganti($jenis, $request->file($jenis), null, $data['nik']);
            }
        }

        Karyawan::create($data);

        // Optional: Mark token as used if you want one-time tokens per user
        // But since it's displayed on TV for multiple users, we keep it active until refresh.

        session()->forget('registration_token');

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan menunggu approval dari HR.');
    }

    public function logout(Request $request)
    {
        Auth::guard('karyawan')->logout();
        $this->akhiriSesi($request, 'user');

        return redirect()->route('login');
    }
}
