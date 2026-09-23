<?php

namespace App\Http\Requests\Karyawan\Izin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;

/**
 * Pengajuan roster: daftar tanggal pilihan (bisa tidak berurutan) dikirim sebagai "Y-m-d,Y-m-d,...".
 */
class IzinMultiTanggalRequest extends FormRequest
{
    protected string $jenis = 'roster';

    public function rules(): array
    {
        return [
            'selected_dates' => 'nullable|string',
            'keterangan' => 'nullable|string',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->tanggalDipilih()->isEmpty()) {
                    $validator->errors()->add('selected_dates', "Pilih minimal satu tanggal {$this->jenis}.");
                }
            },
        ];
    }

    /**
     * @return Collection<int, string> tanggal Y-m-d yang valid, unik & urut
     */
    public function tanggalDipilih(): Collection
    {
        return collect(explode(',', (string) $this->input('selected_dates')))
            ->map(fn ($date) => trim($date))
            ->filter(fn ($date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->unique()
            ->sort()
            ->values();
    }

    public function keterangan(): string
    {
        return trim((string) $this->input('keterangan'));
    }
}
