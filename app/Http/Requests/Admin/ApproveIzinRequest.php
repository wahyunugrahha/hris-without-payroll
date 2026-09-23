<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveIzinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_izinsakit_from' => 'required|string',
            'status_approved' => 'required|in:0,1,2',
            'selected_cuti_dates' => 'nullable|string',
            'catatan_ditolak' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'id_izinsakit_from.required' => 'Pengajuan tidak valid.',
            'status_approved.required' => 'Pilih keputusan persetujuan.',
            'status_approved.in' => 'Keputusan persetujuan tidak valid.',
        ];
    }

    /**
     * @return list<string> tanggal cuti/roster yang dicentang admin (Y-m-d)
     */
    public function tanggalDipilih(): array
    {
        return collect(explode(',', (string) $this->input('selected_cuti_dates')))
            ->map(fn ($date) => trim($date))
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->values()
            ->all();
    }
}
