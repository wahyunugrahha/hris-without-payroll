<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KonfigurasiUmum;
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
        
        $cabang = \App\Models\Cabang::orderBy('nama_cabang')->get();
        
        return view('admin.konfigurasi_umum.index', compact('konfigurasi', 'cabang'));
    }

    public function update(Request $request)
    {
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
