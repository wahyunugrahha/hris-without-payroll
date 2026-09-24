@extends('layouts.admin.tabler')

@php
    $jenisLibur = [
        'nasional' => 'Nasional',
        'cuti_bersama' => 'Cuti bersama',
        'lokal' => 'Lokal',
    ];
    $filterAktif = collect([request('dari'), request('sampai'), request('kode_dept'), request('kode_cabang'), request('jenis_libur'), request('q')])
        ->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Data Hari Libur</h2>
                    <p class="page-subtitle">Hari libur tidak dihitung sebagai alpha di rekap kehadiran.</p>
                </div>
                @can('hari-libur-create-admin')
                    <div class="col-auto ms-auto">
                        <a href="{{ route('harilibur.create') }}" class="btn btn-primary" aria-label="Tambah hari libur">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Hari Libur</span>
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar hari libur">
                <form action="{{ route('harilibur.index') }}" method="GET" class="list-toolbar" autocomplete="off">
                    <div class="list-toolbar-row">
                        <x-admin.search name="q" placeholder="Cari keterangan…" label="Cari hari libur" />
                        <button type="button" class="btn d-lg-none" data-bs-toggle="collapse" data-bs-target="#filterPanel"
                            aria-expanded="false" aria-controls="filterPanel">
                            Filter
                            @if ($filterAktif > 0)
                                <span class="filter-count">{{ $filterAktif }}</span>
                            @endif
                        </button>
                    </div>
                    <div class="collapse filter-panel" id="filterPanel">
                        <div class="filter-bar">
                            <label class="filter-field">
                                <span class="filter-label">Dari tanggal</span>
                                <input type="date" name="dari" class="form-control form-control-sm" value="{{ request('dari') }}">
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Sampai tanggal</span>
                                <input type="date" name="sampai" class="form-control form-control-sm" value="{{ request('sampai') }}">
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Jenis</span>
                                <select name="jenis_libur" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($jenisLibur as $nilai => $label)
                                        <option value="{{ $nilai }}" @selected(request('jenis_libur') === $nilai)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Departemen</span>
                                <select name="kode_dept" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}" @selected(request('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="filter-field">
                                <span class="filter-label">Cabang</span>
                                <select name="kode_cabang" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($cabang as $c)
                                        <option value="{{ $c->kode_cabang }}" @selected(request('kode_cabang') == $c->kode_cabang)>{{ $c->nama_cabang }}</option>
                                    @endforeach
                                </select>
                            </label>
                            @if ($filterAktif > 0)
                                <a href="{{ route('harilibur.index') }}" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($hari_libur->total() > 0)
                        Menampilkan <strong>{{ $hari_libur->firstItem() }}–{{ $hari_libur->lastItem() }}</strong>
                        dari <strong>{{ $hari_libur->total() }}</strong> hari libur
                    @endif
                </div>

                @if ($hari_libur->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada hari libur yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                Ubah kata kunci atau filter — atau <a href="{{ route('harilibur.index') }}">reset filter</a>.
                            @else
                                Tambahkan hari libur nasional, cuti bersama, atau libur lokal.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Jenis</th>
                                    <th>Berlaku untuk</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hari_libur as $item)
                                    @php
                                        $namaDari = fn ($daftarKode, $koleksi, $kolomKode, $kolomNama) => collect(array_unique(array_filter(explode(',', (string) $daftarKode))))
                                            ->map(fn ($kode) => optional($koleksi->firstWhere($kolomKode, $kode))->{$kolomNama} ?? $kode)
                                            ->values();
                                        $namaDept = $namaDari($item->kode_dept, $departemen, 'kode_dept', 'nama_dept');
                                        $namaCabang = $namaDari($item->kode_cabang, $cabang, 'kode_cabang', 'nama_cabang');
                                        $tanggal = \Carbon\Carbon::parse($item->tanggal_libur);
                                        $labelJenis = $jenisLibur[$item->jenis_libur] ?? $item->jenis_libur;
                                    @endphp
                                    <tr>
                                        <td class="cell-person">
                                            <span class="person-name cell-num">{{ $tanggal->translatedFormat('d M Y') }}</span>
                                            <span class="person-sub">{{ $tanggal->translatedFormat('l') }}</span>
                                        </td>
                                        <td data-label="Keterangan">{{ $item->keterangan }}</td>
                                        <td data-label="Jenis">
                                            <span class="tag hue-{{ \App\Support\WarnaJenis::HARI_LIBUR[$item->jenis_libur] ?? 'slate' }}">{{ $labelJenis }}</span>
                                        </td>
                                        <td data-label="Berlaku">
                                            <div class="cell-main" @if ($namaDept->isNotEmpty()) title="{{ $namaDept->implode(', ') }}" @endif>
                                                {{ $namaDept->isEmpty() ? 'Semua departemen' : $namaDept->count() . ' departemen' }}
                                            </div>
                                            <div class="cell-sub" @if ($namaCabang->isNotEmpty()) title="{{ $namaCabang->implode(', ') }}" @endif>
                                                {{ $namaCabang->isEmpty() ? 'Semua cabang' : $namaCabang->count() . ' cabang' }}
                                            </div>
                                        </td>
                                        <td class="cell-actions">
                                            <x-admin.row-menu :label="$item->keterangan">
                                                <a href="{{ route('harilibur.edit', $item->id) }}" class="dropdown-item">Edit</a>
                                                <div class="dropdown-divider"></div>
                                                <form action="{{ route('harilibur.destroy', $item->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"
                                                        data-confirm="Hari libur “{{ $item->keterangan }}” akan dihapus."
                                                        data-confirm-title="Hapus hari libur?">Hapus</button>
                                                </form>
                                            </x-admin.row-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($hari_libur->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $hari_libur->currentPage() }} dari {{ $hari_libur->lastPage() }}</span>
                        {{ $hari_libur->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
