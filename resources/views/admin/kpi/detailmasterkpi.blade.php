@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-start">
                <div class="col">
                    <div class="mb-1">
                        <ol class="breadcrumb" aria-label="breadcrumbs">
                            <li class="breadcrumb-item"><a href="{{ route('kpi.master.index') }}">Master KPI</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detail Indikator</li>
                        </ol>
                    </div>
                    <h2 class="page-title">
                        <span class="text-primary me-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-report-analytics"
                                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path
                                    d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                                <path d="M9 17v-5" />
                                <path d="M12 17v-1" />
                                <path d="M15 17v-3" />
                            </svg>
                        </span>
                        {{ $kpiMaster->nama_kpi }}
                    </h2>
                    <div class="text-muted mt-1">
                        <span class="badge bg-blue-lt">Kode: {{ $kpiMaster->kode_master }}</span>
                        <span class="badge bg-secondary-lt ms-1">Jabatan:
                            {{ $kpiMaster->jabatan->nama_jabatan ?? '-' }}</span>
                        <span class="badge bg-secondary-lt ms-1">Departemen:
                            {{ $kpiMaster->departemen->nama_dept ?? '-' }}</span>
                    </div>

                    <div class="mt-2">
                        @php
                            $cabangTerkait = \App\Models\KPIMaster::with('cabang')
                                ->where('kode_master', $kpiMaster->kode_master)
                                ->get();
                        @endphp
                        <div class="text-muted mb-1"
                            style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            Berlaku di {{ $cabangTerkait->count() }} Cabang:
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @forelse($cabangTerkait as $cbg)
                                <span class="badge badge-outline text-secondary shadow-sm">
                                    {{ $cbg->cabang->nama_cabang ?? 'Cabang Tidak Diketahui' }}
                                </span>
                            @empty
                                <span class="badge badge-outline text-danger">Belum ada cabang terkait</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-auto ms-auto d-flex gap-2 mt-4">
                    @can('kpi-create-admin')
                        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modal-tambah-detail">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg>
                            Tambah Indikator
                        </button>
                    @endcan
                    <a href="{{ route('kpi.master.index') }}" class="btn btn-outline-secondary shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                            <path d="M5 12l14 0" />
                            <path d="M5 12l6 6" />
                            <path d="M5 12l6 -6" />
                        </svg>
                        Kembali</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .focus-border-primary:focus {
            border-color: var(--color-accent) !important;
            background: var(--color-surface) !important;
            box-shadow: 0 0 0 0.25rem color-mix(in oklab, var(--color-focus) 25%, transparent) !important;
        }

        .table-vcenter textarea.form-control {
            min-height: 38px;
            transition: border-color var(--dur-short) var(--ease-out), background-color var(--dur-short) var(--ease-out);
            border-color: transparent;
            background: transparent;
            resize: none;
            overflow: hidden;
        }

        .table-vcenter textarea.form-control:focus {
            border-color: var(--color-rule-2);
            background: var(--color-surface);
        }

        .progress-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
    </style>


    <div class="page-body">
        <div class="container-xl">
            {{-- Main Table --}}
            <div class="card border-0 shadow-sm">
                <form action="{{ route('kpi.master.detail.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="kode_master" value="{{ $kpiMaster->kode_master }}">

                    @php
                        // Menghitung total bobot atasan (hanya yang aktif)
                        $totalBobotAtasan = $atasanDetails->where('is_active', 1)->sum('bobot_atasan');
                        $bobotKpi = floatval($kpiMaster->bobot_kpi);

                        // Menghitung sisa bobot untuk Absensi (asumsi total 100%)
                        $bobotAbsensi = 100 - ($bobotKpi + $totalBobotAtasan);
                        $no = 1;
                    @endphp

                    {{-- TABEL 0: PENGATURAN MASTER KPI --}}
                    <div class="card-header bg-light">
                        <h3 class="card-title text-dark font-weight-bold">Pengaturan Performance Appraisal Karyawan</h3>
                    </div>
                    <div class="table-responsive mb-4 border-bottom">
                        <table class="table table-vcenter table-bordered card-table">
                            <thead class="table-light text-center align-middle"
                                style="border-bottom: 2px solid var(--color-rule); font-size: 0.75rem; letter-spacing: 0.04em;">
                                <tr>
                                    <th rowspan="2" class="w-1 text-dark font-weight-bold text-uppercase">No</th>
                                    <th rowspan="2" style="width: 20%" class="text-dark font-weight-bold text-uppercase">
                                        Strategic Objective
                                    </th>
                                    <th rowspan="2" class="text-dark font-weight-bold text-uppercase">KPI/Deliverable
                                    </th>
                                    <th rowspan="2" style="width: 15%" class="text-dark font-weight-bold text-uppercase">
                                        Weight</th>
                                    <th colspan="2" class="text-dark font-weight-bold border-bottom-0 text-uppercase">
                                        Scoring</th>
                                </tr>
                                <tr>
                                    <th style="width: 15%" class="text-dark font-weight-bold text-uppercase">Target</th>
                                    <th style="width: 10%" class="text-dark font-weight-bold text-uppercase">Achievement
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- 1. ABSENSI (Otomatis dari Sisa Bobot) --}}
                                <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td><input type="text" class="form-control bg-transparent border-0" value="Absensi"
                                            readonly></td>
                                    <td><input type="text" class="form-control bg-transparent border-0 text-muted"
                                            value="% Datang Tepat Waktu" readonly></td>
                                    <td>
                                        <input type="text" id="bobot_absensi"
                                            class="form-control text-center bg-transparent border-0 font-weight-bold text-secondary"
                                            value="{{ $bobotAbsensi }}" readonly>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control text-center bg-transparent border-0"
                                                value="90" readonly>
                                            <span class="input-group-text bg-transparent border-0">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle bg-light text-muted"><em>Auto / Report</em></td>
                                </tr>

                                {{-- 2. KPI KARYAWAN (Satu-satunya yang bisa diedit) --}}
                                <tr style="background-color: var(--color-surface-2);">
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td><input type="text"
                                            class="form-control bg-transparent border-0 font-weight-bold" value="KPI"
                                            readonly></td>
                                    <td><input type="text" class="form-control bg-transparent border-0 text-muted"
                                            value="{{ $kpiMaster->nama_kpi }}" readonly></td>
                                    <td>
                                        {{-- Input dengan name untuk dikirim ke Controller --}}
                                        <input type="number" step="0.5" name="bobot_kpi" id="input_bobot_kpi"
                                            class="form-control text-center font-weight-bold text-success border-success shadow-none focus-border-primary"
                                            value="{{ $bobotKpi }}">
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.5" name="target_kpi"
                                                class="form-control text-center font-weight-bold text-success border-success shadow-none focus-border-primary"
                                                value="{{ floatval($kpiMaster->target_kpi) }}">
                                            <span
                                                class="input-group-text bg-transparent border-success text-success">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle bg-light text-muted"><em>Auto / Report</em></td>
                                </tr>

                                {{-- 3. PENILAIAN ATASAN (Otomatis Looping dari tabel Atasan) --}}
                                @foreach ($atasanDetails->where('is_active', 1) as $atasan)
                                    <tr>
                                        <td class="text-center">{{ $no++ }}</td>
                                        <td><input type="text" class="form-control bg-transparent border-0"
                                                value="{{ $atasan->indikator }}" readonly></td>
                                        <td><input type="text" class="form-control bg-transparent border-0 text-muted"
                                                value="Berdasarkan Penilaian Atasan" readonly></td>
                                        <td>
                                            <input type="text"
                                                class="form-control text-center bg-transparent border-0 text-secondary"
                                                value="{{ floatval($atasan->bobot_atasan) }}" readonly>
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control text-center bg-transparent border-0"
                                                    value="{{ floatval($atasan->target_atasan) }}" readonly>
                                                <span class="input-group-text bg-transparent border-0">%</span>
                                            </div>
                                        </td>
                                        <td class="text-center align-middle bg-light text-muted"><em>Auto / Report</em>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- TABEL 1: KPI KARYAWAN --}}
                    <div class="card-header bg-light">
                        <h3 class="card-title text-primary font-weight-bold">Workbook Daily Karyawan</h3>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-vcenter card-table table-hover table-striped">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="w-1 text-center">No</th>
                                    <th class="text-center" style="min-width: 250px;">Deskripsi Kinerja</th>
                                    <th class="text-center" style="width: 15%">Indikator KPI (%)</th>
                                    <th class="text-center" style="width: 15%">Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($karyawanDetails as $index => $detail)
                                    <tr class="item-row {{ !$detail->is_active ? 'opacity-50 bg-light' : '' }}">
                                        <td class="text-center align-middle row-number">{{ $index + 1 }}</td>
                                        <td class="p-2">
                                            <textarea name="details_karyawan[{{ $detail->id }}][indikator]"
                                                class="form-control border-0 bg-transparent focus-border-primary rounded-1" rows="2">{{ $detail->indikator }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" step="0.5"
                                                name="details_karyawan[{{ $detail->id }}][score_indikator]"
                                                class="form-control text-center font-weight-bold"
                                                value="{{ floatval($detail->score_indikator) }}">
                                        </td>
                                        <td>
                                            <select name="details_karyawan[{{ $detail->id }}][is_active]"
                                                class="form-select form-select-sm select-is-active">
                                                <option value="1" {{ $detail->is_active ? 'selected' : '' }}>Aktif
                                                </option>
                                                <option value="0" {{ !$detail->is_active ? 'selected' : '' }}>
                                                    Nonaktif</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-icon btn-ghost-danger btn-sm btn-delete-detail"
                                                data-id="{{ $detail->id }}" data-jenis="karyawan" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted fst-italic">Belum ada
                                            indikator untuk Karyawan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- TABEL 2: KPI ATASAN --}}
                    <div class="card-header bg-light border-top">
                        <h3 class="card-title text-success font-weight-bold">Penilaian Atasan</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover table-striped">
                            <thead class="bg-light text-secondary">
                                <tr>
                                    <th class="w-1 text-center">No</th>
                                    <th class="text-center" style="min-width: 250px;">Deskripsi Kinerja</th>
                                    <th class="text-center" style="width: 15%">Bobot (%)</th>
                                    <th class="text-center" style="width: 15%">Target (%)</th>
                                    <th class="text-center" style="width: 15%">Status</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($atasanDetails as $index => $detail)
                                    <tr class="item-row {{ !$detail->is_active ? 'opacity-50 bg-light' : '' }}">
                                        <td class="text-center align-middle row-number">{{ $index + 1 }}</td>
                                        <td class="p-2">
                                            <textarea name="details_atasan[{{ $detail->id }}][indikator]"
                                                class="form-control border-0 bg-transparent focus-border-primary rounded-1" rows="2">{{ $detail->indikator }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" step="0.5"
                                                name="details_atasan[{{ $detail->id }}][bobot]"
                                                class="form-control text-center font-weight-bold"
                                                value="{{ floatval($detail->bobot_atasan) }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.5"
                                                name="details_atasan[{{ $detail->id }}][target]"
                                                class="form-control text-center"
                                                value="{{ floatval($detail->target_atasan) }}">
                                        </td>
                                        <td>
                                            <select name="details_atasan[{{ $detail->id }}][is_active]"
                                                class="form-select form-select-sm select-is-active">
                                                <option value="1" {{ $detail->is_active ? 'selected' : '' }}>Aktif
                                                </option>
                                                <option value="0" {{ !$detail->is_active ? 'selected' : '' }}>
                                                    Nonaktif</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-icon btn-ghost-danger btn-sm btn-delete-detail"
                                                data-id="{{ $detail->id }}" data-jenis="atasan" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24"
                                                    height="24" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted fst-italic">Belum ada
                                            indikator untuk Atasan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer d-flex align-items-center bg-light-lt mt-3 border-top">
                        <button type="submit" class="btn btn-success ms-auto shadow-sm px-4">
                            Simpan Semua Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Form Delete (Dinamis dari JS) --}}
    <form id="delete-form" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    {{-- Modal Tambah Detail --}}
    <div class="modal modal-blur fade" id="modal-tambah-detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content shadow-lg border-0">
                <form action="{{ route('kpi.master.detail.store', $kpiMaster->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah Indikator Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Kategori Template</label>
                                <select name="kategori" id="selectKategori" class="form-select" required>
                                    <option value="karyawan">Diisi oleh Karyawan</option>
                                    <option value="atasan">Dinilai oleh Atasan</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Status Awal</label>
                                <select name="is_active" class="form-select" required>
                                    <option value="1" selected>Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Deskripsi Indikator</label>
                            <textarea class="form-control" name="indikator" rows="3"
                                placeholder="E.g. Memeriksa dan menganalisa kerusakan komponen" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required">Bobot (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="bobot"
                                        placeholder="0.00" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            {{-- Field Target akan dimunculkan oleh JS jika pilih Atasan --}}
                            <div class="col-md-6 mb-3" id="targetContainer" style="display: none;">
                                <label class="form-label required">Target (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="target"
                                        id="inputTarget" placeholder="100">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted" style="font-size: 11px;">Hanya wajib untuk form Atasan</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary ms-auto">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            // Auto resize textarea
            $('textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            }).trigger('input');

            // Delete Confirmation & Action (Dinamis dengan parameter {jenis})
            $('.btn-delete-detail').on('click', function(e) {
                e.preventDefault();
                let id = $(this).data('id');
                let jenis = $(this).data('jenis'); // Ambil jenis (karyawan/atasan)

                Swal.fire({
                    title: 'Hapus Indikator?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    cancelButtonColor: 'var(--color-muted)',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Membangun URL Hapus secara dinamis sesuai struktur route baru
                        let deleteUrl = "{{ url('kpi/detailmasterkpi') }}/" + id + "/" + jenis +
                            "/delete";
                        $('#delete-form').attr('action', deleteUrl).submit();
                    }
                });
            });

            // Logic Visual Aktif/Nonaktif
            $(document).on('change', '.select-is-active', function() {
                let row = $(this).closest('tr');
                if ($(this).val() == '0') {
                    row.addClass('opacity-50 bg-light');
                } else {
                    row.removeClass('opacity-50 bg-light');
                }
            });

            @if (Session::has('error'))
                Swal.fire('Gagal!', "{{ Session::get('error') }}", 'error');
            @endif

            @if (Session::has('warning'))
                Swal.fire('Perhatian!', "{{ Session::get('warning') }}", 'warning');
            @endif

            @if (Session::has('success'))
                Swal.fire('Berhasil!', "{{ Session::get('success') }}", 'success');
            @endif
            @if ($errors->any())
                Swal.fire('Data Tidak Valid!', "{{ implode('\n', $errors->all()) }}", 'error');
            @endif

            $('textarea').on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            }).trigger('input');

            $('#selectKategori').on('change', function() {
                if ($(this).val() === 'atasan') {
                    $('#targetContainer').show();
                    $('#inputTarget').prop('required', true);
                } else {
                    $('#targetContainer').hide();
                    $('#inputTarget').prop('required', false).val('');
                }
            });

            $('#input_bobot_kpi').on('input', function() {
                let totalAtasan = {{ $totalBobotAtasan }};
                let bobotKpi = parseFloat($(this).val()) || 0;
                let sisaAbsensi = 100 - (bobotKpi + totalAtasan);

                // Jangan sampai minus
                if (sisaAbsensi < 0) sisaAbsensi = 0;

                $('#bobot_absensi').val(sisaAbsensi);
            });
        });
    </script>
@endpush
