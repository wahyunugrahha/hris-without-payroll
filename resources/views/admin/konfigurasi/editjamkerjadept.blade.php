@extends('layouts.admin.tabler')

@php
    // Hari tanpa detail / tanpa kode jam kerja = libur.
    $terpilih = collect($jamkerjadept_detail)
        ->mapWithKeys(fn ($s) => [ucfirst(strtolower(trim($s->hari))) => $s->kode_jam_kerja ?: 'LIBUR'])
        ->all();
    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h) {
        $terpilih[$h] = $terpilih[$h] ?? 'LIBUR';
    }
    $namaCabang = optional($cabang->firstWhere('kode_cabang', $jamkerjadept->kode_cabang))->nama_cabang ?? $jamkerjadept->kode_cabang;
    $namaDept = optional($departemen->firstWhere('kode_dept', $jamkerjadept->kode_dept))->nama_dept ?? $jamkerjadept->kode_dept;
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master / Jam Kerja Departemen</div>
                    <h2 class="page-title">Edit Jadwal · {{ $namaDept }}</h2>
                    <p class="page-subtitle">{{ $namaCabang }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-3">
                <div class="col-lg-7">
                    <form action="{{ route('konfigurasi.updatejamkerjadept', ['kode_jk_dept' => trim($jamkerjadept->kode_jk_dept)]) }}"
                        method="POST" class="card">
                        @csrf
                        <div class="card-header">
                            <h3 class="card-title">Jadwal mingguan</h3>
                        </div>
                        <div class="card-body">
                            @include('admin.konfigurasi._jadwal-mingguan')
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2">
                            <a href="{{ route('konfigurasi.jamkerjadept') }}" class="btn">Batal</a>
                            <button class="btn btn-primary" type="submit">Simpan perubahan</button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-5">
                    @include('admin.konfigurasi._referensi-jamkerja')
                </div>
            </div>
        </div>
    </div>
@endsection
