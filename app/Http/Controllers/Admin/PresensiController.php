<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Departemen;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Presensi;
use App\Services\JadwalKerjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Monitoring presensi harian: daftar, peta lokasi, dan anulir presensi.
 * Laporan/rekap ada di LaporanPresensiController, approval izin di IzinApprovalController.
 */
class PresensiController extends Controller
{
    public function __construct(private JadwalKerjaService $jadwalKerja) {}

    public function monitoring()
    {
        $departemen = Departemen::orderBy('nama_dept')->get();
        $cabang = Cabang::orderBy('nama_cabang')->get();
        $jabatans = Jabatan::whereHas('role', function ($q) {
            $q->where('guard_name', 'karyawan');
        })->orderBy('nama_jabatan')->get();

        return view('admin.presensi.monitoring', compact('departemen', 'cabang', 'jabatans'));
    }

    public function getpresensi(Request $request)
    {
        $tanggal = $request->tanggal;

        // Konversi format dd-mm-yyyy ke Y-m-d jika diperlukan
        if (! empty($tanggal) && strpos($tanggal, '-') !== false && strlen($tanggal) == 10) {
            $parts = explode('-', $tanggal);
            if (count($parts) == 3 && strlen($parts[2]) == 4) {
                $tanggal = $parts[2].'-'.$parts[1].'-'.$parts[0]; // yyyy-mm-dd
            }
        }
        $kode_dept = $request->kode_dept;
        $loggedInUser = Auth::guard('user')->user();

        // Cek role admin cabang
        $isAdminCabang = (bool) $loggedInUser?->isAdminCabang();
        $kode_cabang = $isAdminCabang ? ($loggedInUser->kode_cabang ?? null) : $request->kode_cabang;

        $search = $request->search;
        $hariNama = $this->jadwalKerja->namaHari(date('D', strtotime($tanggal)));

        // Hindari duplikasi baris akibat multi-record izin/dinas pada tanggal yang sama.
        $dinasLuarSubquery = DB::table('dinas_luar')
            ->select('nik', DB::raw('MAX(id) as latest_dinas_luar_id'))
            ->where('status_acc', 'acc')
            ->whereRaw('? between tgl_mulai and tgl_selesai', [$tanggal])
            ->groupBy('nik');

        $izinSubquery = DB::table('izin')
            ->select('nik', DB::raw('MAX(kode_izin) as latest_izin_kode'))
            ->whereIn('status', ['s', 'i', 'c', 'r'])
            ->where('status_approved', 1)
            ->whereRaw('? between tgl_izin_dari and tgl_izin_sampai', [$tanggal])
            ->groupBy('nik');

        $presensiSubquery = DB::table('presensi')
            ->select('nik', DB::raw('MAX(id) as latest_presensi_id'))
            ->where('tgl_presensi', $tanggal)
            ->groupBy('nik');

        $query = Karyawan::query()
            ->wajibPresensi()
            ->where('karyawan.status_aktif', Karyawan::STATUS_AKTIF)
            ->select([
                'karyawan.nik',
                'karyawan.nama_lengkap as nama_karyawan',
                'karyawan.kode_cabang',
                'karyawan.kode_dept',
                'departemen.nama_dept',
                'presensi_filtered.jam_in',
                'presensi_filtered.jam_out',
                'presensi_filtered.foto_in',
                'presensi_filtered.foto_out',
                'presensi_filtered.lokasi_in',
                'presensi_filtered.lokasi_out',
                'presensi_filtered.status',
                'presensi_filtered.kejanggalan',
                'presensi_filtered.id',
                'presensi_filtered.tgl_presensi',
                'jam_kerja.jam_masuk',
                'jam_kerja.akhir_jam_masuk',
                'jam_kerja.jam_pulang',
                'jam_kerja.nama_jam_kerja',
            ])
            ->leftJoin('departemen', 'karyawan.kode_dept', '=', 'departemen.kode_dept')
            ->leftJoin('cabang', 'karyawan.kode_cabang', '=', 'cabang.kode_cabang')
            ->leftJoin('jabatan', 'karyawan.jabatan_id', '=', 'jabatan.id')
            ->leftJoinSub($presensiSubquery, 'presensi_map', function ($join) {
                $join->on('karyawan.nik', '=', 'presensi_map.nik');
            })
            ->leftJoin('presensi as presensi_filtered', 'presensi_map.latest_presensi_id', '=', 'presensi_filtered.id')
            ->leftJoin('jam_kerja', 'presensi_filtered.kode_jam_kerja', '=', 'jam_kerja.kode_jam_kerja')
            ->leftJoinSub($dinasLuarSubquery, 'dinas_luar_map', function ($join) {
                $join->on('karyawan.nik', '=', 'dinas_luar_map.nik');
            })
            ->leftJoin('dinas_luar as dinas_luar_filtered', 'dinas_luar_map.latest_dinas_luar_id', '=', 'dinas_luar_filtered.id')
            ->leftJoinSub($izinSubquery, 'izin_map', function ($join) {
                $join->on('karyawan.nik', '=', 'izin_map.nik');
            })
            ->leftJoin('izin as izin_filtered', 'izin_map.latest_izin_kode', '=', 'izin_filtered.kode_izin')
            ->addSelect('cabang.nama_cabang')
            ->addSelect('jabatan.nama_jabatan as jabatan_nama')
            ->addSelect('dinas_luar_filtered.id as dinas_luar_id')
            ->addSelect('izin_filtered.status as izin_status')
            ->addSelect('izin_filtered.kode_izin as izin_kode');

        if (Schema::hasColumn('izin', 'doc_sid')) {
            $query->addSelect('izin_filtered.doc_sid');
        }

        // Filter Cabang & Dept
        if (! empty($kode_dept)) {
            $query->where('karyawan.kode_dept', $kode_dept);
        }
        if (! empty($kode_cabang)) {
            $query->where('karyawan.kode_cabang', $kode_cabang);
        }

        // Filter Jabatan (single)
        if ($request->filled('jabatan_id')) {
            $query->where('karyawan.jabatan_id', $request->jabatan_id);
        }

        // Filter Pencarian Nama/NIK
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(karyawan.nik) like ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(karyawan.nama_lengkap) like ?', ['%'.strtolower($search).'%']);
            });
        }

        // Filter Status Presensi
        $statusFilter = $request->status ?? null;
        if (! empty($statusFilter)) {
            if ($statusFilter === 'late') {
                $query->whereNotNull('presensi_filtered.jam_in')
                    ->where('presensi_filtered.jam_in', '!=', '00:00:00')
                    ->whereNotNull('jam_kerja.jam_masuk')
                    ->whereRaw('presensi_filtered.jam_in > jam_kerja.jam_masuk')
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'h') {
                $query->whereNotNull('presensi_filtered.jam_in')
                    ->where('presensi_filtered.jam_in', '!=', '00:00:00')
                    ->where(function ($q) {
                        $q->whereRaw('presensi_filtered.jam_in <= jam_kerja.jam_masuk')
                            ->orWhereNull('jam_kerja.jam_masuk');
                    })
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'n' || $statusFilter === 'a') {
                $query->whereNull('presensi_filtered.id')
                    ->whereNull('izin_filtered.kode_izin')
                    ->whereNull('dinas_luar_filtered.id');
            } elseif ($statusFilter === 'd') {
                $query->whereNotNull('dinas_luar_filtered.id');
            } else {
                $query->where(function ($q) use ($statusFilter) {
                    $q->where('presensi_filtered.status', $statusFilter)
                        ->orWhere('izin_filtered.status', $statusFilter);
                });
            }
        }

        $presensi = $query->orderBy('karyawan.nama_lengkap')->paginate(25);
        $presensi->appends($request->all());

        // Transform Data (Logic Penentuan Status)
        $presensi->getCollection()->transform(function ($item) use ($tanggal, $hariNama) {
            // Jika data presensi kosong (belum absen/alpha/izin/dinas)
            if ($item->id === null) {
                $item->tgl_presensi = $tanggal;

                // Cari Jam Kerja Default jika kosong
                if (empty($item->jam_masuk)) {
                    [$jkObj, $isLiburShift] = $this->jadwalKerja->untukHari($item->nik, $item->kode_dept, $item->kode_cabang, $hariNama);
                    if ($jkObj) {
                        $item->jam_masuk = $jkObj->jam_masuk;
                        $item->akhir_jam_masuk = $jkObj->akhir_jam_masuk;
                        $item->jam_pulang = $jkObj->jam_pulang;
                        $item->nama_jam_kerja = $jkObj->nama_jam_kerja;
                    }
                    $item->is_libur_shift = $isLiburShift ?? false;
                }

                $isHariLibur = HariLibur::isHariLibur($tanggal, $item->kode_cabang, $item->kode_dept);
                $isJadwalLibur = isset($item->is_libur_shift) && $item->is_libur_shift;

                // Prioritas 1: Dinas Luar
                if (! empty($item->dinas_luar_id)) {
                    $item->status = 'd';
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 2: Libur (Nasional/Shift)
                elseif ($isHariLibur || $isJadwalLibur) {
                    $item->status = 'l';
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 3: Izin/Sakit/Cuti
                elseif (! empty($item->izin_status) && in_array($item->izin_status, ['i', 's', 'c', 'r'])) {
                    $item->status = $item->izin_status;
                    $this->setEmptyPresensi($item);
                }
                // Prioritas 4: Alpha / Belum Absen
                else {
                    if ($tanggal < date('Y-m-d')) {
                        $item->status = 'a';
                    } else {
                        $item->status = 'n';
                    }
                }
            }
            // Jika data presensi ada tapi jam kerja tidak ter-join, fallback ke konfigurasi jadwal
            elseif (empty($item->jam_masuk)) {
                [$jkObj, $isLiburShift] = $this->jadwalKerja->untukHari($item->nik, $item->kode_dept, $item->kode_cabang, $hariNama);
                if ($jkObj) {
                    $item->jam_masuk = $jkObj->jam_masuk;
                    $item->akhir_jam_masuk = $jkObj->akhir_jam_masuk;
                    $item->jam_pulang = $jkObj->jam_pulang;
                    $item->nama_jam_kerja = $jkObj->nama_jam_kerja;
                }
                $item->is_libur_shift = $isLiburShift ?? false;
            }
            // Jika sudah ada data presensi tapi status 'd'
            elseif (! empty($item->dinas_luar_id)) {
                $item->status = 'd';
            }
            // Jika hadir (H)
            elseif ($item->status === null && ! empty($item->jam_in) && $item->jam_in != '00:00:00') {
                $item->status = 'h';
            }

            return $item;
        });

        return view('admin.presensi.getpresensi', compact('presensi'));
    }

    public function tampilkanpeta(Request $request)
    {
        $id = $request->id;
        $presensi = Presensi::with('karyawan')->findOrFail($id);
        abort_if($this->outsideAdminCabang($presensi->karyawan), 403, 'Anda tidak memiliki akses ke data cabang lain.');

        $presensi->kode_cabang = $presensi->karyawan->kode_cabang ?? null;
        $presensi->nama_lengkap = $presensi->karyawan->nama_lengkap ?? null;
        $radius = 50;
        if ($presensi->kode_cabang) {
            $cabang = Cabang::where('kode_cabang', $presensi->kode_cabang)->first();
            $radius = $cabang->radius ?? $radius;
        }

        return view('admin.presensi.showmap', compact('presensi', 'radius'));
    }

    // --- Helper Functions ---

    private function setEmptyPresensi($item)
    {
        $item->jam_in = '00:00:00';
        $item->jam_out = '00:00:00';
        $item->foto_in = '-';
        $item->foto_out = '-';
        $item->lokasi_in = '999,999';
        $item->lokasi_out = '999,999';
    }

    public function batalpresensi($id)
    {
        $presensi = Presensi::find($id);
        if (! $presensi) {
            return response()->json(['status' => false, 'message' => 'Data presensi tidak ditemukan.']);
        }

        if ($this->outsideAdminCabang($presensi->karyawan)) {
            return response()->json(['status' => false, 'message' => 'Anda tidak memiliki akses ke data cabang lain.'], 403);
        }

        try {
            // Hapus file foto_in jika ada
            if ($presensi->foto_in && $presensi->foto_in !== '-') {
                Storage::disk('public')->delete('uploads/absensi/'.$presensi->foto_in);
            }
            // Hapus file foto_out jika ada
            if ($presensi->foto_out && $presensi->foto_out !== '-') {
                Storage::disk('public')->delete('uploads/absensi/'.$presensi->foto_out);
            }

            // Update record presensi menjadi anulir (status 'x')
            $presensi->update([
                'jam_in' => '00:00:00',
                'jam_out' => '00:00:00',
                'foto_in' => '-',
                'foto_out' => '-',
                'lokasi_in' => '999,999',
                'lokasi_out' => '999,999',
                'status' => 'x',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Presensi berhasil dianulir. Karyawan dapat melakukan absen masuk ulang.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $this->failMessage('Gagal memproses data.', $e)]);
        }
    }
}
