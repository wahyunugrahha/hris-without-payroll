@extends('layouts.admin.tabler')
@section('page-header')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Set Jam Kerja Departemen</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')

    <div class="page-body">
        <div class="container-xl">


            <form action="{{ route('konfigurasi.storejamkerjadept') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Pilih Cabang</label>
                                    <select name="kode_cabang" id="kode_cabang"
                                        class="form-select @error('kode_cabang') is-invalid @enderror" required>
                                        <option value="">Pilih Cabang</option>
                                        @foreach ($cabang as $data)
                                            <option value="{{ $data->kode_cabang }}"
                                                {{ old('kode_cabang') == $data->kode_cabang ? 'selected' : '' }}>
                                                {{ $data->nama_cabang }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('kode_cabang')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Pilih Departemen</label>
                                    <select name="kode_dept" id="kode_dept"
                                        class="form-select @error('kode_dept') is-invalid @enderror" required>
                                        <option value="">Pilih Departemen</option>
                                        @foreach ($departemen as $data)
                                            <option value="{{ $data->kode_dept }}"
                                                {{ old('kode_dept') == $data->kode_dept ? 'selected' : '' }}>
                                                {{ $data->nama_dept }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('kode_dept')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Set Jam Kerja Harian</h3>
                    </div>

                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Hari</th>
                            <th>Pilihan Jam Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $hari_kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                        @endphp
                        @foreach ($hari_kerja as $index => $hari)
                            <tr>
                                <td>
                                    {{ $hari }}
                                    <input type="hidden" name="hari[]" value="{{ $hari }}">
                                </td>
                                <td>
                                    <select name="kode_jam_kerja[]" class="form-select">
                                        <option value="">Pilih Jam Kerja</option>
                                        <option value="LIBUR" style="background-color: var(--color-warning-tint); font-weight: bold;"> Libur</option>
                                        @foreach ($jamkerja as $d)
                                            <option value="{{ $d->kode_jam_kerja }}">
                                                {{ $d->nama_jam_kerja }}
                                                ({{ substr($d->jam_masuk, 0, 5) }} -
                                                {{ substr($d->jam_pulang, 0, 5) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="card-footer d-grid">
                    <button class="btn btn-primary" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                            <path d="M12 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                            <path d="M14 15h-4" />
                        </svg>
                        Simpan Konfigurasi
                    </button>
                </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Master Jam Kerja (Referensi)</h3>
                    </div>
                <div class="table-responsive">
                    <table class="table card-table table-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Awal Masuk</th>
                                <th>Jam Masuk</th>
                                <th>Akhir Masuk</th>
                                <th>Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jamkerja as $d)
                                <tr>
                                    <td>{{ $d->kode_jam_kerja }}</td>
                                    <td>{{ $d->nama_jam_kerja }}</td>
                                    <td>{{ substr($d->awal_jam_masuk, 0, 5) }}</td>
                                    <td>{{ substr($d->jam_masuk, 0, 5) }}</td>
                                    <td>{{ substr($d->akhir_jam_masuk, 0, 5) }}</td>
                                    <td>{{ substr($d->jam_pulang, 0, 5) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        </form>
    </div>
    </div>
@endsection
