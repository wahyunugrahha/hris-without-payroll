@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">
                        Data Master KPI
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        @can('kpi-create-admin')
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block shadow-sm" data-bs-toggle="modal"
                                data-bs-target="#modalTambahKpiMaster">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M12 5l0 14" />
                                    <path d="M5 12l14 0" />
                                </svg>
                                Tambah Master KPI
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- Alert Section --}}
            @if (Session::get('success'))
                <div class="alert alert-important alert-success alert-dismissible shadow-sm" role="alert">
                    <div class="d-flex">
                        <div><svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M5 12l5 5l10 -10" />
                            </svg></div>
                        <div>{{ Session::get('success') }}</div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body border-bottom py-3">
                    {{-- Search Form --}}
                    <form action="{{ route('kpi.master.index') }}" method="GET" autocomplete="off">

                        {{-- BARIS 1: FILTER DROPDOWN --}}
                        <div class="row g-2 align-items-center mb-2">

                            {{-- Dropdown Jabatan --}}
                            <div class="col-12 col-md-3">
                                <select name="jabatan_id" class="form-select">
                                    <option value="">Semua Jabatan</option>
                                    @foreach ($jabatan as $j)
                                        <option value="{{ $j->id }}"
                                            {{ request('jabatan_id') == $j->id ? 'selected' : '' }}>
                                            {{ $j->nama_jabatan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dropdown Departemen --}}
                            <div class="col-12 col-md-3">
                                <select name="kode_dept" class="form-select">
                                    <option value="">Semua Departemen</option>
                                    @foreach ($departemen as $data)
                                        <option value="{{ $data->kode_dept }}"
                                            {{ request('kode_dept') == $data->kode_dept ? 'selected' : '' }}>
                                            {{ $data->nama_dept }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Dropdown Cabang --}}
                            <div class="col-12 col-md-3">
                                <select name="kode_cabang" class="form-select" {{ count($cabang) <= 1 ? 'disabled' : '' }}>
                                    @if (count($cabang) > 1)
                                        <option value="">Semua Cabang</option>
                                    @endif
                                    @foreach ($cabang as $data_cabang)
                                        <option value="{{ $data_cabang->kode_cabang }}"
                                            {{ request('kode_cabang') == $data_cabang->kode_cabang ? 'selected' : '' }}>
                                            {{ $data_cabang->nama_cabang }}
                                        </option>
                                    @endforeach
                                </select>
                                @if (count($cabang) <= 1)
                                    <input type="hidden" name="kode_cabang" value="{{ $cabang->first()->kode_cabang }}">
                                @endif
                            </div>

                            {{-- Dropdown Status --}}
                            <div class="col-12 col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- BARIS 2: SEARCH BAR --}}
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="input-group">
                                    {{-- Input Field --}}
                                    <input type="text" name="indikator" value="{{ request('indikator') }}"
                                        class="form-control" placeholder="Cari Nama KPI atau Kode KPI...">

                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search"
                                            width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                            stroke="currentColor" fill="none" stroke-linecap="round"
                                            stroke-linejoin="round">
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

                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="w-1">No</th>
                                <th>Kode Master</th>
                                <th>Nama KPI / Jabatan</th>
                                <th>Departemen</th>
                                <th class="text-center">Cabang</th>
                                <th class="text-center">Total Indikator</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kpiMaster as $data)
                                @php
                                    $cabangTerkait = \App\Models\KPIMaster::with('cabang')
                                        ->where('kode_master', $data->kode_master)
                                        ->get();

                                    $cabangCount = $cabangTerkait->count();

                                    $listNamaCabang = $cabangTerkait
                                        ->pluck('cabang.nama_cabang')
                                        ->filter()
                                        ->implode(', ');

                                    $indikatorCount = \App\Models\KPIMasterDetail::where(
                                        'kode_master',
                                        $data->kode_master,
                                    )->count();
                                @endphp
                                <tr>
                                    <td><span
                                            class="text-muted">{{ $loop->iteration + $kpiMaster->firstItem() - 1 }}</span>
                                    </td>
                                    <td><span class="badge bg-blue-lt">{{ $data->kode_master }}</span></td>
                                    <td>
                                        <div class="fw-bold">{{ $data->nama_kpi }}</div>
                                        <div class="small text-muted">Jabatan:
                                            {{ $data->jabatan->nama_jabatan ?? 'Semua' }}</div>
                                    </td>
                                    <td>
                                        <div class="small">{{ $data->departemen->nama_dept ?? '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-lt mb-1">{{ $cabangCount }} Cabang</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-lt">{{ $indikatorCount }} Item</span>
                                    </td>
                                    <td>
                                        @if ($data->is_active)
                                            <span class="badge badge-outline text-success">Aktif</span>
                                        @else
                                            <span class="badge badge-outline text-danger">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-list flex-nowrap justify-content-end">
                                            <a href="{{ route('kpi.master.detail.index', $data->id) }}"
                                                class="btn btn-icon btn-ghost-primary" title="Detail Indikator">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-list-details" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M13 5h8" />
                                                    <path d="M13 9h5" />
                                                    <path d="M13 15h8" />
                                                    <path d="M13 19h5" />
                                                    <path
                                                        d="M3 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                                    <path
                                                        d="M3 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" />
                                                </svg>
                                            </a>
                                            @can('kpi-edit-admin')
                                                <a href="#" class="btn btn-icon btn-ghost-info edit-masterkpi"
                                                    data-id-kpi="{{ $data->id }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                        height="24" viewBox="0 0 24 24" stroke-width="2"
                                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4" />
                                                        <path d="M13.5 6.5l4 4" />
                                                    </svg>
                                                </a>
                                            @endcan
                                            @can('kpi-delete-admin')
                                                <a href="#" class="btn btn-icon btn-ghost-danger delete-confirm"
                                                    data-id-kpi="{{ $data->id }}" data-nama="{{ $data->nama_kpi }}"
                                                    data-kode="{{ $data->kode_master }}" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
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
                                                </a>
                                                <form action="{{ route('kpi.master.delete', ['id' => $data->id]) }}"
                                                    method="POST" id="deleteForm{{ $data->id }}" class="d-none">
                                                    @csrf @method('DELETE')
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty">
                                            <div class="empty-icon"><svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-search-off" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path
                                                        d="M5.039 5.062a7 7 0 0 0 9.91 9.89m1.587 -2.435a7 7 0 0 0 -9.057 -9.043" />
                                                    <path d="M15.703 15.703l5.297 5.297" />
                                                    <path d="M3 3l18 18" />
                                                </svg></div>
                                            <p class="empty-title">Data tidak ditemukan</p>
                                            <p class="empty-subtitle text-muted">Coba ubah filter atau tambahkan data baru.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex align-items-center">
                    <p class="m-0 text-muted">
                        Menampilkan <span>{{ $kpiMaster->firstItem() ?? 0 }}</span> -
                        <span>{{ $kpiMaster->lastItem() ?? 0 }}</span> dari
                        <span>{{ $kpiMaster->total() }}</span> indikator
                    </p>
                    <div class="ms-auto">
                        {{ $kpiMaster->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal modal-blur fade" id="modalTambahKpiMaster" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Konfigurasi Master KPI Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('kpi.master.store') }}" method="POST" id="formTambahKpi">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Kode KPI</label>
                                <input type="text" name="kode_master"
                                    class="form-control @error('kode_master') is-invalid @enderror"
                                    placeholder="E.g. KPI-Ka-Mekanik" required>
                                @error('kode_master')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Nama KPI</label>
                                <input type="text" name="nama_kpi"
                                    class="form-control @error('nama_kpi') is-invalid @enderror"
                                    placeholder="E.g. KPI Harian Kepala Mekanik" required>
                                @error('nama_kpi')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label required">Jabatan</label>
                                <select name="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror"
                                    required>
                                    <option value="" selected disabled>Pilih Jabatan</option>
                                    @foreach ($jabatan as $j)
                                        <option value="{{ $j->id }}">{{ $j->nama_jabatan }}</option>
                                    @endforeach
                                </select>
                                @error('jabatan_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Departemen</label>
                                <select name="kode_dept" class="form-select @error('kode_dept') is-invalid @enderror"
                                    required>
                                    <option value="" selected disabled>Pilih Departemen</option>
                                    @foreach ($departemen as $d)
                                        <option value="{{ $d->kode_dept }}">{{ $d->nama_dept }}</option>
                                    @endforeach
                                </select>
                                @error('kode_dept')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- CHECKLIST CABANG --}}
                        <div class="mb-3">
                            <label class="form-label required">Pilih Cabang (Checklist)</label>
                            <div
                                style="border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; max-height: 250px; overflow-y: auto;">
                                @forelse ($cabang as $c)
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input cabang-checkbox"
                                            id="cabang_{{ $c->kode_cabang }}" name="kode_cabang[]"
                                            value="{{ $c->kode_cabang }}">
                                        <label class="form-check-label" for="cabang_{{ $c->kode_cabang }}">
                                            {{ $c->nama_cabang }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0">Tidak ada cabang tersedia</p>
                                @endforelse
                            </div>
                            <small class="text-danger d-block mt-2" id="cabang-error" style="display:none;">
                                <strong>⚠ Minimal harus memilih 1 cabang!</strong>
                            </small>
                            @error('kode_cabang')
                                <small class="text-danger d-block mt-2">
                                    <strong>⚠ {{ $message }}</strong>
                                </small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Status Aktivasi</label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="is_active" value="1" class="form-selectgroup-input"
                                        checked>
                                    <span class="form-selectgroup-label">Aktif</span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="is_active" value="0"
                                        class="form-selectgroup-input">
                                    <span class="form-selectgroup-label text-danger">Nonaktif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary"
                            data-bs-dismiss="modal">Batalkan</button>
                        <button type="submit" class="btn btn-primary ms-auto shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-plus"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit (Container for AJAX) --}}
    <div class="modal modal-blur fade" id="modal-editkpi" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Master KPI</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="loadeditform">
                    {{-- Form Loaded via AJAX --}}
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted mt-2">Sedang mengambil data...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script>
        $(function() {
            // Validasi Form Tambah
            $('#formTambahKpi').on('submit', function(e) {
                const checkedCabang = $('input.cabang-checkbox:checked').length;

                if (checkedCabang === 0) {
                    e.preventDefault();
                    $('#cabang-error').show();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Cabang',
                        text: 'Minimal harus memilih 1 cabang untuk membuat KPI!',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d6efd'
                    });
                    return false;
                }
                $('#cabang-error').hide();
            });

            // Edit Modal AJAX
            $('.edit-masterkpi').on('click', function(e) {
                e.preventDefault();
                let id = $(this).attr("data-id-kpi");

                // Show modal dengan loading
                $('#modal-editkpi').modal('show');
                $('#loadeditform').html(
                    '<div class="text-center py-5">' +
                    '<div class="spinner-border text-primary" role="status"></div>' +
                    '<div class="text-muted mt-3">Sedang mengambil data...</div>' +
                    '</div>'
                );

                // AJAX GET form
                $.ajax({
                    url: '/kpi/masterkpi/' + id + '/edit',
                    type: 'GET',
                    dataType: 'html',
                    success: function(data) {
                        $('#loadeditform').html(data);

                        // Validasi form edit setelah di-load
                        $('#formKpiEdit').on('submit', function(e) {
                            const checkedCabang = $(
                                'input.cabang-checkbox-edit:checked').length;

                            if (checkedCabang === 0) {
                                e.preventDefault();
                                $('#cabang-error-edit').show();
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Pilih Cabang',
                                    text: 'Minimal harus memilih 1 cabang!',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#0d6efd'
                                });
                                return false;
                            }
                            $('#cabang-error-edit').hide();
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        $('#loadeditform').html(
                            '<div class="alert alert-danger m-3">' +
                            '<strong>Error!</strong> Gagal memuat data. Silakan coba lagi.' +
                            '</div>'
                        );
                    }
                });
            });

            // Delete Confirmation
            $(".delete-confirm").on("click", function(e) {
                e.preventDefault();
                let id = $(this).attr("data-id-kpi");
                let nama = $(this).attr("data-nama");
                let kode = $(this).attr("data-kode");

                Swal.fire({
                    title: "Hapus Master KPI?",
                    text: "Kode '" + kode + "' dan SEMUA cabang terkait akan dihapus permanen.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#6e7174",
                    confirmButtonText: "Ya, Hapus Semua",
                    cancelButtonText: "Batal",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $("#deleteForm" + id).submit();
                    }
                });
            });
        });
    </script>
@endpush
