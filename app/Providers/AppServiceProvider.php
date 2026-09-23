<?php

namespace App\Providers;

use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\Lembur;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Rate limiter kustom dengan pesan bahasa Indonesia
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->input('nik').'|'.$request->ip())
                ->response(function () {
                    return back()->with('warning', 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.');
                });
        });

        RateLimiter::for('register', function ($request) {
            return Limit::perMinute(3)->by($request->ip())
                ->response(function () {
                    return back()->with('warning', 'Terlalu banyak percobaan registrasi. Silakan coba lagi dalam 1 menit.');
                });
        });

        $targetViews = ['layouts.admin.tabler', 'layouts.master'];

        View::composer($targetViews, function ($view) {

            $authUser = Auth::guard('user')->user();

            // Default Values
            $notifData = [
                'totalPending' => 0,
                'recentItems' => collect([]),
                'authUser' => $authUser,
                'userDept' => null,
                'avatarUrl' => asset('assets/img/nophoto.png'),
            ];

            // Jika user belum login, kirim data default saja
            if (! $authUser) {
                $view->with($notifData);

                return;
            }

            // 1. Setup Data User Profile
            $notifData['userDept'] = $authUser->departemen?->nama_dept
                ?? $authUser->cabang?->nama_cabang
                ?? 'Karyawan';

            if ($authUser->foto) {
                $notifData['avatarUrl'] = asset('storage/uploads/karyawan/'.$authUser->foto);
            }

            // 2. Cek Role & Cabang untuk filtering query
            $isAdminCabang = $authUser->isAdminCabang();
            $kodeCabang = $isAdminCabang ? $authUser->kode_cabang : null;

            // 3. Hitung Jumlah Pending (Counts)
            $countIzin = Izin::query()
                ->where('status_approved', 0)
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->count();

            $countDinas = DinasLuar::query()
                ->where('status_acc', 'menunggu')
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->count();

            $countLembur = Lembur::query()
                ->where('status_approved', 0)
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->count();

            $notifData['totalPending'] = $countIzin + $countDinas + $countLembur;

            // 4. Ambil Data Recent Items (Convert ke Array Native)

            // --- DATA IZIN ---
            $izins = Izin::with(['karyawan:nik,nama_lengkap,kode_cabang', 'masterCuti:kode_cuti,nama_cuti'])
                ->where('status_approved', 0)
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->latest()
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    $statusCode = strtolower((string) ($item->status ?? ''));

                    $izinType = match ($statusCode) {
                        's' => 'Izin Sakit',
                        'c' => 'Pengajuan Cuti',
                        'r' => 'Izin Roster',
                        't' => 'Izin Terlambat',
                        'p' => 'Izin Pulang Cepat',
                        default => 'Izin Absen',
                    };

                    if ($statusCode === 'c' && ! empty($item->masterCuti?->nama_cuti)) {
                        $izinType .= ' - '.$item->masterCuti->nama_cuti;
                    }

                    return [
                        'type' => $izinType,
                        'icon' => 'ambulance',
                        'color' => 'danger',
                        'desc' => ($item->karyawan->nama_lengkap ?? '-').' • Kode: '.($item->kode_izin ?? '-'),
                        'time' => $item->created_at,
                        'url' => url('/presensi/izinsakit'),
                    ];
                })
                ->all();

            // --- DATA DINAS LUAR ---
            $dinas = DinasLuar::with('karyawan:nik,nama_lengkap,kode_cabang')
                ->where('status_acc', 'menunggu')
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($item) => [
                    'type' => 'Dinas Luar',
                    'icon' => 'map-pin',
                    'color' => 'success',
                    'desc' => $item->karyawan->nama_lengkap.' - '.Str::limit($item->lokasi_tujuan, 15),
                    'time' => $item->created_at,
                    'url' => route('dinasluars.approval'),
                ])
                ->all();

            // --- DATA LEMBUR ---
            $lembur = Lembur::with('karyawan:nik,nama_lengkap,kode_cabang')
                ->where('status_approved', 0)
                ->when($kodeCabang, fn ($q) => $q->whereHas('karyawan', fn ($k) => $k->where('kode_cabang', $kodeCabang)))
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn ($item) => [
                    'type' => 'Lembur',
                    'icon' => 'clock',
                    'color' => 'primary',
                    'desc' => $item->karyawan->nama_lengkap.' ('.date('d/m', strtotime($item->tanggal_lembur)).')',
                    'time' => $item->created_at,
                    'url' => route('admin.lembur.approval'),
                ])
                ->all();

            // 5. Penggabungan Array Native
            // Menggabungkan array murni, tidak akan ada konflik tipe data
            $mergedArray = array_merge($izins, $dinas, $lembur);

            // Bungkus kembali menjadi Base Collection HANYA untuk fitur sorting
            $notifData['recentItems'] = collect($mergedArray)
                ->sortByDesc('time')
                ->take(5);

            $view->with($notifData);
        });

        View::composer(['layouts.presensi'], function ($view) {
            $karyawan = Auth::guard('karyawan')->user();
            $izinNotifications = collect();

            if ($karyawan) {
                $decisionColumn = Schema::hasColumn('izin', 'status_decided_at') ? 'status_decided_at' : 'updated_at';

                $izinNotifications = Izin::query()
                    ->with('masterCuti')
                    ->where('nik', $karyawan->nik)
                    ->whereIn('status_approved', [1, 2])
                    ->whereNotNull($decisionColumn)
                    ->where($decisionColumn, '>=', Carbon::now()->subDay())
                    ->orderByDesc($decisionColumn)
                    ->limit(10)
                    ->get()
                    ->map(function ($item) use ($decisionColumn) {
                        $jenis = match ($item->status) {
                            'r' => 'Roster',
                            'c' => $item->masterCuti->nama_cuti ?? 'Cuti',
                            's' => 'Sakit',
                            't' => 'Izin Terlambat',
                            'p' => 'Izin Pulang Cepat',
                            default => 'Izin',
                        };

                        $decisionTimeRaw = data_get($item, $decisionColumn);
                        $decisionTime = null;
                        if (! empty($decisionTimeRaw)) {
                            try {
                                $decisionTime = Carbon::parse($decisionTimeRaw);
                            } catch (\Throwable $e) {
                                $decisionTime = null;
                            }
                        }

                        return [
                            'kode_izin' => $item->kode_izin,
                            'status' => (int) $item->status_approved,
                            'status_label' => ((int) $item->status_approved === 1) ? 'Disetujui' : 'Ditolak',
                            'jenis' => $jenis,
                            'updated_at' => $decisionTime ? $decisionTime->format('Y-m-d H:i:s') : null,
                            'updated_human' => $decisionTime ? $decisionTime->diffForHumans() : '-',
                            'catatan_ditolak' => ((int) $item->status_approved === 2) ? ($item->catatan_ditolak ?: null) : null,
                            'detail_url' => url('/pengajuanizin/index?detail_izin='.$item->kode_izin),
                        ];
                    })
                    ->values();
            }

            $view->with('karyawanIzinNotifications', $izinNotifications);
        });
    }
}
