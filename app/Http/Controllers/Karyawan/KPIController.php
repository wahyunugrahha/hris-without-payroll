<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\DinasLuar;
use App\Models\Izin;
use App\Models\JamKerja;
use App\Models\Karyawan;
use App\Models\KonfigurasiJkDept;
use App\Models\KonfigurasiJkDeptDetail;
use App\Models\KPIAtasanDaily;
use App\Models\KPIAtasanDailyDetail;
use App\Models\KPIDaily;
use App\Models\KPIDailyDetail;
use App\Models\KPIDailyExtra;
use App\Models\KPIMaster;
use App\Models\KPIMasterAtasan;
use App\Models\KPIMasterDetail;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class KPIController extends Controller
{
    // ==========================================
    // BAGIAN KARYAWAN
    // ==========================================

    public function indexKPI(Request $request)
    {
        $user = Auth::guard('karyawan')->user();
        $dataMaster = $this->getKpiMasterData($user);

        $periode = $this->getPeriodeData($request->bulan, $request->tahun);

        $riwayatKPI = KPIDaily::where('kpi_daily.nik', $user->nik)
            ->whereBetween('kpi_daily.tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
            ->leftJoin('kpi_leaderboard_snapshots', function ($join) use ($user) {
                $join->on('kpi_daily.tanggal', '=', 'kpi_leaderboard_snapshots.date')
                    ->where('kpi_leaderboard_snapshots.nik', '=', $user->nik);
            })
            ->select('kpi_daily.*', 'kpi_leaderboard_snapshots.points as daily_points')
            ->orderBy('kpi_daily.tanggal', 'asc')
            ->get()
            ->map(fn ($item) => $this->setApproverNames($item));

        $kpiHariIni = KPIDaily::where('nik', $user->nik)->whereDate('tanggal', date('Y-m-d'))->first();

        return view('karyawan.kpi.indexkpi', [
            'riwayatKPI' => $riwayatKPI,
            'kpiHariIni' => $kpiHariIni,
            'periodeList' => $periode['periodeList'],
            'tahunSekarang' => date('Y'),
            'tahunMulai' => date('Y') - 5,
            'reqBulan' => $periode['bulan'],
            'reqTahun' => $periode['tahun'],
            'isConfigured' => ! empty($dataMaster['master']),
            'bawahanBelumIsi' => $this->cekBawahanBelumIsiKPI($user, date('Y-m-d')),
        ]);
    }

    public function createKPI()
    {
        $user = Auth::guard('karyawan')->user();
        $roleInput = $this->checkRoleAksesKPI($user);

        if ($roleInput === false) {
            return redirect()->route('kpi.user.index')->with('error', 'Akses ditolak.');
        }
        $tanggal = now()->toDateString();

        // 1. Validasi Presensi (Diabaikan jika whitelist)
        if (! $user->is_whitelist) {
            $cekPresensi = Presensi::where('nik', $user->nik)->whereDate('tgl_presensi', $tanggal)->first();
            $isIzinT = ! $cekPresensi && Izin::where('nik', $user->nik)->where('status', 't')->where('status_approved', '1')
                ->whereDate('tgl_izin_dari', '<=', $tanggal)->whereDate('tgl_izin_sampai', '>=', $tanggal)->exists();
            $isDinasLuar = ! $cekPresensi && ! $isIzinT && DinasLuar::where('nik', $user->nik)->where('status_acc', 'acc')
                ->whereDate('tgl_mulai', '<=', $tanggal)->whereDate('tgl_selesai', '>=', $tanggal)->exists();

            if (! $cekPresensi && ! $isIzinT && ! $isDinasLuar) {
                return redirect()->route('kpi.user.index')->with('error', 'Akses ditolak. Anda belum melakukan Presensi Masuk, atau Izin Tugas yang valid hari ini.');
            }
        }

        // 1b. Validasi Jam Isi KPI (Maksimal 2 Jam Sebelum Pulang)
        $timeLimitWarning = $this->checkKpiTimeLimits($user, $tanggal);
        if ($timeLimitWarning) {
            return redirect()->route('kpi.user.index')->with('error', $timeLimitWarning);
        }

        // 2. Validasi Bawahan
        $warningBawahan = $this->checkBawahanKpiWarning($user, $tanggal);
        if ($warningBawahan) {
            return redirect()->route('kpi.user.index')->with('error', $warningBawahan);
        }

        // 3. Persiapan Data View
        $data = $this->getKpiMasterData($user, $roleInput);
        if (! $data['master']) {
            return redirect()->route('kpi.user.index')->with('error_template', true);
        }

        $cekDataHariIni = KPIDaily::where('nik', $user->nik)->whereDate('tanggal', $tanggal)->first();
        if ($cekDataHariIni) {
            return redirect()->route('kpi.user.edit', $cekDataHariIni->id)->with('warning', 'Anda sudah memiliki draft KPI hari ini.');
        }

        return view('karyawan.kpi.createkpi', [
            'user' => $user,
            'kpiMaster' => $data['master'],
            'indikators' => $data['indikators'],
            'tanggal' => $tanggal,
            'details' => collect([]),
        ]);
    }

    public function storeKPI(Request $request)
    {
        return $this->processKPI($request, 'store');
    }

    public function editKPI($kpi_daily_id)
    {
        $user = Auth::guard('karyawan')->user();
        $kpiDaily = KPIDaily::where('nik', $user->nik)->findOrFail($kpi_daily_id);
        $targetUser = $kpiDaily->karyawan;
        $roleInput = $this->checkRoleAksesKPI($targetUser);

        if ($roleInput === false) {
            abort(403, 'Akses Ditolak. Anda tidak berhak melihat data KPI ini.');
        }
        $tanggalKPI = $kpiDaily->tanggal->format('Y-m-d');

        // Validasi Bawahan & Waktu Pengisian
        if ($roleInput == 'self') {
            $warningBawahan = $this->checkBawahanKpiWarning($targetUser, $tanggalKPI);
            if ($warningBawahan) {
                return redirect()->route('kpi.user.index')->with('error', $warningBawahan);
            }

            $timeLimitWarning = $this->checkKpiTimeLimits($targetUser, $tanggalKPI);
            if ($timeLimitWarning) {
                return redirect()->route('kpi.user.index')->with('error', $timeLimitWarning);
            }
        }

        // Setup Relasi & Nama Approver
        $kpiDaily = $this->setApproverNames($kpiDaily);
        $data = $this->getKpiMasterData($targetUser, 'self');

        $details = KPIDailyDetail::where('kpi_daily_id', $kpiDaily->id)->get()->keyBy('kpi_master_detail_id');
        $extras = KPIDailyExtra::where('kpi_daily_id', $kpiDaily->id)->get();

        $viewName = ($roleInput == 'atasan') ? 'karyawan.kpi.detailatasankpi' : 'karyawan.kpi.editkpi';

        return view($viewName, [
            'user' => $targetUser,
            'kpiMaster' => $data['master'],
            'indikators' => $data['indikators'],
            'tanggal' => $tanggalKPI,
            'kpiDaily' => $kpiDaily,
            'details' => $details,
            'extras' => $extras,
            'isApproved' => in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr']),
            'isEdit' => true,
            'namaAtasan' => $kpiDaily->nama_atasan_display,
            'namaHR' => $kpiDaily->nama_hr_display,
            'roleInput' => $roleInput,
        ]);
    }

    public function updateKPI(Request $request, $kpi_daily_id)
    {
        return $this->processKPI($request, 'update', $kpi_daily_id);
    }

    // ==========================================
    // BAGIAN ATASAN
    // ==========================================

    public function atasanIndex(Request $request)
    {
        $user = Auth::guard('karyawan')->user();
        $bawahan = $this->getBawahan($user);

        $periode = $this->getPeriodeData($request->bulan, $request->tahun);

        $reqNik = $request->nik;
        $penilaianAtasan = null;

        if (empty($reqNik)) {
            $riwayatKPI = KPIDaily::whereRaw('1 = 0')->get();
        } else {
            if ($bawahan->contains('nik', $reqNik)) {
                $riwayatKPI = KPIDaily::with(['karyawan', 'karyawan.jabatanRel'])
                    ->where('kpi_daily.nik', $reqNik)
                    ->whereBetween('kpi_daily.tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
                    ->leftJoin('kpi_leaderboard_snapshots', function ($join) use ($reqNik) {
                        $join->on('kpi_daily.tanggal', '=', 'kpi_leaderboard_snapshots.date')
                            ->where('kpi_leaderboard_snapshots.nik', '=', $reqNik);
                    })
                    ->select('kpi_daily.*', 'kpi_leaderboard_snapshots.points as daily_points')
                    ->orderBy('kpi_daily.tanggal', 'asc')
                    ->get()
                    ->map(fn ($item) => $this->setApproverNames($item));

                $penilaianAtasan = KPIAtasanDaily::where('nik', $reqNik)
                    ->whereBetween('tanggal', [$periode['tglAwal'], $periode['tglAkhir']])
                    ->first();
            } else {
                $riwayatKPI = KPIDaily::whereRaw('1 = 0')->get();
            }
        }

        return view('karyawan.kpi.atasankpi', [
            'riwayatKPI' => $riwayatKPI,
            'bawahan' => $bawahan,
            'periodeList' => $periode['periodeList'],
            'tahunSekarang' => date('Y'),
            'tahunMulai' => date('Y') - 5,
            'reqBulan' => $periode['bulan'],
            'reqTahun' => $periode['tahun'],
            'reqNik' => $reqNik,
            'penilaianAtasan' => $penilaianAtasan,
            'tglAwal' => $periode['tglAwal'],
            'tglAkhir' => $periode['tglAkhir'],
        ]);
    }

    public function atasanDetailKPI($kpi_daily_id)
    {
        $user = Auth::guard('karyawan')->user();

        $kpiDaily = KPIDaily::with(['karyawan.jabatanRel', 'kpiDailyDetail.kpiMasterDetail', 'kpiDailyExtra'])
            ->findOrFail($kpi_daily_id);

        $kpiDaily = $this->setApproverNames($kpiDaily);

        if ($this->checkRoleAksesKPI($kpiDaily->karyawan) !== 'atasan') {
            return redirect()->route('kpi.atasan.index')->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $targetUser = $kpiDaily->karyawan;
        $dataMasterAtasan = $this->getKpiMasterData($targetUser, 'atasan');

        $kpiAtasanDaily = KPIAtasanDaily::with('details')
            ->where('nik', $targetUser->nik)
            ->whereDate('tanggal', $kpiDaily->tanggal)
            ->first();

        $details = $kpiAtasanDaily ? $kpiAtasanDaily->details->keyBy('kpi_master_atasan_id') : collect();
        $isApproved = in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr']);

        $kpiDate = Carbon::parse($kpiDaily->tanggal);
        if ($kpiDate->day >= 26) {
            $cycleDate = $kpiDate->copy()->addMonth();
            $bulanBack = $cycleDate->format('m');
            $tahunBack = $cycleDate->format('Y');
        } else {
            $bulanBack = $kpiDate->format('m');
            $tahunBack = $kpiDate->format('Y');
        }

        return view('karyawan.kpi.detailatasankpi', [
            'kpiDaily' => $kpiDaily,
            'namaAtasan' => $kpiDaily->nama_atasan_display,
            'namaHR' => $kpiDaily->nama_hr_display,
            'nikBack' => $kpiDaily->nik,
            'bulanBack' => $bulanBack,
            'tahunBack' => $tahunBack,
            'indikators' => $dataMasterAtasan['indikators'],
            'details' => $details,
            'isApproved' => $isApproved,
        ]);
    }

    public function approveKPI(Request $request, $kpi_daily_id)
    {
        $user = Auth::guard('karyawan')->user();
        $kpiDaily = KPIDaily::findOrFail($kpi_daily_id);

        // Hanya atasan langsung (bukan diri sendiri / rekan setingkat) yang boleh approve.
        if ($this->checkRoleAksesKPI($kpiDaily->karyawan) !== 'atasan') {
            return back()->with('error', 'Akses ditolak.');
        }

        if ($kpiDaily->status != 'submitted') {
            return back()->with('error', 'Status KPI sudah berubah, tidak dapat disetujui.');
        }

        $request->validate([
            'kpi' => 'required|array',
            'kpi.*.foto' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        DB::beginTransaction();
        try {
            $kpiDaily->update([
                'status' => 'approved_by_atasan',
                'approve_atasan' => $user->nik,
                'approve_atasan_at' => now(),
                'alasan_reject' => null,
            ]);

            $penilaianAtasan = KPIAtasanDaily::updateOrCreate(
                ['nik' => $kpiDaily->nik, 'tanggal' => $kpiDaily->tanggal],
                ['input_atasan' => $user->nik, 'status' => 'submitted']
            );

            foreach ($request->kpi as $masterId => $data) {
                $existingDetail = KPIAtasanDailyDetail::where('kpi_atasan_daily_id', $penilaianAtasan->id)
                    ->where('kpi_master_atasan_id', $masterId)->first();

                $pathFoto = $existingDetail->bukti_foto ?? null;
                if ($request->hasFile("kpi.$masterId.foto")) {
                    if ($existingDetail && $existingDetail->bukti_foto) {
                        Storage::disk('public')->delete($existingDetail->bukti_foto);
                    }
                    $pathFoto = $request->file("kpi.$masterId.foto")->store('uploads/kpi_atasan', 'public');
                }

                $isChecked = (isset($data['is_checked']) && $data['is_checked'] == 1) ? 1 : 0;
                $masterAtasan = KPIMasterAtasan::find($masterId);
                $score = $isChecked ? ($masterAtasan->bobot_atasan ?? 0) : 0;

                KPIAtasanDailyDetail::updateOrCreate(
                    ['kpi_atasan_daily_id' => $penilaianAtasan->id, 'kpi_master_atasan_id' => $masterId],
                    [
                        'is_checked' => $isChecked,
                        'score' => $score,
                        'catatan' => $data['catatan'] ?? null,
                        'bukti_foto' => $pathFoto,
                    ]
                );
            }

            DB::commit();

            $kpiDate = Carbon::parse($kpiDaily->tanggal);
            if ($kpiDate->day >= 26) {
                $cycleDate = $kpiDate->copy()->addMonth();
                $bulan = $cycleDate->format('m');
                $tahun = $cycleDate->format('Y');
            } else {
                $bulan = $kpiDate->format('m');
                $tahun = $kpiDate->format('Y');
            }

            return redirect()->route('kpi.atasan.index', [
                'nik' => $kpiDaily->nik, 'bulan' => $bulan, 'tahun' => $tahun,
            ])->with('success', 'KPI Karyawan beserta Penilaian Atasan berhasil disetujui & disimpan.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Terjadi kesalahan sistem saat memproses persetujuan.', $e))->withInput();
        }
    }

    public function rejectKPI(Request $request, $kpi_daily_id)
    {
        $request->validate([
            'alasan_reject' => 'required|string',
        ]);

        $kpiDaily = KPIDaily::findOrFail($kpi_daily_id);

        if ($this->checkRoleAksesKPI($kpiDaily->karyawan) !== 'atasan') {
            return back()->with('error', 'Akses ditolak.');
        }

        if ($kpiDaily->status != 'submitted') {
            return back()->with('error', 'Status KPI sudah berubah, tidak dapat dikembalikan.');
        }

        $kpiDaily->update([
            'status' => 'rejected',
            'alasan_reject' => $request->alasan_reject,
        ]);

        $kpiDate = Carbon::parse($kpiDaily->tanggal);
        if ($kpiDate->day >= 26) {
            $cycleDate = $kpiDate->copy()->addMonth();
            $bulan = $cycleDate->format('m');
            $tahun = $cycleDate->format('Y');
        } else {
            $bulan = $kpiDate->format('m');
            $tahun = $kpiDate->format('Y');
        }

        return redirect()->route('kpi.atasan.index', [
            'nik' => $kpiDaily->nik, 'bulan' => $bulan, 'tahun' => $tahun,
        ])->with('warning', 'KPI telah dikembalikan ke karyawan untuk direvisi.');
    }

    // ==========================================
    // PRIVATE HELPER FUNCTIONS
    // ==========================================

    private function processKPI(Request $request, $action, $kpi_daily_id = null)
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

        DB::beginTransaction();
        try {
            $user = Auth::guard('karyawan')->user();
            $tanggal = now()->toDateString();

            $kpiDaily = $action == 'store'
                ? KPIDaily::firstOrCreate(['nik' => $user->nik, 'tanggal' => $tanggal], ['status' => 'draft'])
                : KPIDaily::where('nik', $user->nik)->findOrFail($kpi_daily_id);

            if (! $user->is_whitelist) {
                $sedangCuti = Izin::where('nik', $user->nik)
                    ->whereIn('status', ['c', 'r', 's', 'i'])
                    ->where('status_approved', '1')
                    ->whereDate('tgl_izin_dari', '<=', $kpiDaily->tanggal)
                    ->whereDate('tgl_izin_sampai', '>=', $kpiDaily->tanggal)
                    ->exists();

                if ($sedangCuti) {
                    DB::rollBack();

                    return back()->with('error', 'Anda tidak dapat memproses KPI karena tercatat sedang Cuti / Roster / Sakit / Izin pada tanggal tersebut.');
                }
            }

            // Validasi Limit Waktu Store/Update
            $timeLimitWarning = $this->checkKpiTimeLimits($user, $kpiDaily->tanggal->format('Y-m-d'));
            if ($timeLimitWarning) {
                DB::rollBack();

                return back()->with('error', $timeLimitWarning);
            }

            if (in_array($kpiDaily->status, ['approved_by_atasan', 'approved_by_hr'])) {
                return back()->with('error', 'Laporan sudah disetujui, tidak bisa diubah.');
            }

            if ($request->has('kpi')) {
                foreach ($request->kpi as $masterDetailId => $data) {
                    $existingDetail = KPIDailyDetail::where('kpi_daily_id', $kpiDaily->id)
                        ->where('kpi_master_detail_id', $masterDetailId)->first();

                    $pathFoto = $existingDetail->bukti_foto ?? null;
                    if ($request->hasFile("kpi.$masterDetailId.foto")) {
                        if ($existingDetail && $existingDetail->bukti_foto) {
                            Storage::disk('public')->delete($existingDetail->bukti_foto);
                        }
                        $pathFoto = $request->file("kpi.$masterDetailId.foto")->store('uploads/kpi_bukti', 'public');
                    }

                    $isChecked = isset($data['is_checked']) ? 1 : 0;
                    $score = $isChecked ? (KPIMasterDetail::find($masterDetailId)->score_indikator ?? 0) : 0;

                    KPIDailyDetail::updateOrCreate(
                        ['kpi_daily_id' => $kpiDaily->id, 'kpi_master_detail_id' => $masterDetailId],
                        ['score' => $score, 'catatan' => $data['catatan'] ?? null, 'bukti_foto' => $pathFoto, 'is_checked' => $isChecked]
                    );
                }
            }

            KPIDailyExtra::where('kpi_daily_id', $kpiDaily->id)->delete();
            if ($request->has('extras')) {
                foreach ($request->extras as $extra) {
                    if (! empty($extra['judul'])) {
                        KPIDailyExtra::create([
                            'kpi_daily_id' => $kpiDaily->id, 'indikator_tambahan' => $extra['judul'],
                            'catatan' => $extra['catatan'] ?? null, 'score' => 10,
                        ]);
                    }
                }
            }

            $actionType = $request->input('action_type');
            $kpiDaily->status = $actionType;
            if ($actionType == 'submitted') {
                $kpiDaily->alasan_reject = null;
            }
            $kpiDaily->save();

            DB::commit();

            return redirect()->route('kpi.user.index')
                ->with('success', $actionType == 'submitted' ? 'Laporan berhasil dikirim ke atasan.' : 'Draft berhasil disimpan.');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', $this->failMessage('Terjadi kesalahan sistem.', $e))->withInput();
        }
    }

    private function getKpiMasterData($user, $roleInput = 'self')
    {
        $master = KPIMaster::where('jabatan_id', $user->jabatan_id)
            ->where('kode_dept', $user->kode_dept)
            ->where('kode_cabang', $user->kode_cabang)
            ->where('is_active', true)->first();

        if (! $master) {
            $master = KPIMaster::where('jabatan_id', $user->jabatan_id)
                ->where('kode_dept', $user->kode_dept)
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        if (! $master) {
            $master = KPIMaster::where('jabatan_id', $user->jabatan_id)
                ->whereNull('kode_dept')
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        // Fallback ke jabatan "Staff" jika KPI Master spesifik tidak ditemukan
        if (! $master) {
            $master = KPIMaster::whereHas('jabatan', function ($query) {
                $query->where('nama_jabatan', 'ilike', '%staff%');
            })
                ->where('kode_dept', $user->kode_dept)
                ->where('kode_cabang', $user->kode_cabang)
                ->where('is_active', true)->first();
        }

        if (! $master) {
            $master = KPIMaster::whereHas('jabatan', function ($query) {
                $query->where('nama_jabatan', 'ilike', '%staff%');
            })
                ->where('kode_dept', $user->kode_dept)
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        if (! $master) {
            $master = KPIMaster::whereHas('jabatan', function ($query) {
                $query->where('nama_jabatan', 'ilike', '%staff%');
            })
                ->whereNull('kode_dept')
                ->whereNull('kode_cabang')
                ->where('is_active', true)->first();
        }

        $indikators = collect();
        if ($master) {
            if ($roleInput === 'self') {
                $indikators = KPIMasterDetail::where('kode_master', $master->kode_master)
                    ->where('is_active', true)->orderBy('id', 'asc')->get();
            } elseif ($roleInput === 'atasan') {
                $indikators = KPIMasterAtasan::where('kode_master', $master->kode_master)
                    ->where('is_active', true)->orderBy('id', 'asc')->get();
            }
        }

        return ['master' => $master, 'indikators' => $indikators];
    }

    protected function getBawahan($user)
    {
        $user->loadMissing('jabatanRel');
        $hierarchy = ['spv' => 1, 'pjo' => 2, 'kepala divisi' => 3, 'staff' => 4];

        $myRoleName = Role::where('id', $user->jabatanRel->role_id)->value('name');
        $myLevel = $hierarchy[strtolower($myRoleName ?? '')] ?? 99;

        if ($myLevel == 99) {
            return collect([]);
        }

        $allowedRoleNames = array_keys(array_filter($hierarchy, fn ($level) => $level > $myLevel));
        $allowedRoleIds = Role::whereIn(DB::raw('LOWER(name)'), $allowedRoleNames)->pluck('id');

        $bawahan = Karyawan::with('jabatanRel')
            ->where('kode_cabang', $user->kode_cabang)
            ->where('kode_dept', $user->kode_dept)
            ->where('nik', '!=', $user->nik)
            ->whereHas('jabatanRel', function ($q) use ($allowedRoleIds) {
                $q->whereIn('role_id', $allowedRoleIds);
            })
            ->orderBy('nama_lengkap', 'asc')->get();

        return $bawahan->filter(function ($k) {
            $dataMaster = $this->getKpiMasterData($k);

            return ! empty($dataMaster['master']);
        })->values();
    }

    private function checkRoleAksesKPI($targetUser)
    {
        $currentUser = Auth::guard('karyawan')->user();
        if ($currentUser->nik === $targetUser->nik) {
            return 'self';
        }

        $bawahan = $this->getBawahan($currentUser);
        if ($bawahan->contains('nik', $targetUser->nik)) {
            return 'atasan';
        }

        return false;
    }

    protected function cekBawahanBelumIsiKPI($user, $tanggal)
    {
        $bawahan = $this->getBawahan($user);
        if ($bawahan->isEmpty()) {
            return collect();
        }

        $bawahanNik = $bawahan->pluck('nik')->toArray();

        $hadirNik = Presensi::whereIn('nik', $bawahanNik)
            ->whereDate('tgl_presensi', $tanggal)
            ->where('status', 'h')
            ->pluck('nik')->toArray();

        $izinTugasNik = Izin::whereIn('nik', $bawahanNik)
            ->where('status', 't')
            ->where('status_approved', '1')
            ->whereDate('tgl_izin_dari', '<=', $tanggal)
            ->whereDate('tgl_izin_sampai', '>=', $tanggal)
            ->pluck('nik')->toArray();

        $dinasLuarNik = DinasLuar::whereIn('nik', $bawahanNik)
            ->where('status_acc', 'acc')
            ->whereDate('tgl_mulai', '<=', $tanggal)
            ->whereDate('tgl_selesai', '>=', $tanggal)
            ->pluck('nik')->toArray();

        $wajibKpiNik = array_unique(array_merge($hadirNik, $izinTugasNik, $dinasLuarNik));

        if (empty($wajibKpiNik)) {
            return collect();
        }

        $sudahIsiNik = KPIDaily::whereIn('nik', $wajibKpiNik)
            ->whereDate('tanggal', $tanggal)
            ->where('status', '!=', 'draft')
            ->pluck('nik')->toArray();

        return $bawahan->filter(function ($k) use ($wajibKpiNik, $sudahIsiNik) {
            $isWajibKpi = in_array($k->nik, $wajibKpiNik);
            $isSudahIsi = in_array($k->nik, $sudahIsiNik);

            return $isWajibKpi && ! $isSudahIsi;
        });
    }

    private function getCycleDateRange($bulan, $tahun)
    {
        $bulanB = str_pad($bulan, 2, '0', STR_PAD_LEFT);
        $tahunB = $tahun;

        $bulanA = $bulan - 1;
        $tahunA = $tahun;

        if ($bulanA == 0) {
            $bulanA = 12;
            $tahunA = $tahun - 1;
        }
        $bulanA = str_pad($bulanA, 2, '0', STR_PAD_LEFT);

        return ["$tahunA-$bulanA-26", "$tahunB-$bulanB-25"];
    }

    private function getPeriodeData($reqBulan = null, $reqTahun = null)
    {
        $hariIni = Carbon::now();
        if ($hariIni->day >= 26) {
            $hariIni->addMonth();
        }

        $bulan = $reqBulan ?: $hariIni->format('n');
        $tahun = $reqTahun ?: $hariIni->format('Y');

        $namabulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $periodeList = [];

        for ($i = 1; $i <= 12; $i++) {
            $bulan_lalu = ($i == 1) ? 12 : $i - 1;
            $periodeList[$i] = "26 {$namabulan[$bulan_lalu]} - 25 {$namabulan[$i]}";
        }

        [$tglAwal, $tglAkhir] = $this->getCycleDateRange($bulan, $tahun);

        return compact('bulan', 'tahun', 'periodeList', 'tglAwal', 'tglAkhir', 'namabulan');
    }

    private function setApproverNames($item)
    {
        $item->nama_atasan_display = '-';
        $item->nama_hr_display = '-';

        if (! empty($item->approve_atasan)) {
            $atasan = Karyawan::where('nik', $item->approve_atasan)->first();
            $item->nama_atasan_display = $atasan ? $atasan->nama_lengkap : $item->approve_atasan;
        }

        if (! empty($item->approve_hr)) {
            $hr = User::find($item->approve_hr);
            $item->nama_hr_display = $hr ? $hr->name : $item->approve_hr;
        }

        return $item;
    }

    private function checkBawahanKpiWarning($user, $tanggal)
    {
        if (! $user->is_whitelist) {
            $bawahanBelumIsi = $this->cekBawahanBelumIsiKPI($user, $tanggal);
            if ($bawahanBelumIsi->isNotEmpty()) {
                $namaBawahan = $bawahanBelumIsi->pluck('nama_lengkap')->implode(', ');

                return "Anda belum bisa memproses KPI ini. Bawahan berikut belum mengisi KPI pada tanggal $tanggal: $namaBawahan";
            }
        }

        return null;
    }

    private function checkKpiTimeLimits($user, $tanggal)
    {
        // Hanya verifikasi jika mengisi KPI untuk hari ini
        if ($tanggal !== now()->toDateString()) {
            return null;
        }

        if ($user->is_whitelist) {
            return null;
        }

        $cekPresensi = Presensi::where('nik', $user->nik)->whereDate('tgl_presensi', $tanggal)->first();
        $kodeJamKerja = $cekPresensi ? $cekPresensi->kode_jam_kerja : null;

        if (! $kodeJamKerja) {
            // Jika tidak dapat jam kerja dari presensi, coba ambil dari konfigurasi dept
            $namaHariInggris = date('D', strtotime($tanggal));
            $namaHariIndonesia = ['Sun' => 'Minggu', 'Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu'];
            $hariIniStr = $namaHariIndonesia[$namaHariInggris] ?? '';

            $konfigDept = KonfigurasiJkDept::where('kode_cabang', $user->kode_cabang)
                ->where('kode_dept', $user->kode_dept)
                ->first();

            if ($konfigDept) {
                $konfigDetail = KonfigurasiJkDeptDetail::where('kode_jk_dept', $konfigDept->kode_jk_dept)
                    ->where('hari', $hariIniStr)
                    ->first();
                if ($konfigDetail) {
                    $kodeJamKerja = $konfigDetail->kode_jam_kerja;
                }
            }
        }

        if ($kodeJamKerja) {
            $jamKerja = JamKerja::where('kode_jam_kerja', $kodeJamKerja)->first();
            if ($jamKerja && ! empty($jamKerja->jam_pulang)) {
                $jamPulang = Carbon::parse($tanggal.' '.$jamKerja->jam_pulang);
                $batasMulai = $jamPulang->copy()->subHours(2);

                if (now()->lt($batasMulai)) {
                    return 'KPI hari ini baru dapat diisi mulai pukul '.$batasMulai->format('H:i').' (2 jam sebelum jadwal pulang Anda pukul '.date('H:i', strtotime($jamKerja->jam_pulang)).').';
                }
            }
        }

        return null;
    }
}
