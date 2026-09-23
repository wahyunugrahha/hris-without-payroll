@extends('layouts.admin.tabler')
@section('page-header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Laporan</div>
                    <h2 class="page-title">Rekap Presensi Seluruh Karyawan</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            @if (!empty($forcedKodeCabang))
                                <div class="alert alert-info">
                                    Rekap dibatasi untuk cabang Anda.
                                </div>
                            @endif
                            <form action="{{ route('presensi.cetakrekap') }}" target="_blank" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Bulan</label>
                                            <select name="bulan" id="bulan" class="form-select" required>
                                                @foreach ($list_periode as $val => $teks)
                                                    <option value="{{ $val }}"
                                                        {{ $defaultBulan == $val ? 'selected' : '' }}>
                                                        {{ $teks }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Tahun</label>
                                            <select name="tahun" id="tahun" class="form-select" required>
                                                @for ($t = date('Y'); $t >= date('Y') - 5; $t--)
                                                    <option value="{{ $t }}"
                                                        {{ $defaultTahun == $t ? 'selected' : '' }}>
                                                        {{ $t }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Cabang</label>
                                            <select name="kode_cabang" id="kode_cabang" class="form-select"
                                                {{ !empty($forcedKodeCabang) ? 'disabled' : '' }}>
                                                <option value="">Semua Cabang</option>
                                                @foreach ($cabang as $c)
                                                    <option value="{{ $c->kode_cabang }}"
                                                        {{ !empty($forcedKodeCabang) && $forcedKodeCabang == $c->kode_cabang ? 'selected' : '' }}>
                                                        {{ $c->nama_cabang }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if (!empty($forcedKodeCabang))
                                                <input type="hidden" name="kode_cabang" value="{{ $forcedKodeCabang }}">
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Pilih Departemen</label>
                                            <select name="kode_dept" id="kode_dept" class="form-select">
                                                <option value="">Semua Departemen</option>
                                                @foreach ($departemen as $d)
                                                    <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <button type="submit" name="cetak" class="btn btn-warning w-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-printer">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path
                                                        d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                                                    <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                                                    <path
                                                        d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                                                </svg>Cetak
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <button type="submit" name="exportexcel" value="1"
                                                class="btn btn-primary w-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="icon icon-tabler icons-tabler-outline icon-tabler-download">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" />
                                                    <path d="M7 11l5 5l5 -5" />
                                                    <path d="M12 4l0 12" />
                                                </svg>Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
