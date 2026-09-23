<?php

namespace App\Http\Requests\Admin;

use App\Models\Karyawan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah (POST) & ubah (PUT) data karyawan oleh admin. Hanya field di sini yang disimpan.
 */
class KaryawanRequest extends FormRequest
{
    private const FOTO = 'nullable|image|mimes:jpeg,png,jpg|max:3072';

    private const TEKS = 'nullable|string|max:255';

    public const FIELD_FOTO = ['foto', 'foto_bpjs_kesehatan', 'foto_bpjs_ketenagakerjaan'];

    public function rules(): array
    {
        $rules = [
            // Identitas & penempatan
            'nik' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/', Rule::unique('karyawan', 'nik')->ignore($this->route('nik'), 'nik')],
            'nama_lengkap' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\,\-]+$/u'],
            'nama_panggilan' => 'required|string|max:255',
            'jabatan_id' => 'required|exists:jabatan,id',
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'email' => 'nullable|email|max:255',
            'kode_dept' => 'required|exists:departemen,kode_dept',
            'kode_cabang' => 'required|exists:cabang,kode_cabang',
            'is_whitelist' => 'nullable|boolean',

            // Kepegawaian
            'tmt' => 'nullable|date',
            'tanggal_awal_kontrak' => 'nullable|date',
            'tanggal_habis_kontrak' => 'nullable|date',
            'status_karyawan' => self::TEKS,
            'status_ptkp' => 'nullable|string|max:10',
            'history_karyawan' => 'nullable|string',
            'statemen' => 'nullable|string',

            // Data pribadi, keluarga & darurat
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => self::TEKS,
            'tanggal_lahir' => 'nullable|date',
            'agama' => self::TEKS,
            'status_pernikahan' => self::TEKS,
            'pendidikan_terakhir' => self::TEKS,
            'alamat' => 'nullable|string',
            'nama_ibu_kandung' => self::TEKS,
            'nama_darurat' => self::TEKS,
            'hubungan_darurat' => self::TEKS,
            'no_darurat' => self::TEKS,

            // Legal & keuangan
            'no_rekening' => self::TEKS,
            'no_bpjs_kesehatan' => self::TEKS,
            'no_bpjs_ketenagakerjaan' => self::TEKS,

            'foto' => self::FOTO,
            'foto_bpjs_kesehatan' => self::FOTO,
            'foto_bpjs_ketenagakerjaan' => self::FOTO,
        ];

        if ($this->isMethod('put')) {
            $rules += [
                'status_aktif' => ['nullable', Rule::in(Karyawan::FILTERABLE_STATUSES)],
                'tanggal_keluar' => [
                    'nullable',
                    'date',
                    Rule::requiredIf(fn () => in_array($this->status_aktif, [Karyawan::STATUS_NONAKTIF, Karyawan::STATUS_DIBERHENTIKAN], true)),
                ],
                'password' => 'nullable|string|min:6',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nik.unique' => 'NIK sudah terdaftar pada karyawan lain.',
            'nik.regex' => 'NIK hanya boleh berisi angka.',
            'no_hp.regex' => 'No. HP hanya boleh berisi angka.',
            'nama_lengkap.regex' => 'Nama lengkap hanya boleh berisi huruf, spasi, titik, koma, dan tanda hubung.',
            'tanggal_keluar.required' => 'Tanggal keluar wajib diisi jika status karyawan Nonaktif atau Diberhentikan.',
        ];
    }

    /**
     * Data kolom karyawan (tanpa file foto & password).
     */
    public function dataKaryawan(): array
    {
        return collect($this->validated())->except([...self::FIELD_FOTO, 'password'])->all();
    }
}
