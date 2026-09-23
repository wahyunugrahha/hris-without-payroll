<?php

namespace App\Http\Requests\Karyawan\Izin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan dengan rentang tanggal dari-sampai (izin absen).
 */
class IzinRentangRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dari' => 'required|date',
            'sampai' => 'required|date|after_or_equal:dari',
            'keterangan' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'dari.required' => 'Tanggal mulai wajib diisi.',
            'sampai.required' => 'Tanggal selesai wajib diisi.',
            'sampai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'keterangan.required' => 'Keterangan wajib diisi.',
        ];
    }
}
