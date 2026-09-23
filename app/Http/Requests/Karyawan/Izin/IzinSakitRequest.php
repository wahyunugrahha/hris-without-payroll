<?php

namespace App\Http\Requests\Karyawan\Izin;

class IzinSakitRequest extends IzinRentangRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'sid' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:3072',
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'sid.max' => 'Ukuran file tidak boleh lebih dari 3MB.',
            'sid.mimes' => 'File harus berupa gambar (jpg/png) atau PDF.',
        ];
    }
}
