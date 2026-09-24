@extends('layouts.admin.tabler')

@php
    // Mode edit bila karyawan sudah punya jadwal personal ($setjamkerja dari controller).
    $edit = isset($setjamkerja);
    // Tanpa baris = ikut departemen (''), baris tanpa kode = libur personal.
    $terpilih = $edit
        ? collect($setjamkerja)->mapWithKeys(fn ($s) => [ucfirst(strtolower(trim($s->hari))) => $s->kode_jam_kerja ?: 'LIBUR'])->all()
        : [];
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master / Data Karyawan</div>
                    <h2 class="page-title">Jadwal Kerja · {{ $karyawan->nama_lengkap }}</h2>
                    <p class="page-subtitle">NIK {{ $karyawan->nik }} · Jadwal personal menggantikan jadwal departemen.</p>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('karyawan.show', $karyawan->nik) }}" class="btn">Lihat profil</a>
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
                    <form action="{{ $edit ? route('konfigurasi.updatesetjamkerja') : route('konfigurasi.setstorejamkerja') }}"
                        method="POST" class="card">
                        @csrf
                        <input type="hidden" name="nik" value="{{ $karyawan->nik }}">
                        <div class="card-header">
                            <h3 class="card-title">Jadwal mingguan</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary small mb-3">Pilih “Ikut jadwal departemen” untuk hari yang tidak perlu diatur khusus.</p>
                            @include('admin.konfigurasi._jadwal-mingguan', ['opsiKosong' => 'Ikut jadwal departemen'])
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2">
                            <a href="{{ route('karyawan.index') }}" class="btn">Batal</a>
                            <button class="btn btn-primary" type="submit">{{ $edit ? 'Simpan perubahan' : 'Simpan jadwal' }}</button>
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
