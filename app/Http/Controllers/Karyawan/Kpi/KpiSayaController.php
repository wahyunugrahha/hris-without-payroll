<?php

namespace App\Http\Controllers\Karyawan\Kpi;

use App\Http\Controllers\Controller;
use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\KPIDaily;
use App\Models\KPIDailyDetail;
use App\Models\KPIDailyExtra;
use App\Models\KPIMaster;
use App\Models\Presensi;
use App\Services\KpiKaryawanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * KPI harian milik karyawan yang login: riwayat, isi, dan ubah (draft / kirim ke atasan).
 */
class KpiSayaController extends Controller
{
    public function __construct(private KpiKaryawanService $kpi) {}

    public function indexKPI(Request $request)
    {
        $user = Auth::guard('karyawan')->user();
        $periode = $this->kpi->periode($request->bulan, $request->tahun);

        $riwayatKPI = KPIDaily::where('kpi_daily.nik', $user->nik)
            ->whereBetween('kpi_daily.tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
            ->leftJoin('kpi_leaderboard_snapshots', function ($join) use ($user) {
                $join->on('kpi_daily.tanggal', '=', 'kpi_leaderboard_snapshots.date')
                    ->where('kpi_leaderboard_snapshots.nik', '=', $user->nik);
            })
            ->select('kpi_daily.*', 'kpi_leaderboard_snapshots.points as daily_points')
            ->orderBy('kpi_daily.tanggal', 'asc')
            ->get()
            ->map(fn ($item) => $this->kpi->tandaiNamaApprover($item));

        return view('karyawan.kpi.indexkpi', [
            'riwayatKPI' => $riwayatKPI,
            'kpiHariIni' => KPIDaily::where('nik', $user->nik)->whereDate('tanggal', date('Y-m-d'))->first(),
            'periodeList' => $periode['periodeList'],
            'tahunSekarang' => date('Y'),
            'tahunMulai' => date('Y') - 5,
            'reqBulan' => $periode['bulan'],
            'reqTahun' => $periode['tahun'],
            'isConfigured' => KPIMaster::untukKaryawan($user) !== null,
            'bawahanBelumIsi' => $this->kpi->bawahanBelumIsi($user, date('Y-m-d')),
        ]);
    }

    public function createKPI()
    {
        $user = Auth::guard('karyawan')->user();
        $tanggal = now()->toDateString();

        // 1. Wajib sudah presensi masuk / izin terlambat disetujui / dinas luar (kecuali whitelist).
        if (! $user->is_whitelist) {
            $cekPresensi = Presensi::where('nik', $user->nik)->whereDate('tgl_presensi', $tanggal)->exists();
            $isIzinT = ! $cekPresensi && Izin::where('nik', $user->nik)->where('status', 't')->where('status_approved', '1')
                ->whereDate('tgl_izin_dari', '<=', $tanggal)->whereDate('tgl_izin_sampai', '>=', $tanggal)->exists();
            $isDinasLuar = ! $cekPresensi && ! $isIzinT && DinasLuar::where('nik', $user->nik)->where('status_acc', 'acc')
                ->whereDate('tgl_mulai', '<=', $tanggal)->whereDate('tgl_selesai', '>=', $tanggal)->exists();

            if (! $cekPresensi && ! $isIzinT && ! $isDinasLuar) {
                return redirect()->route('kpi.user.index')->with('error', 'Akses ditolak. Anda belum melakukan Presensi Masuk, atau Izin Tugas yang valid hari ini.');
            }
        }

        // 2. Batas waktu pengisian & bawahan harus sudah mengisi.
        if ($peringatan = $this->kpi->peringatanBatasWaktu($user, $tanggal) ?? $this->kpi->peringatanBawahan($user, $tanggal)) {
            return redirect()->route('kpi.user.index')->with('error', $peringatan);
        }

        $master = KPIMaster::untukKaryawan($user);
        if (! $master) {
            return redirect()->route('kpi.user.index')->with('error_template', true);
        }

        $kpiHariIni = KPIDaily::where('nik', $user->nik)->whereDate('tanggal', $tanggal)->first();
        if ($kpiHariIni) {
            return redirect()->route('kpi.user.edit', $kpiHariIni->id)->with('warning', 'Anda sudah memiliki draft KPI hari ini.');
        }

        return view('karyawan.kpi.createkpi', [
            'user' => $user,
            'kpiMaster' => $master,
            'indikators' => $this->kpi->indikator($master),
            'tanggal' => $tanggal,
            'details' => collect([]),
        ]);
    }

    public function storeKPI(Request $request)
    {
        return $this->simpan($request);
    }

    public function editKPI($kpi_daily_id)
    {
        $user = Auth::guard('karyawan')->user();
        $kpiDaily = $this->kpi->tandaiNamaApprover(KPIDaily::where('nik', $user->nik)->findOrFail($kpi_daily_id));
        $tanggalKPI = $kpiDaily->tanggal->format('Y-m-d');

        if ($peringatan = $this->kpi->peringatanBawahan($user, $tanggalKPI) ?? $this->kpi->peringatanBatasWaktu($user, $tanggalKPI)) {
            return redirect()->route('kpi.user.index')->with('error', $peringatan);
        }

        $master = KPIMaster::untukKaryawan($user);

        return view('karyawan.kpi.editkpi', [
            'user' => $user,
            'kpiMaster' => $master,
            'indikators' => $this->kpi->indikator($master),
            'tanggal' => $tanggalKPI,
            'kpiDaily' => $kpiDaily,
            'details' => KPIDailyDetail::where('kpi_daily_id', $kpiDaily->id)->get()->keyBy('kpi_master_detail_id'),
            'extras' => KPIDailyExtra::where('kpi_daily_id', $kpiDaily->id)->get(),
            'isApproved' => in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr']),
            'isEdit' => true,
            'namaAtasan' => $kpiDaily->nama_atasan_display,
            'namaHR' => $kpiDaily->nama_hr_display,
            'roleInput' => 'self',
        ]);
    }

    public function updateKPI(Request $request, $kpi_daily_id)
    {
        return $this->simpan($request, $kpi_daily_id);
    }

    /**
     * Simpan KPI hari ini (baru) atau KPI yang sudah ada milik sendiri, sebagai draft atau dikirim ke atasan.
     */
    private function simpan(Request $request, $kpiDailyId = null)
    {
        $validator = Validator::make($request->all(), [
            'action_type' => 'required|in:draft,submitted',
            'kpi' => 'required|array',
            'kpi.*.foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'extras' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Data tidak valid. Mohon periksa inputan Anda.')->withInput();
        }

        $user = Auth::guard('karyawan')->user();

        // Hanya indikator dari master KPI milik karyawan ini yang boleh diisi (skor diambil dari master).
        $indikator = $this->kpi->indikator(KPIMaster::untukKaryawan($user))->keyBy('id');
        if (array_diff(array_keys($request->kpi), $indikator->keys()->all())) {
            return back()->with('error', 'Indikator KPI tidak sesuai dengan master KPI Anda.')->withInput();
        }

        $kpiDaily = $kpiDailyId
            ? KPIDaily::where('nik', $user->nik)->findOrFail($kpiDailyId)
            : KPIDaily::firstOrNew(['nik' => $user->nik, 'tanggal' => now()->toDateString()], ['status' => 'draft']);
        $tanggal = $kpiDaily->tanggal instanceof \DateTimeInterface ? $kpiDaily->tanggal->format('Y-m-d') : (string) $kpiDaily->tanggal;

        if (in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr'])) {
            return back()->with('error', 'Laporan sudah disetujui, tidak bisa diubah.');
        }

        if (! $user->is_whitelist) {
            $sedangCuti = Izin::where('nik', $user->nik)
                ->whereIn('status', ['c', 'r', 's', 'i'])
                ->where('status_approved', '1')
                ->whereDate('tgl_izin_dari', '<=', $tanggal)
                ->whereDate('tgl_izin_sampai', '>=', $tanggal)
                ->exists();

            if ($sedangCuti) {
                return back()->with('error', 'Anda tidak dapat memproses KPI karena tercatat sedang Cuti / Roster / Sakit / Izin pada tanggal tersebut.');
            }
        }

        if ($peringatan = $this->kpi->peringatanBatasWaktu($user, $tanggal)) {
            return back()->with('error', $peringatan);
        }

        try {
            DB::transaction(function () use ($request, $kpiDaily, $indikator) {
                $kpiDaily->save();

                foreach ($request->kpi as $masterDetailId => $data) {
                    $existing = KPIDailyDetail::where('kpi_daily_id', $kpiDaily->id)->where('kpi_master_detail_id', $masterDetailId)->first();

                    $pathFoto = $existing->bukti_foto ?? null;
                    if ($request->hasFile("kpi.$masterDetailId.foto")) {
                        if ($existing?->bukti_foto) {
                            Storage::disk('public')->delete($existing->bukti_foto);
                        }
                        $pathFoto = $request->file("kpi.$masterDetailId.foto")->store('uploads/kpi_bukti', 'public');
                    }

                    $isChecked = isset($data['is_checked']) ? 1 : 0;

                    KPIDailyDetail::updateOrCreate(
                        ['kpi_daily_id' => $kpiDaily->id, 'kpi_master_detail_id' => $masterDetailId],
                        [
                            'score' => $isChecked ? ($indikator[$masterDetailId]->score_indikator ?? 0) : 0,
                            'catatan' => $data['catatan'] ?? null,
                            'bukti_foto' => $pathFoto,
                            'is_checked' => $isChecked,
                        ]
                    );
                }

                KPIDailyExtra::where('kpi_daily_id', $kpiDaily->id)->delete();
                foreach ($request->input('extras', []) as $extra) {
                    if (! empty($extra['judul'])) {
                        KPIDailyExtra::create([
                            'kpi_daily_id' => $kpiDaily->id,
                            'indikator_tambahan' => $extra['judul'],
                            'catatan' => $extra['catatan'] ?? null,
                            'score' => 10,
                        ]);
                    }
                }

                $kpiDaily->status = $request->input('action_type');
                if ($kpiDaily->status === 'submitted') {
                    $kpiDaily->alasan_reject = null;
                }
                $kpiDaily->save();
            });

            return redirect()->route('kpi.user.index')
                ->with('success', $kpiDaily->status === 'submitted' ? 'Laporan berhasil dikirim ke atasan.' : 'Draft berhasil disimpan.');
        } catch (Throwable $e) {
            return back()->with('error', $this->failMessage('Terjadi kesalahan sistem.', $e))->withInput();
        }
    }
}
