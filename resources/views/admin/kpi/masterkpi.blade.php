@extends('layouts.admin.tabler')

@php
    // Jumlah cabang & indikator per kode master untuk halaman ini (2 query, bukan per baris).
    $kodeHalaman = $kpiMaster->pluck('kode_master')->unique();
    $cabangPerKode = \App\Models\KPIMaster::whereIn('kode_master', $kodeHalaman)
        ->selectRaw('kode_master, count(*) as jumlah')->groupBy('kode_master')->pluck('jumlah', 'kode_master');
    $indikatorPerKode = \App\Models\KPIMasterDetail::whereIn('kode_master', $kodeHalaman)
        ->selectRaw('kode_master, count(*) as jumlah')->groupBy('kode_master')->pluck('jumlah', 'kode_master');
    $cabangTunggal = count($cabang) <= 1;
    $filterAktif = collect([request('jabatan_id'), request('kode_dept'), $cabangTunggal ? null : request('kode_cabang'), request('status'), request('indikator')])
        ->filter(fn ($v) => filled($v))->count();
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">KPI Karyawan</div>
                    <h2 class="page-title">Master KPI</h2>
                    <p class="page-subtitle">Template KPI per jabatan & departemen beserta indikatornya.</p>
                </div>
                @can('kpi-create-admin')
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahKpiMaster" aria-label="Tambah master KPI">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg><span class="d-none d-sm-inline">Tambah Master KPI</span>
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <section class="card list-card" aria-label="Daftar master KPI">
                <form action="{{ route('kpi.master.index') }}" method="GET" class="list-toolbar" autocomplete="off">
                    <div class="list-toolbar-row">
                        <x-admin.search name="indikator" placeholder="Cari nama atau kode KPI…" label="Cari KPI" />
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
                                <span class="filter-label">Jabatan</span>
                                <select name="jabatan_id" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    @foreach ($jabatan as $j)
                                        <option value="{{ $j->id }}" @selected(request('jabatan_id') == $j->id)>{{ $j->nama_jabatan }}</option>
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
                            @if ($cabangTunggal)
                                <input type="hidden" name="kode_cabang" value="{{ $cabang->first()->kode_cabang ?? '' }}">
                            @else
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
                                <span class="filter-label">Status</span>
                                <select name="status" class="form-select form-select-sm" data-auto-submit>
                                    <option value="">Semua</option>
                                    <option value="1" @selected(request('status') === '1')>Aktif</option>
                                    <option value="0" @selected(request('status') === '0')>Nonaktif</option>
                                </select>
                            </label>
                            @if ($filterAktif > 0)
                                <a href="{{ route('kpi.master.index') }}" class="filter-reset">Reset filter</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="list-meta">
                    @if ($kpiMaster->total() > 0)
                        Menampilkan <strong>{{ $kpiMaster->firstItem() }}–{{ $kpiMaster->lastItem() }}</strong>
                        dari <strong>{{ $kpiMaster->total() }}</strong> master KPI
                    @endif
                </div>

                @if ($kpiMaster->isEmpty())
                    <div class="list-empty">
                        <p class="mb-1 fw-medium">Tidak ada master KPI yang cocok.</p>
                        <p class="mb-0 text-secondary small">
                            @if ($filterAktif > 0)
                                Ubah kata kunci atau filter — atau <a href="{{ route('kpi.master.index') }}">reset filter</a>.
                            @else
                                Buat master KPI lalu tambahkan indikatornya.
                            @endif
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter list-table">
                            <thead>
                                <tr>
                                    <th>KPI</th>
                                    <th>Jabatan &amp; departemen</th>
                                    <th class="text-end">Cabang</th>
                                    <th class="text-end">Indikator</th>
                                    <th>Status</th>
                                    <th class="w-1"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($kpiMaster as $data)
                                    <tr>
                                        <td class="cell-person">
                                            <a href="{{ route('kpi.master.detail.index', $data->id) }}" class="person">
                                                <span class="min-w-0">
                                                    <span class="person-name" title="{{ $data->nama_kpi }}">{{ $data->nama_kpi }}</span>
                                                    <span class="person-sub">{{ $data->kode_master }}</span>
                                                </span>
                                            </a>
                                        </td>
                                        <td data-label="Jabatan">
                                            <div class="cell-main">{{ $data->jabatan->nama_jabatan ?? 'Semua jabatan' }}</div>
                                            <div class="cell-sub">{{ $data->departemen->nama_dept ?? '-' }}</div>
                                        </td>
                                        <td data-label="Cabang" class="cell-num text-lg-end">{{ $cabangPerKode[$data->kode_master] ?? 0 }}</td>
                                        <td data-label="Indikator" class="cell-num text-lg-end">{{ $indikatorPerKode[$data->kode_master] ?? 0 }}</td>
                                        <td data-label="Status">
                                            <span class="emp-status emp-status--{{ $data->is_active ? 'success' : 'neutral' }}">{{ $data->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </td>
                                        <td class="cell-actions">
                                            <x-admin.row-menu :label="$data->nama_kpi">
                                                <a href="{{ route('kpi.master.detail.index', $data->id) }}" class="dropdown-item">Kelola indikator</a>
                                                @can('kpi-edit-admin')
                                                    <button type="button" class="dropdown-item edit-masterkpi" data-id-kpi="{{ $data->id }}">Edit</button>
                                                @endcan
                                                @can('kpi-delete-admin')
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('kpi.master.delete', ['id' => $data->id]) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger"
                                                            data-confirm="Kode “{{ $data->kode_master }}” beserta semua cabang terkait akan dihapus permanen."
                                                            data-confirm-title="Hapus master KPI?">Hapus</button>
                                                    </form>
                                                @endcan
                                            </x-admin.row-menu>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($kpiMaster->hasPages())
                    <div class="list-footer">
                        <span class="text-secondary small">Halaman {{ $kpiMaster->currentPage() }} dari {{ $kpiMaster->lastPage() }}</span>
                        {{ $kpiMaster->appends(request()->except('page'))->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </div>

    @can('kpi-create-admin')
        <div class="modal modal-blur fade" id="modalTambahKpiMaster" tabindex="-1" aria-labelledby="judulTambahKpi" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="judulTambahKpi">Tambah master KPI</h5>
                            <p class="modal-subtitle">Indikator ditambahkan setelah master dibuat.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form action="{{ route('kpi.master.store') }}" method="POST" id="formTambahKpi" class="modal-form-body">
                        @csrf
                        <div class="modal-body">
                            <fieldset class="form-section">
                                <legend class="form-section-title">Identitas</legend>
                                <div class="form-grid">
                                    <div>
                                        <label class="form-label required" for="kpi-kode">Kode KPI</label>
                                        <input type="text" name="kode_master" id="kpi-kode" value="{{ old('kode_master') }}"
                                            class="form-control @error('kode_master') is-invalid @enderror" placeholder="Contoh: KPI-MKN" required>
                                        @error('kode_master')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label required" for="kpi-nama">Nama KPI</label>
                                        <input type="text" name="nama_kpi" id="kpi-nama" value="{{ old('nama_kpi') }}"
                                            class="form-control @error('nama_kpi') is-invalid @enderror" placeholder="Contoh: KPI Harian Mekanik" required>
                                        @error('nama_kpi')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label required" for="kpi-jabatan">Jabatan</label>
                                        <select name="jabatan_id" id="kpi-jabatan" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                                            <option value="" disabled @selected(!old('jabatan_id'))>Pilih jabatan</option>
                                            @foreach ($jabatan as $j)
                                                <option value="{{ $j->id }}" @selected(old('jabatan_id') == $j->id)>{{ $j->nama_jabatan }}</option>
                                            @endforeach
                                        </select>
                                        @error('jabatan_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="form-label required" for="kpi-dept">Departemen</label>
                                        <select name="kode_dept" id="kpi-dept" class="form-select @error('kode_dept') is-invalid @enderror" required>
                                            <option value="" disabled @selected(!old('kode_dept'))>Pilih departemen</option>
                                            @foreach ($departemen as $d)
                                                <option value="{{ $d->kode_dept }}" @selected(old('kode_dept') == $d->kode_dept)>{{ $d->nama_dept }}</option>
                                            @endforeach
                                        </select>
                                        @error('kode_dept')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-grid-full">
                                        <span class="form-label required d-block">Status</span>
                                        <div class="decision-options decision-options--row">
                                            <label class="decision-option">
                                                <input type="radio" name="is_active" value="1" @checked(old('is_active', '1') == '1')>
                                                <span class="emp-status emp-status--success">Aktif</span>
                                            </label>
                                            <label class="decision-option">
                                                <input type="radio" name="is_active" value="0" @checked(old('is_active') === '0')>
                                                <span class="emp-status">Nonaktif</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                            <fieldset class="form-section">
                                <legend class="form-section-title">Berlaku di cabang</legend>
                                <x-admin.check-list name="kode_cabang" :items="$cabang" kode="kode_cabang" label="nama_cabang"
                                    :selected="old('kode_cabang', [])" judul="Semua cabang" item-class="cabang-checkbox" />
                                <div class="form-hint">Pilih minimal satu cabang.</div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <div class="modal modal-blur fade" id="modal-editkpi" tabindex="-1" aria-labelledby="judulEditKpi" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-form">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulEditKpi">Edit master KPI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div id="loadeditform" class="modal-form-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script>
        $(function() {
            // Minimal satu cabang dicentang (form tambah & edit).
            $(document).on('submit', '#formTambahKpi, #formKpiEdit', function(e) {
                if ($(this).find('[data-check-item]:checked').length === 0) {
                    e.preventDefault();
                    Swal.fire('Pilih cabang', 'Centang minimal satu cabang untuk KPI ini.', 'warning');
                }
            });

            $('.edit-masterkpi').on('click', function() {
                const memuat = '<div class="list-empty"><div class="spinner-border spinner-border-sm text-secondary"></div><p class="mt-2 mb-0 text-secondary small">Memuat data…</p></div>';
                $('#loadeditform').html(memuat);
                $('#modal-editkpi').modal('show');

                $.get('/kpi/masterkpi/' + $(this).data('id-kpi') + '/edit')
                    .done(function(html) {
                        $('#loadeditform').html(html);
                        sinkronSemuaDaftarCentang(document.getElementById('loadeditform'));
                    })
                    .fail(() => $('#loadeditform').html('<div class="list-empty text-danger">Data gagal dimuat. Coba lagi.</div>'));
            });

            @if ($errors->any() && old('kode_master') && !old('_method'))
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahKpiMaster')).show();
            @endif
        });
    </script>
@endpush
