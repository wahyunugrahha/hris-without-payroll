@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master / Hari Libur</div>
                    <h2 class="page-title">Tambah Hari Libur</h2>
                    <p class="page-subtitle">Rentang tanggal akan disimpan per hari.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl container-narrow-lg">
            @include('admin.harilibur._form')
        </div>
    </div>
@endsection
