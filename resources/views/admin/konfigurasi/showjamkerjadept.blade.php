@extends('layouts.admin.tabler')

@php
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
                    <h2 class="page-title">{{ $namaDept }}</h2>
                    <p class="page-subtitle">{{ $namaCabang }} · Kode {{ trim($jamkerjadept->kode_jk_dept) }}</p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        <a href="{{ route('konfigurasi.jamkerjadept') }}" class="btn">Kembali</a>
                        @can('jam-kerja-dept-edit-admin')
                            <a href="{{ route('konfigurasi.editjamkerjadept', ['kode_jk_dept' => trim($jamkerjadept->kode_jk_dept)]) }}"
                                class="btn btn-primary">Edit jadwal</a>
                        @endcan
                    </div>
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
                    <section class="card" aria-labelledby="judul-jadwal">
                        <div class="card-header">
                            <h3 class="card-title" id="judul-jadwal">Jadwal mingguan</h3>
                        </div>
                        <div class="card-body">
                            @include('admin.konfigurasi._jadwal-mingguan', ['lihat' => true])
                        </div>
                    </section>
                </div>
                <div class="col-lg-5">
                    @include('admin.konfigurasi._referensi-jamkerja')
                </div>
            </div>
        </div>
    </div>
@endsection
