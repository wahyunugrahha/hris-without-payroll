@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan</div>
                    <h2 class="page-title">Laporan Presensi Karyawan</h2>
                    <p class="page-subtitle">Rincian presensi harian satu karyawan dalam satu periode.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            @include('admin._laporan-form', [
                'action' => route('presensi.cetaklaporan'),
                'periodeList' => $list_periode,
                'defaultBulan' => $defaultBulan,
                'defaultTahun' => $defaultTahun,
                'karyawan' => $karyawan,
                'karyawanWajib' => true,
                'forcedKodeCabang' => $forcedKodeCabang ?? null,
                'isi' => 'Pilih karyawan yang ingin dicetak laporannya.',
                'cabang' => $cabang,
                'departemen' => $departemen,
            ])
        </div>
    </div>
@endsection
