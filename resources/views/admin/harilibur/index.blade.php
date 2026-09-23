@extends('layouts.admin.tabler')

@section('content')

    {{-- Header Halaman (Sama seperti sebelumnya) --}}
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Hari Libur</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    @can('hari-libur-create-admin')
                        <a href="{{ route('harilibur.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            Tambah Data
                        </a>
                        <a href="{{ route('harilibur.create') }}" class="btn btn-primary d-sm-none btn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0">

                {{-- FILTER SECTION --}}
                <div class="card-header">
                    <form action="{{ route('harilibur.index') }}" method="GET" autocomplete="off" class="w-100">
                        {{-- Row 1: Tanggal, Departemen, Cabang --}}
                        <div class="row g-2 mb-2">
                            {{-- 1. Filter Tanggal --}}
                            <div class="col-12 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text date-filter-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-calendar-event" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path
                                                d="M4 5m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" />
                                            <path d="M16 3l0 4" />
                                            <path d="M8 3l0 4" />
                                            <path d="M4 11l16 0" />
                                            <path d="M15 15h.01" />
                                        </svg>
                                    </span>
                                    <input type="text" id="dari" class="form-control" name="dari"
                                        placeholder="Dari" value="{{ request('dari') }}">
                                    <input type="text" id="sampai" class="form-control" name="sampai"
                                        placeholder="Sampai" value="{{ request('sampai') }}">
                                </div>
                            </div>

                            {{-- 2. Filter Departemen --}}
                            <div class="col-6 col-md-4">
                                <select name="kode_dept" class="form-select">
                                    <option value="">Semua Departemen</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}"
                                            {{ request('kode_dept') == $d->kode_dept ? 'selected' : '' }}>
                                            {{ $d->nama_dept }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- 3. Filter Cabang --}}
                            <div class="col-6 col-md-4">
                                <select name="kode_cabang" class="form-select">
                                    <option value="">Semua Cabang</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}"
                                            {{ request('kode_cabang') == $c->kode_cabang ? 'selected' : '' }}>
                                            {{ $c->nama_cabang }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Row 2: Jenis Libur & Pencarian --}}
                        <div class="row g-2">
                            {{-- 4. Filter Jenis Libur --}}
                            <div class="col-12 col-md-4">
                                <select name="jenis_libur" class="form-select">
                                    <option value="">Semua Jenis Libur</option>
                                    <option value="nasional"
                                        {{ request('jenis_libur') === 'nasional' ? 'selected' : '' }}>
                                        Nasional</option>
                                    <option value="cuti_bersama"
                                        {{ request('jenis_libur') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama
                                    </option>
                                    <option value="lokal" {{ request('jenis_libur') === 'lokal' ? 'selected' : '' }}>
                                        Lokal
                                    </option>
                                </select>
                            </div>

                            {{-- 5. Pencarian & Tombol Submit --}}
                            <div class="col-12 col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="q"
                                        placeholder="Cari keterangan hari libur..." value="{{ request('q') }}">
                                    <button class="btn btn-primary" type="submit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <circle cx="10" cy="10" r="7" />
                                            <line x1="21" y1="21" x2="15" y2="15" />
                                        </svg>
                                        Cari Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Alert Sukses/Gagal --}}
                @if (session('success') || session('error'))
                    <div class="p-3 pb-0">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
                                <div>{{ session('success') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
                                <div>{{ session('error') }}</div>
                                <a class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Tabel Data --}}
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="w-1">No.</th>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th>Kategori</th>
                                <th>Departemen</th>
                                <th>Cabang</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($hari_libur as $index => $item)
                                @php
                                    $listKodeCabang = $item->kode_cabang
                                        ? array_unique(array_filter(explode(',', $item->kode_cabang)))
                                        : [];
                                    $listKodeDept = $item->kode_dept
                                        ? array_unique(array_filter(explode(',', $item->kode_dept)))
                                        : [];

                                    $namaCabang = collect($listKodeCabang)
                                        ->map(function ($kode) use ($cabang) {
                                            $c = $cabang->where('kode_cabang', $kode)->first();
                                            return $c ? $c->nama_cabang : $kode;
                                        })
                                        ->toArray();

                                    // 3. Terjemahkan Kode Departemen menjadi Nama Departemen
                                    $namaDept = collect($listKodeDept)
                                        ->map(function ($kode) use ($departemen) {
                                            // Cari data departemen berdasarkan kodenya
                                            $d = $departemen->where('kode_dept', $kode)->first();
                                            return $d ? $d->nama_dept : $kode;
                                        })
                                        ->toArray();

                                    $jmlCabang = count($listKodeCabang);
                                    $jmlDept = count($listKodeDept);
                                @endphp
                                <tr>
                                    <td>{{ $hari_libur->firstItem() + $index }}</td>
                                    <td>
                                        <div class="fw-bold">
                                            {{ \Carbon\Carbon::parse($item->tanggal_libur)->translatedFormat('d M Y') }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ \Carbon\Carbon::parse($item->tanggal_libur)->translatedFormat('l') }}</div>
                                    </td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>
                                        @if ($item->jenis_libur == 'nasional')
                                            <span class="badge bg-red-lt">Nasional</span>
                                        @elseif($item->jenis_libur == 'cuti_bersama')
                                            <span class="badge bg-orange-lt">Cuti Bersama</span>
                                        @else
                                            <span class="badge bg-blue-lt">Lokal</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($jmlDept > 0)
                                            <span class="badge badge-outline text-azure" data-bs-toggle="tooltip"
                                                title="{{ implode(', ', $namaDept) }}">{{ $jmlDept }} Dept</span>
                                        @else
                                            <span class="badge badge-outline text-muted border-0 ps-0">Semua</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($jmlCabang > 0)
                                            <span class="badge badge-outline text-purple" data-bs-toggle="tooltip"
                                                title="{{ implode(', ', $namaCabang) }}">{{ $jmlCabang }}
                                                Cabang</span>
                                        @else
                                            <span class="badge badge-outline text-muted border-0 ps-0">Semua</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list flex-nowrap text-nowrap justify-content-end">
                                            <a href="{{ route('harilibur.edit', $item->id) }}"
                                                class="btn btn-ghost-primary btn-icon" title="Edit Data">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-pencil" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4" />
                                                    <path d="M13.5 6.5l4 4" />
                                                </svg>
                                            </a>
                                            <button type="button" class="btn btn-ghost-danger btn-icon btn-hapus"
                                                data-url="{{ route('harilibur.destroy', $item->id) }}"
                                                data-keterangan="{{ $item->keterangan }}" title="Hapus Data">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-img"><img
                                                    src="{{ asset('assets/static/illustrations/undraw_printing_invoices_5r4r.svg') }}"
                                                    height="128" alt=""></div>
                                            <p class="empty-title">Data tidak ditemukan</p>
                                            <p class="empty-subtitle text-muted">Coba sesuaikan pencarian atau filter Anda.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $hari_libur->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Hapus (Tetap Sama) --}}
    <div class="modal modal-blur fade" id="modal-hapus" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="form-hapus-modal" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body text-center py-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24"
                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M12 9v2m0 4v.01" />
                            <path
                                d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.84 2.75z" />
                        </svg>
                        <h3>Apakah Anda yakin?</h3>
                        <div class="text-muted">Anda akan menghapus data libur: <br><span class="fw-bold text-dark"
                                id="nama-hapus">...</span></div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col"><a href="#" class="btn w-100"
                                        data-bs-dismiss="modal">Batal</a></div>
                                <div class="col"><button type="submit" class="btn btn-danger w-100">Hapus
                                        Data</button></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    {{-- LOAD FLATPICKR UNTUK DATEPICKER YANG CANTIK & WORK --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script>
        $(function() {
            // 1. Inisialisasi Datepicker (Flatpickr)
            // Ini akan memastikan format tanggal yang dikirim ke controller adalah YYYY-MM-DD
            flatpickr("#dari", {
                dateFormat: "Y-m-d", // Format Database
                allowInput: true // User bisa ketik manual
            });

            flatpickr("#sampai", {
                dateFormat: "Y-m-d",
                allowInput: true
            });

            // 2. Binding Tombol Hapus
            $(document).on('click', '.btn-hapus', function(e) {
                e.preventDefault();
                let url = $(this).data('url');
                let keterangan = $(this).data('keterangan');

                $('#form-hapus-modal').attr('action', url);
                $('#nama-hapus').text('"' + keterangan + '"');
                $('#modal-hapus').modal('show');
            });
        });
    </script>
@endpush
