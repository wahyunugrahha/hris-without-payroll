<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\KonfigurasiUmum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;

class KonfigurasiUmumController extends Controller
{
    public function index()
    {
        // Group the configurations by 'group', excluding any document-related keys or groups
        $konfigurasi = KonfigurasiUmum::where('group', '!=', 'Dokumen')
            ->where('key', '!=', 'nama_pengesahan_dokumen')
            ->get()
            ->groupBy('group');

        $cabang = Cabang::orderBy('nama_cabang')->get();

        return view('admin.konfigurasi_umum.index', compact('konfigurasi', 'cabang'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings.point_gaji_kantor' => 'sometimes|nullable|integer|min:0',
            'settings.point_gaji_tambang' => 'sometimes|nullable|integer|min:0',
            'settings.toleransi_keterlambatan' => 'sometimes|nullable|integer|min:0|max:240',
            'settings.cabang_tambang' => 'sometimes|nullable|array',
            'settings.cabang_tambang.*' => 'exists:cabang,kode_cabang',
            'settings.sp_tambang_aktif' => 'sometimes|nullable|in:0,1',
        ], [
            'settings.*.integer' => 'Nilai harus berupa angka bulat.',
            'settings.toleransi_keterlambatan.max' => 'Toleransi keterlambatan maksimal 240 menit.',
            'settings.cabang_tambang.*.exists' => 'Cabang tambang tidak dikenal.',
        ]);

        try {
            $settings = $request->input('settings', []);

            foreach ($settings as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                KonfigurasiUmum::where('key', $key)->update(['value' => $value]);

                // Clear the cache for each updated key
                Cache::forget("setting.{$key}");
            }

            return Redirect::back()->with(['success' => 'Konfigurasi berhasil disimpan.']);
        } catch (\Exception $e) {
            return Redirect::back()->with(['warning' => $this->failMessage('Konfigurasi gagal disimpan.', $e)]);
        }
    }
}
