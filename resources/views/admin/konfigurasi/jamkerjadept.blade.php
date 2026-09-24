@extends('layouts.admin.tabler')

@php
    $perCabang = $jamkerjadept->groupBy('kode_cabang');
    $filterAktif = collect([request('kode_cabang'), request('kode_dept')])->filter(fn ($v) => filled($v))->count();
    $hariKerja = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Jam Kerja Departemen</h2>
                    <p class="page-subtitle">Jadwal mingguan default tiap departemen per cabang.</p>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list flex-nowrap">
                        @can('jam-kerja-dept-edit-admin')
                            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#modalSetAllCabang"
                                aria-label="Atur massal per cabang">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 6l8 0" />
                                    <path d="M16 6l4 0" />
                                    <path d="M14 4m0 2a2 2 0 1 0 0 0" />
                                    <path d="M4 12l2 0" />
                                    <path d="M10 12l10 0" />
                                    <path d="M8 12m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M4 18l11 0" />
                                    <path d="M19 18l1 0" />
                                    <path d="M17 18m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                    <path d="M14 6m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" />
                                </svg><span class="d-none d-md-inline">Atur massal</span>
                            </button>
                        @endcan
                        @can('jam-kerja-dept-create-admin')
                            <a href="{{ route('konfigurasi.createjamkerjadept') }}" class="btn btn-primary" aria-label="Tambah jam kerja departemen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                    stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg><span class="d-none d-sm-inline">Tambah Jadwal</span>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Jam kerja departemen">
                <div class="summary-strip" role="group" aria-label="Ringkasan">
                    <div class="summary-item">
                        <span class="summary-label">Cabang terkonfigurasi</span>
                        <span class="summary-value">{{ $perCabang->count() }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Jadwal departemen</span>
                        <span class="summary-value">{{ $jamkerjadept->count() }}</span>
                    </div>
                </div>

                <form action="{{ route('konfigurasi.jamkerjadept') }}" method="GET" class="list-toolbar">
                    <div class="filter-bar">
                        @if (empty($forcedKodeCabang))
                            <label class="filter-field">
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                        @endif
                        <label class="filter-field">
                            <span class="filter-label">Departemen</span>
                            <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                <option value="">Semua</option>
                                @foreach ($departemen as $d)
                                    <option value="{{ $d->kode_dept }}" @selected(request('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                @endforeach
                            </select>
                        </label>
                        @if ($filterAktif > 0)
                            <a href="{{ route('konfigurasi.jamkerjadept') }}" class="filter-reset">Reset filter</a>
                        @endif
                    </div>
                </form>

                @if ($jamkerjadept->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">{{ $filterAktif > 0 ? 'Tidak ada jadwal yang cocok.' : 'Belum ada jadwal departemen.' }}</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                <a href="{{ route('konfigurasi.jamkerjadept') }}">Reset filter</a> untuk melihat semua jadwal.
                            @else
                                Tanpa jadwal, presensi karyawan tidak bisa dihitung. Tambahkan lewat tombol di kanan atas.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Departemen</th>
                                    <th>Kode jadwal</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            @foreach ($perCabang as $kodeCabang => $daftarDept)
                                <tbody>
                                    <tr class="row-group">
                                        <th colspan="3" scope="rowgroup">
                                            {{ $daftarDept->first()->nama_cabang }}
                                            <span class="row-group-meta">{{ trim($kodeCabang) }} · {{ $daftarDept->count() }} departemen</span>
                                        </th>
                                    </tr>
                                    @foreach ($daftarDept as $d)
                                        @php $kodeJk = trim($d->kode_jk_dept); @endphp
                                        <tr>
                                            <td class="cell-person">
                                                @can('jam-kerja-dept-view-admin')
                                                    <a href="{{ route('konfigurasi.showjamkerjadept', ['kode_jk_dept' => $kodeJk]) }}" class="person">
                                                        <span class="person-name">{{ $d->nama_dept }}</span>
                                                    </a>
                                                @else
                                                    <span class="person-name">{{ $d->nama_dept }}</span>
                                                @endcan
                                            </td>
                                            <td data-label="Kode" class="cell-num text-secondary">{{ $kodeJk }}</td>
                                            <td class="cell-actions">
                                                @canany(['jam-kerja-dept-view-admin', 'jam-kerja-dept-edit-admin', 'jam-kerja-dept-delete-admin'])
                                                    <x-admin.row-menu :label="$d->nama_dept">
                                                        @can('jam-kerja-dept-view-admin')
                                                            <a href="{{ route('konfigurasi.showjamkerjadept', ['kode_jk_dept' => $kodeJk]) }}" class="dropdown-item">Lihat jadwal</a>
                                                        @endcan
                                                        @can('jam-kerja-dept-edit-admin')
                                                            <a href="{{ route('konfigurasi.editjamkerjadept', ['kode_jk_dept' => $kodeJk]) }}" class="dropdown-item">Edit</a>
                                                        @endcan
                                                        @can('jam-kerja-dept-delete-admin')
                                                            <div class="dropdown-divider"></div>
                                                            <button type="button" class="dropdown-item text-danger delete-jkdept"
                                                                data-kode="{{ $kodeJk }}" data-nama="{{ $d->nama_dept }} — {{ $d->nama_cabang }}">Hapus</button>
                                                        @endcan
                                                    </x-admin.row-menu>
                                                    @can('jam-kerja-dept-delete-admin')
                                                        <form action="{{ route('konfigurasi.deletejamkerjadept', ['kode_jk_dept' => $kodeJk]) }}"
                                                            method="POST" id="deleteFormJkDept{{ $kodeJk }}" hidden>
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endcan
                                                @endcanany
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('jam-kerja-dept-edit-admin')
        <div class="modal modal-blur fade" id="modalSetAllCabang" tabindex="-1" aria-labelledby="judulAturMassal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulAturMassal">Atur massal jam kerja</h5>
                            <p class="modal-subtitle">Menimpa jadwal <strong>semua departemen</strong> di cabang yang dipilih.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="/konfigurasi/jamkerjadept/setallbycabang" method="POST" id="formSetAllCabang" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <fieldset class="form-section">
                                <legend class="form-section-title">Target</legend>
                                <label class="form-label required" for="kode_cabang_set">Cabang</label>
                                <select name="kode_cabang_set" id="kode_cabang_set" class="form-select" required @disabled(!empty($forcedKodeCabang))>
                                    <option value="">Pilih cabang</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(!empty($forcedKodeCabang) && $forcedKodeCabang == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                                @if (!empty($forcedKodeCabang))
                                    <input type="hidden" name="kode_cabang_set" value="{{ $forcedKodeCabang }}">
                                @endif
                            </fieldset>
                            <fieldset class="form-section">
                                <legend class="form-section-title">Jadwal mingguan</legend>
                                <div class="form-grid">
                                    @foreach ($hariKerja as $hari)
                                        <div>
                                            <label class="form-label required" for="set-{{ $hari }}">{{ $hari }}</label>
                                            <select name="jam_kerja[{{ $hari }}]" id="set-{{ $hari }}" class="form-select jam-kerja-select" required>
                                                <option value="">Memuat…</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Terapkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('myscript')
    <script>
        $(function() {
            // Hapus: tampilkan dulu karyawan yang kehilangan jadwal.
            $('.delete-jkdept').on('click', function() {
                let kode = String($(this).data('kode'));
                hapusDenganRelasi({
                    url: '/konfigurasi/jamkerjadept/' + encodeURIComponent(kode) + '/relations',
                    jenis: 'jadwal departemen',
                    nama: $(this).data('nama'),
                    form: document.getElementById('deleteFormJkDept' + kode),
                    kosongkan: { karyawan: 'karyawan kehilangan jadwal departemen ini' }
                });
            });

            // Pilihan jam kerja dimuat saat modal pertama kali dibuka.
            $('#modalSetAllCabang').on('shown.bs.modal', function() {
                if ($('.jam-kerja-select').first().children('option').length > 1) return;
                $.get('/konfigurasi/getjamkerja', function(response) {
                    let options = '<option value="">Pilih jam kerja</option><option value="LIBUR">Libur</option>';
                    response.forEach(function(item) {
                        options += $('<option>', {
                            value: item.kode_jam_kerja,
                            text: `${item.nama_jam_kerja} (${item.jam_masuk.substr(0, 5)}–${item.jam_pulang.substr(0, 5)})`
                        })[0].outerHTML;
                    });
                    $('.jam-kerja-select').html(options);
                });
            });

            $('#formSetAllCabang').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                Swal.fire({
                    title: 'Terapkan ke semua departemen?',
                    text: 'Jadwal semua departemen di cabang ini akan ditimpa.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, terapkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    $.post(form.attr('action'), form.serialize())
                        .done((response) => Swal.fire('Berhasil', response.message, 'success').then(() => location.reload()))
                        .fail((xhr) => Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan sistem.', 'error'));
                });
            });
        });
    </script>
@endpush
