@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan</div>
                    <h2 class="page-title">Rekap Performance Appraisal</h2>
                    <p class="page-subtitle">Nilai PA karyawan per departemen: absensi, KPI, dan penilaian atasan.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            @include('admin._laporan-form', [
                'action' => route('kpi.report.cetak'),
                'periodeList' => $periodeList,
                'defaultBulan' => $defaultBulan,
                'defaultTahun' => $defaultTahun,
                'karyawan' => $karyawan,
                'deptWajib' => true,
                'isi' => 'Pilih departemen; karyawan opsional.',
                'cabang' => $cabang,
                'departemen' => $departemen,
            ])
        </div>
    </div>
@endsection
