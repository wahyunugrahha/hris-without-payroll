@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master / Jam Kerja Departemen</div>
                    <h2 class="page-title">Tambah Jadwal Departemen</h2>
                    <p class="page-subtitle">Jadwal default untuk semua karyawan di departemen & cabang ini.</p>
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
                    <form action="{{ route('konfigurasi.storejamkerjadept') }}" method="POST" class="card">
                        @csrf
                        <div class="card-body">
                            <fieldset class="form-section">
                                <legend class="form-section-title">Target</legend>
                                <div class="form-grid">
                                    <div>
                                        <label class="form-label required" for="kode_cabang">Cabang</label>
                                        <select name="kode_cabang" id="kode_cabang" class="form-select @error('kode_cabang') is-invalid @enderror" required>
                                            <option value="">Pilih cabang</option>
                                            @foreach ($cabang as $data)
                                                <option value="{{ $data->kode_cabang }}" @selected(old('kode_cabang') == $data->kode_cabang)>{{ $data->nama_cabang }}</option>
                                            @endforeach
                                        </select>
                                        @error('kode_cabang')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label required" for="kode_dept">Departemen</label>
                                        <select name="kode_dept" id="kode_dept" class="form-select @error('kode_dept') is-invalid @enderror" required>
                                            <option value="">Pilih departemen</option>
                                            @foreach ($departemen as $data)
                                                <option value="{{ $data->kode_dept }}" @selected(old('kode_dept') == $data->kode_dept)>{{ $data->nama_dept }}</option>
                                            @endforeach
                                        </select>
                                        @error('kode_dept')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="form-section">
                                <legend class="form-section-title">Jadwal mingguan</legend>
                                @include('admin.konfigurasi._jadwal-mingguan', ['terpilih' => []])
                            </fieldset>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-2">
                            <a href="{{ route('konfigurasi.jamkerjadept') }}" class="btn">Batal</a>
                            <button class="btn btn-primary" type="submit">Simpan jadwal</button>
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
