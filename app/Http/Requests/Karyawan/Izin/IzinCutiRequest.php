<?php

namespace App\Http\Requests\Karyawan\Izin;

class IzinCutiRequest extends IzinMultiTanggalRequest
{
    protected string $jenis = 'cuti';

    public function rules(): array
    {
        return parent::rules() + [
            'kode_cuti' => 'required|exists:master_cuti,kode_cuti',
        ];
    }

    public function messages(): array
    {
        return [
            'kode_cuti.required' => 'Pilih jenis cuti.',
            'kode_cuti.exists' => 'Jenis cuti tidak valid.',
        ];
    }
}
