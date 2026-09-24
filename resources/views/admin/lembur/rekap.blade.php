@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan</div>
                    <h2 class="page-title">Rekap Lembur</h2>
                    <p class="page-subtitle">Total jam lembur seluruh karyawan per periode.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            @include('admin._laporan-form', [
                'action' => route('admin.lembur.cetakrekap'),
                'periodeList' => $list_periode,
                'defaultBulan' => $defaultBulan,
                'defaultTahun' => $defaultTahun,
                'forcedKodeCabang' => $forcedKodeCabang ?? null,
                'isi' => 'Kosongkan cabang/departemen untuk semua karyawan.',
                'cabang' => $cabang,
                'departemen' => $departemen,
            ])
        </div>
    </div>
@endsection
