@extends('layouts.admin.tabler')
@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Edit Set Jam Kerja Karyawan</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <th style="width: 150px">NIK</th>
                                    <td>: {{ $karyawan->nik }}</td>
                                </tr>
                                <tr>
                                    <th>Nama Karyawan</th>
                                    <td>: {{ $karyawan->nama_lengkap }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-lg-6">
                    <h4 class="mb-3">Edit Jam Kerja Harian</h4>
                    <form action="{{ route('konfigurasi.updatesetjamkerja') }}" method="POST">
                        @csrf
                        <input type="hidden" name="nik" value="{{ $karyawan->nik }}">
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

                                    // Mapping data $setjamkerja ke dalam array berdasarkan hari
                                    $detailByHari = [];
                                    foreach ($setjamkerja as $s) {
                                        $kunci = strtolower(trim($s->hari));
                                        $detailByHari[$kunci] = $s; // Simpan seluruh object agar bisa dicek keberadaannya
                                    }
                                @endphp

                                @foreach ($hari_kerja as $hari)
                                    @php
                                        $kunciPencarian = strtolower(trim($hari));
                                        // Ambil data jika ada, jika tidak ada biarkan null (berarti Ikut Departemen)
                                        $dataHariIni = $detailByHari[$kunciPencarian] ?? null;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $hari }}
                                            <input type="hidden" name="hari[]" value="{{ $hari }}">
                                        </td>
                                        <td>
                                            <select name="kode_jam_kerja[]" id="kode_jam_kerja_{{ $hari }}"
                                                class="form-select">

                                                {{-- 1. Opsi Ikut Departemen (Terpilih jika $dataHariIni adalah null) --}}
                                                <option value="" {{ is_null($dataHariIni) ? 'selected' : '' }}>
                                                    -- Ikut Jadwal Departemen --
                                                </option>

                                                {{-- 2. Opsi Libur Personal (Terpilih jika ada data, tapi kode_jam_kerja nya null) --}}
                                                <option value="LIBUR"
                                                    {{ !is_null($dataHariIni) && is_null($dataHariIni->kode_jam_kerja) ? 'selected' : '' }}
                                                    style="background-color: var(--color-warning-tint); font-weight: bold;">
                                                    🏖️ LIBUR
                                                </option>

                                                {{-- 3. Opsi Jam Kerja Biasa --}}
                                                @foreach ($jamkerja as $d)
                                                    <option value="{{ $d->kode_jam_kerja }}"
                                                        {{ !is_null($dataHariIni) && $dataHariIni->kode_jam_kerja == $d->kode_jam_kerja ? 'selected' : '' }}>
                                                        {{ $d->nama_jam_kerja }} ({{ substr($d->jam_masuk, 0, 5) }} -
                                                        {{ substr($d->jam_pulang, 0, 5) }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-grid mt-3">
                            <button class="btn btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="icon icon-tabler icons-tabler-outline icon-tabler-device-floppy">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                    <path d="M12 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M14 15h-4" />
                                </svg>
                                Update Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-12 col-lg-6">
                    <h4 class="mb-3">Daftar Master Jam Kerja</h4>
                    <div class="table-responsive">
                        <table class="table card-table table-bordered table-sm">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jamkerja as $d)
                                    <tr>
                                        <td>{{ $d->kode_jam_kerja }}</td>
                                        <td>{{ $d->nama_jam_kerja }}</td>
                                        <td>{{ substr($d->awal_jam_masuk, 0, 5) }}</td>
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
