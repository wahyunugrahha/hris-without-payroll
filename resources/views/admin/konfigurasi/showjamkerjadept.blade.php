@extends('layouts.admin.tabler')
@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Data Jam Kerja Departemen</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    {{ session('warning') }}
                </div>
            @endif
            <div class="row mb-3">
                <div class="col-12">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Pilih Cabang</label>
                                <select name="kode_cabang" id="kode_cabang" class="form-select" disabled>
                                    <option value="">Pilih Cabang</option>
                                    @foreach ($cabang as $data)
                                        <option value="{{ $data->kode_cabang }}"
                                            {{ $jamkerjadept->kode_cabang == $data->kode_cabang ? 'selected' : '' }}>
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
                                <select name="kode_dept" id="kode_dept" class="form-select" disabled>
                                    <option value="">Pilih Departemen</option>
                                    @foreach ($departemen as $data)
                                        <option value="{{ $data->kode_dept }}"
                                            {{ $jamkerjadept->kode_dept == $data->kode_dept ? 'selected' : '' }}>
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
        </div>
        <div class="row">
            <div class="col-12 col-lg-6">
                <h4 class="mb-3">Set Jam Kerja Harian</h4>

                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th style="width: 150px;">Hari</th>
                            <th>Pilihan Jam Kerja</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            // 1. Array hari yang pasti berurutan
                            $hari_kerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

                            // 2. Mapping data ke dalam array (Kamus Hari) untuk pencarian cepat
                            $detailByHari = [];
                            foreach ($jamkerjadept_detail as $s) {
                                $kunci = strtolower(trim($s->hari));
                                $detailByHari[$kunci] = $s;
                            }
                        @endphp

                        {{-- 3. Looping menggunakan array $hari_kerja, bukan data dari database --}}
                        @foreach ($hari_kerja as $hari)
                            @php
                                // Cari data menggunakan kunci huruf kecil
                                $kunciPencarian = strtolower(trim($hari));

                                // Jika ketemu datanya ambil, jika tidak ada set sebagai null (dianggap Libur)
                                $s = isset($detailByHari[$kunciPencarian])
                                    ? $detailByHari[$kunciPencarian]
                                    : (object) ['kode_jam_kerja' => null];
                            @endphp
                            <tr>
                                <td>
                                    {{ $hari }}
                                    {{-- Hidden input tetap dipertahankan sesuai kebutuhan form --}}
                                    <input type="hidden" name="hari[]" value="{{ $hari }}">
                                </td>
                                <td>
                                    @if ($s->kode_jam_kerja == null || $s->kode_jam_kerja == 'LIBUR')
                                        <span class="badge bg-warning text-dark">Libur</span>
                                    @else
                                        {{ $s->nama_jam_kerja ?? '-' }}
                                        ({{ isset($s->jam_masuk) ? substr($s->jam_masuk, 0, 5) : '00:00' }} -
                                        {{ isset($s->jam_pulang) ? substr($s->jam_pulang, 0, 5) : '00:00' }})
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="col-12 col-lg-6">
                <h4 class="mb-3">Daftar Master Jam Kerja (Referensi)</h4>
                <div class="table-responsive">
                    <table class="table card-table table-bordered table-sm">
                        <thead class="bg-dark text-white">
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
    </div>
@endsection
